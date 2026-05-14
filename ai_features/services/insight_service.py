from datetime import datetime
import pandas as pd
from sqlalchemy import text
from database import get_connection

SLOTS = [
    {"label": "8:00 AM – 11:00 AM", "start": 8,  "end": 10},
    {"label": "11:00 AM – 2:00 PM", "start": 11, "end": 13},
    {"label": "2:00 PM – 5:00 PM",  "start": 14, "end": 16},
    {"label": "5:00 PM – 8:00 PM",  "start": 17, "end": 19},
    {"label": "8:00 PM – 11:00 PM", "start": 20, "end": 22},
]

def _current_slot_label(hour: int) -> str:
    for s in SLOTS:
        if s["start"] <= hour <= s["end"]:
            return s["label"]
    return "Off-peak hours"

def gather_insight_context(tenant_id: int) -> dict:
    """
    Gathers all live + forecast data needed for the AI insight.
    Returns a structured dict — no AI calls here.
    """
    now         = datetime.now()
    current_hour = now.hour
    day_name    = now.strftime("%A")
    slot_label  = _current_slot_label(current_hour)

    with get_connection() as conn:

        # ── Today's orders + revenue so far ──────────────────
        today = conn.execute(text("""
            SELECT
                COUNT(o.id)         AS order_count,
                COALESCE(SUM(o.total_amount), 0) AS revenue
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE u.tenant_id  = :tid
              AND DATE(o.created_at) = CURRENT_DATE
              AND o.status = 'served'
        """), {"tid": tenant_id}).fetchone()

        # ── Yesterday at same hour ────────────────────────────
        yesterday = conn.execute(text("""
            SELECT
                COUNT(o.id)         AS order_count,
                COALESCE(SUM(o.total_amount), 0) AS revenue
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE u.tenant_id  = :tid
              AND DATE(o.created_at) = CURRENT_DATE - INTERVAL '1 day'
              AND EXTRACT(HOUR FROM o.created_at) <= :hour
              AND o.status = 'served'
        """), {"tid": tenant_id, "hour": current_hour}).fetchone()

        # ── Top 3 items sold today ────────────────────────────
        top_today = conn.execute(text("""
            SELECT mi.name, SUM(oi.quantity) AS qty
            FROM order_items oi
            JOIN orders     o  ON oi.order_id     = o.id
            JOIN menu_items mi ON oi.menu_item_id = mi.id
            JOIN users      u  ON o.user_id       = u.id
            WHERE u.tenant_id  = :tid
              AND DATE(o.created_at) = CURRENT_DATE
              AND o.status = 'served'
            GROUP BY mi.name
            ORDER BY qty DESC
            LIMIT 3
        """), {"tid": tenant_id}).fetchall()

        # ── Low stock alerts ──────────────────────────────────
        low_stock = conn.execute(text("""
            SELECT mi.name, s.current_quantity, s.low_stock_threshold
            FROM stock s
            JOIN menu_items mi ON s.menu_item_id  = mi.id
            JOIN categories c  ON mi.category_id  = c.id
            WHERE c.tenant_id  = :tid
              AND s.current_quantity <= s.low_stock_threshold
              AND mi.deleted_at IS NULL
            ORDER BY s.current_quantity ASC
            LIMIT 3
        """), {"tid": tenant_id}).fetchall()

        # ── Last 2 months revenue for trend ──────────────────
        revenue_trend = conn.execute(text("""
            SELECT
                DATE_TRUNC('month', o.created_at) AS month,
                SUM(o.total_amount)               AS revenue
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE u.tenant_id = :tid
              AND o.status    = 'served'
              AND o.created_at >= NOW() - INTERVAL '60 days'
            GROUP BY DATE_TRUNC('month', o.created_at)
            ORDER BY month ASC
        """), {"tid": tenant_id}).fetchall()

        # ── This week vs last week ────────────────────────────
        this_week = conn.execute(text("""
            SELECT COALESCE(SUM(o.total_amount), 0) AS revenue
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE u.tenant_id = :tid
              AND o.status    = 'served'
              AND o.created_at >= DATE_TRUNC('week', NOW())
        """), {"tid": tenant_id}).fetchone()

        last_week = conn.execute(text("""
            SELECT COALESCE(SUM(o.total_amount), 0) AS revenue
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE u.tenant_id = :tid
              AND o.status    = 'served'
              AND o.created_at >= DATE_TRUNC('week', NOW()) - INTERVAL '7 days'
              AND o.created_at <  DATE_TRUNC('week', NOW())
        """), {"tid": tenant_id}).fetchone()

    # ── Compute derived metrics ───────────────────────────────
    today_orders  = int(today.order_count)   if today   else 0
    today_rev     = float(today.revenue)     if today   else 0.0
    yest_orders   = int(yesterday.order_count) if yesterday else 0
    yest_rev      = float(yesterday.revenue)   if yesterday else 0.0

    def pct_change(current, previous):
        if previous == 0:
            return None
        return round(((current - previous) / previous) * 100, 1)

    orders_vs_yesterday = pct_change(today_orders, yest_orders)
    revenue_vs_yesterday = pct_change(today_rev, yest_rev)

    this_week_rev = float(this_week.revenue) if this_week else 0.0
    last_week_rev = float(last_week.revenue) if last_week else 0.0
    week_vs_week  = pct_change(this_week_rev, last_week_rev)

    # Monthly trend direction
    monthly_direction = None
    if len(revenue_trend) >= 2:
        prev_month = float(revenue_trend[-2].revenue)
        curr_month = float(revenue_trend[-1].revenue)
        monthly_direction = pct_change(curr_month, prev_month)

    top_items_today = [
        {"name": r.name, "qty": int(r.qty)}
        for r in top_today
    ]

    low_stock_items = [
        {"name": r.name, "qty": int(r.current_quantity), "threshold": int(r.low_stock_threshold)}
        for r in low_stock
    ]

    return {
        "day":                   day_name,
        "time_slot":             slot_label,
        "current_hour":          current_hour,
        "today_orders":          today_orders,
        "today_revenue":         round(today_rev, 2),
        "yesterday_orders":      yest_orders,
        "yesterday_revenue":     round(yest_rev, 2),
        "orders_vs_yesterday":   orders_vs_yesterday,
        "revenue_vs_yesterday":  revenue_vs_yesterday,
        "week_vs_last_week":     week_vs_week,
        "monthly_trend_pct":     monthly_direction,
        "top_items_today":       top_items_today,
    }