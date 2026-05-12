import pandas as pd
from sqlalchemy import text
from database import get_connection

def get_affinity(tenant_id: int) -> dict:
    try:
        with get_connection() as conn:
            rows = conn.execute(text("""
                SELECT
                    mi1.name  AS anchor,
                    mi2.name  AS companion,
                    COUNT(*)  AS co_count
                FROM order_items oi1
                JOIN order_items oi2 ON  oi1.order_id     = oi2.order_id
                                     AND oi1.menu_item_id < oi2.menu_item_id
                JOIN menu_items mi1  ON  oi1.menu_item_id = mi1.id
                JOIN menu_items mi2  ON  oi2.menu_item_id = mi2.id
                JOIN orders     o    ON  oi1.order_id     = o.id
                JOIN users      u    ON  o.user_id        = u.id
                WHERE u.tenant_id    = :tenant_id
                  AND o.status       = 'served'
                  AND mi1.deleted_at IS NULL
                  AND mi2.deleted_at IS NULL
                GROUP BY mi1.name, mi2.name
                HAVING COUNT(*) > 1
                ORDER BY co_count DESC
                LIMIT 200
            """), {"tenant_id": tenant_id}).fetchall()

        if not rows:
            return {"success": False, "error": "Not enough co-purchase data found."}

        df = pd.DataFrame(rows, columns=["anchor", "companion", "co_count"])

        # Build anchor-grouped structure
        # Each pair appears once (anchor < companion by ID),
        # so add the reverse direction too
        full = pd.concat([
            df[["anchor", "companion", "co_count"]],
            df.rename(columns={"anchor": "companion", "companion": "anchor"})[["anchor", "companion", "co_count"]],
        ])

        grouped = {}
        for _, row in full.iterrows():
            a = row["anchor"]
            if a not in grouped:
                grouped[a] = []
            grouped[a].append({"name": row["companion"], "count": int(row["co_count"])})

        # Sort companions and anchor list by total co-occurrence
        anchors = []
        for anchor, companions in grouped.items():
            companions.sort(key=lambda x: x["count"], reverse=True)
            anchors.append({
                "anchor":     anchor,
                "companions": companions[:5],  # top 5 companions per anchor
                "total":      sum(c["count"] for c in companions),
            })

        anchors.sort(key=lambda x: x["total"], reverse=True)

        return {"success": True, "anchors": anchors[:15]}  # top 15 anchors

    except Exception as e:
        return {"success": False, "error": str(e)}