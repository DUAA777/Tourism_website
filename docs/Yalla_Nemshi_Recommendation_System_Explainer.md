# Yalla Nemshi Recommendation System Explainer

## Purpose of this document

This document explains how the current Yalla Nemshi recommendation system works so that I can confidently explain it in a meeting, demo, or viva. It focuses on the real implementation in the codebase, not on an idealized version.

The system is best described as a hybrid tourism recommendation system:

- Content-based: it matches users to places using real place attributes such as city, food type, vibe tags, price, beach distance, rating, and descriptive text.
- Knowledge-based: it applies travel logic such as duration, overnight stay requirements, city scope, multi-city routing, lunch and dinner placement, and day-by-day itinerary assembly.
- Slight sentiment-aware behavior: it does not run full sentiment analysis, but it captures emotional intent through mapped concepts such as romantic, relaxing, lively, anniversary, couple, quiet, sunset, scenic, and similar soft user preferences.

The recommendation decision is primarily made in PHP inside the recommendation service. The Python layer is mainly responsible for turning structured results into more natural wording. The frontend is responsible for presenting structured cards and trip itineraries cleanly.

## Main files involved

- `app/Services/RecommendationService.php`
- `app/Http/Controllers/ChatbotController.php`
- `resources/python/chatbot_api.py`
- `resources/views/chatbot_fullscreen.blade.php`
- `app/Models/Hotel.php`
- `app/Models/Restaurant.php`
- `app/Models/Activity.php`
- `tests/Feature/RecommendationServiceTest.php`
- `tests/Feature/RecommendationPromptEvaluationTest.php`
- `tests/Feature/ChatbotControllerTest.php`

## High-level architecture

The system follows this pipeline:

1. The user types a request in the chatbot UI.
2. Laravel receives the message in `ChatbotController`.
3. Laravel calls `RecommendationService::buildResponseData()`.
4. The recommendation service:
   - extracts user intent
   - selects which categories are needed
   - retrieves candidate hotels, restaurants, and activities
   - scores and ranks them
   - optionally builds a trip plan
   - returns structured recommendation data plus diagnostics
5. Laravel sends that structured data to the local Python service.
6. The Python service asks Gemini to phrase the answer naturally using only the provided data.
7. Laravel returns both:
   - a natural-language reply
   - a structured payload for the frontend
8. The frontend renders:
   - normal recommendation cards for hotels and restaurants
   - an inline trip itinerary for trip requests
   - follow-up cards under trip plans for the actual stay and restaurant stops used in the itinerary

## Why this is not just a chatbot

The system is not simply asking an LLM to invent tourism advice. The LLM is not the source of truth for recommendations. The real recommendation logic is deterministic and code-driven.

The LLM only receives:

- parsed intent
- top hotels
- top restaurants
- top activities
- trip plan when one exists

This means the LLM is used for wording, not for deciding the underlying recommendations. That is an important design choice because it makes the system easier to explain, test, and debug.

## Step 1: Intent extraction

Intent extraction happens in `RecommendationService::extractIntent()`.

The first thing the system does is normalize the text:

- lowercasing
- removing punctuation
- collapsing extra spaces

Then it extracts several types of intent signals.

### 1. Cities

Cities are extracted using `extractMentionedCities()` and a city alias map. For example:

- Byblos and Jbeil map to the same canonical city
- Tyre and Sur map to the same canonical city
- Saida and Sidon map to the same canonical city

This gives the system:

- `city`
- `start_city`
- `end_city`
- `mentioned_cities`

These are later used for retrieval, ranking, and multi-city trip planning.

### 2. Vibe and soft sentiment

The system uses concept maps to detect softer travel intent. This is the “slight sentiment” layer.

Examples:

- `romantic` covers terms like intimate, date, anniversary, couple, special, lovely
- `relaxing` covers calm, quiet, peaceful, unwind, escape
- `lively` covers energetic, vibrant, celebrate, exciting
- `cozy` covers cosy, warm, comfortable, homey
- `beach` covers seaside, coastal, waterfront, sea, shore
- `sunset` covers sundown and golden hour
- `hidden_gem` covers hidden gem, hidden gems, offbeat, less touristy

This is not machine-learned sentiment analysis. It is a controlled concept-matching layer that turns emotional wording into structured recommendation features.

### 3. Food preferences

Food is extracted from a food concept map. For example:

- seafood
- lebanese
- italian
- japanese
- grill
- cafe
- dessert

This helps restaurant ranking and also trip meal selection.

### 4. Occasion and audience

The system separately tracks:

- occasion tags
- audience tags

Examples:

- `date`
- `dinner`
- `anniversary`
- `friends`
- `family`
- `business`
- `couple`

This matters because a “romantic dinner”, “family lunch”, and “friends night out” should not return the same results even in the same city.

### 5. Activity type and time preference

Activity-related user requests are mapped into:

- activity types such as beach, walking, scenic, cultural, historical, nightlife, city, food, hidden_gem
- time preferences such as morning, afternoon, evening, sunset, breakfast, lunch, dinner

This helps both direct activity recommendations and trip-plan slot filling.

### 6. Budget

Budget is extracted in two forms:

- qualitative budget: budget, mid_range, premium, luxury
- numeric maximum budget such as `$80` or `under $120`

This allows the system to combine soft budget preference with explicit ceilings.

### 7. Day count and trip detection

Trip length is detected from phrases such as:

- `2 days`
- `3 nights`
- `weekend`
- `long weekend`
- `day trip`

The system also distinguishes between:

- normal recommendations
- trip planning requests

Important recent improvement:

- the system no longer treats the word `plan` by itself as a trip trigger
- this prevents errors like `plan a romantic dinner in Beirut` turning into a 2-day itinerary

### 8. Requested categories

After extracting intent, the system decides which categories should be included:

- hotels
- restaurants
- activities
- trip_plan

There is also a `requires_stay` field. This is important because:

- a one-day trip should not force a hotel stay
- a multi-day trip usually should
- a direct stay/hotel request definitely should

## Step 2: Candidate retrieval

After intent extraction, the system decides what data to fetch.

### Hotels

Hotel retrieval is handled through `fetchScopedHotels()` and hotel city matching.

Important consistency improvement:

- hotel city scoping no longer checks only the address
- it now also looks across hotel name, nearby landmark, description, and search text

This matters because real scraped or imported hotel records do not always place the city inside the address field.

### Restaurants

Restaurants are retrieved with fuzzy city scope over the `location` field.

### Activities

Activities are retrieved with exact city scope over the `city` field.

### Multi-city coverage prioritization

For trip planning with multiple cities, the system now prioritizes city coverage when selecting ranked results. This improves the likelihood that:

- both cities are represented
- the planner has data to work with for each city

This prioritization is applied separately to:

- hotels
- restaurants
- activities

## Step 3: Scoring and ranking

Each entity type has its own evaluation function.

### Hotel scoring

Hotels are scored in `evaluateHotel()`.

The main components are:

- city match
- vibe overlap
- audience overlap
- budget match
- beach distance preference
- semantic overlap
- keyword overlap
- rating
- review count

Approximate hotel weighting:

- city match: very important
- vibe match: strong
- audience fit: meaningful
- budget fit: meaningful
- distance to beach: important when beach intent exists
- semantic and keyword overlap: supporting evidence
- rating and reviews: quality confidence signals

This makes hotel ranking good for prompts like:

- budget seaside stay in Batroun
- romantic hotel in Beirut
- family resort near the beach

### Restaurant scoring

Restaurants are scored in `evaluateRestaurant()`.

The main components are:

- city match
- food preference match
- vibe match
- occasion match
- budget match
- semantic overlap
- keyword overlap
- rating

Restaurant ranking is especially strong for prompts like:

- romantic dinner in Beirut
- quiet seafood lunch in Batroun
- casual cafe in Byblos

### Activity scoring

Activities are scored in `evaluateActivity()`.

The main components are:

- city match
- activity type match
- vibe match
- occasion match
- time preference match
- budget match
- semantic overlap
- keyword overlap

This makes activities more context-aware than a plain keyword search.

## Content-based, knowledge-based, and slight sentiment in one system

The current system works well academically because these three ideas are all present.

### Content-based part

The system uses place attributes and content:

- vibe tags
- audience tags
- occasion tags
- food type
- category
- best time
- beach distance
- rating
- review count
- price
- descriptive text
- search text

### Knowledge-based part

The system also uses travel rules:

- city must match
- duration affects day count
- overnight trips may require a stay
- one-day trips should not force a hotel
- multi-city trips should use city-appropriate items
- sunset activities fit evening better
- lunch and dinner are selected differently from general activities

### Slight sentiment part

The system interprets emotional wording without doing full sentiment analysis:

- romantic
- relaxing
- lively
- cozy
- special
- anniversary
- quiet
- hidden gem
- scenic

This lets soft user intent influence ranking in a controlled and explainable way.

## Step 4: Diversification

The system does not only sort by score and stop. It also diversifies ranked items.

Examples:

- hotels are diversified partly by budget tier
- restaurants are diversified partly by primary food group
- activities are diversified partly by category

This reduces repetition and helps the results look more useful in the interface.

## Step 5: Trip planning

Trip planning happens in `buildTripPlan()`.

There are two main branches:

- single-city trip planning
- multi-city trip planning

### Single-city trip planning

Each day can contain:

- morning
- lunch
- afternoon
- evening
- dinner
- stay

Rules:

- one-day trips do not force a hotel stay unless the user explicitly asked for one
- multi-day trips include a stay on overnight days
- the last day usually has no overnight stay because it is the departure day

### Multi-city trip planning

Multi-city planning uses:

- `start_city`
- `end_city`
- a transition day

Important logic:

- city-specific activities are filtered per city
- restaurants are selected from the current city where possible
- overnight stays prefer the correct city
- the system now avoids inserting the wrong city’s hotel just to fill a stay slot

This is a safer knowledge-based rule because omission is better than a wrong stay.

## Step 6: Transformation into API-friendly recommendation objects

Before returning results, the service transforms raw models into structured arrays.

For example:

- hotels get fields such as `hotel_name`, `city`, `address`, `price_per_night`, `budget_tier`, `top_reasons`
- restaurants get `restaurant_name`, `city`, `location`, `food_type`, `price_tier`, `top_reasons`
- activities get `name`, `city`, `category`, `best_time`, `top_reasons`

This is important because the controller, Python service, and frontend all rely on this transformed format.

## Step 7: Diagnostics and explainability

The service also returns diagnostics. This is useful for debugging, evaluation, and explanation.

Diagnostics include:

- summary chips
- intent overview
- result counts
- top match digests

This makes the system easier to defend academically because the output is not a black box.

## Step 8: Controller orchestration

`ChatbotController` acts as the orchestrator.

Its responsibilities are:

- validate input
- resolve or create chat sessions
- save chat messages
- call the recommendation service
- build a structured payload
- call the Python service for natural language generation
- fall back safely if Python fails
- return both text and structured data to the frontend

This separation is good architecture because:

- recommendation logic stays in the service
- orchestration stays in the controller
- wording stays in Python
- presentation stays in the frontend

## Step 9: Structured payload sent to the frontend

The controller builds a frontend-friendly structure that includes:

- summary chips
- hotel sections
- restaurant sections
- activity sections
- decorated trip plan with URLs

Even if the natural text is imperfect, the frontend still has grounded structured data to render.

This makes the system more reliable than a plain text chatbot.

## Step 10: Python phrasing layer

The Python service in `chatbot_api.py` does not invent the recommendation set. It takes structured data and asks Gemini to phrase it naturally.

Key prompt constraints:

- use only the provided data
- do not invent hotels, restaurants, activities, ratings, prices, or addresses
- use the exact names provided
- if a trip plan includes a stay, keep it in the answer
- use the trip title only once
- do not add a second compact recap after the itinerary

It also has guardrails:

- retries if the first answer looks incomplete
- checks whether all trip days are present
- falls back to deterministic rendering if needed

This is important because it keeps the UI dependable even when the model output varies.

## Step 11: Frontend rendering

The frontend in `chatbot_fullscreen.blade.php` renders two different styles of result.

### Normal recommendation requests

These show:

- assistant text
- hotel cards
- restaurant cards

Activities are currently kept mostly in text rather than boxed cards.

### Trip requests

These show:

- an inline itinerary
- parsed day structure
- slot labels like Morning, Lunch, Afternoon, Evening, Dinner, Stay
- actual trip support cards underneath for:
  - the stay used in the plan
  - the restaurants used in the plan

This is a strong UX choice because the user gets both:

- human-readable narrative
- actionable cards with links

## Example walkthrough

Consider the prompt:

`Plan a 2 day seaside trip in Batroun with sunset and seafood`

### Intent extraction result

The system detects:

- city: Batroun
- trip request: yes
- day count: 2
- food preference: seafood
- vibes: beach, sunset
- categories: hotels, restaurants, activities, trip_plan
- requires stay: yes

### Retrieval

The system retrieves:

- Batroun hotels
- Batroun restaurants
- Batroun activities

### Ranking

Hotels that match Batroun, seaside context, beach distance, and quality score better.

Restaurants that match:

- Batroun
- seafood
- beach or sunset vibe
- strong ratings

Activities that match:

- Batroun
- beach
- sunset
- scenic or walking patterns

### Trip planning

Day 1 will usually include:

- morning activity
- lunch restaurant
- afternoon activity
- evening activity
- dinner restaurant
- overnight stay

Day 2 will include:

- morning activity
- lunch
- afternoon wrap-up

Usually no stay on day 2 because there is no second overnight.

### Controller and Python

The structured plan is passed to Python, which turns it into a more natural travel-style response. The frontend then renders the inline trip and the actual stay/restaurant cards used by the plan.

## What to say in a meeting

If I need to explain the system briefly, a strong answer is:

“Yalla Nemshi uses a hybrid recommendation approach. The core logic is deterministic and built in PHP. It first extracts structured intent from the user’s message, including city, duration, budget, vibe, food preference, and occasion. It then ranks hotels, restaurants, and activities using content-based signals like tags, price, rating, and text, while also applying knowledge-based rules such as city constraints, overnight stay logic, and multi-city trip flow. A Python layer then uses Gemini only to turn those structured recommendations into natural language. The frontend renders both the text and structured cards, so the system stays grounded and usable.”

## Strengths of the current design

- Hybrid approach is easy to explain academically
- Recommendation decisions are mostly deterministic
- The LLM is not the source of truth
- Structured payload supports reliable UI rendering
- Multi-day and multi-city planning are supported
- The system is explainable and testable

## Limitations to acknowledge honestly

- It is still rule-based rather than embedding-based semantic retrieval
- Quality depends on dataset coverage and cleanliness
- Some city fields in source data are inconsistent, so fallback logic matters
- The system is “slight sentiment-aware” through mapped concepts, not full sentiment analysis
- The natural-language layer can still vary in phrasing, even though guardrails reduce this

## Final understanding

The current recommendation system is not a generic chatbot pretending to recommend tourism content. It is a structured tourism recommendation engine with a chatbot interface on top.

Its main intelligence comes from:

- controlled intent extraction
- category-aware retrieval
- weighted ranking
- knowledge-based trip assembly
- careful controller orchestration
- constrained LLM phrasing

That is the correct way to understand the current implementation.
