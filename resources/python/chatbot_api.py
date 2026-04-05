import os
import json
import re
from flask import Flask, request, jsonify
from google import genai
from google.genai import types

app = Flask(__name__)

GEMINI_MODEL = "gemini-2.5-flash"
api_key = os.getenv("GEMINI_API_KEY")

if not api_key:
    raise RuntimeError("GEMINI_API_KEY is missing from environment variables.")

client = genai.Client(api_key=api_key)


def format_history(history_list):
    formatted = []

    for entry in history_list:
        role = "user" if entry.get("role") == "user" else "model"
        text = (entry.get("message") or entry.get("content") or "").strip()

        if text:
            formatted.append(
                types.Content(
                    role=role,
                    parts=[types.Part.from_text(text=text)]
                )
            )

    return formatted


def compact_hotel(h):
    if not h:
        return None

    return {
        "hotel_name": h.get("hotel_name"),
        "city": h.get("city"),
        "address": h.get("address"),
        "rating_score": h.get("rating_score"),
        "price_per_night": h.get("price_per_night"),
        "budget_tier": h.get("budget_tier"),
        "description": h.get("description"),
        "vibe_tags": h.get("vibe_tags"),
        "audience_tags": h.get("audience_tags"),
        "match_reasons": h.get("match_reasons"),
    }


def compact_restaurant(r):
    if not r:
        return None

    return {
        "restaurant_name": r.get("restaurant_name"),
        "city": r.get("city"),
        "location": r.get("location"),
        "rating": r.get("rating"),
        "food_type": r.get("food_type"),
        "price_tier": r.get("price_tier"),
        "budget_tier": r.get("budget_tier"),
        "description": r.get("description"),
        "vibe_tags": r.get("vibe_tags"),
        "occasion_tags": r.get("occasion_tags"),
        "match_reasons": r.get("match_reasons"),
    }


def compact_activity(a):
    if not a:
        return None

    return {
        "name": a.get("name"),
        "city": a.get("city"),
        "category": a.get("category"),
        "description": a.get("description"),
        "location": a.get("location"),
        "best_time": a.get("best_time"),
        "duration_estimate": a.get("duration_estimate"),
        "price_type": a.get("price_type"),
        "budget_tier": a.get("budget_tier"),
        "vibe_tags": a.get("vibe_tags"),
        "occasion_tags": a.get("occasion_tags"),
        "match_reasons": a.get("match_reasons"),
    }


def compact_trip_plan(trip_plan):
    if not trip_plan:
        return None

    return trip_plan


def build_prompt_data(intent, hotels, restaurants, activities, trip_plan):
    return {
        "intent": {
            "city": intent.get("city"),
            "start_city": intent.get("start_city"),
            "end_city": intent.get("end_city"),
            "mentioned_cities": intent.get("mentioned_cities", []),
            "vibe_tags": intent.get("vibe_tags", []),
            "food_type": intent.get("food_type"),
            "food_preferences": intent.get("food_preferences", []),
            "budget": intent.get("budget"),
            "budget_max": intent.get("budget_max"),
            "duration": intent.get("duration"),
            "occasion_tags": intent.get("occasion_tags", []),
            "audience_tags": intent.get("audience_tags", []),
            "activity_types": intent.get("activity_types", []),
            "time_preferences": intent.get("time_preferences", []),
            "semantic_concepts": intent.get("semantic_concepts", []),
            "requested_categories": intent.get("requested_categories", []),
            "requires_stay": intent.get("requires_stay", False),
            "wants_trip_plan": intent.get("wants_trip_plan", False),
        },
        "hotels": [compact_hotel(h) for h in hotels[:3]],
        "restaurants": [compact_restaurant(r) for r in restaurants[:3]],
        "activities": [compact_activity(a) for a in activities[:6]],
        "trip_plan": compact_trip_plan(trip_plan),
    }


def format_match_reasons(item):
    reasons = item.get("match_reasons") or []
    if not reasons:
        return ""

    return f" ({', '.join(reasons[:2])})"


def render_trip_plan_slot(key, value):
    label = key.replace("_", " ").title()

    if isinstance(value, dict):
        if value.get("hotel_name"):
            location = value.get("address") or "the selected area"
            return f"{label}: {value.get('hotel_name')} in {location}{format_match_reasons(value)}."

        if value.get("restaurant_name"):
            location = value.get("location") or "the selected area"
            return f"{label}: {value.get('restaurant_name')} in {location}{format_match_reasons(value)}."

        if value.get("title") and value.get("activities"):
            activities = "; ".join(value.get("activities", [])[:3])
            return f"{label}: {value.get('title')}. {activities}"

    if isinstance(value, str) and value.strip():
        return f"{label}: {value.strip()}"

    return None


def render_trip_plan_text(trip_plan, prefix=None):
    parts = []

    if prefix:
        parts.append(prefix)

    parts.append(trip_plan.get("title", "Trip Plan"))

    for day in trip_plan.get("days", []):
        day_number = day.get("day")
        location = day.get("location")
        day_label = f"Day {day_number}"

        if location:
            parts.append(f"{day_label} in {location}:")
        else:
            parts.append(f"{day_label}:")

        flow = day.get("flow", {})
        for key, value in flow.items():
            line = render_trip_plan_slot(key, value)
            if line:
                parts.append(line)

    return "\n".join(parts).strip()


def trip_plan_reply_is_complete(reply, trip_plan):
    if not trip_plan:
        return True

    days = trip_plan.get("days", [])
    if not days:
        return True

    reply = reply or ""

    for day in days:
        day_number = day.get("day")
        if day_number is None:
            continue

        if not re.search(rf"\bDay\s+{re.escape(str(day_number))}\b", reply, re.IGNORECASE):
            return False

    return True


def handle_fallback(hotels, restaurants, activities, trip_plan, prefix):
    if trip_plan:
        return jsonify({"reply": render_trip_plan_text(trip_plan, prefix)}), 200

    parts = [prefix]

    if hotels:
        parts.append("\nHotel options:")
        for h in hotels[:3]:
            parts.append(f"- {h.get('hotel_name')} in {h.get('address')}")

    if restaurants:
        parts.append("\nRestaurant options:")
        for r in restaurants[:3]:
            parts.append(f"- {r.get('restaurant_name')} in {r.get('location')}")

    if activities:
        parts.append("\nActivity ideas:")
        for a in activities[:3]:
            parts.append(f"- {a.get('name')} in {a.get('city')}")

    if not hotels and not restaurants and not activities and not trip_plan:
        parts.append("\nI couldn't find matching places. Try another city, vibe, or budget.")

    return jsonify({"reply": "\n".join(parts)}), 200


def generate_reply(contents):
    response = client.models.generate_content(
        model=GEMINI_MODEL,
        contents=contents,
        config=types.GenerateContentConfig(
            temperature=0.65,
            max_output_tokens=1400,
            system_instruction="""
You are Yalla Nemshi, a warm, friendly Lebanon travel planner.

STYLE:
- Sound human, helpful, and natural
- Sound like a smart local assistant
- Be clear and pleasant, not robotic
- Keep the reply polished but easy to read

IMPORTANT RULES:
1. Use ONLY the provided data.
2. Never invent hotels, restaurants, activities, prices, ratings, or addresses.
3. If trip_plan exists, turn it into a COMPLETE itinerary.
4. Always finish all days fully.
5. Never stop mid-sentence.
6. Do not use markdown like **, #, or bullet symbols.
7. Use plain text only.
8. Keep it concise but complete.
9. For greetings no need to give data.
10. Sound human change up some stuff from time to time.
11. No need to put "Ahlan! Yalla Nemshi is here to help you find that perfect romantic dinner with a sea view in Beirut." in every response.
12. No need to add "Ahlan" in every response.
13. When you recommend a hotel, restaurant, or activity, use its exact provided name.
14. Keep the names you mention aligned with the top options in the provided data.
15. If a trip plan includes a stay, keep that stay in the answer.
16. Use the trip title only once.
17. Do not add a second compact recap after the itinerary.

FOR TRIP PLANS:
- Structure the answer clearly by Day 1, Day 2, Day 3
- For each day, mention morning, lunch, afternoon, evening, and stay/dinner when available
- Use the activity blocks to make the trip feel rich and realistic
- Mention why a hotel or restaurant fits when useful
- Make the route feel logical and smooth
- Write the itinerary like polished website travel copy, not like raw database output
- Keep the tone warm and natural, with short human-friendly descriptions for each part of the day
- Avoid sounding mechanical or repetitive

FOR NORMAL RECOMMENDATIONS:
- Mention up to 3 strong options
- Explicitly name the options you are recommending
- Briefly explain why each one fits
- End with a helpful follow-up suggestion
"""
        )
    )

    return (response.text or "").replace("**", "").strip()


@app.route("/chat", methods=["POST"])
def chat():
    hotels = []
    restaurants = []
    activities = []
    trip_plan = None

    try:
        data = request.get_json(silent=True) or {}

        message = (data.get("message") or "").strip()
        history = data.get("history") or []
        intent = data.get("intent") or {}
        hotels = data.get("hotels") or []
        restaurants = data.get("restaurants") or []
        activities = data.get("activities") or []
        trip_plan = data.get("trip_plan")

        if not message:
            return jsonify({"reply": "Tell me what kind of trip you're looking for."}), 400

        recent_history = history[-5:]
        prompt_data = build_prompt_data(intent, hotels, restaurants, activities, trip_plan)

        contents = format_history(recent_history)

        user_prompt = f"""
Use this structured travel data to answer the user naturally.

DATA:
{json.dumps(prompt_data, ensure_ascii=False)}

USER REQUEST:
{message}
"""

        contents.append(
            types.Content(
                role="user",
                parts=[types.Part.from_text(text=user_prompt)]
            )
        )

        reply = generate_reply(contents)

        incomplete_endings = ("and", "then", "with", "to", "for", "in", "at", "of", "Day", "Morning", "Afternoon", "Evening")
        if (
            len(reply) < 80
            or reply.endswith(incomplete_endings)
            or not reply.endswith((".", "!", "?"))
        ):
            contents.append(
                types.Content(
                    role="user",
                    parts=[types.Part.from_text(
                        text="Rewrite the full answer completely. Make sure every day is complete and the response ends properly."
                    )]
                )
            )
            retry_reply = generate_reply(contents)
            if retry_reply:
                reply = retry_reply

        if trip_plan and reply and not trip_plan_reply_is_complete(reply, trip_plan):
            reply = render_trip_plan_text(
                trip_plan,
                "Here's a complete itinerary built from your matched places:"
            )

        if not reply:
            return handle_fallback(
                hotels,
                restaurants,
                activities,
                trip_plan,
                "I couldn't fully generate the response, but here's a useful version from your data:"
            )

        return jsonify({"reply": reply})

    except Exception as e:
        error_text = str(e)
        print("GEMINI ERROR:", error_text)

        if any(err in error_text for err in ["429", "RESOURCE_EXHAUSTED", "quota"]):
            return handle_fallback(
                hotels,
                restaurants,
                activities,
                trip_plan,
                "The AI service is busy right now, but I still found some good options for you:"
            )

        return jsonify({
            "reply": "Something went wrong on our side. Please try again."
        }), 500


if __name__ == "__main__":
    app.run(debug=True, port=5000)
