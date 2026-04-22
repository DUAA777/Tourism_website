import os
import json
import re
from flask import Flask, request, jsonify
from google import genai
from google.genai import types
from config import ENV

app = Flask(__name__)

GEMINI_MODEL = "gemini-2.5-flash"
api_key = os.getenv("GEMINI_API_KEY", ENV.get("GEMINI_API_KEY"))

if not api_key:
    raise RuntimeError("GEMINI_API_KEY is missing from environment variables and .env.")

os.environ.setdefault("GEMINI_API_KEY", api_key)

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


def compact_diagnostics(diagnostics):
    if not diagnostics:
        return {}

    guidance = diagnostics.get("guidance") or {}
    confidence = diagnostics.get("confidence") or {}
    tone = diagnostics.get("tone") or {}

    return {
        "guidance": {
            "should_hold_results": bool(guidance.get("should_hold_results")),
            "reason": guidance.get("reason"),
            "follow_up_hints": guidance.get("follow_up_hints") or [],
            "fallback_reply": guidance.get("fallback_reply"),
        },
        "confidence": {
            "signal_score": confidence.get("signal_score"),
            "overall": confidence.get("overall"),
            "hotels": confidence.get("hotels") or {},
            "restaurants": confidence.get("restaurants") or {},
            "activities": confidence.get("activities") or {},
        },
        "tone": {
            "response_tone": tone.get("response_tone") or "neutral",
        },
    }


def compact_session_context(session_context):
    if not isinstance(session_context, dict):
        return {}

    intent = session_context.get("intent") if isinstance(session_context.get("intent"), dict) else {}
    diagnostics = session_context.get("diagnostics") if isinstance(session_context.get("diagnostics"), dict) else {}

    return {
        "intent": {
            "city": intent.get("city"),
            "resolved_city": intent.get("resolved_city"),
            "mentioned_cities": intent.get("mentioned_cities") or [],
            "requested_categories": intent.get("requested_categories") or [],
            "wants_trip_plan": bool(intent.get("wants_trip_plan")),
        },
        "summary_chips": (session_context.get("summary_chips") or diagnostics.get("summary_chips") or [])[:6],
        "last_user_message": (session_context.get("last_user_message") or "").strip(),
        "last_reply": (session_context.get("last_reply") or "").strip()[:500],
    }


def collect_candidate_cities(hotels, restaurants, activities):
    cities = []

    for item in hotels or []:
        city = (item.get("city") or "").strip()
        if city and city not in cities:
            cities.append(city)

    for item in restaurants or []:
        city = (item.get("city") or "").strip()
        if city and city not in cities:
            cities.append(city)

    for item in activities or []:
        city = (item.get("city") or "").strip()
        if city and city not in cities:
            cities.append(city)

    return cities[:5]


def normalize_message_text(message):
    return re.sub(r"\s+", " ", (message or "").strip().lower()).strip(" .!?")


def is_generic_guidance_request(message):
    normalized = normalize_message_text(message)

    if not normalized:
        return True

    exact_phrases = {
        "help",
        "help me",
        "can you help me",
        "recommend something",
        "suggest something",
        "show me options",
        "give me ideas",
        "what do you recommend",
        "where should i go",
        "what should i do",
        "i dont know",
        "i don't know",
        "im not sure",
        "i'm not sure",
        "surprise me",
        "anything works",
        "hotel",
        "hotels",
        "restaurant",
        "restaurants",
        "activity",
        "activities",
        "place",
        "places",
        "trip",
        "trips",
        "plan a trip",
    }

    if normalized in exact_phrases:
        return True

    broad_patterns = [
        r"^(can you )?(help|guide) me\b",
        r"^what can you (do|recommend)\b",
        r"^(recommend|suggest) (me )?(a )?(trip|hotel|restaurant|place|activity)\b$",
        r"^i want (a )?(trip|hotel|restaurant|place|activity)\b$",
    ]

    return any(re.search(pattern, normalized, re.IGNORECASE) for pattern in broad_patterns)


def looks_like_small_talk_message(message):
    normalized = normalize_message_text(message)

    if not normalized:
        return False

    if normalized.isalpha() and len(normalized) <= 2:
        return True

    exact_phrases = {
        "h",
        "hi",
        "hello",
        "hey",
        "yo",
        "sup",
        "good morning",
        "good afternoon",
        "good evening",
        "hala",
        "ahlan",
        "thanks",
        "thank you",
        "thx",
        "ok",
        "okay",
        "nice",
        "cool",
        "great",
        "awesome",
        "perfect",
        "good",
        "sweet",
        "lovely",
        "haha",
        "lol",
        "lmao",
        "test",
        "testing",
    }

    if normalized in exact_phrases:
        return True

    patterns = [
        r"^(hi|hello|hey)\b",
        r"^(thanks|thank you|thx)\b",
        r"^(nice|cool|great|awesome|perfect|good)\b$",
        r"^(haha|lol|lmao)\b",
        r"^testing\b",
        r"^just testing\b",
    ]

    return any(re.search(pattern, normalized, re.IGNORECASE) for pattern in patterns)


def looks_like_meta_chat_message(message):
    normalized = normalize_message_text(message)

    if not normalized:
        return False

    patterns = [
        r"\bis (the )?(ai|bot|chatbot) down\b",
        r"\bare you (there|working|alive)\b",
        r"\bis this working\b",
        r"\bare you down\b",
        r"\bwhy (are|is) (the )?(ai|bot|chatbot)\b",
        r"\bcan you hear me\b",
        r"\bdid you break\b",
    ]

    return any(re.search(pattern, normalized, re.IGNORECASE) for pattern in patterns)


def build_small_talk_reply(message):
    normalized = normalize_message_text(message)

    if normalized.startswith(("thanks", "thank you", "thx")):
        return "Anytime. If you want, send me a city, budget, trip length, or vibe and I'll make the next suggestion much more specific."

    if normalized in {"nice", "cool", "great", "awesome", "perfect", "good", "sweet", "lovely"}:
        return "Glad that helped. If you want, send me a city, budget, trip length, or vibe and I'll narrow it down properly."

    if normalized in {"test", "testing"} or normalized.startswith("just testing"):
        return "I'm here. Try me with a city, hotel, restaurant, activity, or trip idea and I'll plan from there."

    return "Hi. I can help with hotels, restaurants, places to visit, or a full trip plan. Tell me a city or the kind of vibe you want, and I'll take it from there."


def build_meta_chat_reply():
    return (
        "I'm here. If the last reply felt generic, it usually means I still need a city, budget, trip length, "
        "or the kind of vibe you want before I narrow things down properly."
    )


def has_user_preference_signal(message, intent):
    city = (intent.get("city") or "").strip()
    start_city = (intent.get("start_city") or "").strip()
    end_city = (intent.get("end_city") or "").strip()
    mentioned_cities = [c for c in (intent.get("mentioned_cities") or []) if str(c).strip()]
    vibe_tags = [v for v in (intent.get("vibe_tags") or []) if str(v).strip()]
    food_preferences = [f for f in (intent.get("food_preferences") or []) if str(f).strip()]
    occasion_tags = [o for o in (intent.get("occasion_tags") or []) if str(o).strip()]
    audience_tags = [a for a in (intent.get("audience_tags") or []) if str(a).strip()]
    activity_types = [a for a in (intent.get("activity_types") or []) if str(a).strip()]
    time_preferences = [t for t in (intent.get("time_preferences") or []) if str(t).strip()]
    semantic_concepts = [s for s in (intent.get("semantic_concepts") or []) if str(s).strip()]
    budget = intent.get("budget")
    budget_max = intent.get("budget_max")
    duration = intent.get("duration")
    food_type = (intent.get("food_type") or "").strip()

    strong_signal = any([
        city,
        start_city,
        end_city,
        mentioned_cities,
        vibe_tags,
        food_preferences,
        food_type,
        occasion_tags,
        audience_tags,
        activity_types,
        time_preferences,
        budget,
        budget_max,
        duration,
    ])

    if strong_signal:
        return True

    return bool(
        semantic_concepts
        and len((message or "").split()) >= 3
        and not is_generic_guidance_request(message)
    )


def build_conversation_notes(message, intent, hotels, restaurants, activities, trip_plan, diagnostics=None):
    message = (message or "").strip()
    city = (intent.get("city") or "").strip()
    start_city = (intent.get("start_city") or "").strip()
    end_city = (intent.get("end_city") or "").strip()
    mentioned_cities = [c for c in (intent.get("mentioned_cities") or []) if str(c).strip()]
    vibe_tags = [v for v in (intent.get("vibe_tags") or []) if str(v).strip()]
    food_preferences = [f for f in (intent.get("food_preferences") or []) if str(f).strip()]
    occasion_tags = [o for o in (intent.get("occasion_tags") or []) if str(o).strip()]
    audience_tags = [a for a in (intent.get("audience_tags") or []) if str(a).strip()]
    activity_types = [a for a in (intent.get("activity_types") or []) if str(a).strip()]
    time_preferences = [t for t in (intent.get("time_preferences") or []) if str(t).strip()]
    semantic_concepts = [s for s in (intent.get("semantic_concepts") or []) if str(s).strip()]
    requested_categories = [c for c in (intent.get("requested_categories") or []) if str(c).strip()]
    budget = intent.get("budget")
    budget_max = intent.get("budget_max")
    duration = intent.get("duration")

    has_specific_city = bool(city or start_city or end_city or mentioned_cities)
    has_category_request = bool(requested_categories or intent.get("wants_trip_plan") or intent.get("requires_stay"))
    has_preference_signal = has_user_preference_signal(message, intent)
    is_open_ended_request = not has_preference_signal and not has_category_request
    looks_like_small_talk = looks_like_small_talk_message(message)
    looks_like_meta_chat = looks_like_meta_chat_message(message)
    diagnostics = diagnostics or {}
    guidance = diagnostics.get("guidance") or {}
    confidence = diagnostics.get("confidence") or {}
    tone = diagnostics.get("tone") or {}
    follow_up_context = intent.get("follow_up_context") or {}
    should_hold_recommendation_results = bool(
        guidance.get("should_hold_results")
        or looks_like_small_talk
        or looks_like_meta_chat
        or not has_preference_signal
    )

    candidate_cities = collect_candidate_cities(hotels, restaurants, activities)

    return {
        "message_text": message,
        "has_specific_city": has_specific_city,
        "has_preference_signal": has_preference_signal,
        "has_category_request": has_category_request,
        "is_open_ended_request": is_open_ended_request,
        "looks_like_small_talk": looks_like_small_talk,
        "looks_like_meta_chat": looks_like_meta_chat,
        "is_follow_up": bool(follow_up_context.get("is_follow_up")),
        "should_hold_recommendation_results": should_hold_recommendation_results,
        "should_guide_user_gently": should_hold_recommendation_results or (not has_specific_city),
        "wants_trip_plan": bool(intent.get("wants_trip_plan")),
        "requires_stay": bool(intent.get("requires_stay")),
        "guidance_reason": guidance.get("reason"),
        "follow_up_hints": guidance.get("follow_up_hints") or [],
        "carryover_fields": follow_up_context.get("carryover_fields") or [],
        "confidence_overall": confidence.get("overall"),
        "signal_score": confidence.get("signal_score"),
        "response_tone": tone.get("response_tone") or intent.get("response_tone") or "neutral",
        "available_result_counts": {
            "hotels": len(hotels or []),
            "restaurants": len(restaurants or []),
            "activities": len(activities or []),
            "trip_days": len((trip_plan or {}).get("days", [])) if isinstance(trip_plan, dict) else 0,
        },
        "candidate_cities_from_results": candidate_cities,
    }


def sanitize_recommendation_payload(conversation_notes, hotels, restaurants, activities, trip_plan):
    if conversation_notes.get("should_hold_recommendation_results"):
        return [], [], [], None

    return hotels, restaurants, activities, trip_plan


def build_prompt_data(intent, hotels, restaurants, activities, trip_plan, diagnostics=None, session_context=None):
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
            "response_tone": intent.get("response_tone"),
            "follow_up_context": intent.get("follow_up_context") or {},
        },
        "conversation_notes": build_conversation_notes(
            "",
            intent,
            hotels,
            restaurants,
            activities,
            trip_plan,
            diagnostics,
        ),
        "hotels": [compact_hotel(h) for h in hotels[:3]],
        "restaurants": [compact_restaurant(r) for r in restaurants[:3]],
        "activities": [compact_activity(a) for a in activities[:6]],
        "trip_plan": compact_trip_plan(trip_plan),
        "diagnostics": compact_diagnostics(diagnostics),
        "session_context": compact_session_context(session_context),
    }


def format_match_reasons(item):
    reasons = item.get("match_reasons") or []
    if not reasons:
        return ""

    return f" ({', '.join(reasons[:2])})"


def clean_text(text):
    text = re.sub(r"\s+", " ", (text or "")).strip()
    text = text.replace(".;", ".")
    text = re.sub(r";\s*\.", ".", text)
    text = re.sub(r"\.\.+", ".", text)
    text = re.sub(r"[\\/]+$", "", text)

    return text.strip()


def ensure_sentence(text):
    text = clean_text(text).strip()
    if not text:
        return ""

    if text[-1] not in ".!?":
        text += "."

    return text


def normalize_compare_text(text):
    return re.sub(r"[^a-z0-9]+", " ", (text or "").lower()).strip()


def humanize_label(value):
    return clean_text(str(value or "").replace("_", " ").replace("-", " ")).title()


def display_city(city):
    return humanize_label(city)


def natural_join(items):
    items = [clean_text(item) for item in items if clean_text(item)]

    if not items:
        return ""

    if len(items) == 1:
        return items[0]

    if len(items) == 2:
        return f"{items[0]} and {items[1]}"

    return f"{', '.join(items[:-1])}, and {items[-1]}"


def location_prefix_contains_name_tokens(name_norm, location_prefix_norm):
    name_tokens = [token for token in name_norm.split(" ") if token]
    location_tokens = [token for token in location_prefix_norm.split(" ") if token]

    if len(name_tokens) < 2 or not location_tokens:
        return False

    return all(token in location_tokens for token in name_tokens)


def trim_leading_name(name, location):
    name = clean_text(name)
    location = clean_text(location).strip(" ,.;")

    if not name or not location:
        return location

    segments = [segment.strip() for segment in location.split(",") if segment.strip()]
    if segments:
        segment_norm = normalize_compare_text(segments[0])
        name_norm = normalize_compare_text(name)

        if (
            segment_norm == name_norm
            or segment_norm.startswith(name_norm)
            or name_norm.startswith(segment_norm)
            or location_prefix_contains_name_tokens(name_norm, segment_norm)
        ):
            return ", ".join(segments[1:]).strip(" ,.;")

    return location


def format_trip_prefix(prefix):
    prefix = clean_text(prefix)
    if not prefix:
        return ""

    if prefix.endswith(":"):
        return prefix

    return ensure_sentence(prefix)


def format_food_type(food_type):
    raw = clean_text(food_type)
    if not raw:
        return ""

    parts = [humanize_label(part) for part in re.split(r"[,/]+", raw) if clean_text(part)]
    if parts:
        return ", ".join(parts)

    return raw


def activity_text_reads_like_sentence(activity_text, lead):
    normalized_text = normalize_compare_text(activity_text)
    normalized_lead = normalize_compare_text(lead)

    if not normalized_text:
        return False

    sentence_like_starts = {
        normalized_lead,
        "ease into the day",
        "spend the afternoon",
        "spend some time",
        "keep the evening",
        "start your day",
        "begin your day",
        "enjoy",
        "explore",
        "exploring",
        "relax",
        "relaxing",
        "take",
        "head to",
        "wrap up",
        "unwind",
    }

    return any(
        starter and normalized_text.startswith(starter)
        for starter in sentence_like_starts
    )


def split_paragraphs(text):
    text = re.sub(r"\r\n?", "\n", text or "")
    text = re.sub(r"\n{3,}", "\n\n", text)

    return [part.strip() for part in re.split(r"\n\s*\n", text) if part.strip()]


def dedupe_sentences(paragraph):
    sentences = [part.strip() for part in re.split(r"(?<=[.!?])\s+", paragraph or "") if part.strip()]
    if len(sentences) <= 1:
        return clean_text(paragraph)

    result = []
    seen = set()

    for sentence in sentences:
        normalized = normalize_compare_text(sentence)
        if not normalized or normalized in seen:
            continue

        seen.add(normalized)
        result.append(sentence)

    return clean_text(" ".join(result))


def soften_stock_opening(paragraph):
    paragraph = clean_text(paragraph)

    replacements = [
        (r"\bI can certainly help you\b", "I can help you"),
        (r"\bI'd be delighted to help\b", "I can help"),
        (r"\bI would be delighted to help\b", "I can help"),
        (r"\bThat sounds absolutely wonderful\b", "That sounds like a great plan"),
        (r"\bThat sounds absolutely lovely\b", "That sounds like a great plan"),
        (r"\bIt sounds like a perfect getaway\b", "That fits the kind of getaway you are after"),
        (r"\bIt sounds absolutely lovely\b", "That sounds like a great fit"),
    ]

    for pattern, replacement in replacements:
        paragraph = re.sub(pattern, replacement, paragraph, flags=re.IGNORECASE)

    return paragraph.strip()


def remove_redundant_trip_title(paragraphs, trip_plan):
    if not trip_plan:
        return paragraphs

    title = clean_text((trip_plan or {}).get("title"))
    if not title:
        return paragraphs

    title_norm = normalize_compare_text(title)
    cleaned = []

    for paragraph in paragraphs:
        paragraph_norm = normalize_compare_text(paragraph)

        if paragraph_norm == title_norm:
            previous_text = " ".join(normalize_compare_text(item) for item in cleaned[-2:])
            if title_norm in previous_text:
                continue

        cleaned.append(paragraph)

    return cleaned


def collapse_trip_title_and_intro(paragraphs, trip_plan):
    if not trip_plan or len(paragraphs) < 2:
        return paragraphs

    title = clean_text((trip_plan or {}).get("title"))
    if not title:
        return paragraphs

    title_norm = normalize_compare_text(title)
    first_norm = normalize_compare_text(paragraphs[0])
    second_norm = normalize_compare_text(paragraphs[1])

    if first_norm == title_norm and title_norm in second_norm:
        return paragraphs[1:]

    return paragraphs


def compress_redundant_trip_intro(paragraphs, trip_plan):
    if not trip_plan or len(paragraphs) < 2:
        return paragraphs

    first_norm = normalize_compare_text(paragraphs[0])
    second_norm = normalize_compare_text(paragraphs[1])

    first_is_intro = first_norm.startswith("here s ") or first_norm.startswith("here is ")
    second_is_redundant_intro = (
        second_norm.startswith("ahlan")
        or "i can help you plan" in second_norm
        or "i d love to help you plan" in second_norm
        or "i can help plan" in second_norm
    )

    if first_is_intro and second_is_redundant_intro:
        return [paragraphs[0]] + paragraphs[2:]

    return paragraphs


def dedupe_paragraphs(paragraphs):
    cleaned = []
    seen = set()

    for paragraph in paragraphs:
        paragraph = dedupe_sentences(soften_stock_opening(paragraph))
        if not paragraph:
            continue

        normalized = normalize_compare_text(paragraph)
        if not normalized or normalized in seen:
            continue

        seen.add(normalized)
        cleaned.append(paragraph)

    return cleaned


def polish_goofy_phrasing(text):
    text = text or ""

    replacements = [
        (r"\bSeafood\b", "seafood"),
        (r"\bMid-range\b", "mid-range"),
        (r"\bPremium\b", "premium"),
        (r"\boften boasts\b", "offers"),
        (r"\bboasts\b", "offers"),
        (r"\bwith rates around ([0-9]+)\$", r"at around $\1 per night"),
    ]

    for pattern, replacement in replacements:
        text = re.sub(pattern, replacement, text)

    return text.strip()


def normalize_trip_slot_line_breaks(text):
    text = text or ""

    text = re.sub(
        r"\s+(?=(Morning|Lunch|Afternoon|Evening|Dinner|Stay)\s*:)",
        "\n",
        text,
        flags=re.IGNORECASE,
    )
    text = re.sub(r"\n{3,}", "\n\n", text)

    return text.strip()


def polish_generated_reply(text, trip_plan=None):
    text = (text or "").replace("**", "").strip()
    if not text:
        return ""

    paragraphs = split_paragraphs(text)
    paragraphs = remove_redundant_trip_title(paragraphs, trip_plan)
    paragraphs = collapse_trip_title_and_intro(paragraphs, trip_plan)
    paragraphs = compress_redundant_trip_intro(paragraphs, trip_plan)
    paragraphs = dedupe_paragraphs(paragraphs)

    polished = "\n\n".join(paragraphs).strip()
    polished = re.sub(r"\n{3,}", "\n\n", polished)
    polished = polish_goofy_phrasing(polished)
    if trip_plan:
        polished = normalize_trip_slot_line_breaks(polished)

    return polished.strip()


def build_trip_fallback_intro(intent, trip_plan):
    days = len(trip_plan.get("days", []) or [])
    city = display_city(intent.get("city"))
    start_city = display_city(intent.get("start_city"))
    end_city = display_city(intent.get("end_city"))
    vibe_tags = [humanize_label(tag).lower() for tag in (intent.get("vibe_tags") or [])[:2]]
    food_preferences = [humanize_label(tag).lower() for tag in (intent.get("food_preferences") or [])[:1]]

    if start_city and end_city and start_city != end_city:
        place_part = f"from {start_city} to {end_city}"
    elif city:
        place_part = f"in {city}"
    else:
        place_part = "across Lebanon"

    descriptor_parts = []
    if vibe_tags:
        descriptor_parts.append(f"a {natural_join(vibe_tags)} feel")
    if food_preferences:
        descriptor_parts.append(f"{natural_join(food_preferences)} stops")

    if descriptor_parts:
        return ensure_sentence(
            f"Here is a clear {days}-day plan {place_part} with {natural_join(descriptor_parts)}"
        )

    return ensure_sentence(f"Here is a clear {days}-day plan {place_part}")


def build_no_match_reply(intent):
    city = display_city(intent.get("city"))
    wants_trip_plan = bool(intent.get("wants_trip_plan"))
    requires_stay = bool(intent.get("requires_stay"))
    requested_categories = {str(item).strip().lower() for item in (intent.get("requested_categories") or []) if str(item).strip()}
    food_preferences = [humanize_label(item).lower() for item in (intent.get("food_preferences") or []) if str(item).strip()]
    vibe_tags = [humanize_label(item).lower() for item in (intent.get("vibe_tags") or []) if str(item).strip()]

    city_part = f" in {city}" if city else ""

    if wants_trip_plan:
        return (
            f"I couldn't build a strong full trip plan{city_part} from the current matches yet. "
            "If you want, give me a slightly clearer city, route, or vibe and I can tighten it up."
        )

    if requested_categories.intersection({"restaurants"}) or food_preferences:
        food_part = f" {food_preferences[0]}" if food_preferences else ""
        return (
            f"I couldn't find a strong{food_part} restaurant match{city_part} right now. "
            "If you want, I can suggest another kind of meal there or look in a different city."
        )

    if requires_stay or requested_categories.intersection({"hotels"}):
        vibe_part = f" for a {' '.join(vibe_tags[:2])} stay" if vibe_tags else ""
        return (
            f"I couldn't find a strong hotel match{city_part}{vibe_part} right now. "
            "If you want, I can try a different budget, vibe, or nearby city."
        )

    if requested_categories.intersection({"activities"}):
        return (
            f"I couldn't find a strong activity match{city_part} right now. "
            "If you want, I can try a different vibe or a nearby city."
        )

    return "I couldn't find a strong match yet. Try another city, vibe, or budget and I'll narrow it down better."


def render_hotel_fallback_reply(intent, hotels):
    city = display_city(intent.get("city")) or display_city((hotels[0] or {}).get("city"))
    city_part = f" in {city}" if city else ""
    intro = f"For stays{city_part}, these are the clearest options I can still stand behind right now."
    lines = [ensure_sentence(intro)]

    for hotel in hotels[:3]:
        name = clean_text(hotel.get("hotel_name"))
        location = trim_leading_name(name, hotel.get("address") or hotel.get("city") or "")
        details = []

        if location:
            details.append(f"in {location}")

        price = clean_text(hotel.get("price_per_night"))
        if price:
            details.append(f"rates around {price}")

        rating = hotel.get("rating_score")
        if rating:
            details.append(f"rated {rating}/10")

        sentence = name
        if details:
            sentence += " is " + ", ".join(details)

        lines.append(ensure_sentence(sentence))

    return "\n\n".join([line for line in lines if line])


def render_restaurant_fallback_reply(intent, restaurants):
    city = display_city(intent.get("city")) or display_city((restaurants[0] or {}).get("city"))
    city_part = f" in {city}" if city else ""
    intro = f"For food spots{city_part}, these are the clearest matches I can still stand behind right now."
    lines = [ensure_sentence(intro)]

    for restaurant in restaurants[:3]:
        name = clean_text(restaurant.get("restaurant_name"))
        location = trim_leading_name(name, restaurant.get("location") or restaurant.get("city") or "")
        details = []

        if location:
            details.append(f"in {location}")

        food_type = clean_text(restaurant.get("food_type"))
        if food_type:
            details.append(food_type)

        rating = restaurant.get("rating")
        if rating:
            details.append(f"rated {rating}/5")

        sentence = name
        if details:
            sentence += " is " + ", ".join(details)

        lines.append(ensure_sentence(sentence))

    return "\n\n".join([line for line in lines if line])


def activity_city_list(activities, limit=3):
    cities = []

    for activity in (activities or [])[:limit]:
        city = display_city(activity.get("city"))
        if city and city not in cities:
            cities.append(city)

    return cities


def format_activity_location(activity):
    name = clean_text(activity.get("name"))
    location = clean_text(activity.get("location") or "").strip(" ,.;")
    city = display_city(activity.get("city"))

    if normalize_compare_text(location) == normalize_compare_text(name):
        location = ""

    if location and city and normalize_compare_text(city) not in normalize_compare_text(location):
        return f"{location}, {city}"

    return location or city


def render_activity_fallback_reply(intent, activities):
    explicit_city = display_city(intent.get("city") or intent.get("resolved_city"))
    activity_cities = activity_city_list(activities)

    if explicit_city:
        city_part = f" in {explicit_city}"
    elif intent.get("country_scope") or len(activity_cities) > 1:
        city_part = " across Lebanon"
    elif len(activity_cities) == 1:
        city_part = f" in {activity_cities[0]}"
    else:
        city_part = ""

    intro = f"For places to visit{city_part}, these are the strongest ideas I can still stand behind right now."
    lines = [ensure_sentence(intro)]

    for activity in activities[:3]:
        name = clean_text(activity.get("name"))
        location = clean_text(format_activity_location(activity))
        best_time = clean_text(activity.get("best_time"))

        details = []
        if location:
            details.append(f"around {location}")
        if best_time:
            details.append(f"best in the {best_time}")

        sentence = name
        if details:
            sentence += " works well " + ", ".join(details)

        lines.append(ensure_sentence(sentence))

    return "\n\n".join([line for line in lines if line])


def is_generic_trip_section_title(title, label, location=None):
    title_norm = normalize_compare_text(title)
    label_norm = normalize_compare_text(label)
    location_norm = normalize_compare_text(location)

    if not title_norm:
        return False

    generic_titles = {
        label_norm,
        f"{label_norm} in {location_norm}".strip(),
        f"day 2 {label_norm}".strip(),
        f"day 3 {label_norm}".strip(),
        "final morning",
        "wrap up and explore",
    }

    generic_titles = {item for item in generic_titles if item}

    if title_norm in generic_titles:
        return True

    if label_norm and title_norm.startswith(f"{label_norm} in "):
        return True

    return False


def format_trip_activity_items(activities):
    cleaned = [clean_text(activity).strip(" .") for activity in (activities or []) if clean_text(activity)]
    if not cleaned:
        return ""

    return natural_join(cleaned)


def render_trip_activity_slot(label, value, day_location=None):
    title = clean_text(value.get("title"))
    activities = value.get("activities") or []
    activity_text = format_trip_activity_items(activities)

    if title and not is_generic_trip_section_title(title, label, day_location):
        if activity_text:
            return ensure_sentence(f"{title}. {activity_text}")
        return ensure_sentence(title)

    if not activity_text:
        return ensure_sentence(title) if title else None

    label_lower = (label or "").lower()
    slot_leads = {
        "morning": "Ease into the day with",
        "afternoon": "Spend the afternoon with",
        "evening": "Keep the evening for",
    }

    lead = slot_leads.get(label_lower, "Plan time for")

    if activity_text_reads_like_sentence(activity_text, lead):
        return ensure_sentence(activity_text)

    return ensure_sentence(f"{lead} {activity_text}")


def render_trip_restaurant_slot(label, value):
    name = clean_text(value.get("restaurant_name"))
    if not name:
        return None

    location = trim_leading_name(name, value.get("location") or "the selected area")
    food_type = format_food_type(value.get("food_type"))

    label_lower = (label or "").lower()
    if label_lower == "lunch":
        sentence = f"Head to {name}"
    elif label_lower == "dinner":
        sentence = f"For dinner, head to {name}"
    else:
        sentence = name

    if location:
        sentence += f" in {location}"

    if food_type:
        sentence += f" for {food_type}"

    return ensure_sentence(sentence)


def render_trip_hotel_slot(value):
    name = clean_text(value.get("hotel_name"))
    if not name:
        return None

    location = trim_leading_name(name, value.get("address") or "the selected area")
    price = clean_text(value.get("price_per_night"))

    sentence = f"Stay at {name}"
    if location:
        sentence += f" in {location}"
    if price:
        sentence += f", with rates around {price}"

    return ensure_sentence(sentence)


def render_trip_plan_slot(key, value):
    label = key.replace("_", " ").title()

    if isinstance(value, dict):
        note = clean_text(value.get("note"))
        if note and key == "stay":
            return f"{label}\n{ensure_sentence(note)}"

        if value.get("hotel_name"):
            sentence = render_trip_hotel_slot(value)
            return f"{label}\n{sentence}" if sentence else None

        if value.get("restaurant_name"):
            sentence = render_trip_restaurant_slot(label, value)
            return f"{label}\n{sentence}" if sentence else None

        if value.get("title") and value.get("activities"):
            return None

    if isinstance(value, str) and value.strip():
        return f"{label}\n{ensure_sentence(value)}"

    return None


def render_trip_plan_text(trip_plan, intent=None, prefix=None):
    parts = []

    title = clean_text(trip_plan.get("title", "Trip Plan"))
    if prefix:
        parts.append(format_trip_prefix(prefix))

    parts.append(title)
    intro = build_trip_fallback_intro(intent or {}, trip_plan)
    if intro and not prefix:
        parts.append(intro)

    for day in trip_plan.get("days", []):
        day_number = day.get("day")
        location = display_city(day.get("location"))
        day_label = f"Day {day_number}"

        if location:
            parts.append(f"{day_label}\n{location}")
        else:
            parts.append(f"{day_label}")

        flow = day.get("flow", {})
        for key, value in flow.items():
            label = key.replace("_", " ").title()

            if isinstance(value, dict) and value.get("title") and value.get("activities"):
                line = render_trip_activity_slot(label, value, location)
                if line:
                    parts.append(f"{label}\n{line}")
                continue

            line = render_trip_plan_slot(key, value)
            if line:
                parts.append(line)

    return "\n\n".join(parts).strip()


def trip_slot_has_renderable_content(key, value):
    if isinstance(value, str):
        return bool(clean_text(value))

    if not isinstance(value, dict):
        return False

    if key == "stay":
        return bool(clean_text(value.get("hotel_name")) or clean_text(value.get("note")))

    if key in {"lunch", "dinner"}:
        return bool(clean_text(value.get("restaurant_name")))

    if value.get("title") and value.get("activities"):
        activities = value.get("activities") or []
        return any(clean_text(activity) for activity in activities)

    return bool(
        clean_text(value.get("title"))
        or clean_text(value.get("note"))
        or clean_text(value.get("description"))
    )


def expected_trip_slot_labels(day):
    flow = day.get("flow", {}) if isinstance(day, dict) else {}
    if not isinstance(flow, dict):
        return []

    labels = []
    for key, value in flow.items():
        if trip_slot_has_renderable_content(key, value):
            labels.append(key.replace("_", " ").title())

    return labels


def split_reply_into_day_sections(reply):
    reply = reply or ""
    matches = list(re.finditer(r"\bDay\s+(\d+)\b", reply, re.IGNORECASE))
    sections = {}

    for index, match in enumerate(matches):
        day_number = match.group(1)
        next_start = matches[index + 1].start() if index + 1 < len(matches) else len(reply)
        sections[str(day_number)] = reply[match.start():next_start]

    return sections


def trip_plan_reply_is_complete(reply, trip_plan):
    if not trip_plan:
        return True

    days = trip_plan.get("days", [])
    if not days:
        return True

    reply = reply or ""
    day_sections = split_reply_into_day_sections(reply)

    for day in days:
        day_number = day.get("day")
        if day_number is None:
            continue

        day_key = str(day_number)
        day_section = day_sections.get(day_key, "")

        if not day_section:
            return False

        for label in expected_trip_slot_labels(day):
            if not re.search(rf"\b{re.escape(label)}\b", day_section, re.IGNORECASE):
                return False

    return True


def build_guidance_reply(intent, conversation_notes, diagnostics=None):
    diagnostics = diagnostics or {}
    guidance = diagnostics.get("guidance") or {}
    fallback_reply = (guidance.get("fallback_reply") or "").strip()

    if conversation_notes.get("looks_like_meta_chat"):
        return build_meta_chat_reply()

    if conversation_notes.get("looks_like_small_talk"):
        return build_small_talk_reply((conversation_notes or {}).get("message_text", ""))

    if fallback_reply:
        return fallback_reply

    requested_categories = {str(item).strip().lower() for item in (intent.get("requested_categories") or []) if str(item).strip()}
    wants_trip_plan = bool(intent.get("wants_trip_plan"))
    requires_stay = bool(intent.get("requires_stay"))

    if wants_trip_plan:
        return "I can plan that properly. Tell me the city or route, how many days you have, and the kind of vibe you want, and I'll turn it into a real itinerary."

    if requires_stay or requested_categories.intersection({"hotel", "hotels", "stay", "stays", "accommodation"}):
        return "I can help with stays. Tell me the city and what kind of place you want, like budget, seaside, romantic, or family-friendly."

    if requested_categories.intersection({"restaurant", "restaurants", "food", "dining"}):
        return "I can help with food spots. Tell me the city and the mood you want, like seafood by the sea, date night, local breakfast, or budget bites."

    if requested_categories.intersection({"activity", "activities", "place", "places", "attraction", "attractions"}):
        return "I can help with places to visit. Tell me the city and the kind of day you want, like beach, old town, quiet, nature, or nightlife."

    return "I can help with a hotel, restaurant, place to visit, or a full trip plan. Tell me a city, budget, or the kind of vibe you want, and I'll narrow it down properly."


def handle_fallback(hotels, restaurants, activities, trip_plan, prefix, intent=None, conversation_notes=None, diagnostics=None):
    if conversation_notes and conversation_notes.get("should_hold_recommendation_results"):
        return jsonify({"reply": build_guidance_reply(intent or {}, conversation_notes, diagnostics)}), 200

    if trip_plan:
        return jsonify({"reply": render_trip_plan_text(trip_plan, intent, prefix)}), 200

    if hotels:
        return jsonify({"reply": render_hotel_fallback_reply(intent or {}, hotels)}), 200

    if restaurants:
        return jsonify({"reply": render_restaurant_fallback_reply(intent or {}, restaurants)}), 200

    if activities:
        return jsonify({"reply": render_activity_fallback_reply(intent or {}, activities)}), 200

    return jsonify({"reply": build_no_match_reply(intent or {})}), 200


def generate_reply(contents, trip_plan=None):
    response = client.models.generate_content(
        model=GEMINI_MODEL,
        contents=contents,
        config=types.GenerateContentConfig(
            temperature=0.58,
            max_output_tokens=1400,
            system_instruction="""
You are Yalla Nemshi, a warm, friendly Lebanon travel planner.

STYLE:
- Sound human, helpful, and natural
- Sound like a smart local assistant
- Be clear and pleasant, not robotic
- Keep the reply polished but easy to read
- Write like you are genuinely talking to a traveler, not filling a template
- Use natural transitions and short warm observations when useful
- Vary your openings and sentence rhythm so replies do not sound repetitive
- Lightly mirror the user's tone using conversation_notes.response_tone, but keep it professional and helpful
- Prefer concrete travel language over generic praise
- Avoid overusing words like wonderful, lovely, delightful, perfect, beautiful, and absolutely
- Keep the writing confident and human, not dramatic or salesy

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
18. If the user doesnt make any requests or is just trolling or is try to have a conversation reply and match their vibe.
19. If the user doesnt have anything specific in mind help guide them.
20. If the user is vague, do not sound abrupt or interrogative. Gently guide them.
21. Ask at most one clear follow-up question unless the user is very unclear.
22. If no city is given but the vibe or goal is clear, you may proactively recommend across the available cities in the data and mention those cities naturally.
23. If no city, no vibe, and no real preference are given, offer 2 to 4 natural directions the user can choose from, such as seaside, romantic dinner, budget stay, mountain escape, old-town walk, or family plan, based only on the data you have.
24. Do not say phrases like "based on the structured data" or "from the provided data".
25. Do not dump raw attributes mechanically. Turn them into natural travel language.
26. When mentioning why a place fits, connect it to the user's mood, occasion, or likely goal.
27. If the user is only greeting you, greet them back naturally and help them get started in one short warm sentence.
28. Avoid sounding pushy, overexcited, or overly formal.
29. Avoid repeating the exact same closing line every time.
30. When the user does not know the city, act like a helpful planner who can narrow things down, not like a form asking for missing fields.
31. For vague or guidance-only replies, do not use bullet lists. Keep it to 2 to 4 natural sentences.
32. If the available places do not truly match a specific cuisine, category, or city request, say that clearly and do not force weak alternatives as if they match.
33. If you offer fallback alternatives in another city or another style, frame them as optional next steps, not as direct answers to the original request.
34. If you already mention the trip in the opening sentence, do not repeat the same title again as a standalone line.
35. Avoid repeating nearly the same idea in two consecutive paragraphs.
36. Keep the opening to one short paragraph before the actual recommendation or itinerary.
37. If the reply opens with a sentence like "Here is your 2-day trip in Byblos", do not repeat the same trip title again on its own line.
38. If a trip day is missing a dinner, stay, or another slot in the data, simply leave that slot out instead of inventing one.
39. When a place location starts with the same place name, avoid repeating the name twice in the same sentence.
40. If intent.follow_up_context.is_follow_up is true, treat the reply as a continuation of the same request. Do not reset the conversation or ask the user to repeat details that are already present unless the new request is still ambiguous after the carried context.

FOR TRIP PLANS:
- Structure the answer clearly by Day 1, Day 2, Day 3
- For each day, mention morning, lunch, afternoon, evening, and stay/dinner when available
- Put each trip slot on its own separate labeled line. Do not combine Lunch, Afternoon, Evening, Dinner, or Stay inside the Morning paragraph.
- Use the activity blocks to make the trip feel rich and realistic
- Mention why a hotel or restaurant fits when useful
- Make the route feel logical and smooth
- Write the itinerary like polished website travel copy, not like raw database output
- Keep the tone warm and natural, with short human-friendly descriptions for each part of the day
- Avoid sounding mechanical or repetitive
- Keep cuisine/common category words natural in sentences: write "seafood" and "mid-range" lowercase unless they start a sentence; keep country cuisines like Lebanese, French, Japanese capitalized.
- Keep the intro short: one concise setup paragraph is enough before Day 1
- Do not repeat the city name in every sentence when the context is already clear
- Vary verbs across the plan instead of starting every section with the same wording
- Do not turn each slot into a long promotional paragraph
- Do not add a second summary paragraph after the final day
- Keep each slot readable in one or two compact sentences
- Avoid filler lines like "It sounds like a perfect getaway" unless they genuinely add something useful

FOR NORMAL RECOMMENDATIONS:
- Mention up to 3 strong options
- Explicitly name the options you are recommending
- Briefly explain why each one fits
- End with a helpful follow-up suggestion
- If there is no city but the request is clear, spread the suggestions across the strongest matching cities instead of clustering them unnaturally in one place

WHEN THE USER HAS NO SPECIFIC CITY:
- If the request still has a clear mood or goal, take initiative and suggest the strongest fits from the available cities.
- Mention the city naturally inside the recommendation, so the user feels guided.
- If the request is truly broad, first offer a few human-sounding directions they can choose from, then add one simple follow-up question.
- Example tone: "If you are open on the city, I can point you toward a quiet seaside day, a lively Beirut dinner, or a relaxed old-town stay."

WHEN THE USER IS JUST CHATTING OR TESTING YOU:
- Reply casually and naturally.
- Keep it short.
- Then gently steer back toward travel help if appropriate.
"""
        )
    )

    return polish_generated_reply(response.text or "", trip_plan)


@app.route("/chat", methods=["POST"])
def chat():
    hotels = []
    restaurants = []
    activities = []
    trip_plan = None
    diagnostics = {}

    try:
        data = request.get_json(silent=True) or {}

        message = (data.get("message") or "").strip()
        history = data.get("history") or []
        intent = data.get("intent") or {}
        hotels = data.get("hotels") or []
        restaurants = data.get("restaurants") or []
        activities = data.get("activities") or []
        trip_plan = data.get("trip_plan")
        diagnostics = compact_diagnostics(data.get("diagnostics") or {})
        session_context = compact_session_context(data.get("session_context") or {})

        if not message:
            return jsonify({"reply": "Tell me what kind of trip you're looking for."}), 400

        recent_history = history[-5:]
        raw_conversation_notes = build_conversation_notes(
            message,
            intent,
            hotels,
            restaurants,
            activities,
            trip_plan,
            diagnostics,
        )
        prompt_hotels, prompt_restaurants, prompt_activities, prompt_trip_plan = sanitize_recommendation_payload(
            raw_conversation_notes,
            hotels,
            restaurants,
            activities,
            trip_plan,
        )
        prompt_data = build_prompt_data(
            intent,
            prompt_hotels,
            prompt_restaurants,
            prompt_activities,
            prompt_trip_plan,
            diagnostics,
            session_context,
        )
        prompt_data["conversation_notes"] = build_conversation_notes(
            message,
            intent,
            prompt_hotels,
            prompt_restaurants,
            prompt_activities,
            prompt_trip_plan,
            diagnostics,
        )

        contents = format_history(recent_history)

        user_prompt = f"""
Use this structured travel data to answer the user naturally.

DATA:
{json.dumps(prompt_data, ensure_ascii=False)}

USER REQUEST:
{message}

RESPONSE BEHAVIOR:
- If conversation_notes.should_guide_user_gently is true, guide the user like a human planner instead of sounding like a form.
- If conversation_notes.is_open_ended_request is true, offer a few natural directions before asking one light follow-up.
- If conversation_notes.is_follow_up is true, continue from the carried context naturally instead of restarting from zero.
- If conversation_notes.has_specific_city is false but there are good matches, it is okay to recommend across cities naturally.
- If conversation_notes.should_hold_recommendation_results is true, do not invent or force specific place recommendations. Guide the user first.
- If diagnostics.confidence.overall is "needs_guidance" or "low", prefer a helpful narrowing response over overconfident recommendations.
- If a specific cuisine or category request does not have real matching places in the provided results, say so plainly and keep the answer aligned with that limitation.
"""

        contents.append(
            types.Content(
                role="user",
                parts=[types.Part.from_text(text=user_prompt)]
            )
        )

        reply = generate_reply(contents, prompt_trip_plan)

        incomplete_endings = ("and", "then", "with", "to", "for", "in", "at", "of", "Day", "Morning", "Afternoon", "Evening")
        should_retry_for_completeness = bool(prompt_trip_plan) or not prompt_data["conversation_notes"].get("should_hold_recommendation_results")
        if should_retry_for_completeness and (
            len(reply) < 80
            or reply.endswith(incomplete_endings)
            or not reply.endswith((".", "!", "?"))
        ):
            contents.append(
                types.Content(
                    role="user",
                    parts=[types.Part.from_text(
                        text="Rewrite the full answer completely. Make sure every day is complete, the response ends properly, the opening does not repeat the trip title or the same introduction twice, and the wording stays natural and concise."
                    )]
                )
            )
            retry_reply = generate_reply(contents, prompt_trip_plan)
            if retry_reply:
                reply = retry_reply

        if prompt_trip_plan and reply and not trip_plan_reply_is_complete(reply, prompt_trip_plan):
            reply = render_trip_plan_text(
                prompt_trip_plan,
                intent=intent,
                prefix="Here is a complete itinerary built from your matched places"
            )

        if not reply:
            return handle_fallback(
                prompt_hotels,
                prompt_restaurants,
                prompt_activities,
                prompt_trip_plan,
                "Here's a clear version I can still put together for you:",
                intent,
                prompt_data["conversation_notes"],
                diagnostics,
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
                "Here's a clear version I can still put together for you:",
                intent if 'intent' in locals() else {},
                raw_conversation_notes if 'raw_conversation_notes' in locals() else None,
                diagnostics if 'diagnostics' in locals() else None,
            )

        return jsonify({
            "reply": "Something went wrong on our side. Please try again."
        }), 500


if __name__ == "__main__":
    app.run(debug=True, port=5000)
