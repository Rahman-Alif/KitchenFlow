import json
from groq import AsyncGroq
from config import GROQ_API_KEY

client = AsyncGroq(api_key=GROQ_API_KEY)
MODEL  = "llama-3.1-8b-instant"

async def generate_description(category_name: str, item_name: str) -> dict:
    try:
        prompt = (
            f"Write a concise, appetizing 1–2 sentence menu description for a food item.\n\n"
            f"Category: {category_name}\n"
            f"Item name: {item_name}\n\n"
            f"Rules: Be specific and sensory. No generic phrases like 'delicious' or 'tasty'. "
            f"Mention key ingredients or cooking method. Max 35 words. No quotation marks."
            f"Don't include luxury food items, you can mention general toppings, ingredients etc. but do not mention any rich or luxurious ones like truffle, caviar, etc."
            f"Keep in mind that the menu is for a corporate/institution catering service place."
        )

        response = await client.chat.completions.create(
            model=MODEL,
            messages=[{"role": "user", "content": prompt}],
            max_tokens=80,
            temperature=0.7,
        )

        description = response.choices[0].message.content.strip()
        return {"success": True, "description": description}

    except Exception as e:
        return {"success": False, "error": str(e)}


async def autofill_message(content: str) -> dict:
    try:
        prompt = (
            f"Analyze this internal staff message from a restaurant/canteen management system "
            f"and return a JSON object with three fields:\n"
            f"- title: a short subject line (max 8 words)\n"
            f"- tag: one of [item_requirement, customer_inquiry, staff_duty, incident, other]\n"
            f"- priority: one of [high, medium, low]\n\n"
            f"Message: {content}\n\n"
            f"Return ONLY valid JSON. No explanation, no markdown, no backticks."
        )

        response = await client.chat.completions.create(
            model=MODEL,
            messages=[{"role": "user", "content": prompt}],
            max_tokens=80,
            temperature=0.2,
        )

        raw  = response.choices[0].message.content.strip()
        data = json.loads(raw)

        # Validate and sanitise
        valid_tags       = ["item_requirement", "customer_inquiry", "staff_duty", "incident", "other"]
        valid_priorities = ["high", "medium", "low"]

        return {
            "success":  True,
            "title":    str(data.get("title", ""))[:100],
            "tag":      data.get("tag") if data.get("tag") in valid_tags else "other",
            "priority": data.get("priority") if data.get("priority") in valid_priorities else "medium",
        }

    except Exception as e:
        return {"success": False, "error": str(e)}

async def generate_insight(context: dict) -> dict:
    try:
        # Build a readable data block for Groq
        top_items_str = ", ".join(
            f"{i['name']} ({i['qty']} portions)"
            for i in context["top_items_today"]
        ) or "no orders yet today"

        # low_stock_str = ", ".join(
        #     f"{i['name']} ({i['qty']} left)"
        #     for i in context["low_stock_items"]
        # ) or "none"

        def fmt_pct(val):
            if val is None: return "no data to compare"
            direction = "up" if val >= 0 else "down"
            return f"{direction} {abs(val)}%"

        prompt = f"""You are an AI operations assistant for a B2B canteen management platform.
Write a 4-5 line business insight for the canteen admin based on today's live data.

Rules:
- Be specific with the numbers provided. Do not invent numbers.
- Sound like a smart operations manager — direct, observational, slightly predictive.
- Vary your opening line. Do not always start with "Today".
- Highlight what is most notable: a strong day, a slow start, or a weekly trend.
- Note: item names may contain portion sizes in brackets e.g. "Rasgulla (3 pcs)" — this is the item name, not a quantity.
- Do not mention stock levels or low stock — that is handled separately.
- Do not use bullet points. Write in flowing prose.
- 4-5 lines maximum.

Live data for {context['day']}, {context['time_slot']}:
- Orders so far today: {context['today_orders']} ({fmt_pct(context['orders_vs_yesterday'])} vs yesterday at same time)
- Revenue so far today: ${context['today_revenue']:,.2f} ({fmt_pct(context['revenue_vs_yesterday'])} vs yesterday)
- This week vs last week: {fmt_pct(context['week_vs_last_week'])}
- Monthly revenue trend: {fmt_pct(context['monthly_trend_pct'])} vs previous month
- Top selling items today: {top_items_str}
"""

        response = await client.chat.completions.create(
            model=MODEL,
            messages=[{"role": "user", "content": prompt}],
            max_tokens=180,
            temperature=0.85,  # higher temp = more varied day-to-day phrasing
        )

        insight_text = response.choices[0].message.content.strip()
        return {"success": True, "insight": insight_text}

    except Exception as e:
        return {"success": False, "error": str(e)}