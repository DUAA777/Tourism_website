import os
import json
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
        text = entry.get("message") or entry.get("content") or ""

        if text.strip():
            formatted.append(
                types.Content(
                    role=role,
                    parts=[types.Part.from_text(text=text)]
                )
            )
    return formatted

def handle_fallback(hotels, restaurants, trip_plan, prefix):
    parts = [prefix]

    if trip_plan:
        parts.append("\nSuggested trip plan:")
        parts.append(f"Duration: {trip_plan.get('duration', 'N/A')}")

        hotel = trip_plan.get('hotel')
        if hotel:
            parts.append(f"Recommended stay: {hotel.get('hotel_name', 'Hotel')} ({hotel.get('address', 'Location')})")

    if hotels:
        parts.append("\nHotels:")
        for h in hotels[:3]:
            parts.append(f"- {h.get('hotel_name', 'Hotel')} ({h.get('address', 'Location')})")

    if restaurants:
        parts.append("\nRestaurants:")
        for r in restaurants[:3]:
            parts.append(f"- {r.get('restaurant_name', 'Restaurant')} ({r.get('location', 'Location')})")

    if not hotels and not restaurants:
        parts.append("\nNo matching places were found in the database.")

    return jsonify({"reply": "\n".join(parts)}), 200

@app.route("/chat", methods=["POST"])
def chat():
    hotels = []
    restaurants = []
    trip_plan = None

    try:
        data = request.get_json(silent=True) or {}

        message = (data.get("message") or "").strip()
        history = data.get("history") or []
        intent = data.get("intent") or {}
        hotels = data.get("hotels") or []
        restaurants = data.get("restaurants") or []
        trip_plan = data.get("trip_plan")

        if not message:
            return jsonify({"reply": "Please enter a message."}), 400

        recent_history = history[-6:]
        top_hotels = hotels[:3]
        top_restaurants = restaurants[:3]

        context_data = f"""
DETECTED USER INTENT:
{json.dumps(intent, indent=2, ensure_ascii=False)}

AVAILABLE HOTELS:
{json.dumps(top_hotels, indent=2, ensure_ascii=False)}

AVAILABLE RESTAURANTS:
{json.dumps(top_restaurants, indent=2, ensure_ascii=False)}

TRIP PLAN DATA:
{json.dumps(trip_plan, indent=2, ensure_ascii=False)}
"""

        contents = format_history(recent_history)

        user_prompt = f"""
Context Data:
{context_data}

User Request:
{message}
"""
        contents.append(
            types.Content(
                role="user",
                parts=[types.Part.from_text(text=user_prompt)]
            )
        )

        response = client.models.generate_content(
            model=GEMINI_MODEL,
            contents=contents,
            config=types.GenerateContentConfig(
                temperature=0.4,
                max_output_tokens=700,
                system_instruction="""
You are 'Yalla Nemshi', a helpful and concise Lebanon tourism assistant.

GUIDELINES:
1. Use ONLY the provided Hotels and Restaurants data for recommendations.
2. Use the detected intent and vibe tags when deciding which places fit best.
3. If trip_plan data exists, convert it into a natural itinerary explanation.
4. Use the conversation history to maintain context.
5. Keep answers friendly, practical, and concise.
6. Do not invent phone numbers, ratings, addresses, or prices.
7. Do not use Markdown formatting like **bold**, *, bullet syntax, or headings.
8. Return clean plain text only.
"""
            )
        )

        reply = response.text if response.text else "I'm sorry, I couldn't process that request."
        reply = reply.replace("**", "")

        return jsonify({"reply": reply})

    except Exception as e:
        error_text = str(e)
        print("GEMINI ERROR:", error_text)

        if any(err in error_text for err in ["429", "RESOURCE_EXHAUSTED", "quota"]):
            return handle_fallback(
                hotels,
                restaurants,
                trip_plan,
                "The AI service is temporarily busy, but here are matching options from your database:"
            )

        return jsonify({"reply": f"Service error: {error_text}"}), 500

if __name__ == "__main__":
    app.run(debug=True, port=5000)