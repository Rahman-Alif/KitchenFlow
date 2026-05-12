import os
import joblib
import numpy as np
import pandas as pd
from datetime import datetime
from sklearn.ensemble import RandomForestRegressor
from sklearn.preprocessing import LabelEncoder
from sqlalchemy import text
from database import get_connection
from config import MODELS_DIR

SLOTS = [
    {"label": "8:00 AM – 11:00 AM", "start": 8,  "end": 10},
    {"label": "11:00 AM – 2:00 PM", "start": 11, "end": 13},
    {"label": "2:00 PM – 5:00 PM",  "start": 14, "end": 16},
    {"label": "5:00 PM – 8:00 PM",  "start": 17, "end": 19},
    {"label": "8:00 PM – 11:00 PM", "start": 20, "end": 22},
]

def _hour_to_slot(hour: int) -> int | None:
    for i, s in enumerate(SLOTS):
        if s["start"] <= hour <= s["end"]:
            return i
    return None

def _model_path(tenant_id: int) -> str:
    path = os.path.join(MODELS_DIR, str(tenant_id))
    os.makedirs(path, exist_ok=True)
    return os.path.join(path, "stock.joblib")

def train_stock_model(tenant_id: int) -> dict:
    try:
        with get_connection() as conn:
            rows = conn.execute(text("""
                SELECT
                    oi.menu_item_id,
                    mi.name,
                    EXTRACT(DOW  FROM o.created_at)::int AS dow,
                    EXTRACT(HOUR FROM o.created_at)::int AS hour,
                    SUM(oi.quantity)                     AS qty
                FROM order_items oi
                JOIN orders     o  ON oi.order_id     = o.id
                JOIN menu_items mi ON oi.menu_item_id = mi.id
                JOIN users      u  ON o.user_id       = u.id
                WHERE u.tenant_id = :tenant_id
                  AND o.status    = 'served'
                  AND o.created_at >= NOW() - INTERVAL '30 days'
                  AND mi.deleted_at IS NULL
                GROUP BY oi.menu_item_id, mi.name,
                         EXTRACT(DOW FROM o.created_at),
                         EXTRACT(HOUR FROM o.created_at)
            """), {"tenant_id": tenant_id}).fetchall()

        if not rows:
            return {"success": False, "error": "No recent order data (30 days) found for this tenant."}

        df = pd.DataFrame(rows, columns=["item_id", "name", "dow", "hour", "qty"])
        df["slot"] = df["hour"].apply(_hour_to_slot)
        df = df.dropna(subset=["slot"]).copy()
        df["slot"] = df["slot"].astype(int)
        df["qty"]  = df["qty"].astype(float)

        if df.empty or len(df) < 5:
            return {"success": False, "error": "Insufficient data in valid time slots."}

        le = LabelEncoder()
        df["item_encoded"] = le.fit_transform(df["name"])

        X = df[["dow", "slot", "item_encoded"]].values
        y = df["qty"].values

        rf = RandomForestRegressor(n_estimators=100, random_state=42)
        rf.fit(X, y)

        payload = {
            "model":      rf,
            "encoder":    le,
            "item_names": le.classes_.tolist(),
        }
        joblib.dump(payload, _model_path(tenant_id))

        return {"success": True, "message": f"Stock model trained on {len(df)} records across {df['name'].nunique()} items."}

    except Exception as e:
        return {"success": False, "error": str(e)}


def recommend_stock(tenant_id: int) -> dict:
    path = _model_path(tenant_id)
    if not os.path.exists(path):
        return {"success": False, "error": "Model not trained yet. Please train first."}

    try:
        payload    = joblib.load(path)
        rf         = payload["model"]
        le         = payload["encoder"]
        item_names = payload["item_names"]

        today_dow      = datetime.now().weekday() + 1  # Python Mon=0 → PostgreSQL Sun=0 Mon=1
        today_dow      = today_dow % 7                 # convert: Mon=1 … Sun=0
        current_hour   = datetime.now().hour
        current_slot   = _hour_to_slot(current_hour)

        result_slots = []
        for slot_idx, slot in enumerate(SLOTS):
            # Skip slots already past today
            if current_slot is not None and slot_idx < current_slot:
                continue

            encoded_items = list(range(len(item_names)))
            X_pred = np.array([[today_dow, slot_idx, enc] for enc in encoded_items])
            preds  = rf.predict(X_pred)

            items_with_pred = [
                {"name": item_names[i], "predicted_qty": max(0, round(float(preds[i])))}
                for i in range(len(item_names))
            ]
            # Return top 5 items per slot
            items_with_pred.sort(key=lambda x: x["predicted_qty"], reverse=True)

            result_slots.append({
                "label": slot["label"],
                "items": items_with_pred[:5],
            })

        return {"success": True, "slots": result_slots}

    except Exception as e:
        return {"success": False, "error": str(e)}