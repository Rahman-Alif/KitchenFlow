import os
import joblib
import numpy as np
import pandas as pd
from xgboost import XGBRegressor
from sqlalchemy import text
from database import get_connection
from config import MODELS_DIR
from utils import FEATURE_COLS, add_time_features, features_for_next_date

def _model_path(tenant_id: int) -> str:
    path = os.path.join(MODELS_DIR, str(tenant_id))
    os.makedirs(path, exist_ok=True)
    return os.path.join(path, "revenue.joblib")

def train_revenue_model(tenant_id: int) -> dict:
    try:
        with get_connection() as conn:
            rows = conn.execute(text("""
                SELECT
                    DATE(o.created_at)  AS day,
                    SUM(o.total_amount) AS revenue
                FROM orders o
                JOIN users u ON o.user_id = u.id
                WHERE u.tenant_id = :tenant_id
                AND o.status    = 'served'
                AND DATE_TRUNC('month', o.created_at) < DATE_TRUNC('month', CURRENT_DATE)
                GROUP BY DATE(o.created_at)
                ORDER BY day ASC
            """), {"tenant_id": tenant_id}).fetchall()

        if len(rows) < 30:
            return {"success": False, "error": "Need at least 30 days of order history to train."}

        df = pd.DataFrame(rows, columns=["date", "revenue"])
        df["date"]    = pd.to_datetime(df["date"])
        df["revenue"] = df["revenue"].astype(float)

        # Fill missing days with 0 (closed days, no orders)
        full_range = pd.date_range(df["date"].min(), df["date"].max(), freq="D")
        df = (df.set_index("date")
                .reindex(full_range, fill_value=0.0)
                .reset_index()
                .rename(columns={"index": "date"}))

        df = add_time_features(df, "revenue")
        df = df.dropna(subset=FEATURE_COLS)

        if len(df) < 14:
            return {"success": False, "error": "Not enough data after feature engineering."}

        X = df[FEATURE_COLS].values
        y = df["revenue"].values

        model = XGBRegressor(
            n_estimators=300,
            learning_rate=0.05,
            max_depth=5,
            subsample=0.8,
            colsample_bytree=0.8,
            random_state=42,
            verbosity=0,
        )
        model.fit(X, y)

        payload = {
            "model":     model,
            "last_date": df["date"].max(),
            "history":   df[["date", "revenue"]].copy(),
        }
        joblib.dump(payload, _model_path(tenant_id))

        return {"success": True, "message": f"Revenue model trained on {len(df)} days of data."}

    except Exception as e:
        return {"success": False, "error": str(e)}


def forecast_revenue(tenant_id: int) -> dict:
    path = _model_path(tenant_id)
    if not os.path.exists(path):
        return {"success": False, "error": "Model not trained yet. Please train first."}

    try:
        payload    = joblib.load(path)
        model      = payload["model"]
        last_date  = pd.Timestamp(payload["last_date"])
        history_df = payload["history"].copy()
        history_df["date"] = pd.to_datetime(history_df["date"])

        # ── Iterative daily forecast: 60 days forward ─────────
        working     = history_df.copy()
        predictions = []

        for i in range(60):
            next_date = last_date + pd.Timedelta(days=i + 1)
            X_next    = features_for_next_date(next_date, working, "revenue")
            pred      = max(0.0, float(model.predict(X_next)[0]))
            predictions.append({"date": next_date, "revenue": round(pred, 2)})
            working = pd.concat(
                [working, pd.DataFrame({"date": [next_date], "revenue": [pred]})],
                ignore_index=True
            )

        # ── Bi-weekly period helper ───────────────────────────
        # Each date maps to a label "YYYY-MM W1" or "YYYY-MM W2".
        # W1 = days 1–15, W2 = days 16–end of month.
        # This gives exactly 2 periods per month, clean labels,
        # and consistent bucket sizes regardless of month length.
        def biweekly_label(dt) -> str:
            half = "W1" if dt.day <= 15 else "W2"
            return f"{dt.strftime('%Y-%m')} {half}"

        # ── Aggregate historical to bi-weekly ─────────────────
        # Use last 6 months (≈ 12 bi-weekly buckets) for chart readability
        cutoff = history_df["date"].max() - pd.DateOffset(months=6)
        hist_recent = history_df[history_df["date"] > cutoff].copy()
        hist_recent["period"] = hist_recent["date"].apply(biweekly_label)

        monthly_hist = (hist_recent
                        .groupby("period")["revenue"]
                        .sum()
                        .reset_index()
                        .sort_values("period"))  # YYYY-MM WX sorts correctly as a string

        # ── Aggregate forecast to bi-weekly ───────────────────
        fore_df           = pd.DataFrame(predictions)
        fore_df["period"] = fore_df["date"].apply(biweekly_label)
        monthly_fore      = (fore_df
                             .groupby("period")["revenue"]
                             .sum()
                             .reset_index()
                             .sort_values("period"))

        historical = [
            {"period": r["period"], "revenue": round(float(r["revenue"]), 2)}
            for _, r in monthly_hist.iterrows()
        ]

        # ── Forecast: 4 bi-weekly periods (≈ 2 months) ────────
        last_actual = float(monthly_hist["revenue"].iloc[-1]) if len(monthly_hist) else 1.0
        forecast    = []
        prev        = last_actual
        for _, r in monthly_fore.iterrows():
            v   = round(float(r["revenue"]), 2)
            pct = round(((v - prev) / abs(prev)) * 100, 1) if prev != 0 else 0.0
            forecast.append({"period": r["period"], "revenue": v, "pct_change": pct})
            prev = v

        # Return 4 periods (2 full months bi-weekly) instead of 2 monthly
        return {
            "success":    True,
            "historical": historical,
            "forecast":   forecast[:2],
        }

    except Exception as e:
        return {"success": False, "error": str(e)}