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
    return os.path.join(path, "demand.joblib")

def train_demand_model(tenant_id: int) -> dict:
    try:
        with get_connection() as conn:
            rows = conn.execute(text("""
                SELECT
                    mi.name,
                    DATE(o.created_at) AS day,
                    SUM(oi.quantity)   AS qty
                FROM order_items oi
                JOIN orders     o  ON oi.order_id     = o.id
                JOIN menu_items mi ON oi.menu_item_id = mi.id
                JOIN users      u  ON o.user_id       = u.id
                WHERE u.tenant_id  = :tenant_id
                  AND o.status     = 'served'
                  AND mi.deleted_at IS NULL
                GROUP BY mi.name, DATE(o.created_at)
                ORDER BY mi.name, day
            """), {"tenant_id": tenant_id}).fetchall()

        if not rows:
            return {"success": False, "error": "No order data found for this tenant."}

        df = pd.DataFrame(rows, columns=["name", "date", "qty"])
        df["date"] = pd.to_datetime(df["date"])
        df["qty"]  = df["qty"].astype(float)

        # Top 10 items by total quantity
        top_items = (df.groupby("name")["qty"].sum()
                       .nlargest(10).index.tolist())

        models       = {}
        min_days     = 14  # minimum training days per item

        for item_name in top_items:
            item_df = df[df["name"] == item_name].copy()

            # Fill missing days in the item's date range
            full_range = pd.date_range(item_df["date"].min(), item_df["date"].max(), freq="D")
            item_df    = (item_df.set_index("date")["qty"]
                                 .reindex(full_range, fill_value=0.0)
                                 .reset_index()
                                 .rename(columns={"index": "date", 0: "qty"}))
            if "qty" not in item_df.columns:
                item_df.columns = ["date", "qty"]

            item_df = add_time_features(item_df, "qty")
            item_df = item_df.dropna(subset=FEATURE_COLS)

            if len(item_df) < min_days:
                continue

            X = item_df[FEATURE_COLS].values
            y = item_df["qty"].values

            model = XGBRegressor(
                n_estimators=200,
                learning_rate=0.05,
                max_depth=4,
                subsample=0.8,
                random_state=42,
                verbosity=0,
            )
            model.fit(X, y)

            models[item_name] = {
                "model":     model,
                "last_date": item_df["date"].max(),
                "history":   item_df[["date", "qty"]].copy(),
            }

        if not models:
            return {"success": False, "error": "Not enough per-item data to train."}

        joblib.dump(models, _model_path(tenant_id))
        return {"success": True, "message": f"Demand models trained for {len(models)} items."}

    except Exception as e:
        return {"success": False, "error": str(e)}


def forecast_demand(tenant_id: int) -> dict:
    path = _model_path(tenant_id)
    if not os.path.exists(path):
        return {"success": False, "error": "Model not trained yet. Please train first."}

    try:
        models = joblib.load(path)
        items  = []

        for item_name, payload in models.items():
            model      = payload["model"]
            last_date  = pd.Timestamp(payload["last_date"])
            history_df = payload["history"].copy()
            history_df["date"] = pd.to_datetime(history_df["date"])

            # Forecast next 30 days iteratively
            working = history_df.copy()
            daily_preds = []

            for i in range(30):
                next_date = last_date + pd.Timedelta(days=i + 1)
                X_next    = features_for_next_date(next_date, working, "qty")
                pred      = max(0.0, float(model.predict(X_next)[0]))
                daily_preds.append({"date": next_date, "qty": pred})
                new_row = pd.DataFrame({"date": [next_date], "qty": [pred]})
                working = pd.concat([working, new_row], ignore_index=True)

            # Historical: monthly aggregates (last 12 months)
            history_df["month"] = history_df["date"].dt.to_period("M")
            monthly_hist = (history_df.groupby("month")["qty"]
                                       .sum().reset_index().tail(12))

            # Forecast: aggregate the 30 days to one monthly total
            fore_df           = pd.DataFrame(daily_preds)
            fore_df["month"]  = fore_df["date"].dt.to_period("M")
            monthly_fore      = fore_df.groupby("month")["qty"].sum().reset_index()

            historical = [
                {"month": str(r["month"]), "qty": round(float(r["qty"]))}
                for _, r in monthly_hist.iterrows()
            ]
            next_month_label = str(monthly_fore["month"].iloc[0]) if len(monthly_fore) else "N/A"
            next_month_pred  = round(float(monthly_fore["qty"].iloc[0])) if len(monthly_fore) else 0

            items.append({
                "name":                  item_name,
                "historical":            historical,
                "next_month_label":      next_month_label,
                "next_month_prediction": next_month_pred,
            })

        items.sort(key=lambda x: x["next_month_prediction"], reverse=True)
        return {"success": True, "items": items}

    except Exception as e:
        return {"success": False, "error": str(e)}