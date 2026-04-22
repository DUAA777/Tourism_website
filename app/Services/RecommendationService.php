<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Hotel;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RecommendationService
{
    public function buildResponseData(string $message, ?array $sessionContext = null): array
    {
        $intent = $this->extractIntent($message);
        [$intent, $intentSourceMessage] = $this->applySessionContextToIntent($message, $intent, $sessionContext);
        $normalizedMessage = $this->normalizeText($intentSourceMessage);

        if (($intent['should_hold_results'] ?? false) === true) {
            $diagnostics = $this->buildDiagnostics($intent, [], [], [], null);

            return [
                'intent' => $intent,
                'hotels' => [],
                'restaurants' => [],
                'activities' => [],
                'trip_plan' => null,
                'diagnostics' => $diagnostics,
            ];
        }

        $needsHotels = $this->shouldIncludeCategory($intent, 'hotels');
        $needsRestaurants = $this->shouldIncludeCategory($intent, 'restaurants');
        $needsActivities = $this->shouldIncludeCategory($intent, 'activities');

        $rankedHotels = collect();
        $rankedRestaurants = collect();
        $rankedActivities = collect();

        if ($needsHotels) {
            $hotels = $this->fetchScopedHotels($intent['mentioned_cities']);

            $rankedHotels = $hotels
                ->map(function ($hotel) use ($intent, $normalizedMessage) {
                    $evaluation = $this->evaluateHotel($hotel, $intent, $normalizedMessage);

                    return [
                        'item' => $hotel,
                        'score' => $evaluation['score'],
                        'reasons' => $evaluation['reasons'],
                    ];
                })
                ->sortByDesc('score')
                ->values();

            $rankedHotels = $this->deduplicateRankedItems(
                $rankedHotels,
                fn ($row) => $this->buildHotelIdentityKey($row['item'])
            );

            $rankedHotels = $this->diversifyRankedItems(
                $rankedHotels,
                fn ($row) => $this->normalizeHotelBudgetTier($row['item']->price_per_night) ?? $this->normalizeText($row['item']->address ?? ''),
                ($intent['wants_trip_plan'] && count($intent['mentioned_cities']) > 1) ? 8 : ($intent['wants_trip_plan'] ? 5 : 4)
            );

            if ($intent['wants_trip_plan'] && count($intent['mentioned_cities']) > 1) {
                $rankedHotels = $this->prioritizeTripCityCoverage(
                    $rankedHotels,
                    $intent['mentioned_cities'],
                    fn ($hotel, $city) => in_array($city, $this->hotelCityCandidates($hotel), true),
                    4
                );
            }

            if (!empty($intent['excluded_cities'])) {
                $rankedHotels = $this->filterRankedItemsExcludingCities($rankedHotels, 'hotels', $intent['excluded_cities']);
            }

            $rankedHotels = $this->filterWeakRankedItems($rankedHotels, $intent, 'hotels');

            if ($intent['wants_trip_plan'] && count($intent['mentioned_cities']) > 1) {
                $rankedHotels = $this->prioritizeTripCityCoverage(
                    $rankedHotels,
                    $intent['mentioned_cities'],
                    fn ($hotel, $city) => in_array($city, $this->hotelCityCandidates($hotel), true),
                    4
                );
            }

            if ($this->shouldPreferCityDiversity($intent)) {
                $rankedHotels = $this->diversifyRankedItems(
                    $rankedHotels,
                    fn ($row) => $this->hotelCityCandidates($row['item'])[0] ?? '__hotel_city__',
                    6
                );
            }
        }

        if ($needsRestaurants) {
            $restaurantsQuery = Restaurant::query();
            $this->applyRestaurantCityScope($restaurantsQuery, $intent['mentioned_cities']);

            $rankedRestaurants = $restaurantsQuery->limit(150)->get()
                ->map(function ($restaurant) use ($intent, $normalizedMessage) {
                    $evaluation = $this->evaluateRestaurant($restaurant, $intent, $normalizedMessage);

                    return [
                        'item' => $restaurant,
                        'score' => $evaluation['score'],
                        'reasons' => $evaluation['reasons'],
                    ];
                })
                ->sortByDesc('score')
                ->values();

            $rankedRestaurants = $this->deduplicateRankedItems(
                $rankedRestaurants,
                fn ($row) => $this->buildRestaurantIdentityKey($row['item'])
            );

            $rankedRestaurants = $this->diversifyRankedItems(
                $rankedRestaurants,
                fn ($row) => $this->primaryFoodGroup($row['item']->food_type) ?? $this->normalizeRestaurantBudgetTier($row['item']->price_tier) ?? '__restaurant__',
                ($intent['wants_trip_plan'] && count($intent['mentioned_cities']) > 1) ? 12 : ($intent['wants_trip_plan'] ? 5 : 4)
            );

            if ($intent['wants_trip_plan'] && count($intent['mentioned_cities']) > 1) {
                $rankedRestaurants = $this->prioritizeTripCityCoverage(
                    $rankedRestaurants,
                    $intent['mentioned_cities'],
                    fn ($restaurant, $city) => in_array($city, $this->restaurantCityCandidates($restaurant), true),
                    4
                );
            }

            if (!empty($intent['excluded_cities'])) {
                $rankedRestaurants = $this->filterRankedItemsExcludingCities($rankedRestaurants, 'restaurants', $intent['excluded_cities']);
            }

            $rankedRestaurants = $this->filterWeakRankedItems($rankedRestaurants, $intent, 'restaurants');
            $rankedRestaurants = $this->filterRankedItemsToExplicitCities($rankedRestaurants, 'restaurants', $intent);

            if ($intent['wants_trip_plan'] && count($intent['mentioned_cities']) > 1) {
                $rankedRestaurants = $this->prioritizeTripCityCoverage(
                    $rankedRestaurants,
                    $intent['mentioned_cities'],
                    fn ($restaurant, $city) => in_array($city, $this->restaurantCityCandidates($restaurant), true),
                    4
                );
            }

            if ($this->shouldPreferCityDiversity($intent)) {
                $rankedRestaurants = $this->diversifyRankedItems(
                    $rankedRestaurants,
                    fn ($row) => $this->restaurantCityCandidates($row['item'])[0] ?? '__restaurant_city__',
                    8
                );
            }
        }

        if ($needsActivities) {
            $activitiesQuery = Activity::query();
            $this->applyActivityCityScope($activitiesQuery, $intent['mentioned_cities']);

            $rankedActivities = $activitiesQuery->limit(150)->get()
                ->map(function ($activity) use ($intent, $normalizedMessage) {
                    $evaluation = $this->evaluateActivity($activity, $intent, $normalizedMessage);

                    return [
                        'item' => $activity,
                        'score' => $evaluation['score'],
                        'reasons' => $evaluation['reasons'],
                    ];
                })
                ->sortByDesc('score')
                ->values();

            $rankedActivities = $this->deduplicateRankedItems(
                $rankedActivities,
                fn ($row) => $this->buildActivityIdentityKey($row['item'])
            );

            $rankedActivities = $this->diversifyRankedItems(
                $rankedActivities,
                fn ($row) => $this->normalizeText($row['item']->category ?? ''),
                ($intent['wants_trip_plan'] && count($intent['mentioned_cities']) > 1) ? 16 : ($intent['wants_trip_plan'] ? 8 : 6)
            );

            if ($intent['wants_trip_plan'] && count($intent['mentioned_cities']) > 1) {
                $rankedActivities = $this->prioritizeTripCityCoverage(
                    $rankedActivities,
                    $intent['mentioned_cities'],
                    fn ($activity, $city) => in_array($city, $this->activityCityCandidates($activity), true),
                    8
                );
            }

            if (!empty($intent['excluded_cities'])) {
                $rankedActivities = $this->filterRankedItemsExcludingCities($rankedActivities, 'activities', $intent['excluded_cities']);
            }

            $rankedActivities = $this->filterWeakRankedItems($rankedActivities, $intent, 'activities');
            $rankedActivities = $this->filterRankedItemsToExplicitCities($rankedActivities, 'activities', $intent);

            if ($intent['wants_trip_plan'] && count($intent['mentioned_cities']) > 1) {
                $rankedActivities = $this->prioritizeTripCityCoverage(
                    $rankedActivities,
                    $intent['mentioned_cities'],
                    fn ($activity, $city) => in_array($city, $this->activityCityCandidates($activity), true),
                    8
                );
            }

            if ($this->shouldPreferCityDiversity($intent)) {
                $rankedActivities = $this->diversifyRankedItems(
                    $rankedActivities,
                    fn ($row) => $this->activityCityCandidates($row['item'])[0] ?? '__activity_city__',
                    10
                );
            }

            $rankedActivities = $this->applyNoCityActivityGuardrail($rankedActivities, $intent);
        }

        if (!empty($intent['excluded_cities'])) {
            $rankedHotels = $this->filterRankedItemsExcludingCities($rankedHotels, 'hotels', $intent['excluded_cities']);
            $rankedRestaurants = $this->filterRankedItemsExcludingCities($rankedRestaurants, 'restaurants', $intent['excluded_cities']);
            $rankedActivities = $this->filterRankedItemsExcludingCities($rankedActivities, 'activities', $intent['excluded_cities']);
        }

        if (
            !empty($intent['wants_trip_plan'])
            && empty($intent['mentioned_cities'])
            && empty($intent['start_city'])
            && empty($intent['end_city'])
        ) {
            $resolvedTripCity = $this->resolveImplicitTripCity($intent, $rankedHotels, $rankedRestaurants, $rankedActivities);

            if ($resolvedTripCity !== null) {
                $intent['resolved_city'] = $resolvedTripCity;
                $intent['city'] = $intent['city'] ?? $resolvedTripCity;

                $rankedHotels = $this->filterRankedItemsByCity($rankedHotels, 'hotels', $resolvedTripCity);
                $rankedRestaurants = $this->filterRankedItemsByCity($rankedRestaurants, 'restaurants', $resolvedTripCity);
                $rankedActivities = $this->filterRankedItemsByCity($rankedActivities, 'activities', $resolvedTripCity);
            } elseif (
                (int) ($intent['day_count'] ?? 0) === 1
                && empty($intent['requires_stay'])
                && !empty($intent['can_plan_without_explicit_city'])
                && ($rankedRestaurants->isNotEmpty() || $rankedActivities->isNotEmpty())
            ) {
                // A cityless one-day request can still be useful as grounded options.
                // Avoid inventing a route when the city evidence is spread across Lebanon.
                $intent['wants_trip_plan'] = false;
                $intent['requested_categories'] = array_values(array_diff(
                    (array) ($intent['requested_categories'] ?? []),
                    ['trip_plan']
                ));
                $intent['message_type'] = $this->deriveMessageType($intent);
            } else {
                $intent['city'] = null;
                $intent['resolved_city'] = null;
                $intent['should_hold_results'] = true;
                $intent['message_type'] = $this->deriveMessageType($intent);
                $diagnostics = $this->buildDiagnostics($intent, [], [], [], null);

                return [
                    'intent' => $intent,
                    'hotels' => [],
                    'restaurants' => [],
                    'activities' => [],
                    'trip_plan' => null,
                    'diagnostics' => $diagnostics,
                ];
            }
        }

        $planHotels = $rankedHotels
            ->take($intent['wants_trip_plan'] ? 6 : 3)
            ->map(fn ($row) => $this->transformHotel($row['item'], $row['score'], $row['reasons']))
            ->values()
            ->all();

        $topHotels = array_values(array_slice($planHotels, 0, $intent['wants_trip_plan'] ? 4 : 3));

        $planRestaurants = $rankedRestaurants
            ->take($intent['wants_trip_plan'] ? 8 : 3)
            ->map(fn ($row) => $this->transformRestaurant($row['item'], $row['score'], $row['reasons']))
            ->values()
            ->all();

        $topRestaurants = array_values(array_slice($planRestaurants, 0, $intent['wants_trip_plan'] ? 4 : 3));

        $planActivities = $rankedActivities
            ->take($intent['wants_trip_plan'] ? 12 : 4)
            ->map(fn ($row) => $this->transformActivity($row['item'], $row['score'], $row['reasons']))
            ->values()
            ->all();

        $topActivities = array_values(array_slice($planActivities, 0, $intent['wants_trip_plan'] ? 8 : 4));

        $tripPlan = null;

        if ($intent['wants_trip_plan']) {
            $tripPlan = $this->buildTripPlan($intent, $planHotels, $planRestaurants, $planActivities);
        }

        [$topHotels, $topRestaurants, $topActivities, $tripPlan] = $this->stabilizeRecommendationPayload(
            $topHotels,
            $topRestaurants,
            $topActivities,
            $tripPlan
        );

        $diagnostics = $this->buildDiagnostics($intent, $topHotels, $topRestaurants, $topActivities, $tripPlan);

        return [
            'intent' => $intent,
            'hotels' => $topHotels,
            'restaurants' => $topRestaurants,
            'activities' => $topActivities,
            'trip_plan' => $tripPlan,
            'diagnostics' => $diagnostics,
        ];
    }

    private function extractIntent(string $message): array
    {
        $text = $this->normalizeText($message);
        $mentionedCities = $this->extractMentionedCities($text);
        $excludedCities = $this->extractExcludedCities($text);
        if (!empty($excludedCities)) {
            $mentionedCities = array_values(array_diff($mentionedCities, $excludedCities));
        }
        $countryScope = $this->containsAnyPhrase($text, [
            'lebanon',
            'anywhere',
            'no specific city',
            'across the country',
            'around the country',
        ]);

        $city = $mentionedCities[0] ?? null;
        $startCity = $mentionedCities[0] ?? null;
        $endCity = count($mentionedCities) > 1 ? $mentionedCities[array_key_last($mentionedCities)] : null;

        $vibeTags = $this->extractConceptMatches($text, $this->vibeConceptMap());
        $foodPreferences = $this->extractConceptMatches($text, $this->foodConceptMap());
        $occasionTags = $this->extractConceptMatches($text, $this->occasionConceptMap());
        $audienceTags = $this->extractConceptMatches($text, $this->audienceConceptMap());
        $activityTypes = $this->extractConceptMatches($text, $this->activityConceptMap());
        $timePreferences = $this->extractConceptMatches($text, $this->timeConceptMap());

        $vibeTags = $this->removeNegatedConceptMatches($text, $vibeTags, $this->vibeConceptMap());
        $foodPreferences = $this->removeNegatedConceptMatches($text, $foodPreferences, $this->foodConceptMap());
        $occasionTags = $this->removeNegatedConceptMatches($text, $occasionTags, $this->occasionConceptMap());
        $activityTypes = $this->removeNegatedConceptMatches($text, $activityTypes, $this->activityConceptMap());
        $timePreferences = $this->removeNegatedConceptMatches($text, $timePreferences, $this->timeConceptMap());

        $budget = $this->extractBudgetPreference($text);
        $budgetMax = $this->extractBudgetAmount($message);
        $explicitDayCount = $this->extractDayCount($text);
        $dayCount = $explicitDayCount;
        $duration = $dayCount ? $this->durationKeyFromDayCount($dayCount) : null;

        $noStayRequested = $this->containsNoStayRequest($text);
        $noRestaurantRequested = $this->messageNegatesCategory($text, 'restaurants');
        $noActivityRequested = $this->messageNegatesCategory($text, 'activities');
        $wantsHotel = $this->containsAnyPhrase($text, ['hotel', 'hotels', 'stay', 'stays', 'room', 'rooms', 'accommodation', 'accommodations', 'resort', 'resorts', 'retreat', 'retreats', 'guesthouse', 'guesthouses', 'guest house', 'guest houses']);
        if ($noStayRequested) {
            $wantsHotel = false;
        }

        $hasDateNightDrinkCue = $this->containsAnyPhrase($text, ['drink', 'drinks', 'cocktail', 'cocktails', 'wine'])
            && in_array('date', $occasionTags, true);
        $wantsRestaurant = $this->containsAnyPhrase($text, ['restaurant', 'restaurants', 'food', 'foods', 'dinner', 'lunch', 'breakfast', 'brunch', 'cafe', 'cafes', 'coffee', 'eat', 'dining'])
            || !empty($foodPreferences)
            || !empty(array_intersect($occasionTags, ['breakfast', 'lunch', 'dinner']))
            || $hasDateNightDrinkCue;
        if ($noRestaurantRequested) {
            $wantsRestaurant = false;
        }
        $hasSpecificActivityCue = $this->containsAnyPhrase($text, [
            'activity', 'activities', 'things to do', 'visit', 'visiting',
            'explore', 'exploring', 'sight', 'sights',
            'hidden gem', 'hidden gems', 'walk', 'walking', 'stroll', 'strolling',
            'promenade', 'nightlife', 'night out', 'night-out', 'rooftop', 'bars', 'club',
        ]);
        $hasGenericActivityPlaceCue = $this->containsAnyPhrase($text, ['place', 'places', 'destination', 'destinations']);
        $strongActivityConcepts = array_intersect($activityTypes, [
            'walking',
            'nightlife',
            'hidden_gem',
            'cultural',
            'historical',
            'touristy',
            'scenic',
            'beach',
            'nature',
            'city',
        ]);
        $restaurantCompatibleActivityConcepts = array_intersect($activityTypes, [
            'walking',
            'hidden_gem',
            'cultural',
            'historical',
            'touristy',
            'scenic',
            'beach',
            'nature',
            'city',
        ]);
        $hasStandaloneActivityConcept = $wantsRestaurant
            ? !empty($restaurantCompatibleActivityConcepts)
            : !empty($strongActivityConcepts);
        $wantsActivity = $hasSpecificActivityCue
            || ($hasGenericActivityPlaceCue && !$wantsHotel && !$wantsRestaurant)
            || ($hasStandaloneActivityConcept && !($wantsHotel && !$wantsRestaurant));
        if ($noActivityRequested) {
            $wantsActivity = false;
        }
        $isRecommendationAsk = $this->containsAnyPhrase($text, ['find me', 'recommend', 'show me', 'suggest']);
        $hasPlanningVerb = $this->containsAnyPhrase($text, ['plan', 'build me', 'build', 'organize', 'schedule']);
        $hasTripKeyword = $this->containsAnyPhrase($text, [
            'trip', 'itinerary', 'day trip', 'vacation', 'holiday', 'road trip',
            'trip plan', 'travel plan', 'multi day', 'multi-day',
        ]);
        if ($dayCount === null && $hasPlanningVerb && preg_match('/\ba\s+(?:[a-z]+\s+){0,3}day\b/', $text)) {
            $dayCount = 1;
            $duration = '1_day';
        }
        $hasDurationCue = $dayCount !== null || $this->containsAnyPhrase($text, [
            'weekend', 'one day', '1 day', 'two days', '2 days', '2 day', 'two day',
            'three days', '3 days', '3 day', 'three day', '2 nights', 'two nights',
            '2 night', 'two night', 'long weekend', 'couple days', 'couple of days',
        ]);
        $wantsTripPlan = $hasTripKeyword || ($hasPlanningVerb && ($hasDurationCue || count($mentionedCities) > 1));

        if (!$wantsTripPlan && count($mentionedCities) > 1 && $this->containsAnyPhrase($text, ['from', 'to'])) {
            $wantsTripPlan = true;
        }

        if (
            !$wantsTripPlan
            && $dayCount === 1
            && ($wantsRestaurant || $wantsActivity)
        ) {
            $wantsTripPlan = true;
        }

        if ($wantsTripPlan && $this->containsTripPlanNegation($text)) {
            $wantsTripPlan = false;
        }

        if (
            !$wantsTripPlan
            && $dayCount
            && $dayCount > 1
            && !$isRecommendationAsk
            && !$this->looksLikeStayOnlyRecommendationRequest($text)
            && (!$wantsHotel || $this->containsAnyPhrase($text, ['stay in', 'staying in', '2 nights', 'two nights', '2 night', 'two night']))
            && !$wantsRestaurant
            && !$wantsActivity
        ) {
            $wantsTripPlan = true;
        }

        if ($wantsTripPlan && !$dayCount) {
            $dayCount = 2;
            $duration = '2_days';
        }

        $requiresStay = !$noStayRequested && ($wantsHotel || ($wantsTripPlan && ($dayCount ?? 0) > 1));

        $semanticConcepts = array_values(array_unique(array_merge(
            $vibeTags,
            $foodPreferences,
            $occasionTags,
            $audienceTags,
            $activityTypes,
            $timePreferences,
            $budget ? [$budget] : []
        )));
        $hasPreferenceHints = !empty($mentionedCities)
            || !empty($semanticConcepts)
            || $budget !== null
            || $budgetMax !== null
            || $explicitDayCount !== null;

        $requestedCategories = [];

        if ($requiresStay) {
            $requestedCategories[] = 'hotels';
        }

        if ($wantsRestaurant || ($wantsTripPlan && !$noRestaurantRequested)) {
            $requestedCategories[] = 'restaurants';
        }

        if ($wantsActivity || $wantsTripPlan || (!$wantsHotel && !$wantsRestaurant && $hasPreferenceHints)) {
            $requestedCategories[] = 'activities';
        }

        if ($wantsTripPlan) {
            $requestedCategories[] = 'trip_plan';
        }

        $hasTravelSignal = $this->hasTravelSignal(
            $mentionedCities,
            $semanticConcepts,
            $budget,
            $budgetMax,
            $explicitDayCount,
            $wantsHotel,
            $wantsRestaurant,
            $wantsActivity,
            $wantsTripPlan
        );
        $signalScore = $this->calculateIntentSignalScore([
            !empty($mentionedCities),
            !empty($vibeTags),
            !empty($foodPreferences),
            !empty($occasionTags),
            !empty($audienceTags),
            !empty($activityTypes),
            !empty($timePreferences),
            !empty($budget),
            !empty($budgetMax),
            !empty($explicitDayCount),
        ]);
        $hasCrossCityAnchor = $this->hasCrossCityAnchor(
            $vibeTags,
            $foodPreferences,
            $occasionTags,
            $audienceTags,
            $activityTypes,
            $timePreferences,
            $semanticConcepts,
            $budget,
            $budgetMax
        );
        $canPlanWithoutExplicitCity = $this->canPlanTripWithoutExplicitCity(
            $wantsTripPlan,
            $mentionedCities,
            $vibeTags,
            $foodPreferences,
            $occasionTags,
            $audienceTags,
            $activityTypes,
            $timePreferences,
            $budget,
            $budgetMax,
            $dayCount
        );
        $shouldHoldResults = !$hasTravelSignal
            || $signalScore === 0
            || (empty($mentionedCities) && !$hasCrossCityAnchor && !$canPlanWithoutExplicitCity);

        if (empty($mentionedCities) && !$wantsTripPlan) {
            $hasGenericRestaurantOnlySignal = $wantsRestaurant
                && empty($foodPreferences)
                && empty($vibeTags)
                && empty($audienceTags)
                && empty(array_diff($activityTypes, ['food']))
                && empty(array_diff($timePreferences, ['breakfast', 'lunch', 'dinner']))
                && $budget === null
                && $budgetMax === null
                && count(array_intersect($occasionTags, ['breakfast', 'lunch', 'dinner'])) <= 1;

            $hasGenericHotelOnlySignal = $wantsHotel
                && empty($vibeTags)
                && empty($audienceTags)
                && empty($timePreferences)
                && $budget === null
                && $budgetMax === null;

            $hasGenericActivityOnlySignal = $wantsActivity
                && empty($vibeTags)
                && empty($audienceTags)
                && empty($activityTypes)
                && empty($timePreferences)
                && $budget === null
                && $budgetMax === null;

            if ($hasGenericRestaurantOnlySignal || $hasGenericHotelOnlySignal || $hasGenericActivityOnlySignal) {
                $shouldHoldResults = true;
            }

            if ($this->shouldHoldCitylessHotelRequest(
                $wantsHotel,
                $mentionedCities,
                $wantsTripPlan,
                $vibeTags,
                $audienceTags,
                $timePreferences,
                $budget,
                $budgetMax
            )) {
                $shouldHoldResults = true;
            }

            if (!$wantsActivity && $this->shouldHoldCitylessRestaurantRequest(
                $wantsRestaurant,
                $mentionedCities,
                $wantsTripPlan,
                $foodPreferences,
                $occasionTags,
                $timePreferences,
                $vibeTags,
                $budget,
                $budgetMax
            )) {
                $shouldHoldResults = true;
            }
        }

        if ($wantsTripPlan && empty($mentionedCities) && !$canPlanWithoutExplicitCity) {
            $shouldHoldResults = true;
        }

        if (empty($mentionedCities) && !$wantsTripPlan && $this->looksLikeContextDependentFollowUpRequest($text)) {
            $shouldHoldResults = true;
        }

        $responseTone = $this->inferResponseTone($text, $vibeTags, $occasionTags);
        $intent = [
            'city' => $city,
            'resolved_city' => $city,
            'start_city' => $startCity,
            'end_city' => $endCity,
            'mentioned_cities' => $mentionedCities,
            'excluded_cities' => $excludedCities,
            'vibe_tags' => array_values(array_unique($vibeTags)),
            'food_type' => $foodPreferences[0] ?? null,
            'food_preferences' => array_values(array_unique($foodPreferences)),
            'budget' => $budget,
            'budget_max' => $budgetMax,
            'day_count' => $dayCount,
            'duration' => $duration,
            'occasion_tags' => array_values(array_unique($occasionTags)),
            'audience_tags' => array_values(array_unique($audienceTags)),
            'activity_types' => array_values(array_unique($activityTypes)),
            'excluded_categories' => array_values(array_filter([
                $noStayRequested ? 'hotels' : null,
                $noRestaurantRequested ? 'restaurants' : null,
                $noActivityRequested ? 'activities' : null,
            ])),
            'time_preferences' => array_values(array_unique($timePreferences)),
            'semantic_concepts' => $semanticConcepts,
            'country_scope' => $countryScope,
            'requested_categories' => array_values(array_unique($requestedCategories)),
            'requires_stay' => $requiresStay,
            'wants_hotel' => $wantsHotel,
            'wants_restaurant' => $wantsRestaurant,
            'wants_activity' => $wantsActivity || $wantsTripPlan || (!$wantsHotel && !$wantsRestaurant && $hasPreferenceHints),
            'wants_trip_plan' => $wantsTripPlan,
            'has_travel_signal' => $hasTravelSignal,
            'signal_score' => $signalScore,
            'can_plan_without_explicit_city' => $canPlanWithoutExplicitCity,
            'should_hold_results' => $shouldHoldResults,
            'response_tone' => $responseTone,
        ];

        $intent['message_type'] = $this->deriveMessageType($intent);

        return $intent;
    }

    private function applySessionContextToIntent(string $message, array $intent, ?array $sessionContext): array
    {
        $previousIntent = is_array($sessionContext['intent'] ?? null) ? $sessionContext['intent'] : null;
        if ($previousIntent === null || !$this->shouldUseSessionContextForIntent($message, $intent, $previousIntent)) {
            return [$intent, $message];
        }

        $mergedSeed = $this->mergeIntentSeedWithSessionContext($intent, $previousIntent, $message);
        $intentSourceMessage = $this->buildIntentPromptFromSeed($mergedSeed);
        $mergedIntent = $this->extractIntent($intentSourceMessage);
        if (!empty($mergedSeed['excluded_cities'])) {
            $excludedCities = array_values(array_unique(array_map(
                fn ($city) => $this->normalizeText((string) $city),
                (array) $mergedSeed['excluded_cities']
            )));

            $mergedIntent['excluded_cities'] = array_values(array_unique(array_merge(
                (array) ($mergedIntent['excluded_cities'] ?? []),
                $excludedCities
            )));
            $mergedIntent['mentioned_cities'] = array_values(array_diff(
                (array) ($mergedIntent['mentioned_cities'] ?? []),
                $excludedCities
            ));

            if (in_array($mergedIntent['resolved_city'] ?? null, $excludedCities, true)) {
                $mergedIntent['city'] = $mergedIntent['mentioned_cities'][0] ?? null;
                $mergedIntent['resolved_city'] = $mergedIntent['city'];
            }
        }
        $mergedIntent = $this->enforceFollowUpCategoryMode(
            $mergedIntent,
            (string) ($mergedSeed['_category_mode'] ?? 'carry_previous')
        );

        $mergedIntent['follow_up_context'] = [
            'is_follow_up' => true,
            'previous_message_type' => $previousIntent['message_type'] ?? null,
            'current_message' => trim($message),
            'context_city' => $previousIntent['resolved_city'] ?? $previousIntent['city'] ?? null,
            'carryover_fields' => $this->detectIntentCarryoverFields($intent, $previousIntent),
        ];

        if (!empty($intent['response_tone'])) {
            $mergedIntent['response_tone'] = $intent['response_tone'];
        }

        return [$mergedIntent, $intentSourceMessage];
    }

    private function shouldUseSessionContextForIntent(string $message, array $intent, array $previousIntent): bool
    {
        $normalizedMessage = $this->normalizeText($message);
        if ($normalizedMessage === '') {
            return false;
        }

        if (!$this->hasUsableSessionIntent($previousIntent)) {
            return false;
        }

        if ($this->looksLikeGreetingOnlyMessage($normalizedMessage)) {
            return false;
        }

        if ($this->looksLikeMetaOnlyMessage($normalizedMessage)) {
            return false;
        }

        if ($this->looksLikeFreshExplicitRequest($normalizedMessage, $intent)) {
            return false;
        }

        if ($this->containsFollowUpRefinementCue($normalizedMessage)) {
            return true;
        }

        if ($this->isShortIntentRefinement($normalizedMessage, $intent)) {
            return true;
        }

        return false;
    }

    private function looksLikeFreshExplicitRequest(string $message, array $intent): bool
    {
        $hasExplicitCategory = !empty($intent['wants_hotel'])
            || !empty($intent['wants_restaurant'])
            || !empty($intent['wants_activity'])
            || !empty($intent['wants_trip_plan']);
        $hasExplicitCity = !empty($intent['mentioned_cities']);

        if (
            $hasExplicitCategory
            && $hasExplicitCity
            && !$this->looksLikeContextDependentFollowUpRequest($message)
        ) {
            return true;
        }

        $startsFresh = preg_match('/^(find|recommend|suggest|show|plan|build|create|give)\b/u', $message) === 1
            || str_starts_with($message, 'find me ')
            || str_starts_with($message, 'recommend me ')
            || str_starts_with($message, 'can you recommend ')
            || str_starts_with($message, 'i want ')
            || str_starts_with($message, 'i m with ')
            || str_starts_with($message, 'im with ')
            || str_starts_with($message, 'we re with ')
            || str_starts_with($message, 'were with ')
            || str_starts_with($message, 'we are with ');

        if (!$startsFresh) {
            return false;
        }

        $hasExplicitScope = !empty($intent['mentioned_cities'])
            || !empty($intent['wants_hotel'])
            || !empty($intent['wants_restaurant'])
            || !empty($intent['wants_activity'])
            || !empty($intent['wants_trip_plan']);

        if (!$hasExplicitScope) {
            return false;
        }

        return !$this->containsAnyPhrase($message, [
            'same',
            'instead',
            'what about',
            'turn it',
            'make it',
            'change it',
            'switch it',
            'plan it',
            'extend it',
            'shorten it',
        ]);
    }

    private function hasUsableSessionIntent(array $intent): bool
    {
        return !empty($intent['has_travel_signal'])
            || !empty($intent['wants_trip_plan'])
            || !empty($intent['requested_categories'])
            || !empty($intent['mentioned_cities'])
            || !empty($intent['resolved_city']);
    }

    private function looksLikeGreetingOnlyMessage(string $message): bool
    {
        return in_array($message, [
            'h',
            'hi',
            'hello',
            'hey',
            'yo',
            'thanks',
            'thank you',
            'thx',
            'nice',
            'cool',
            'great',
            'awesome',
            'perfect',
            'ok',
            'okay',
        ], true);
    }

    private function looksLikeMetaOnlyMessage(string $message): bool
    {
        foreach ([
            'is ai down',
            'is the ai down',
            'is chatbot down',
            'is this working',
            'are you there',
            'are you working',
            'did you break',
        ] as $phrase) {
            if (str_contains($message, $phrase)) {
                return true;
            }
        }

        return false;
    }

    private function looksLikeContextDependentFollowUpRequest(string $message): bool
    {
        return $this->containsAnyPhrase($message, [
            'same city',
            'same vibe',
            'same place',
            'same budget',
            'same but',
            'same for',
            'same one',
            'make it',
            'make them',
            'change it',
            'change to',
            'switch it',
            'switch to',
            'move it to',
            'move to',
            'plan it',
            'plan this',
            'turn it',
            'turn that',
            'turn this',
            'that one',
            'those ones',
            'the previous',
            'previous one',
            'last one',
        ]);
    }

    private function containsTripPlanNegation(string $message): bool
    {
        return $this->containsAnyPhrase($message, [
            'not a trip',
            'not a full trip',
            'not an itinerary',
            'no trip',
            'no itinerary',
            'without a trip',
            'without itinerary',
            'just hotel',
            'just hotels',
            'only hotel',
            'only hotels',
            'hotel only',
            'just restaurant',
            'just restaurants',
            'restaurant only',
            'only restaurant',
            'only restaurants',
            'just activities',
            'activity only',
            'activities only',
            'only activities',
        ]);
    }

    private function containsNoStayRequest(string $message): bool
    {
        return $this->containsAnyPhrase($message, [
            'no hotel',
            'no hotels',
            'no stay',
            'no stays',
            'without hotel',
            'without hotels',
            'without a hotel',
            'without a stay',
            'skip hotel',
            'skip hotels',
            'skip the hotel',
            'remove hotel',
            'remove the hotel',
            'remove stay',
            'remove the stay',
        ]);
    }

    private function messageNegatesCategory(string $message, string $category): bool
    {
        return match ($category) {
            'hotels' => $this->containsNoStayRequest($message),
            'restaurants' => $this->containsAnyPhrase($message, [
                'no restaurant',
                'no restaurants',
                'without restaurant',
                'without restaurants',
                'without food',
                'skip restaurant',
                'skip restaurants',
                'skip dinner',
                'remove restaurant',
                'remove restaurants',
            ]),
            'activities' => $this->containsAnyPhrase($message, [
                'no activity',
                'no activities',
                'without activity',
                'without activities',
                'without places',
                'skip activity',
                'skip activities',
                'remove activity',
                'remove activities',
            ]),
            default => false,
        };
    }

    private function containsGenericCityRejection(string $message): bool
    {
        return $this->containsAnyPhrase($message, [
            'dont like that city',
            'don t like that city',
            'do not like that city',
            'dont like this city',
            'don t like this city',
            'do not like this city',
            'i dont like that city',
            'i don t like that city',
            'i do not like that city',
            'give me another city',
            'another city',
            'different city',
            'somewhere else',
            'another area',
            'different area',
        ]);
    }

    private function containsAddStayRequest(string $message): bool
    {
        return $this->containsAnyPhrase($message, [
            'add a stay',
            'add stay',
            'add hotel',
            'add hotels',
            'add a hotel',
            'add accommodation',
            'include a stay',
            'include stay',
            'include hotel',
            'include hotels',
            'with a stay',
            'with stay',
            'with hotel',
            'with hotels',
            'hotel back',
            'hotels back',
            'stay back',
            'bring back hotel',
            'bring the hotel back',
        ]);
    }

    private function looksLikeStayOnlyRecommendationRequest(string $message): bool
    {
        return $this->containsAnyPhrase($message, [
            'where can we stay',
            'where can i stay',
            'where should we stay',
            'where should i stay',
            'where to stay',
            'recommend a stay',
            'recommend a hotel',
            'find me a stay',
            'find me a hotel',
            'show me hotels',
            'hotel recommendation',
            'hotel options',
        ]);
    }

    private function containsFollowUpRefinementCue(string $message): bool
    {
        return $this->containsAnyPhrase($message, [
            'instead',
            'actually',
            'what about',
            'same but',
            'make it',
            'make them',
            'make this',
            'make that',
            'more ',
            'less ',
            'cheaper',
            'budget',
            'mid range',
            'mid-range',
            'luxury',
            'premium',
            'romantic',
            'quiet',
            'quieter',
            'calm',
            'lively',
            'family',
            'friends',
            'couple',
            'seafood',
            'sushi',
            'breakfast',
            'lunch',
            'dinner',
            'with a stay',
            'with hotel',
            'with hotels',
            'without a stay',
            'without hotel',
            'without hotels',
            'no hotel',
            'no hotels',
            'add a stay',
            'add hotel',
            'add hotels',
            'remove stay',
            'remove the stay',
            'remove hotel',
            'remove hotels',
            'remove the hotel',
            'remove the hotels',
            'hotel back',
            'hotels back',
            'extend it',
            'shorten it',
            'another option',
            'another one',
            'change it',
            'change to',
            'switch it',
            'switch to',
            'move it to',
            'move to',
            'plan it',
            'plan this',
            'keep the same',
            'same city',
            'same vibe',
            'turn that',
            'turn this',
            'turn it',
        ]);
    }

    private function isShortIntentRefinement(string $message, array $intent): bool
    {
        $wordCount = str_word_count($message);
        if ($wordCount === 0 || $wordCount > 10) {
            return false;
        }

        return !empty($intent['mentioned_cities'])
            || !empty($intent['day_count'])
            || !empty($intent['budget'])
            || !empty($intent['budget_max'])
            || !empty($intent['vibe_tags'])
            || !empty($intent['food_preferences'])
            || !empty($intent['occasion_tags'])
            || !empty($intent['audience_tags'])
            || !empty($intent['activity_types'])
            || !empty($intent['time_preferences'])
            || !empty($intent['requested_categories']);
    }

    private function mergeIntentSeedWithSessionContext(array $intent, array $previousIntent, string $message): array
    {
        $normalizedMessage = $this->normalizeText($message);
        $categoryMode = $this->determineFollowUpCategoryMode($normalizedMessage, $intent, $previousIntent);
        $hasExplicitCategoryCue = $this->messageExplicitlyRequestsCategory($normalizedMessage, 'hotels')
            || $this->messageExplicitlyRequestsCategory($normalizedMessage, 'restaurants')
            || $this->messageExplicitlyRequestsCategory($normalizedMessage, 'activities')
            || !empty($intent['wants_trip_plan']);

        $merged = [
            'mentioned_cities' => !empty($intent['mentioned_cities']) ? (array) $intent['mentioned_cities'] : (array) ($previousIntent['mentioned_cities'] ?? []),
            'vibe_tags' => !empty($intent['vibe_tags']) ? (array) $intent['vibe_tags'] : (array) ($previousIntent['vibe_tags'] ?? []),
            'food_preferences' => !empty($intent['food_preferences']) ? (array) $intent['food_preferences'] : (array) ($previousIntent['food_preferences'] ?? []),
            'occasion_tags' => !empty($intent['occasion_tags']) ? (array) $intent['occasion_tags'] : (array) ($previousIntent['occasion_tags'] ?? []),
            'audience_tags' => !empty($intent['audience_tags']) ? (array) $intent['audience_tags'] : (array) ($previousIntent['audience_tags'] ?? []),
            'activity_types' => !empty($intent['activity_types']) ? (array) $intent['activity_types'] : (array) ($previousIntent['activity_types'] ?? []),
            'time_preferences' => !empty($intent['time_preferences']) ? (array) $intent['time_preferences'] : (array) ($previousIntent['time_preferences'] ?? []),
            'excluded_cities' => !empty($intent['excluded_cities']) ? (array) $intent['excluded_cities'] : (array) ($previousIntent['excluded_cities'] ?? []),
            'budget' => $this->intentValueOrFallback($intent, 'budget', $previousIntent),
            'budget_max' => $this->intentValueOrFallback($intent, 'budget_max', $previousIntent),
            'day_count' => $this->intentValueOrFallback($intent, 'day_count', $previousIntent),
            'duration' => $this->intentValueOrFallback($intent, 'duration', $previousIntent),
            'wants_trip_plan' => $categoryMode === 'trip_plan'
                || (!empty($previousIntent['wants_trip_plan']) && $categoryMode === 'carry_previous'),
            'requires_stay' => false,
            'wants_hotel' => false,
            'wants_restaurant' => false,
            'wants_activity' => false,
            '_category_mode' => $categoryMode,
            '_suppress_stay' => $this->containsNoStayRequest($normalizedMessage),
            '_force_stay' => $this->containsAddStayRequest($normalizedMessage),
        ];

        $previousResolvedCity = $this->normalizeText((string) ($previousIntent['resolved_city'] ?? $previousIntent['city'] ?? ''));
        if ($previousResolvedCity !== '' && $this->containsGenericCityRejection($normalizedMessage)) {
            $merged['excluded_cities'] = array_values(array_unique(array_merge(
                (array) ($merged['excluded_cities'] ?? []),
                [$previousResolvedCity]
            )));

            if (empty($intent['mentioned_cities'])) {
                $merged['mentioned_cities'] = [];
            }
        }

        if (empty($merged['mentioned_cities']) && empty($merged['excluded_cities'])) {
            $resolvedCity = trim((string) ($previousIntent['resolved_city'] ?? $previousIntent['city'] ?? ''));
            if ($resolvedCity !== '') {
                $merged['mentioned_cities'] = [$resolvedCity];
            }
        } elseif (!empty($merged['excluded_cities'])) {
            $merged['mentioned_cities'] = array_values(array_diff(
                (array) ($merged['mentioned_cities'] ?? []),
                (array) ($merged['excluded_cities'] ?? [])
            ));
        }

        if (!empty($intent['day_count']) && empty($intent['duration'])) {
            $merged['duration'] = $this->durationKeyFromDayCount((int) $intent['day_count']);
        }

        switch ($categoryMode) {
            case 'hotels':
                $merged['wants_hotel'] = true;
                break;
            case 'restaurants':
                $merged['wants_restaurant'] = true;
                break;
            case 'activities':
                $merged['wants_activity'] = true;
                break;
            case 'trip_plan':
                $merged['wants_trip_plan'] = true;
                $merged['wants_hotel'] = !$merged['_suppress_stay'] && (
                    $merged['_force_stay']
                    || !empty($intent['wants_hotel'])
                    || !empty($intent['requires_stay'])
                    || ((int) ($merged['day_count'] ?? 0)) > 1
                );
                $merged['wants_restaurant'] = true;
                $merged['wants_activity'] = true;
                break;
            default:
                $merged['wants_trip_plan'] = !empty($previousIntent['wants_trip_plan']);
                $merged['wants_hotel'] = !empty($previousIntent['wants_hotel']);
                $merged['wants_restaurant'] = !empty($previousIntent['wants_restaurant']);
                $merged['wants_activity'] = !empty($previousIntent['wants_activity']);
                break;
        }

        if (!$merged['wants_trip_plan'] && $hasExplicitCategoryCue) {
            $merged['wants_hotel'] = $merged['wants_hotel'] || !empty($intent['wants_hotel']);
            $merged['wants_restaurant'] = $merged['wants_restaurant'] || !empty($intent['wants_restaurant']);
            $merged['wants_activity'] = $merged['wants_activity'] || !empty($intent['wants_activity']);
        }

        if ($merged['_suppress_stay']) {
            $merged['wants_hotel'] = false;
        }

        if ($merged['_force_stay']) {
            $merged['wants_hotel'] = true;
        }

        if ($merged['wants_trip_plan'] && empty($merged['day_count'])) {
            $merged['day_count'] = (int) ($previousIntent['day_count'] ?? 2);
            $merged['duration'] = $this->durationKeyFromDayCount((int) $merged['day_count']);
        }

        $merged['requires_stay'] = !$merged['_suppress_stay'] && (
            $merged['wants_hotel']
            || ($merged['wants_trip_plan'] && ((int) ($merged['day_count'] ?? 0)) > 1)
        );

        return $merged;
    }

    private function enforceFollowUpCategoryMode(array $intent, string $categoryMode): array
    {
        switch ($categoryMode) {
            case 'hotels':
                $intent['wants_trip_plan'] = false;
                $intent['wants_hotel'] = true;
                $intent['wants_restaurant'] = false;
                $intent['wants_activity'] = false;
                $intent['requires_stay'] = true;
                $intent['requested_categories'] = ['hotels'];
                break;

            case 'restaurants':
                $intent['wants_trip_plan'] = false;
                $intent['wants_hotel'] = false;
                $intent['wants_restaurant'] = true;
                $intent['wants_activity'] = false;
                $intent['requires_stay'] = false;
                $intent['requested_categories'] = ['restaurants'];
                break;

            case 'activities':
                $intent['wants_trip_plan'] = false;
                $intent['wants_hotel'] = false;
                $intent['wants_restaurant'] = false;
                $intent['wants_activity'] = true;
                $intent['requires_stay'] = false;
                $intent['requested_categories'] = ['activities'];
                break;

            case 'trip_plan':
                $requestedCategories = (array) ($intent['requested_categories'] ?? []);
                $includeStay = !empty($intent['requires_stay'])
                    || !empty($intent['wants_hotel'])
                    || (((int) ($intent['day_count'] ?? 0)) > 1 && in_array('hotels', $requestedCategories, true));
                $intent['wants_trip_plan'] = true;
                $intent['wants_hotel'] = $includeStay;
                $intent['wants_restaurant'] = true;
                $intent['wants_activity'] = true;
                $baseCategories = $includeStay
                    ? ['hotels', 'restaurants', 'activities', 'trip_plan']
                    : ['restaurants', 'activities', 'trip_plan'];
                $intent['requested_categories'] = array_values(array_unique(array_merge(
                    $baseCategories,
                    array_values(array_diff($requestedCategories, $includeStay ? [] : ['hotels']))
                )));
                $intent['requires_stay'] = $includeStay;
                break;
        }

        $intent['message_type'] = $this->deriveMessageType($intent);

        return $intent;
    }

    private function intentValueOrFallback(array $intent, string $key, array $previousIntent): mixed
    {
        if (array_key_exists($key, $intent) && $intent[$key] !== null && $intent[$key] !== '') {
            return $intent[$key];
        }

        return $previousIntent[$key] ?? null;
    }

    private function determineFollowUpCategoryMode(string $message, array $intent, array $previousIntent): string
    {
        if (
            $this->containsAnyPhrase($message, ['turn that into', 'turn this into', 'turn it into', 'plan it as', 'plan this as'])
            && (!empty($intent['day_count']) || !empty($intent['duration']))
        ) {
            return 'trip_plan';
        }

        if ($this->containsAnyPhrase($message, ['just hotels', 'only hotels', 'hotels only', 'hotel only', 'hotel options'])) {
            return 'hotels';
        }

        if ($this->containsAnyPhrase($message, ['just restaurants', 'only restaurants', 'restaurants only', 'restaurant only', 'food only', 'dinner only'])) {
            return 'restaurants';
        }

        if ($this->containsAnyPhrase($message, ['just activities', 'only activities', 'activities only', 'activity only', 'things to do only', 'just places'])) {
            return 'activities';
        }

        if (!empty($intent['wants_trip_plan'])) {
            return 'trip_plan';
        }

        if (!empty($previousIntent['wants_trip_plan'])) {
            return 'carry_previous';
        }

        if (
            $this->messageExplicitlyRequestsCategory($message, 'hotels')
            || $this->messageExplicitlyRequestsCategory($message, 'restaurants')
            || $this->messageExplicitlyRequestsCategory($message, 'activities')
        ) {
            if (
                $this->messageExplicitlyRequestsCategory($message, 'hotels')
                && !$this->messageExplicitlyRequestsCategory($message, 'restaurants')
                && !$this->messageExplicitlyRequestsCategory($message, 'activities')
            ) {
                return 'hotels';
            }

            if (
                $this->messageExplicitlyRequestsCategory($message, 'restaurants')
                && !$this->messageExplicitlyRequestsCategory($message, 'hotels')
                && !$this->messageExplicitlyRequestsCategory($message, 'activities')
            ) {
                return 'restaurants';
            }

            if (
                $this->messageExplicitlyRequestsCategory($message, 'activities')
                && !$this->messageExplicitlyRequestsCategory($message, 'hotels')
                && !$this->messageExplicitlyRequestsCategory($message, 'restaurants')
            ) {
                return 'activities';
            }
        }

        $previousCategoryModes = array_filter([
            !empty($previousIntent['wants_hotel']) || !empty($previousIntent['requires_stay']) ? 'hotels' : null,
            !empty($previousIntent['wants_restaurant']) ? 'restaurants' : null,
            !empty($previousIntent['wants_activity']) ? 'activities' : null,
        ]);

        if (count($previousCategoryModes) === 1) {
            return array_values($previousCategoryModes)[0];
        }

        return 'carry_previous';
    }

    private function messageExplicitlyRequestsCategory(string $message, string $category): bool
    {
        if ($this->messageNegatesCategory($message, $category)) {
            return false;
        }

        return match ($category) {
            'hotels' => $this->containsAnyPhrase($message, [
                'hotel',
                'hotels',
                'stay',
                'stays',
                'room',
                'rooms',
                'accommodation',
                'resort',
                'guesthouse',
                'guesthouses',
                'guest house',
                'guest houses',
            ]),
            'restaurants' => $this->containsAnyPhrase($message, [
                'restaurant',
                'restaurants',
                'food',
                'dinner',
                'lunch',
                'breakfast',
                'brunch',
                'cafe',
                'coffee',
                'sushi',
                'pizza',
            ]),
            'activities' => $this->containsAnyPhrase($message, [
                'activity',
                'activities',
                'things to do',
                'place',
                'places',
                'walk',
                'nightlife',
                'rooftop',
                'hidden gem',
                'visit',
                'explore',
            ]),
            default => false,
        };
    }

    private function buildIntentPromptFromSeed(array $seed): string
    {
        $parts = [];
        $cities = array_values(array_filter((array) ($seed['mentioned_cities'] ?? []), fn ($city) => is_string($city) && trim($city) !== ''));
        $cityText = count($cities) > 1
            ? implode(' to ', array_map(fn ($city) => Str::title((string) $city), $cities))
            : (isset($cities[0]) ? Str::title((string) $cities[0]) : null);

        if (!empty($seed['wants_trip_plan'])) {
            $dayCount = (int) ($seed['day_count'] ?? 2);
            $parts[] = $cityText !== null
                ? "Plan a {$dayCount} day trip in {$cityText}"
                : "Plan a {$dayCount} day trip";
        } else {
            $categories = [];
            if (!empty($seed['wants_hotel'])) {
                $categories[] = 'hotels';
            }
            if (!empty($seed['wants_restaurant'])) {
                $categories[] = 'restaurants';
            }
            if (!empty($seed['wants_activity'])) {
                $categories[] = 'activities';
            }

            $categoryText = empty($categories) ? 'travel options' : implode(', ', $categories);
            $parts[] = $cityText !== null
                ? 'Recommend ' . $categoryText . ' in ' . $cityText
                : 'Recommend ' . $categoryText;
        }

        if (!empty($seed['_suppress_stay']) && !empty($seed['wants_trip_plan'])) {
            $parts[] = 'without a stay';
        } elseif (!empty($seed['wants_trip_plan']) && (!empty($seed['requires_stay']) || !empty($seed['wants_hotel']))) {
            $parts[] = 'include a stay';
        }

        foreach ([
            'vibe_tags' => 'prefer',
            'food_preferences' => 'food',
            'occasion_tags' => 'occasion',
            'audience_tags' => 'audience',
            'activity_types' => 'activities',
            'time_preferences' => 'timing',
        ] as $key => $label) {
            $values = array_values(array_filter((array) ($seed[$key] ?? []), fn ($value) => is_string($value) && trim($value) !== ''));
            if (empty($values)) {
                continue;
            }

            $readableValues = implode(', ', array_map(fn ($value) => str_replace('_', ' ', (string) $value), $values));
            $parts[] = $label . ' ' . $readableValues;
        }

        if (!empty($seed['budget'])) {
            $parts[] = 'price preference ' . str_replace('_', ' ', (string) $seed['budget']);
        }

        if (!empty($seed['budget_max'])) {
            $parts[] = 'under $' . (int) $seed['budget_max'];
        }

        return implode('. ', array_filter(array_map(fn ($part) => trim((string) $part), $parts))) . '.';
    }

    private function detectIntentCarryoverFields(array $intent, array $previousIntent): array
    {
        $fields = [];

        foreach ([
            'mentioned_cities',
            'vibe_tags',
            'food_preferences',
            'occasion_tags',
            'audience_tags',
            'activity_types',
            'time_preferences',
            'budget',
            'budget_max',
            'day_count',
            'duration',
        ] as $field) {
            $current = $intent[$field] ?? null;
            $previous = $previousIntent[$field] ?? null;

            if (($current === null || $current === [] || $current === '') && $previous !== null && $previous !== [] && $previous !== '') {
                $fields[] = $field;
            }
        }

        if (empty($intent['requested_categories']) && !empty($previousIntent['requested_categories'])) {
            $fields[] = 'requested_categories';
        }

        return array_values(array_unique($fields));
    }

    private function evaluateHotel($hotel, array $intent, string $message): array
    {
        $score = 0.0;
        $reasons = [];
        $profile = $this->buildHotelProfile($hotel);
        $candidateConcepts = array_values(array_unique(array_merge(
            $profile['vibe_tags'],
            $profile['audience_tags'],
            $profile['semantic_concepts']
        )));

        $score += $this->scoreCityMatch($intent['mentioned_cities'], $profile['cities'], $reasons);
        $score += $this->scoreConceptOverlap($intent['vibe_tags'], $profile['vibe_tags'], 10, 24, 'matches vibe', $reasons);
        $score += $this->scoreConceptOverlap($intent['audience_tags'], $profile['audience_tags'], 10, 18, 'fits audience', $reasons);
        $score += $this->scoreConceptConflicts($intent['semantic_concepts'], $candidateConcepts, 6, 16);
        $score += $this->scoreBudgetMatch($intent, $profile['budget_tier'], $profile['price_value'], $reasons);
        $score += $this->scoreBeachDistance($intent, $profile['distance_from_beach_km'], $reasons);
        $score += $this->scoreSemanticOverlap($intent['semantic_concepts'], $profile['semantic_concepts'], 4, 14, $reasons);

        $textScore = $this->keywordOverlapScore($profile['text'], $message, $intent['semantic_concepts']);
        if ($textScore > 0) {
            $score += $textScore;
            if ($textScore >= 6) {
                $reasons[] = 'description aligns with the request';
            }
        }

        if ($profile['rating'] > 0) {
            $score += min($profile['rating'] * 3.5, 18);
            $reasons[] = 'strong rating';
        }

        if ($profile['review_count'] > 0) {
            $score += min(log($profile['review_count'] + 1, 10) * 5, 10);
        }

        return ['score' => round($score, 2), 'reasons' => array_values(array_unique($reasons))];
    }

    private function evaluateRestaurant($restaurant, array $intent, string $message): array
    {
        $score = 0.0;
        $reasons = [];
        $profile = $this->buildRestaurantProfile($restaurant);
        $candidateConcepts = array_values(array_unique(array_merge(
            $profile['vibe_tags'],
            $profile['occasion_tags'],
            $profile['food_types'],
            $profile['semantic_concepts']
        )));

        $score += $this->scoreCityMatch($intent['mentioned_cities'], $profile['cities'], $reasons);
        $score += $this->scoreConceptOverlap($intent['food_preferences'], $profile['food_types'], 12, 24, 'matches food preference', $reasons);
        $score += $this->scoreConceptOverlap($intent['vibe_tags'], $profile['vibe_tags'], 9, 22, 'matches vibe', $reasons);
        $score += $this->scoreConceptOverlap($intent['occasion_tags'], $profile['occasion_tags'], 9, 18, 'fits occasion', $reasons);
        $score += $this->scoreConceptConflicts($intent['semantic_concepts'], $candidateConcepts, 6, 18);
        $score += $this->scoreBudgetMatch($intent, $profile['budget_tier'], null, $reasons);
        $score += $this->scoreSemanticOverlap($intent['semantic_concepts'], $profile['semantic_concepts'], 4, 14, $reasons);

        $textScore = $this->keywordOverlapScore($profile['text'], $message, $intent['semantic_concepts']);
        if ($textScore > 0) {
            $score += $textScore;
            if ($textScore >= 6) {
                $reasons[] = 'menu and description fit the request';
            }
        }

        if ($profile['rating'] > 0) {
            $score += min($profile['rating'] * 4, 18);
            $reasons[] = 'strong rating';
        }

        return ['score' => round($score, 2), 'reasons' => array_values(array_unique($reasons))];
    }

    private function evaluateActivity($activity, array $intent, string $message): array
    {
        $score = 0.0;
        $reasons = [];
        $profile = $this->buildActivityProfile($activity);
        $candidateConcepts = array_values(array_unique(array_merge(
            $profile['vibe_tags'],
            $profile['occasion_tags'],
            $profile['activity_types'],
            $profile['time_tags'],
            $profile['semantic_concepts']
        )));

        $score += $this->scoreCityMatch($intent['mentioned_cities'], $profile['cities'], $reasons);
        $score += $this->scoreConceptOverlap($intent['activity_types'], $profile['activity_types'], 12, 24, 'matches activity type', $reasons);
        $score += $this->scoreConceptOverlap($intent['vibe_tags'], $profile['vibe_tags'], 9, 22, 'matches vibe', $reasons);
        $score += $this->scoreConceptOverlap($intent['occasion_tags'], $profile['occasion_tags'], 8, 18, 'fits occasion', $reasons);
        $score += $this->scoreConceptOverlap($intent['time_preferences'], $profile['time_tags'], 10, 12, 'fits timing', $reasons);
        $score += $this->scoreConceptConflicts($intent['semantic_concepts'], $candidateConcepts, 5, 14);
        $score += $this->scoreBudgetMatch($intent, $profile['budget_tier'], null, $reasons);
        $score += $this->scoreSemanticOverlap($intent['semantic_concepts'], $profile['semantic_concepts'], 4, 14, $reasons);

        $textScore = $this->keywordOverlapScore($profile['text'], $message, $intent['semantic_concepts']);
        if ($textScore > 0) {
            $score += $textScore;
            if ($textScore >= 6) {
                $reasons[] = 'activity details align with the request';
            }
        }

        return ['score' => round($score, 2), 'reasons' => array_values(array_unique($reasons))];
    }

    private function buildTripPlan(array $intent, array $hotels, array $restaurants, array $activities): array
    {
        $dayCount = max(1, (int) ($intent['day_count'] ?? $this->durationToDayCount($intent['duration'] ?? null) ?? ($intent['wants_trip_plan'] ? 2 : 1)));
        $duration = $this->durationKeyFromDayCount($dayCount);
        $resolvedCity = $intent['resolved_city'] ?? ($intent['city'] ?? null);
        $startCity = ucfirst($intent['start_city'] ?? ($resolvedCity ?? 'Lebanon'));
        $endCity = ucfirst($intent['end_city'] ?? ($resolvedCity ?? 'Lebanon'));
        $isMultiCity = !empty($intent['start_city']) && !empty($intent['end_city']) && $intent['start_city'] !== $intent['end_city'];
        $includeStay = (bool) ($intent['requires_stay'] ?? (!empty($intent['wants_hotel']) || $dayCount > 1));

        $restaurantPool = array_values($restaurants);

        if ($isMultiCity && $dayCount >= 1) {
            return $this->buildMultiCityTripPlan($duration, $dayCount, $startCity, $endCity, $intent, $hotels, $restaurantPool, $activities, $includeStay);
        }

        $city = ucfirst($resolvedCity ?? 'Lebanon');
        $hotel = $this->pickListItem($hotels, 0);

        return $this->buildSingleCityTripPlan($duration, $dayCount, $city, $hotel, $restaurantPool, $activities, $includeStay);
    }

    private function buildSingleCityTripPlan(string $duration, int $dayCount, string $city, ?array $hotel, array $restaurants, array $activities, bool $includeStay): array
    {
        $days = [];
        $usedMealIds = [];

        for ($day = 1; $day <= $dayCount; $day++) {
            $flow = [
                'morning' => [
                    'title' => $day === 1 ? "Morning in {$city}" : ($day === $dayCount ? 'Final morning' : "Day {$day} morning"),
                    'activities' => $this->pickActivitiesForSlot($activities, 'morning', 2, $day - 1),
                ],
                'lunch' => $this->pickPlanningListItem($restaurants, ($day - 1) * 2, $usedMealIds),
                'afternoon' => [
                    'title' => $day === $dayCount ? 'Wrap up and explore' : "Afternoon in {$city}",
                    'activities' => $this->pickActivitiesForSlot($activities, 'afternoon', $day === $dayCount ? 1 : 2, $day - 1),
                ],
            ];

            if ($day < $dayCount || $dayCount === 1) {
                $flow['evening'] = [
                    'title' => $day === $dayCount ? "Evening in {$city}" : ($day === 1 ? "Evening in {$city}" : "Day {$day} evening"),
                    'activities' => $this->pickActivitiesForSlot($activities, 'evening', 2, $day - 1),
                ];
                $flow['dinner'] = $this->pickPlanningListItem($restaurants, (($day - 1) * 2) + 1, $usedMealIds);
            }

            if ($hotel && $this->shouldIncludeStayOnDay($includeStay, $day, $dayCount)) {
                $flow['stay'] = $hotel;
            } elseif (!$hotel && $this->shouldIncludeStayOnDay($includeStay, $day, $dayCount)) {
                $flow['stay'] = $this->buildUnavailableStaySlot($city);
            }

            $days[] = [
                'day' => $day,
                'location' => $city,
                'flow' => $flow,
            ];
        }

        return [
            'duration' => $duration,
            'title' => "{$dayCount}-Day Trip in {$city}",
            'summary' => $dayCount >= 3
                ? 'A fuller itinerary built from recommended stays, food, and activities.'
                : ($dayCount === 2
                    ? 'A practical itinerary built from recommended stays, food, and activities.'
                    : 'A short day plan with recommended food and activities.'),
            'days' => $days,
        ];
    }

    private function buildMultiCityTripPlan(string $duration, int $dayCount, string $startCity, string $endCity, array $intent, array $hotels, array $restaurants, array $activities, bool $includeStay): array
    {
        $routeCityKeys = array_values(array_unique(array_filter(
            array_map(fn ($city) => $this->normalizeText((string) $city), (array) ($intent['mentioned_cities'] ?? [])),
            fn ($city) => $city !== ''
        )));

        if (count($routeCityKeys) < 2) {
            $routeCityKeys = array_values(array_unique(array_filter([
                $this->normalizeText($intent['start_city'] ?? $startCity),
                $this->normalizeText($intent['end_city'] ?? $endCity),
            ])));
        }

        $routeCityLabels = array_map(fn ($city) => Str::title($city), $routeCityKeys);
        $routeCityCount = max(1, count($routeCityKeys));
        $startCityKey = $routeCityKeys[0] ?? $this->normalizeText($intent['start_city'] ?? $startCity);
        $endCityKey = $routeCityKeys[$routeCityCount - 1] ?? $this->normalizeText($intent['end_city'] ?? $endCity);
        $startCity = $routeCityLabels[0] ?? $startCity;
        $endCity = $routeCityLabels[$routeCityCount - 1] ?? $endCity;

        $dayCityIndexes = [];
        for ($day = 1; $day <= $dayCount; $day++) {
            $dayCityIndexes[$day] = $day === $dayCount
                ? $routeCityCount - 1
                : min($routeCityCount - 1, (int) floor((($day - 1) * $routeCityCount) / $dayCount));
        }

        $usedMealsByCity = [];

        $days = [];

        for ($day = 1; $day <= $dayCount; $day++) {
            $cityIndex = $dayCityIndexes[$day] ?? 0;
            $nextCityIndex = $dayCityIndexes[$day + 1] ?? $cityIndex;
            $cityKey = $routeCityKeys[$cityIndex] ?? $startCityKey;
            $nextCityKey = $routeCityKeys[$nextCityIndex] ?? $cityKey;
            $city = $routeCityLabels[$cityIndex] ?? Str::title($cityKey);
            $nextCity = $routeCityLabels[$nextCityIndex] ?? Str::title($nextCityKey);
            $isTransitionDay = $day < $dayCount && $nextCityKey !== $cityKey;
            $cityActivities = array_values(array_filter($activities, fn ($a) => ($a['city'] ?? null) === $cityKey));
            $cityRestaurants = $this->filterItemsByCity($restaurants, $cityKey);
            $mealPool = $cityRestaurants;

            if (!isset($usedMealsByCity[$cityKey])) {
                $usedMealsByCity[$cityKey] = [];
            }

            $flow = [
                'morning' => [
                    'title' => "Morning in {$city}",
                    'activities' => $this->pickActivitiesForSlot($cityActivities, 'morning', 2, $day - 1),
                ],
                'lunch' => $this->pickPlanningListItem($mealPool, ($day - 1) * 2, $usedMealsByCity[$cityKey]),
                'afternoon' => [
                    'title' => "Afternoon in {$city}",
                    'activities' => $this->pickActivitiesForSlot($cityActivities, 'afternoon', 2, $day - 1),
                ],
            ];

            if ($isTransitionDay) {
                $flow['evening'] = [
                    'title' => "Travel to {$nextCity}",
                    'activities' => [
                        "Leave {$city} in the evening and continue toward {$nextCity} at an easy pace before dinner.",
                    ],
                ];
            } elseif ($day < $dayCount) {
                $flow['evening'] = [
                    'title' => "Evening in {$city}",
                    'activities' => $this->pickActivitiesForSlot($cityActivities, 'evening', 2, $day - 1),
                ];
            }

            $dinnerPool = $mealPool;
            $dinnerCityKey = $cityKey;
            if ($isTransitionDay) {
                $nextCityRestaurants = $this->filterItemsByCity($restaurants, $nextCityKey);
                if (!empty($nextCityRestaurants)) {
                    $dinnerPool = $nextCityRestaurants;
                    $dinnerCityKey = $nextCityKey;
                } else {
                    $dinnerPool = [];
                }
            }

            if (!isset($usedMealsByCity[$dinnerCityKey])) {
                $usedMealsByCity[$dinnerCityKey] = [];
            }

            $flow['dinner'] = $this->pickPlanningListItem($dinnerPool, (($day - 1) * 2) + 1, $usedMealsByCity[$dinnerCityKey]);

            if ($this->shouldIncludeStayOnDay($includeStay, $day, $dayCount)) {
                $stayCityKey = $isTransitionDay ? $nextCityKey : $cityKey;
                $stayCity = $isTransitionDay ? $nextCity : $city;
                $stay = $this->pickListItem($this->filterItemsByCity($hotels, $stayCityKey), 0);

                if ($stay) {
                    $flow['stay'] = $stay;
                } else {
                    $flow['stay'] = $this->buildUnavailableStaySlot($stayCity);
                }
            }

            $days[] = [
                'day' => $day,
                'location' => $city,
                'flow' => $flow,
            ];
        }

        $routeTitle = count($routeCityLabels) > 2
            ? implode(' to ', $routeCityLabels)
            : "{$startCity} to {$endCity}";

        return [
            'duration' => $duration,
            'title' => "{$dayCount}-Day Trip from {$routeTitle}",
            'summary' => "A route that eases through {$routeTitle} with grounded stops along the way.",
            'days' => $days,
        ];
    }

    private function pickActivitiesForSlot(array $activities, string $slot, int $limit = 2, int $offset = 0): array
    {
        if (!empty($activities) && $offset > 0) {
            $offset = $offset % count($activities);
            $activities = array_merge(
                array_slice($activities, $offset),
                array_slice($activities, 0, $offset)
            );
        }

        $picked = [];

        foreach ($activities as $activity) {
            $bestTime = $activity['best_time'] ?? 'any';

            if ($bestTime === $slot || $bestTime === 'any' || ($slot === 'evening' && $bestTime === 'sunset')) {
                $picked[] = $activity['name'];
            }

            if (count($picked) >= $limit) {
                break;
            }
        }

        if (empty($picked)) {
            return match ($slot) {
                'morning' => ['a relaxed walk and coffee'],
                'afternoon' => ['nearby streets and a local stop'],
                'evening' => ['sunset views or a calm local stop'],
                default => ['the local atmosphere'],
            };
        }

        return $picked;
    }

    private function pickListItem(array $items, int $index): ?array
    {
        if (empty($items)) {
            return null;
        }

        return $items[$index % count($items)] ?? null;
    }

    private function pickPlanningListItem(array $items, int $index, array &$usedIds): ?array
    {
        if (empty($items)) {
            return null;
        }

        $count = count($items);
        $start = $count > 0 ? $index % $count : 0;
        $rotated = array_merge(
            array_slice($items, $start),
            array_slice($items, 0, $start)
        );

        foreach ($rotated as $item) {
            $id = $item['id'] ?? null;

            if ($id === null || !in_array($id, $usedIds, true)) {
                if ($id !== null) {
                    $usedIds[] = $id;
                }

                return $item;
            }
        }

        $fallback = $rotated[0] ?? null;
        if ($fallback && isset($fallback['id'])) {
            $usedIds[] = $fallback['id'];
        }

        return $fallback;
    }

    private function diversifyRankedItems(Collection $items, callable $groupBy, int $max = 6): Collection
    {
        $picked = collect();
        $usedGroups = [];

        foreach ($items as $item) {
            $group = $groupBy($item);
            $group = $group ?: '__unknown__';

            if (!in_array($group, $usedGroups, true)) {
                $usedGroups[] = $group;
                $picked->push($item);
            }

            if ($picked->count() >= $max) {
                break;
            }
        }

        if ($picked->count() < $max) {
            foreach ($items as $item) {
                if ($picked->contains(fn ($pickedItem) => $pickedItem['item']->getKey() === $item['item']->getKey())) {
                    continue;
                }

                $picked->push($item);

                if ($picked->count() >= $max) {
                    break;
                }
            }
        }

        return $picked->sortByDesc('score')->values();
    }

    private function deduplicateRankedItems(Collection $items, callable $identityResolver): Collection
    {
        $seen = [];

        return $items->filter(function ($item) use (&$seen, $identityResolver) {
            $identity = $this->normalizeText((string) $identityResolver($item));
            if ($identity === '') {
                return true;
            }

            if (isset($seen[$identity])) {
                return false;
            }

            $seen[$identity] = true;

            return true;
        })->values();
    }

    private function filterWeakRankedItems(Collection $items, array $intent, string $category): Collection
    {
        if ($items->isEmpty()) {
            return $items;
        }

        $topScore = (float) ($items->first()['score'] ?? 0);
        if ($topScore <= 0) {
            return collect();
        }

        $baseThreshold = match ($category) {
            'hotels' => 22.0,
            'restaurants' => 20.0,
            'activities' => 16.0,
            default => 18.0,
        };

        $signalScore = (int) ($intent['signal_score'] ?? 0);

        if (!empty($intent['mentioned_cities'])) {
            $baseThreshold += 2;
        }

        if ($signalScore >= 3) {
            $baseThreshold -= 2;
        }

        if ($signalScore >= 5) {
            $baseThreshold -= 2;
        }

        if (!empty($intent['wants_trip_plan'])) {
            $baseThreshold -= 1;
        }

        $relativeThreshold = $topScore >= 35 ? $topScore * 0.55 : $topScore * 0.45;
        $threshold = (!empty($intent['wants_trip_plan']) && count((array) ($intent['mentioned_cities'] ?? [])) > 1)
            ? $baseThreshold
            : max($baseThreshold, $relativeThreshold);

        $items = $items
            ->filter(fn ($row) => (float) ($row['score'] ?? 0) >= $threshold)
            ->values();

        if ($category === 'restaurants') {
            $items = $this->filterRestaurantsByRequestedFood($items, $intent);
        }

        return $items;
    }

    private function applyNoCityActivityGuardrail(Collection $items, array $intent): Collection
    {
        if (
            $items->isEmpty()
            || !empty($intent['mentioned_cities'])
            || !empty($intent['wants_trip_plan'])
            || empty($intent['wants_activity'])
            || !empty($intent['wants_hotel'])
            || !empty($intent['wants_restaurant'])
            || !empty($intent['country_scope'])
        ) {
            return $items;
        }

        $signalScore = (int) ($intent['signal_score'] ?? 0);
        if ($signalScore >= 4) {
            return $items;
        }

        $dominantCity = $this->resolveDominantRankedCity(
            $items,
            fn ($activity) => $this->activityCityCandidates($activity),
            6
        );

        if ($dominantCity !== null) {
            $items = $this->filterRankedItemsByCity($items, 'activities', $dominantCity);
        }

        return $items->take($signalScore >= 2 ? 3 : 2)->values();
    }

    private function filterRestaurantsByRequestedFood(Collection $items, array $intent): Collection
    {
        if ($items->isEmpty() || !empty($intent['wants_trip_plan']) || empty($intent['food_preferences'])) {
            return $items;
        }

        $requestedFood = array_values(array_filter(
            (array) ($intent['food_preferences'] ?? []),
            fn ($value) => is_string($value) && trim($value) !== ''
        ));

        if (empty($requestedFood)) {
            return $items;
        }

        $matched = $items->filter(function ($row) use ($requestedFood) {
            $restaurant = $row['item'] ?? null;
            if (!$restaurant) {
                return false;
            }

            $profile = $this->buildRestaurantProfile($restaurant);
            $candidateConcepts = array_values(array_unique(array_merge(
                $profile['food_types'],
                $profile['semantic_concepts']
            )));

            return !empty(array_intersect($requestedFood, $candidateConcepts));
        })->values();

        if ($matched->isNotEmpty()) {
            return $matched;
        }

        return collect();
    }

    private function prioritizeTripCityCoverage(Collection $items, array $cities, callable $matchesCity, int $max): Collection
    {
        if ($items->isEmpty() || count($cities) < 2) {
            return $items;
        }

        $picked = collect();
        $pickedIds = [];

        foreach ($cities as $city) {
            $match = $items->first(function ($row) use ($matchesCity, $city, $pickedIds) {
                $item = $row['item'] ?? null;

                return $item
                    && !in_array($item->getKey(), $pickedIds, true)
                    && $matchesCity($item, $city);
            });

            if (!$match) {
                continue;
            }

            $picked->push($match);
            $pickedIds[] = $match['item']->getKey();
        }

        foreach ($items as $row) {
            $item = $row['item'] ?? null;
            if (!$item || in_array($item->getKey(), $pickedIds, true)) {
                continue;
            }

            $picked->push($row);
            $pickedIds[] = $item->getKey();

            if ($picked->count() >= $max) {
                break;
            }
        }

        return $picked->values();
    }

    private function fetchScopedHotels(array $cities): Collection
    {
        $hotelsQuery = Hotel::query();
        $this->applyHotelCityScope($hotelsQuery, $cities);

        return $hotelsQuery->limit(150)->get();
    }

    private function buildHotelIdentityKey($hotel): string
    {
        return implode('|', array_filter([
            $this->normalizeText((string) ($hotel->hotel_name ?? '')),
            $this->normalizeText((string) ($hotel->address ?? '')),
            $this->normalizeText((string) ($this->hotelCityCandidates($hotel)[0] ?? '')),
        ]));
    }

    private function buildRestaurantIdentityKey($restaurant): string
    {
        return implode('|', array_filter([
            $this->normalizeText((string) ($restaurant->restaurant_name ?? '')),
            $this->normalizeText((string) ($restaurant->location ?? '')),
            $this->normalizeText((string) ($this->restaurantCityCandidates($restaurant)[0] ?? '')),
        ]));
    }

    private function buildActivityIdentityKey($activity): string
    {
        return implode('|', array_filter([
            $this->normalizeText((string) ($activity->name ?? '')),
            $this->normalizeText((string) ($activity->location ?? '')),
            $this->normalizeText((string) ($activity->city ?? '')),
        ]));
    }

    private function transformHotel($hotel, float $score, array $reasons): array
    {
        return [
            'id' => $hotel->id,
            'hotel_name' => $hotel->hotel_name,
            'city' => $this->hotelCityCandidates($hotel)[0] ?? null,
            'address' => $hotel->address,
            'distance_from_beach' => $hotel->distance_from_beach,
            'rating_score' => $hotel->rating_score,
            'review_count' => $hotel->review_count,
            'price_per_night' => $hotel->price_per_night,
            'budget_tier' => $this->normalizeHotelBudgetTier($hotel->price_per_night),
            'description' => $hotel->description,
            'stay_details' => $hotel->stay_details,
            'vibe_tags' => $hotel->vibe_tags,
            'audience_tags' => $hotel->audience_tags,
            'score' => $score,
            'primary_reason' => $reasons[0] ?? null,
            'top_reasons' => array_slice($reasons, 0, 3),
            'match_reasons' => $reasons,
        ];
    }

    private function stabilizeRecommendationPayload(array $hotels, array $restaurants, array $activities, ?array $tripPlan): array
    {
        $hotels = $this->deduplicateTransformedItems(
            array_map(fn (array $hotel) => $this->sanitizeHotelPayload($hotel), $hotels),
            fn (array $hotel) => $this->buildTransformedIdentityKey(
                (string) ($hotel['hotel_name'] ?? ''),
                (string) ($hotel['address'] ?? ''),
                (string) ($hotel['city'] ?? '')
            )
        );

        $restaurants = $this->deduplicateTransformedItems(
            array_map(fn (array $restaurant) => $this->sanitizeRestaurantPayload($restaurant), $restaurants),
            fn (array $restaurant) => $this->buildTransformedIdentityKey(
                (string) ($restaurant['restaurant_name'] ?? ''),
                (string) ($restaurant['location'] ?? ''),
                (string) ($restaurant['city'] ?? '')
            )
        );

        $activities = $this->deduplicateTransformedItems(
            array_map(fn (array $activity) => $this->sanitizeActivityPayload($activity), $activities),
            fn (array $activity) => $this->buildTransformedIdentityKey(
                (string) ($activity['name'] ?? ''),
                (string) ($activity['location'] ?? ''),
                (string) ($activity['city'] ?? '')
            )
        );

        return [$hotels, $restaurants, $activities, $this->sanitizeTripPlan($tripPlan)];
    }

    private function deduplicateTransformedItems(array $items, callable $identityResolver): array
    {
        $unique = [];
        $seen = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $identity = $this->normalizeText((string) $identityResolver($item));
            if ($identity !== '' && isset($seen[$identity])) {
                continue;
            }

            if ($identity !== '') {
                $seen[$identity] = true;
            }

            $unique[] = $item;
        }

        return array_values($unique);
    }

    private function buildTransformedIdentityKey(string $name, string $location, string $city = ''): string
    {
        return implode('|', array_filter([
            $this->normalizeText($name),
            $this->normalizeText($location),
            $this->normalizeText($city),
        ]));
    }

    private function sanitizeHotelPayload(array $hotel): array
    {
        $hotel['hotel_name'] = $this->sanitizeDisplayText((string) ($hotel['hotel_name'] ?? ''));
        $hotel['address'] = $this->cleanDisplayLocation($hotel['hotel_name'], (string) ($hotel['address'] ?? ''));
        $hotel['distance_from_beach'] = $this->sanitizeDisplayText((string) ($hotel['distance_from_beach'] ?? ''));
        $hotel['price_per_night'] = $this->sanitizeDisplayText((string) ($hotel['price_per_night'] ?? ''));
        $hotel['description'] = $this->sanitizeDisplayText((string) ($hotel['description'] ?? ''));
        $hotel['stay_details'] = $this->sanitizeDisplayText((string) ($hotel['stay_details'] ?? ''));
        $hotel['vibe_tags'] = $this->sanitizeDisplayList($hotel['vibe_tags'] ?? []);
        $hotel['audience_tags'] = $this->sanitizeDisplayList($hotel['audience_tags'] ?? []);
        $hotel['match_reasons'] = $this->sanitizeDisplayList($hotel['match_reasons'] ?? []);
        $hotel['top_reasons'] = array_slice($hotel['match_reasons'], 0, 3);
        $hotel['primary_reason'] = $hotel['match_reasons'][0] ?? null;

        return $hotel;
    }

    private function sanitizeRestaurantPayload(array $restaurant): array
    {
        $restaurant['restaurant_name'] = $this->sanitizeDisplayText((string) ($restaurant['restaurant_name'] ?? ''));
        $restaurant['location'] = $this->cleanDisplayLocation($restaurant['restaurant_name'], (string) ($restaurant['location'] ?? ''));
        $restaurant['food_type'] = $this->sanitizeDisplayText((string) ($restaurant['food_type'] ?? ''));
        $restaurant['price_tier'] = $this->sanitizeDisplayText((string) ($restaurant['price_tier'] ?? ''));
        $restaurant['description'] = $this->sanitizeDisplayText((string) ($restaurant['description'] ?? ''));
        $restaurant['opening_hours'] = $this->sanitizeDisplayText((string) ($restaurant['opening_hours'] ?? ''));
        $restaurant['vibe_tags'] = $this->sanitizeDisplayList($restaurant['vibe_tags'] ?? []);
        $restaurant['occasion_tags'] = $this->sanitizeDisplayList($restaurant['occasion_tags'] ?? []);
        $restaurant['match_reasons'] = $this->sanitizeDisplayList($restaurant['match_reasons'] ?? []);
        $restaurant['top_reasons'] = array_slice($restaurant['match_reasons'], 0, 3);
        $restaurant['primary_reason'] = $restaurant['match_reasons'][0] ?? null;

        return $restaurant;
    }

    private function sanitizeActivityPayload(array $activity): array
    {
        $activity['name'] = $this->sanitizeDisplayText((string) ($activity['name'] ?? ''));
        $activity['location'] = $this->sanitizeDisplayText((string) ($activity['location'] ?? ''));
        $activity['description'] = $this->sanitizeDisplayText((string) ($activity['description'] ?? ''));
        $activity['best_time'] = $this->sanitizeDisplayText((string) ($activity['best_time'] ?? ''));
        $activity['duration_estimate'] = $this->sanitizeDisplayText((string) ($activity['duration_estimate'] ?? ''));
        $activity['price_type'] = $this->sanitizeDisplayText((string) ($activity['price_type'] ?? ''));
        $activity['vibe_tags'] = $this->sanitizeDisplayList($activity['vibe_tags'] ?? []);
        $activity['occasion_tags'] = $this->sanitizeDisplayList($activity['occasion_tags'] ?? []);
        $activity['match_reasons'] = $this->sanitizeDisplayList($activity['match_reasons'] ?? []);
        $activity['top_reasons'] = array_slice($activity['match_reasons'], 0, 3);
        $activity['primary_reason'] = $activity['match_reasons'][0] ?? null;

        return $activity;
    }

    private function sanitizeTripPlan(?array $tripPlan): ?array
    {
        if (!is_array($tripPlan)) {
            return null;
        }

        $tripPlan['title'] = $this->sanitizeDisplayText((string) ($tripPlan['title'] ?? ''));
        $tripPlan['summary'] = $this->sanitizeDisplayText((string) ($tripPlan['summary'] ?? ''));

        $tripPlan['days'] = array_values(array_map(function ($day) {
            if (!is_array($day)) {
                return $day;
            }

            $day['location'] = $this->sanitizeDisplayText((string) ($day['location'] ?? ''));
            $flow = is_array($day['flow'] ?? null) ? $day['flow'] : [];

            foreach ($flow as $slot => $value) {
                if (is_array($value) && isset($value['hotel_name'])) {
                    $value['hotel_name'] = $this->sanitizeDisplayText((string) ($value['hotel_name'] ?? ''));
                    $value['address'] = $this->cleanDisplayLocation($value['hotel_name'], (string) ($value['address'] ?? ''));
                    $value['price_per_night'] = $this->sanitizeDisplayText((string) ($value['price_per_night'] ?? ''));
                    $flow[$slot] = $value;

                    continue;
                }

                if (is_array($value) && isset($value['restaurant_name'])) {
                    $value['restaurant_name'] = $this->sanitizeDisplayText((string) ($value['restaurant_name'] ?? ''));
                    $value['location'] = $this->cleanDisplayLocation($value['restaurant_name'], (string) ($value['location'] ?? ''));
                    $value['food_type'] = $this->sanitizeDisplayText((string) ($value['food_type'] ?? ''));
                    $value['price_tier'] = $this->sanitizeDisplayText((string) ($value['price_tier'] ?? ''));
                    $flow[$slot] = $value;

                    continue;
                }

                if (is_array($value)) {
                    $value['title'] = $this->sanitizeDisplayText((string) ($value['title'] ?? ''));
                    $value['activities'] = $this->sanitizeDisplayList($value['activities'] ?? []);
                    $flow[$slot] = $value;

                    continue;
                }

                if (is_string($value)) {
                    $flow[$slot] = $this->sanitizeDisplayText($value);
                }
            }

            $day['flow'] = $flow;

            return $day;
        }, is_array($tripPlan['days'] ?? null) ? $tripPlan['days'] : []));

        return $tripPlan;
    }

    private function sanitizeDisplayText(string $value): string
    {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = str_replace(
            ['â€™', 'â€˜', 'â€œ', 'â€', 'â€“', 'â€”', 'Ã©', 'Ã¨', 'Ã¢â‚¬Â¢', 'ØŒ', 'Â'],
            ["'", "'", '"', '"', '-', '-', 'e', 'e', '', ',', ''],
            $value
        );
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);
        $value = str_replace('.;', '.', $value);
        $value = preg_replace('/;\s*\./', '.', $value) ?? $value;
        $value = preg_replace('/\.\.+/', '.', $value) ?? $value;

        return trim($value, " \t\n\r\0\x0B,;");
    }

    private function sanitizeDisplayList(array|string|null $values): array
    {
        if (is_string($values)) {
            $values = [$values];
        }

        $cleaned = [];
        $seen = [];

        foreach ((array) $values as $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $text = $this->sanitizeDisplayText((string) $value);
            $identity = $this->normalizeText($text);

            if ($text === '' || $identity === '' || isset($seen[$identity])) {
                continue;
            }

            $seen[$identity] = true;
            $cleaned[] = $text;
        }

        return array_values($cleaned);
    }

    private function cleanDisplayLocation(string $name, string $location): string
    {
        $location = $this->sanitizeDisplayText($location);
        if ($location === '') {
            return '';
        }

        $parts = array_values(array_filter(array_map(
            fn ($part) => $this->sanitizeDisplayText((string) $part),
            explode(',', $location)
        )));

        if (empty($parts)) {
            return $location;
        }

        $normalizedName = $this->normalizeText($name);
        $firstPart = $this->normalizeText($parts[0]);

        if (
            $normalizedName !== ''
            && (
                $firstPart === $normalizedName
                || str_starts_with($firstPart, $normalizedName)
                || str_starts_with($normalizedName, $firstPart)
                || $this->locationPrefixContainsNameTokens($normalizedName, $firstPart)
            )
        ) {
            array_shift($parts);
        }

        return trim(implode(', ', $parts), " \t\n\r\0\x0B,;");
    }

    private function locationPrefixContainsNameTokens(string $normalizedName, string $normalizedLocationPrefix): bool
    {
        $nameTokens = array_values(array_filter(explode(' ', $normalizedName)));
        $locationTokens = array_values(array_filter(explode(' ', $normalizedLocationPrefix)));

        if (count($nameTokens) < 2 || empty($locationTokens)) {
            return false;
        }

        return empty(array_diff($nameTokens, $locationTokens));
    }

    private function transformRestaurant($restaurant, float $score, array $reasons): array
    {
        return [
            'id' => $restaurant->id,
            'restaurant_name' => $restaurant->restaurant_name,
            'city' => $this->restaurantCityCandidates($restaurant)[0] ?? null,
            'location' => $restaurant->location,
            'rating' => $restaurant->rating,
            'food_type' => $restaurant->food_type,
            'price_tier' => $restaurant->price_tier,
            'budget_tier' => $this->normalizeRestaurantBudgetTier($restaurant->price_tier),
            'description' => $restaurant->description,
            'opening_hours' => $restaurant->opening_hours,
            'vibe_tags' => $restaurant->vibe_tags,
            'occasion_tags' => $restaurant->occasion_tags,
            'score' => $score,
            'primary_reason' => $reasons[0] ?? null,
            'top_reasons' => array_slice($reasons, 0, 3),
            'match_reasons' => $reasons,
        ];
    }

    private function transformActivity($activity, float $score, array $reasons): array
    {
        return [
            'name' => $activity->name,
            'city' => $activity->city,
            'category' => $activity->category,
            'description' => $activity->description,
            'location' => $activity->location,
            'best_time' => $activity->best_time,
            'duration_estimate' => $activity->duration_estimate,
            'price_type' => $activity->price_type,
            'budget_tier' => $this->normalizeActivityBudgetTier($activity->price_type),
            'vibe_tags' => $activity->vibe_tags,
            'occasion_tags' => $activity->occasion_tags,
            'score' => $score,
            'primary_reason' => $reasons[0] ?? null,
            'top_reasons' => array_slice($reasons, 0, 3),
            'match_reasons' => $reasons,
        ];
    }

    private function buildDiagnostics(array $intent, array $hotels, array $restaurants, array $activities, ?array $tripPlan): array
    {
        $displayCities = !empty($intent['mentioned_cities'])
            ? (array) ($intent['mentioned_cities'] ?? [])
            : array_values(array_filter([
                $intent['resolved_city'] ?? null,
            ]));

        return [
            'summary_chips' => $this->buildIntentSummaryChips($intent),
            'guidance' => [
                'should_hold_results' => (bool) ($intent['should_hold_results'] ?? false),
                'reason' => $this->buildGuidanceReason($intent),
                'follow_up_hints' => $this->buildGuidanceHints($intent),
                'fallback_reply' => $this->buildGuidanceFallbackReply($intent),
            ],
            'confidence' => [
                'signal_score' => (int) ($intent['signal_score'] ?? 0),
                'overall' => $this->buildOverallConfidenceLabel($intent, $hotels, $restaurants, $activities),
                'hotels' => $this->buildCategoryConfidence($hotels),
                'restaurants' => $this->buildCategoryConfidence($restaurants),
                'activities' => $this->buildCategoryConfidence($activities),
            ],
            'tone' => [
                'response_tone' => $intent['response_tone'] ?? 'neutral',
            ],
            'intent_overview' => array_filter([
                'message_type' => $intent['message_type'] ?? null,
                'cities' => array_map(fn ($city) => ucfirst((string) $city), $displayCities),
                'budget' => isset($intent['budget']) ? $this->humanizeLabel((string) $intent['budget']) : null,
                'budget_ceiling' => isset($intent['budget_max']) && $intent['budget_max'] ? '$' . $intent['budget_max'] : null,
                'day_count' => $intent['day_count'] ?? null,
                'food_preferences' => $this->humanizeList($intent['food_preferences'] ?? []),
                'vibe_tags' => $this->humanizeList($intent['vibe_tags'] ?? []),
                'activity_types' => $this->humanizeList($intent['activity_types'] ?? []),
                'requested_categories' => $this->humanizeList($intent['requested_categories'] ?? []),
            ], fn ($value) => !($value === null || $value === '' || $value === [])),
            'result_counts' => [
                'hotels' => count($hotels),
                'restaurants' => count($restaurants),
                'activities' => count($activities),
                'trip_days' => is_array($tripPlan['days'] ?? null) ? count($tripPlan['days']) : 0,
            ],
            'top_matches' => array_filter([
                'hotel' => $this->buildTopMatchDigest($hotels[0] ?? null, 'hotel_name'),
                'restaurant' => $this->buildTopMatchDigest($restaurants[0] ?? null, 'restaurant_name'),
                'activity' => $this->buildTopMatchDigest($activities[0] ?? null, 'name'),
                'trip_plan' => is_array($tripPlan) ? [
                    'title' => trim((string) ($tripPlan['title'] ?? '')),
                    'days' => is_array($tripPlan['days'] ?? null) ? count($tripPlan['days']) : 0,
                ] : null,
            ]),
        ];
    }

    private function buildIntentSummaryChips(array $intent): array
    {
        $chips = [];
        $summaryCities = !empty($intent['mentioned_cities'])
            ? (array) ($intent['mentioned_cities'] ?? [])
            : array_values(array_filter([
                $intent['resolved_city'] ?? null,
            ]));

        if (!empty($intent['should_hold_results'])) {
            $chips[] = 'Need more detail';
        }

        if (!empty($summaryCities)) {
            $chips[] = 'City: ' . implode(' / ', array_map(fn ($city) => ucfirst((string) $city), $summaryCities));
        }

        if (!empty($intent['day_count'])) {
            $chips[] = 'Duration: ' . $intent['day_count'] . ' day' . ((int) $intent['day_count'] === 1 ? '' : 's');
        }

        if (!empty($intent['budget'])) {
            $chips[] = 'Budget: ' . ucfirst($this->humanizeLabel((string) $intent['budget']));
        }

        if (!empty($intent['budget_max'])) {
            $chips[] = 'Max: $' . $intent['budget_max'];
        }

        if (!empty($intent['food_preferences'])) {
            $chips[] = 'Food: ' . ucfirst($this->humanizeLabel((string) $intent['food_preferences'][0]));
        }

        if (!empty($intent['vibe_tags'])) {
            $chips[] = 'Vibe: ' . implode(', ', array_map(
                fn ($tag) => ucfirst($this->humanizeLabel((string) $tag)),
                array_slice($intent['vibe_tags'], 0, 2)
            ));
        }

        if (!empty($intent['requested_categories'])) {
            $chips[] = 'Looking for: ' . implode(', ', array_map(
                fn ($category) => ucfirst($this->humanizeLabel((string) $category)),
                array_slice($intent['requested_categories'], 0, 3)
            ));
        }

        return array_values(array_unique($chips));
    }

    private function buildGuidanceReason(array $intent): string
    {
        if (empty($intent['should_hold_results'])) {
            return 'ready';
        }

        if (empty($intent['has_travel_signal'])) {
            return 'no_travel_signal';
        }

        if (empty($intent['mentioned_cities']) && empty($intent['signal_score']) && empty($intent['wants_trip_plan'])) {
            return 'category_only';
        }

        if (!empty($intent['wants_trip_plan']) && empty($intent['mentioned_cities'])) {
            return 'missing_trip_anchor';
        }

        if (empty($intent['mentioned_cities'])) {
            return 'missing_location_anchor';
        }

        return 'ready';
    }

    private function buildGuidanceHints(array $intent): array
    {
        if (empty($intent['should_hold_results'])) {
            return [];
        }

        if (!empty($intent['wants_trip_plan'])) {
            return [
                'Add a city or route',
                'Mention how many days you have',
                'Describe the vibe or budget',
            ];
        }

        if (!empty($intent['requires_stay']) || !empty($intent['wants_hotel'])) {
            return [
                'Add a city',
                'Mention your budget',
                'Describe the stay style you want',
            ];
        }

        if (!empty($intent['wants_restaurant'])) {
            return [
                'Add a city',
                'Mention the mood or food type',
                'Say if it is for lunch, dinner, or a date',
            ];
        }

        return [
            'Add a city',
            'Mention your vibe, budget, or trip length',
            'Say whether you want a hotel, restaurant, places to visit, or a full plan',
        ];
    }

    private function buildGuidanceFallbackReply(array $intent): string
    {
        if (empty($intent['should_hold_results'])) {
            return '';
        }

        if (!empty($intent['wants_trip_plan'])) {
            return 'I can build a real trip plan once you give me a city or route, your trip length, and the kind of vibe or budget you want.';
        }

        if (!empty($intent['requires_stay']) || !empty($intent['wants_hotel'])) {
            return 'I can help with stays once you tell me the city and the kind of place you want, like budget, seaside, romantic, or family-friendly.';
        }

        if (!empty($intent['wants_restaurant'])) {
            return 'I can recommend food spots once you tell me the city and the mood you want, like seafood, date night, casual lunch, or budget-friendly.';
        }

        return 'Tell me a city, budget, trip length, or the kind of vibe you want, and I will narrow the recommendations down properly.';
    }

    private function buildCategoryConfidence(array $items): array
    {
        $topScore = isset($items[0]['score']) ? round((float) $items[0]['score'], 2) : null;

        return [
            'count' => count($items),
            'top_score' => $topScore,
            'level' => match (true) {
                $topScore === null => 'none',
                $topScore >= 72 => 'high',
                $topScore >= 48 => 'medium',
                default => 'low',
            },
        ];
    }

    private function buildOverallConfidenceLabel(array $intent, array $hotels, array $restaurants, array $activities): string
    {
        if (!empty($intent['should_hold_results'])) {
            return 'needs_guidance';
        }

        $topScore = max(
            (float) ($hotels[0]['score'] ?? 0),
            (float) ($restaurants[0]['score'] ?? 0),
            (float) ($activities[0]['score'] ?? 0),
        );

        return match (true) {
            $topScore >= 72 => 'high',
            $topScore >= 48 => 'medium',
            $topScore > 0 => 'low',
            default => 'none',
        };
    }

    private function buildTopMatchDigest(?array $item, string $nameKey): ?array
    {
        if (!is_array($item)) {
            return null;
        }

        $name = trim((string) ($item[$nameKey] ?? ''));
        if ($name === '') {
            return null;
        }

        return [
            'name' => $name,
            'score' => isset($item['score']) ? round((float) $item['score'], 2) : null,
            'reasons' => array_values(array_slice(array_filter(
                (array) ($item['top_reasons'] ?? $item['match_reasons'] ?? []),
                fn ($reason) => is_string($reason) && trim($reason) !== ''
            ), 0, 3)),
        ];
    }

    private function humanizeList(array $values): array
    {
        return array_values(array_map(
            fn ($value) => ucfirst($this->humanizeLabel((string) $value)),
            array_filter($values, fn ($value) => is_scalar($value) && trim((string) $value) !== '')
        ));
    }

    private function buildHotelProfile($hotel): array
    {
        $text = $this->normalizeText(implode(' ', array_filter([
            $hotel->hotel_name,
            $hotel->address,
            $hotel->nearby_landmark,
            $hotel->description,
            $hotel->stay_details,
            $hotel->search_text,
        ])));

        $vibeTags = $this->normalizeConceptList($hotel->vibe_tags ?? [], $this->vibeConceptMap());
        $audienceTags = $this->normalizeConceptList($hotel->audience_tags ?? [], $this->audienceConceptMap());

        return [
            'cities' => $this->hotelCityCandidates($hotel),
            'text' => $text,
            'vibe_tags' => $vibeTags,
            'audience_tags' => $audienceTags,
            'semantic_concepts' => array_values(array_unique(array_merge(
                $vibeTags,
                $audienceTags,
                $this->extractConceptMatches($text, $this->semanticConceptMap())
            ))),
            'budget_tier' => $this->normalizeHotelBudgetTier($hotel->price_per_night),
            'price_value' => $this->extractMoneyValue($hotel->price_per_night),
            'distance_from_beach_km' => $this->extractDistanceKm($hotel->distance_from_beach),
            'rating' => (float) ($hotel->rating_score ?? 0),
            'review_count' => (int) ($hotel->review_count ?? 0),
        ];
    }

    private function buildRestaurantProfile($restaurant): array
    {
        $tagText = implode(' ', $this->normalizeTagArray($restaurant->tags ?? []));
        $text = $this->normalizeText(implode(' ', array_filter([
            $restaurant->restaurant_name,
            $restaurant->location,
            $restaurant->restaurant_type,
            $restaurant->food_type,
            $restaurant->description,
            $tagText,
            $restaurant->search_text,
        ])));

        $vibeTags = $this->normalizeConceptList($restaurant->vibe_tags ?? [], $this->vibeConceptMap());
        $occasionTags = $this->normalizeConceptList($restaurant->occasion_tags ?? [], $this->occasionConceptMap());
        $foodTypes = $this->normalizeConceptList($this->normalizeMultiValueString($restaurant->food_type), $this->foodConceptMap());

        return [
            'cities' => $this->restaurantCityCandidates($restaurant),
            'text' => $text,
            'vibe_tags' => $vibeTags,
            'occasion_tags' => $occasionTags,
            'food_types' => $foodTypes,
            'semantic_concepts' => array_values(array_unique(array_merge(
                $vibeTags,
                $occasionTags,
                $foodTypes,
                $this->extractConceptMatches($text, $this->semanticConceptMap())
            ))),
            'budget_tier' => $this->normalizeRestaurantBudgetTier($restaurant->price_tier),
            'rating' => (float) ($restaurant->rating ?? 0),
        ];
    }

    private function buildActivityProfile($activity): array
    {
        $text = $this->normalizeText(implode(' ', array_filter([
            $activity->name,
            $activity->city,
            $activity->category,
            $activity->description,
            $activity->location,
            $activity->best_time,
            $activity->search_text,
        ])));

        $vibeTags = $this->normalizeConceptList($activity->vibe_tags ?? [], $this->vibeConceptMap());
        $occasionTags = $this->normalizeConceptList($activity->occasion_tags ?? [], $this->occasionConceptMap());
        $activityTypes = $this->normalizeConceptList([$activity->category], $this->activityConceptMap());
        $timeTags = $this->normalizeConceptList([$activity->best_time], $this->timeConceptMap());

        return [
            'cities' => $this->activityCityCandidates($activity),
            'text' => $text,
            'vibe_tags' => $vibeTags,
            'occasion_tags' => $occasionTags,
            'activity_types' => $activityTypes,
            'time_tags' => $timeTags,
            'semantic_concepts' => array_values(array_unique(array_merge(
                $vibeTags,
                $occasionTags,
                $activityTypes,
                $timeTags,
                $this->extractConceptMatches($text, $this->semanticConceptMap())
            ))),
            'budget_tier' => $this->normalizeActivityBudgetTier($activity->price_type),
        ];
    }

    private function shouldIncludeCategory(array $intent, string $category): bool
    {
        if ($category === 'hotels') {
            return !empty($intent['requires_stay']) || !empty($intent['wants_hotel']);
        }

        return !empty($intent['wants_trip_plan'])
            || in_array($category, $intent['requested_categories'] ?? [], true);
    }

    private function deriveMessageType(array $intent): string
    {
        if (!empty($intent['should_hold_results'])) {
            return 'guidance';
        }

        if (!empty($intent['wants_trip_plan'])) {
            return 'trip_plan';
        }

        $wantsHotel = !empty($intent['wants_hotel']) || !empty($intent['requires_stay']);
        $wantsRestaurant = !empty($intent['wants_restaurant']);
        $wantsActivity = !empty($intent['wants_activity']);

        return match (true) {
            $wantsHotel && !$wantsRestaurant && !$wantsActivity => 'hotel_recommendation',
            $wantsRestaurant && !$wantsHotel && !$wantsActivity => 'restaurant_recommendation',
            $wantsActivity && !$wantsHotel && !$wantsRestaurant => 'activity_recommendation',
            default => 'mixed_recommendation',
        };
    }

    private function shouldPreferCityDiversity(array $intent): bool
    {
        return empty($intent['mentioned_cities'])
            && empty($intent['wants_trip_plan'])
            && empty($intent['should_hold_results']);
    }

    private function shouldIncludeStayOnDay(bool $includeStay, int $day, int $dayCount): bool
    {
        if (!$includeStay) {
            return false;
        }

        if ($dayCount <= 1) {
            return true;
        }

        return $day < $dayCount;
    }

    private function filterItemsByCity(array $items, ?string $city): array
    {
        $city = $this->normalizeText($city);
        if ($city === '') {
            return array_values($items);
        }

        return array_values(array_filter($items, function ($item) use ($city) {
            return is_array($item) && $this->normalizeText((string) ($item['city'] ?? '')) === $city;
        }));
    }

    private function hotelCityCandidates($hotel): array
    {
        return $this->extractMentionedCities(implode(' ', array_filter([
            $hotel->hotel_name,
            $hotel->address,
            $hotel->nearby_landmark,
            $hotel->description,
            $hotel->stay_details,
            $hotel->search_text,
        ])));
    }

    private function resolveImplicitTripCity(
        array $intent,
        Collection $hotels,
        Collection $restaurants,
        Collection $activities
    ): ?string {
        if (
            empty($intent['wants_trip_plan'])
            || !empty($intent['mentioned_cities'])
            || !empty($intent['start_city'])
            || !empty($intent['end_city'])
        ) {
            return null;
        }

        $cityScores = [];
        $cityCategories = [];

        $this->addRankedCitySignals(
            $cityScores,
            $cityCategories,
            $hotels,
            fn ($hotel) => $this->hotelCityCandidates($hotel),
            'hotels',
            !empty($intent['requires_stay']) ? 1.15 : 0.9,
            4
        );
        $this->addRankedCitySignals(
            $cityScores,
            $cityCategories,
            $restaurants,
            fn ($restaurant) => $this->restaurantCityCandidates($restaurant),
            'restaurants',
            1.0,
            6
        );
        $this->addRankedCitySignals(
            $cityScores,
            $cityCategories,
            $activities,
            fn ($activity) => $this->activityCityCandidates($activity),
            'activities',
            0.9,
            8
        );

        if (empty($cityScores)) {
            return null;
        }

        uksort($cityScores, function (string $left, string $right) use ($cityScores, $cityCategories) {
            $leftCategoryCount = count($cityCategories[$left] ?? []);
            $rightCategoryCount = count($cityCategories[$right] ?? []);

            if ($leftCategoryCount !== $rightCategoryCount) {
                return $rightCategoryCount <=> $leftCategoryCount;
            }

            return ($cityScores[$right] ?? 0) <=> ($cityScores[$left] ?? 0);
        });

        $topCity = array_key_first($cityScores);
        $runnerUpCity = array_keys($cityScores)[1] ?? null;

        if (!$this->implicitTripCityLooksReliable($topCity, $runnerUpCity, $cityScores, $cityCategories, $intent)) {
            return null;
        }

        return $topCity;
    }

    private function implicitTripCityLooksReliable(
        string $topCity,
        ?string $runnerUpCity,
        array $cityScores,
        array $cityCategories,
        array $intent
    ): bool {
        $topCategories = $cityCategories[$topCity] ?? [];
        $topCategoryCount = count($topCategories);
        $topScore = (float) ($cityScores[$topCity] ?? 0);
        $signalScore = (int) ($intent['signal_score'] ?? 0);
        $requiresStay = !empty($intent['requires_stay']);
        $isFlexibleOneDayPlan = !$requiresStay
            && (int) ($intent['day_count'] ?? 0) === 1
            && !empty($intent['can_plan_without_explicit_city']);
        $minimumCategoryCount = ($requiresStay && $signalScore <= 3) ? 3 : 2;
        $minimumScore = $isFlexibleOneDayPlan ? 55.0 : ($signalScore <= 3 ? 100.0 : 90.0);

        if ($requiresStay && empty($topCategories['hotels'])) {
            return false;
        }

        if ($topCategoryCount < $minimumCategoryCount || $topScore < $minimumScore) {
            return false;
        }

        if ($runnerUpCity === null) {
            return true;
        }

        $runnerUpCategoryCount = count($cityCategories[$runnerUpCity] ?? []);
        $runnerUpScore = (float) ($cityScores[$runnerUpCity] ?? 0);
        $requiredGap = $isFlexibleOneDayPlan ? 6.0 : ($signalScore <= 3 ? 14.0 : 8.0);

        if ($runnerUpCategoryCount >= $topCategoryCount && ($topScore - $runnerUpScore) < $requiredGap) {
            return false;
        }

        return true;
    }

    private function addRankedCitySignals(
        array &$cityScores,
        array &$cityCategories,
        Collection $items,
        callable $cityResolver,
        string $category,
        float $weight,
        int $limit
    ): void {
        foreach ($items->take($limit)->values() as $index => $row) {
            $item = $row['item'] ?? null;
            if ($item === null) {
                continue;
            }

            $city = $this->normalizeText((string) (($cityResolver($item)[0] ?? null) ?? ''));
            if ($city === '') {
                continue;
            }

            $positionBonus = max(0, 8 - ($index * 1.5));
            $cityScores[$city] = ($cityScores[$city] ?? 0.0) + (((float) ($row['score'] ?? 0)) * $weight) + $positionBonus;
            $cityCategories[$city][$category] = true;
        }
    }

    private function resolveDominantRankedCity(Collection $items, callable $cityResolver, int $limit = 6): ?string
    {
        $cityScores = [];

        foreach ($items->take($limit)->values() as $index => $row) {
            $item = $row['item'] ?? null;
            if ($item === null) {
                continue;
            }

            $city = $this->normalizeText((string) (($cityResolver($item)[0] ?? null) ?? ''));
            if ($city === '') {
                continue;
            }

            $positionBonus = max(0, 6 - ($index * 1.25));
            $cityScores[$city] = ($cityScores[$city] ?? 0.0) + ((float) ($row['score'] ?? 0)) + $positionBonus;
        }

        if (empty($cityScores)) {
            return null;
        }

        arsort($cityScores);

        return array_key_first($cityScores);
    }

    private function filterRankedItemsByCity(Collection $items, string $category, string $city): Collection
    {
        $city = $this->normalizeText($city);
        if ($city === '' || $items->isEmpty()) {
            return $items;
        }

        return $items->filter(function ($row) use ($category, $city) {
            $item = $row['item'] ?? null;

            return $item !== null && $this->rankedItemMatchesCity($item, $category, $city);
        })->values();
    }

    private function filterRankedItemsToExplicitCities(Collection $items, string $category, array $intent): Collection
    {
        $cities = array_values(array_unique(array_filter(array_map(
            fn ($city) => $this->normalizeText((string) $city),
            $intent['mentioned_cities'] ?? []
        ))));

        if ($items->isEmpty() || empty($cities)) {
            return $items;
        }

        return $items->filter(function ($row) use ($category, $cities) {
            $item = $row['item'] ?? null;
            if ($item === null) {
                return false;
            }

            $itemCities = array_map(
                fn ($city) => $this->normalizeText((string) $city),
                $this->rankedItemCityCandidates($item, $category)
            );

            return !empty(array_intersect($cities, $itemCities));
        })->values();
    }

    private function filterRankedItemsExcludingCities(Collection $items, string $category, array $excludedCities): Collection
    {
        $excludedCities = array_values(array_unique(array_filter(array_map(
            fn ($city) => $this->normalizeText((string) $city),
            $excludedCities
        ))));

        if ($items->isEmpty() || empty($excludedCities)) {
            return $items;
        }

        return $items->reject(function ($row) use ($category, $excludedCities) {
            $item = $row['item'] ?? null;
            if ($item === null) {
                return false;
            }

            $itemCities = array_map(
                fn ($city) => $this->normalizeText((string) $city),
                $this->rankedItemCityCandidates($item, $category)
            );

            return !empty(array_intersect($excludedCities, $itemCities));
        })->values();
    }

    private function rankedItemMatchesCity($item, string $category, string $city): bool
    {
        $candidates = $this->rankedItemCityCandidates($item, $category);

        return in_array($city, array_map(fn ($candidate) => $this->normalizeText((string) $candidate), $candidates), true);
    }

    private function rankedItemCityCandidates($item, string $category): array
    {
        return match ($category) {
            'hotels' => $this->hotelCityCandidates($item),
            'restaurants' => $this->restaurantCityCandidates($item),
            'activities' => $this->activityCityCandidates($item),
            default => [],
        };
    }

    private function restaurantCityCandidates($restaurant): array
    {
        return $this->extractMentionedCities(implode(' ', array_filter([
            $restaurant->restaurant_name,
            $restaurant->location,
            $restaurant->restaurant_type,
            $restaurant->description,
            $restaurant->search_text,
        ])));
    }

    private function activityCityCandidates($activity): array
    {
        return array_values(array_unique(array_filter(array_merge(
            array_filter([$this->normalizeText($activity->city ?? '')]),
            $this->extractMentionedCities(implode(' ', array_filter([
                $activity->name,
                $activity->city,
                $activity->location,
                $activity->description,
                $activity->search_text,
            ])))
        ))));
    }

    private function applyHotelCityScope(Builder $query, array $cities): void
    {
        $columns = ['address', 'hotel_name', 'nearby_landmark', 'description', 'search_text'];
        $terms = [];

        foreach ($cities as $city) {
            $terms = array_merge($terms, $this->citySearchTerms($city));
        }

        $terms = array_values(array_unique(array_filter($terms)));
        if (empty($terms)) {
            return;
        }

        $query->where(function (Builder $builder) use ($columns, $terms) {
            foreach ($columns as $column) {
                $builder->orWhere(function (Builder $columnQuery) use ($column, $terms) {
                    foreach ($terms as $index => $term) {
                        if ($index === 0) {
                            $columnQuery->where($column, 'like', '%' . $term . '%');
                        } else {
                            $columnQuery->orWhere($column, 'like', '%' . $term . '%');
                        }
                    }
                });
            }
        });
    }

    private function applyRestaurantCityScope(Builder $query, array $cities): void
    {
        $columns = ['location', 'restaurant_name', 'description', 'search_text'];
        $terms = [];

        foreach ($cities as $city) {
            $terms = array_merge($terms, $this->citySearchTerms($city));
        }

        $terms = array_values(array_unique(array_filter($terms)));
        if (empty($terms)) {
            return;
        }

        $query->where(function (Builder $builder) use ($columns, $terms) {
            foreach ($columns as $column) {
                $builder->orWhere(function (Builder $columnQuery) use ($column, $terms) {
                    foreach ($terms as $index => $term) {
                        if ($index === 0) {
                            $columnQuery->where($column, 'like', '%' . $term . '%');
                        } else {
                            $columnQuery->orWhere($column, 'like', '%' . $term . '%');
                        }
                    }
                });
            }
        });
    }

    private function applyFuzzyCityScope(Builder $query, string $column, array $cities): void
    {
        if (empty($cities)) {
            return;
        }

        $terms = [];
        foreach ($cities as $city) {
            $terms = array_merge($terms, $this->citySearchTerms($city));
        }

        $terms = array_values(array_unique(array_filter($terms)));

        $query->where(function (Builder $builder) use ($column, $terms) {
            foreach ($terms as $index => $term) {
                if ($index === 0) {
                    $builder->where($column, 'like', '%' . $term . '%');
                } else {
                    $builder->orWhere($column, 'like', '%' . $term . '%');
                }
            }
        });
    }

    private function applyExactCityScope(Builder $query, string $column, array $cities): void
    {
        if (empty($cities)) {
            return;
        }

        $query->whereIn($column, array_values(array_unique($cities)));
    }

    private function applyActivityCityScope(Builder $query, array $cities): void
    {
        $columns = ['city', 'location', 'description', 'search_text'];
        $terms = [];

        foreach ($cities as $city) {
            $terms = array_merge($terms, $this->citySearchTerms($city));
        }

        $terms = array_values(array_unique(array_filter($terms)));
        if (empty($terms)) {
            return;
        }

        $query->where(function (Builder $builder) use ($columns, $terms) {
            foreach ($columns as $column) {
                $builder->orWhere(function (Builder $columnQuery) use ($column, $terms) {
                    foreach ($terms as $index => $term) {
                        if ($index === 0) {
                            $columnQuery->where($column, 'like', '%' . $term . '%');
                        } else {
                            $columnQuery->orWhere($column, 'like', '%' . $term . '%');
                        }
                    }
                });
            }
        });
    }

    private function scoreCityMatch(array $requestedCities, array $candidateCities, array &$reasons): float
    {
        if (empty($requestedCities) || empty($candidateCities)) {
            return 0.0;
        }

        $matches = array_values(array_intersect($requestedCities, $candidateCities));
        if (empty($matches)) {
            return 0.0;
        }

        $primaryCity = $requestedCities[0];
        $score = in_array($primaryCity, $matches, true) ? 34.0 : 26.0;
        $reasons[] = 'matches ' . $this->humanizeLabel($matches[0]);

        return $score;
    }

    private function scoreConceptOverlap(array $requested, array $candidate, float $perMatch, float $max, string $reasonPrefix, array &$reasons): float
    {
        if (empty($requested) || empty($candidate)) {
            return 0.0;
        }

        $matches = array_values(array_intersect($requested, $candidate));
        if (empty($matches)) {
            return 0.0;
        }

        $reasons[] = $reasonPrefix . ': ' . implode(', ', array_map(fn ($match) => $this->humanizeLabel($match), array_slice($matches, 0, 3)));

        return min(count($matches) * $perMatch, $max);
    }

    private function scoreConceptConflicts(array $requestedConcepts, array $candidateConcepts, float $perConflict, float $maxPenalty): float
    {
        if (empty($requestedConcepts) || empty($candidateConcepts)) {
            return 0.0;
        }

        $oppositions = $this->conceptOppositionMap();
        $conflicts = [];

        foreach ($requestedConcepts as $requestedConcept) {
            foreach ($oppositions[$requestedConcept] ?? [] as $oppositeConcept) {
                if (in_array($oppositeConcept, $candidateConcepts, true)) {
                    $conflicts[] = $oppositeConcept;
                }
            }
        }

        if (empty($conflicts)) {
            return 0.0;
        }

        return -1 * min(count(array_unique($conflicts)) * $perConflict, $maxPenalty);
    }

    private function scoreBudgetMatch(array $intent, ?string $candidateBudget, ?float $candidatePrice, array &$reasons): float
    {
        $score = 0.0;
        $requestedBudget = $intent['budget'] ?? null;
        $budgetMax = $intent['budget_max'] ?? null;

        if ($requestedBudget && $candidateBudget) {
            $score += match ($requestedBudget) {
                'budget' => match ($candidateBudget) {
                    'budget' => 26,
                    'mid_range' => 8,
                    'premium' => -18,
                    'luxury' => -28,
                    default => 0,
                },
                'mid_range' => match ($candidateBudget) {
                    'budget' => 6,
                    'mid_range' => 14,
                    'premium' => 8,
                    'luxury' => -8,
                    default => 0,
                },
                'premium' => match ($candidateBudget) {
                    'budget' => -4,
                    'mid_range' => 8,
                    'premium' => 14,
                    'luxury' => 10,
                    default => 0,
                },
                'luxury' => match ($candidateBudget) {
                    'budget' => -10,
                    'mid_range' => -2,
                    'premium' => 10,
                    'luxury' => 16,
                    default => 0,
                },
                default => 0,
            };

            if ($score >= 10) {
                $reasons[] = 'fits ' . $this->humanizeLabel($requestedBudget) . ' budget';
            }
        }

        if ($budgetMax && $candidatePrice) {
            if ($candidatePrice <= $budgetMax) {
                $score += 16;
                $reasons[] = 'within budget ceiling';
            } elseif ($candidatePrice <= ($budgetMax * 1.15)) {
                $score += 6;
            } else {
                $score -= 12;
            }
        }

        return $score;
    }

    private function scoreBeachDistance(array $intent, ?float $distanceKm, array &$reasons): float
    {
        if ($distanceKm === null || !in_array('beach', $intent['semantic_concepts'] ?? [], true)) {
            return 0.0;
        }

        if ($distanceKm <= 0.5) {
            $reasons[] = 'very close to the beach';

            return 10.0;
        }

        if ($distanceKm <= 1.5) {
            $reasons[] = 'close to the beach';

            return 6.0;
        }

        if ($distanceKm <= 3) {
            return 2.0;
        }

        return -4.0;
    }

    private function scoreSemanticOverlap(array $requestedConcepts, array $candidateConcepts, float $perMatch, float $max, array &$reasons): float
    {
        if (empty($requestedConcepts) || empty($candidateConcepts)) {
            return 0.0;
        }

        $matches = array_values(array_intersect($requestedConcepts, $candidateConcepts));
        if (empty($matches)) {
            return 0.0;
        }

        $score = min(count($matches) * $perMatch, $max);
        if ($score >= 8) {
            $reasons[] = 'semantic fit: ' . implode(', ', array_map(fn ($match) => $this->humanizeLabel($match), array_slice($matches, 0, 3)));
        }

        return $score;
    }

    private function keywordOverlapScore(string $haystack, string $message, array $semanticConcepts = []): float
    {
        $haystack = $this->normalizeText($haystack);
        $tokens = $this->tokenizeText($message);
        $expandedTerms = $this->expandSemanticTerms($semanticConcepts);
        $score = 0.0;

        foreach (array_unique(array_merge($tokens, $expandedTerms)) as $term) {
            if (mb_strlen($term) < 4) {
                continue;
            }

            if ($this->containsPhrase($haystack, $term)) {
                $score += mb_strlen($term) > 8 ? 2.5 : 1.5;
            }
        }

        return min($score, 14);
    }

    private function hasTravelSignal(
        array $mentionedCities,
        array $semanticConcepts,
        ?string $budget,
        ?int $budgetMax,
        ?int $explicitDayCount,
        bool $wantsHotel,
        bool $wantsRestaurant,
        bool $wantsActivity,
        bool $wantsTripPlan
    ): bool {
        return !empty($mentionedCities)
            || !empty($semanticConcepts)
            || $budget !== null
            || $budgetMax !== null
            || $explicitDayCount !== null
            || $wantsHotel
            || $wantsRestaurant
            || $wantsActivity
            || $wantsTripPlan;
    }

    private function calculateIntentSignalScore(array $signals): int
    {
        return count(array_filter($signals, fn ($signal) => !empty($signal)));
    }

    private function hasCrossCityAnchor(
        array $vibeTags,
        array $foodPreferences,
        array $occasionTags,
        array $audienceTags,
        array $activityTypes,
        array $timePreferences,
        array $semanticConcepts,
        ?string $budget,
        ?int $budgetMax
    ): bool {
        return !empty($vibeTags)
            || !empty($foodPreferences)
            || !empty($occasionTags)
            || !empty($audienceTags)
            || !empty($activityTypes)
            || !empty($timePreferences)
            || !empty($semanticConcepts)
            || $budget !== null
            || $budgetMax !== null;
    }

    private function canPlanTripWithoutExplicitCity(
        bool $wantsTripPlan,
        array $mentionedCities,
        array $vibeTags,
        array $foodPreferences,
        array $occasionTags,
        array $audienceTags,
        array $activityTypes,
        array $timePreferences,
        ?string $budget,
        ?int $budgetMax,
        ?int $dayCount
    ): bool {
        if (!$wantsTripPlan || !empty($mentionedCities) || $dayCount === null) {
            return false;
        }

        $anchorSignals = $this->calculateIntentSignalScore([
            !empty($vibeTags),
            !empty($foodPreferences),
            !empty($occasionTags),
            !empty($audienceTags),
            !empty($activityTypes),
            !empty($timePreferences),
            $budget !== null,
            $budgetMax !== null,
            $dayCount !== null,
        ]);

        $experienceVibes = array_values(array_diff($vibeTags, ['family']));
        $experienceOccasions = array_values(array_diff($occasionTags, ['family']));
        $experienceSignalCount = count(array_unique(array_merge(
            $experienceVibes,
            $foodPreferences,
            $experienceOccasions,
            $activityTypes,
            $timePreferences,
            $budget !== null ? [$budget] : [],
            $budgetMax !== null ? ['budget_amount'] : []
        )));
        $specificDestinationAnchorCount = count(array_unique(array_merge(
            array_diff($experienceVibes, ['romantic']),
            $foodPreferences,
            array_diff($experienceOccasions, ['date']),
            $activityTypes,
            $timePreferences,
            $budget !== null ? [$budget] : [],
            $budgetMax !== null ? ['budget_amount'] : []
        )));

        $hasExperientialAnchor = $experienceSignalCount >= 2 && $specificDestinationAnchorCount >= 1;

        return $hasExperientialAnchor && $anchorSignals >= 3;
    }

    private function shouldHoldCitylessHotelRequest(
        bool $wantsHotel,
        array $mentionedCities,
        bool $wantsTripPlan,
        array $vibeTags,
        array $audienceTags,
        array $timePreferences,
        ?string $budget,
        ?int $budgetMax
    ): bool {
        if (!$wantsHotel || !empty($mentionedCities) || $wantsTripPlan) {
            return false;
        }

        if ($budgetMax !== null) {
            return false;
        }

        $experienceVibes = array_values(array_diff($vibeTags, ['family']));
        $hasExperienceAnchor = !empty($experienceVibes) || !empty($timePreferences);
        $hasSupportAnchor = !empty($audienceTags) || $budget !== null;
        $hasBusinessAnchor = in_array('business', $audienceTags, true);

        if ($hasBusinessAnchor) {
            return false;
        }

        return !($hasExperienceAnchor || $hasSupportAnchor);
    }

    private function shouldHoldCitylessRestaurantRequest(
        bool $wantsRestaurant,
        array $mentionedCities,
        bool $wantsTripPlan,
        array $foodPreferences,
        array $occasionTags,
        array $timePreferences,
        array $vibeTags,
        ?string $budget,
        ?int $budgetMax
    ): bool {
        if (!$wantsRestaurant || !empty($mentionedCities) || $wantsTripPlan) {
            return false;
        }

        $restaurantAnchorScore = $this->calculateIntentSignalScore([
            !empty($foodPreferences),
            !empty($occasionTags),
            !empty($timePreferences),
            !empty($vibeTags),
            $budget !== null,
            $budgetMax !== null,
        ]);

        $hasDiningSpecificAnchor = !empty($foodPreferences)
            || !empty(array_intersect($occasionTags, ['breakfast', 'lunch', 'dinner', 'date', 'night_out']))
            || !empty(array_intersect($timePreferences, ['breakfast', 'lunch', 'dinner']));

        return !$hasDiningSpecificAnchor || $restaurantAnchorScore < 2;
    }

    private function inferResponseTone(string $text, array $vibeTags, array $occasionTags): string
    {
        if ($this->containsAnyPhrase($text, ['confused', 'not sure', "don't know", 'dont know', 'help me', 'stuck'])) {
            return 'supportive';
        }

        if (
            in_array('relaxing', $vibeTags, true)
            || in_array('romantic', $vibeTags, true)
            || in_array('date', $occasionTags, true)
        ) {
            return 'warm';
        }

        if (
            in_array('lively', $vibeTags, true)
            || in_array('nightlife', $vibeTags, true)
            || $this->containsAnyPhrase($text, ['fun', 'exciting', 'celebrate', 'party'])
        ) {
            return 'upbeat';
        }

        return 'neutral';
    }

    private function extractMentionedCities(string $text): array
    {
        $normalized = $this->normalizeText($text);
        $positions = [];

        foreach ($this->cityAliasMap() as $canonical => $aliases) {
            foreach ($aliases as $alias) {
                $position = $this->phrasePosition($normalized, $alias);
                if ($position === null) {
                    continue;
                }

                if ($this->isSuppressedCityAliasMatch($normalized, $canonical, $alias, $position)) {
                    continue;
                }

                if (!array_key_exists($canonical, $positions) || $position < $positions[$canonical]) {
                    $positions[$canonical] = $position;
                }
            }
        }

        asort($positions);

        return array_keys($positions);
    }

    private function isSuppressedCityAliasMatch(string $text, string $canonical, string $alias, int $position): bool
    {
        $aliasText = $this->normalizeText($alias);
        $locationContextPattern = '/\b(?:minute drive from|minutes drive from|drive from|minutes from|minute from|mins from|min from|away from|outside of|outside|near|close to)\s+' . preg_quote($aliasText, '/') . '\b/u';
        if ($aliasText !== '' && preg_match($locationContextPattern, $text)) {
            return true;
        }

        $contextBefore = mb_substr(' ' . $text . ' ', max(0, $position - 44), min(44, $position));
        if (preg_match('/\b(?:minute drive from|minutes drive from|drive from|minutes from|minute from|mins from|min from|away from|outside of|outside|near|close to)\s+$/u', $contextBefore)) {
            return true;
        }

        if ($canonical === 'batroun' && $this->containsPhrase($text, 'smar jbeil')) {
            return true;
        }

        if ($canonical !== 'byblos' || $this->normalizeText($alias) !== 'jbeil') {
            return false;
        }

        foreach (['smar jbeil', 's mar jbeil'] as $suppressedPhrase) {
            $phrasePosition = $this->phrasePosition($text, $suppressedPhrase);
            if ($phrasePosition === null) {
                continue;
            }

            $jbeilPosition = $phrasePosition + mb_strpos($suppressedPhrase, 'jbeil');
            if ($position === $jbeilPosition) {
                return true;
            }
        }

        return false;
    }

    private function extractExcludedCities(string $text): array
    {
        $normalized = $this->normalizeText($text);
        $excluded = [];

        foreach ($this->cityAliasMap() as $canonical => $aliases) {
            foreach ($aliases as $alias) {
                $alias = $this->normalizeText($alias);
                if ($alias === '') {
                    continue;
                }

                foreach ([
                    "dont like {$alias}",
                    "don t like {$alias}",
                    "do not like {$alias}",
                    "dont want {$alias}",
                    "don t want {$alias}",
                    "do not want {$alias}",
                    "avoid {$alias}",
                    "skip {$alias}",
                    "not {$alias}",
                    "not in {$alias}",
                    "instead of {$alias}",
                    "other than {$alias}",
                    "another city than {$alias}",
                    "somewhere else than {$alias}",
                    "anything but {$alias}",
                ] as $phrase) {
                    if ($this->containsPhrase($normalized, $phrase)) {
                        $excluded[] = $canonical;
                        break 2;
                    }
                }
            }
        }

        return array_values(array_unique($excluded));
    }

    private function extractConceptMatches(string $text, array $conceptMap): array
    {
        $text = $this->normalizeText($text);
        $matches = [];

        foreach ($conceptMap as $concept => $terms) {
            $allTerms = array_values(array_unique(array_merge([$concept], $terms)));

            foreach ($allTerms as $term) {
                if ($this->containsPhrase($text, $term)) {
                    $matches[] = $concept;
                    break;
                }
            }
        }

        return array_values(array_unique($matches));
    }

    private function removeNegatedConceptMatches(string $text, array $matches, array $conceptMap): array
    {
        if (empty($matches)) {
            return [];
        }

        $text = $this->normalizeText($text);

        return array_values(array_filter($matches, function (string $concept) use ($text, $conceptMap) {
            $terms = array_values(array_unique(array_filter(array_merge(
                [$concept, str_replace('_', ' ', $concept)],
                (array) ($conceptMap[$concept] ?? [])
            ))));

            foreach ($terms as $term) {
                $term = $this->normalizeText($term);
                if ($term === '') {
                    continue;
                }

                if ($this->containsAnyPhrase($text, [
                    "not {$term}",
                    "no {$term}",
                    "avoid {$term}",
                    "without {$term}",
                    "don t want {$term}",
                    "dont want {$term}",
                    "do not want {$term}",
                ])) {
                    return false;
                }
            }

            return true;
        }));
    }

    private function normalizeConceptList(array|string|null $value, array $conceptMap): array
    {
        $values = $this->normalizeTagArray($value);
        if (empty($values)) {
            return [];
        }

        return array_values(array_unique(array_merge(
            $values,
            $this->extractConceptMatches(implode(' ', $values), $conceptMap)
        )));
    }

    private function normalizeTagArray(array|string|null $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded;
            } else {
                $value = $this->normalizeMultiValueString($value);
            }
        }

        if (!is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            fn ($item) => $this->normalizeText(is_scalar($item) ? (string) $item : ''),
            $value
        ))));
    }

    private function normalizeMultiValueString(?string $value): array
    {
        $value = trim((string) $value);
        if ($value === '') {
            return [];
        }

        $parts = preg_split('/\s*,\s*|\s*\/\s*|\s*&\s*|\s+and\s+/i', $value) ?: [];

        return array_values(array_unique(array_filter(array_map(
            fn ($part) => $this->normalizeText($part),
            $parts
        ))));
    }

    private function extractBudgetPreference(string $text): ?string
    {
        $matches = $this->extractConceptMatches($text, $this->budgetConceptMap());

        return $matches[0] ?? null;
    }

    private function extractBudgetAmount(string $text): ?int
    {
        if (preg_match('/(?:under|below|less than|max|maximum|up to)\s*\$?\s*(\d{2,4})/i', $text, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/\$\s*(\d{2,4})\s*(?:max|maximum|budget)?/i', $text, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function buildUnavailableStaySlot(string $city): array
    {
        return [
            'title' => 'Stay note',
            'note' => "I do not have a confirmed hotel match for {$city} in the current data, so choose the overnight stay separately before booking.",
            'activities' => [],
        ];
    }

    private function extractDayCount(string $text): ?int
    {
        if (preg_match('/\b([1-9])\s*(day|days|night|nights)\b/', $text, $matches)) {
            return max(1, min(7, (int) $matches[1]));
        }

        if (preg_match('/\b([1-9])\s+[a-z]+\s+(day|days|night|nights)\b/', $text, $matches)) {
            return max(1, min(7, (int) $matches[1]));
        }

        if (preg_match('/\b(one|two|three|four|five|six|seven)\s*(day|days|night|nights)\b/', $text, $matches)) {
            return match ($matches[1]) {
                'one' => 1,
                'two' => 2,
                'three' => 3,
                'four' => 4,
                'five' => 5,
                'six' => 6,
                'seven' => 7,
                default => null,
            };
        }

        if (preg_match('/\b(one|two|three|four|five|six|seven)\s+[a-z]+\s+(day|days|night|nights)\b/', $text, $matches)) {
            return match ($matches[1]) {
                'one' => 1,
                'two' => 2,
                'three' => 3,
                'four' => 4,
                'five' => 5,
                'six' => 6,
                'seven' => 7,
                default => null,
            };
        }

        if ($this->containsAnyPhrase($text, ['long weekend'])) {
            return 3;
        }

        if ($this->containsAnyPhrase($text, ['one day', '1 day', 'day trip', '1 day trip', 'single day'])) {
            return 1;
        }

        if ($this->containsAnyPhrase($text, ['two days', '2 days', '2 day', 'two day', 'weekend', '2 day trip', 'couple days', 'couple of days', '2 nights', 'two nights', '2 night', 'two night'])) {
            return 2;
        }

        if ($this->containsAnyPhrase($text, ['three days', '3 days', '3 day', 'three day', '3 day trip', '3 nights', 'three nights', 'few days'])) {
            return 3;
        }

        if ($this->containsAnyPhrase($text, ['four days', '4 days', '4 day', 'four day', '4 nights', 'four nights'])) {
            return 4;
        }

        if ($this->containsAnyPhrase($text, ['five days', '5 days', '5 day', 'five day', '5 nights', 'five nights'])) {
            return 5;
        }

        if ($this->containsAnyPhrase($text, ['six days', '6 days', '6 day', 'six day', '6 nights', 'six nights'])) {
            return 6;
        }

        if ($this->containsAnyPhrase($text, ['seven days', '7 days', '7 day', 'seven day', '7 nights', 'seven nights', 'week long', 'week-long'])) {
            return 7;
        }

        return null;
    }

    private function durationKeyFromDayCount(int $dayCount): string
    {
        return $dayCount === 1 ? '1_day' : "{$dayCount}_days";
    }

    private function durationToDayCount(?string $duration): ?int
    {
        $duration = $this->normalizeText($duration);

        if ($duration === '1 day' || $duration === '1_day') {
            return 1;
        }

        if (preg_match('/\b(\d+)\s*days?\b/', str_replace('_', ' ', $duration), $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function extractMoneyValue(?string $value): ?float
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        if (preg_match('/(\d+(?:\.\d+)?)/', $value, $matches)) {
            return (float) $matches[1];
        }

        return null;
    }

    private function extractDistanceKm(?string $value): ?float
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $normalized = Str::lower($value);
        if (!preg_match('/(\d+(?:\.\d+)?)/', $normalized, $matches)) {
            return null;
        }

        $distance = (float) $matches[1];

        if (str_contains($normalized, 'km')) {
            return $distance;
        }

        if (preg_match('/\b\d+(?:\.\d+)?\s*m\b/', $normalized)) {
            return $distance / 1000;
        }

        return $distance;
    }

    private function normalizeHotelBudgetTier(?string $price): ?string
    {
        $value = $this->extractMoneyValue($price);
        if ($value === null) {
            return null;
        }

        return match (true) {
            $value <= 65 => 'budget',
            $value <= 135 => 'mid_range',
            $value <= 240 => 'premium',
            default => 'luxury',
        };
    }

    private function normalizeRestaurantBudgetTier(?string $priceTier): ?string
    {
        $priceTier = $this->normalizeText($priceTier);

        return match (true) {
            $priceTier === 'budget' => 'budget',
            $priceTier === 'mid range', $priceTier === 'midrange' => 'mid_range',
            $priceTier === 'premium', $priceTier === 'upscale' => 'premium',
            $priceTier === 'luxury' => 'luxury',
            default => null,
        };
    }

    private function normalizeActivityBudgetTier(?string $priceType): ?string
    {
        $priceType = $this->normalizeText($priceType);

        return match ($priceType) {
            'free', 'low' => 'budget',
            'medium' => 'mid_range',
            'premium', 'paid' => 'premium',
            default => null,
        };
    }

    private function primaryFoodGroup(?string $foodType): ?string
    {
        $normalized = $this->normalizeConceptList($this->normalizeMultiValueString($foodType), $this->foodConceptMap());

        foreach ($normalized as $item) {
            if (array_key_exists($item, $this->foodConceptMap())) {
                return $item;
            }
        }

        return $normalized[0] ?? null;
    }

    private function expandSemanticTerms(array $semanticConcepts): array
    {
        $expanded = [];
        $conceptMap = $this->semanticConceptMap();

        foreach ($semanticConcepts as $concept) {
            $expanded[] = $concept;

            if (isset($conceptMap[$concept])) {
                $expanded = array_merge($expanded, $conceptMap[$concept]);
            }
        }

        return array_values(array_unique(array_map(
            fn ($term) => $this->normalizeText($term),
            array_filter($expanded)
        )));
    }

    private function tokenizeText(string $text): array
    {
        $tokens = preg_split('/\s+/', $this->normalizeText($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique(array_filter($tokens, function ($token) {
            return mb_strlen($token) >= 4 && !in_array($token, $this->stopWords(), true);
        })));
    }

    private function containsAnyPhrase(string $haystack, array $phrases): bool
    {
        foreach ($phrases as $phrase) {
            if ($this->containsPhrase($haystack, $phrase)) {
                return true;
            }
        }

        return false;
    }

    private function containsPhrase(string $haystack, string $needle): bool
    {
        $haystack = ' ' . $this->normalizeText($haystack) . ' ';
        $needle = trim($this->normalizeText($needle));

        if ($needle === '') {
            return false;
        }

        return str_contains($haystack, ' ' . $needle . ' ');
    }

    private function phrasePosition(string $haystack, string $needle): ?int
    {
        $haystack = ' ' . $this->normalizeText($haystack) . ' ';
        $needle = ' ' . trim($this->normalizeText($needle)) . ' ';
        $position = strpos($haystack, $needle);

        return $position === false ? null : $position;
    }

    private function citySearchTerms(string $canonicalCity): array
    {
        return $this->cityAliasMap()[$canonicalCity] ?? [$canonicalCity];
    }

    private function humanizeLabel(string $value): string
    {
        return str_replace('_', ' ', str_replace('-', ' ', $value));
    }

    private function cityAliasMap(): array
    {
        return [
            'beirut' => ['beirut'],
            'batroun' => ['batroun'],
            'tyre' => ['tyre', 'sur'],
            'byblos' => ['byblos', 'jbeil'],
            'tripoli' => ['tripoli', 'trablos'],
            'jounieh' => ['jounieh'],
            'saida' => ['saida', 'sidon'],
            'broumana' => ['broumana', 'brummana'],
            'zahle' => ['zahle', 'zahleh'],
        ];
    }

    private function vibeConceptMap(): array
    {
        return [
            'romantic' => ['intimate', 'date', 'anniversary', 'couple', 'special', 'lovely'],
            'relaxing' => ['relaxed', 'calm', 'quiet', 'peaceful', 'chill', 'laid back', 'laid-back', 'unwind', 'unwinding', 'decompress', 'stress free', 'stress-free', 'escape'],
            'lively' => ['energetic', 'vibrant', 'buzzing', 'celebrate', 'celebration', 'exciting'],
            'fun' => ['playful', 'enjoyable', 'happy'],
            'cozy' => ['cosy', 'warm', 'comfortable', 'homey'],
            'luxury' => ['luxurious', 'upscale', 'elegant', 'premium'],
            'beach' => ['seaside', 'coastal', 'waterfront', 'sea', 'shore'],
            'sunset' => ['sundown', 'golden hour'],
            'nightlife' => ['night out', 'night-out', 'party', 'bars', 'club', 'cocktails'],
            'cultural' => ['heritage', 'history', 'historical', 'old town', 'old city', 'old streets', 'souk', 'castle', 'touristy', 'famous', 'must see', 'must-see', 'landmark', 'iconic'],
            'scenic' => ['view', 'views', 'panoramic', 'panorama', 'photo', 'photos'],
            'city' => ['downtown', 'urban'],
            'family' => ['kid friendly', 'kid-friendly', 'children'],
            'hidden_gem' => ['hidden gem', 'hidden gems', 'offbeat', 'less touristy'],
        ];
    }

    private function foodConceptMap(): array
    {
        return [
            'lebanese' => ['mezze', 'mezza', 'local cuisine', 'local food', 'local'],
            'seafood' => ['fish', 'shrimp', 'oysters'],
            'italian' => ['pasta', 'risotto'],
            'pizza' => ['pizzeria'],
            'burgers' => ['burger'],
            'grill' => ['bbq', 'barbecue', 'steak'],
            'japanese' => ['sushi', 'ramen', 'nikkei'],
            'peruvian' => ['ceviche'],
            'cafe' => ['coffee', 'brunch'],
            'dessert' => ['sweets', 'bakery', 'pastry'],
            'healthy' => ['salads', 'bowls', 'organic'],
            'wine' => ['wine bar'],
        ];
    }

    private function occasionConceptMap(): array
    {
        return [
            'date' => ['romantic dinner', 'special night'],
            'dinner' => ['supper'],
            'lunch' => [],
            'breakfast' => ['brunch'],
            'casual' => [],
            'anniversary' => ['celebrate', 'celebration', 'special occasion'],
            'friends' => ['group'],
            'family' => ['kids', 'children'],
            'business' => ['meeting', 'meetings'],
            'night-out' => ['night out', 'drinks', 'bar hopping'],
        ];
    }

    private function audienceConceptMap(): array
    {
        return [
            'family' => ['kids', 'children'],
            'friends' => ['group'],
            'business' => ['meeting', 'meetings', 'client', 'clients'],
            'couple' => ['romantic', 'date'],
        ];
    }

    private function activityConceptMap(): array
    {
        return [
            'beach' => ['beach club', 'seaside', 'coastal'],
            'walking' => ['walk', 'walking', 'stroll', 'promenade'],
            'scenic' => ['view', 'views', 'photos'],
            'cultural' => ['culture', 'heritage', 'souk', 'old streets', 'old town', 'old city', 'touristy', 'famous', 'must see', 'must-see', 'landmark', 'iconic'],
            'historical' => ['historic', 'history', 'castle', 'ancient', 'ruins', 'landmark', 'iconic'],
            'touristy' => ['touristy', 'famous', 'must see', 'must-see', 'landmark', 'iconic'],
            'nightlife' => ['bars', 'party', 'club', 'night out', 'night-out', 'rooftop', 'drinks', 'bar hopping'],
            'city' => ['downtown', 'urban'],
            'food' => ['dessert', 'cafe', 'culinary'],
            'hidden_gem' => ['hidden gem', 'offbeat'],
        ];
    }

    private function timeConceptMap(): array
    {
        return [
            'morning' => ['early'],
            'afternoon' => [],
            'evening' => ['night', 'tonight'],
            'sunset' => ['golden hour'],
            'breakfast' => [],
            'lunch' => [],
            'dinner' => ['tonight'],
        ];
    }

    private function budgetConceptMap(): array
    {
        return [
            'budget' => ['cheap', 'cheaper', 'affordable', 'value', 'low cost', 'low-cost', 'less expensive', 'not expensive', 'not too expensive', 'not that expensive', 'not very expensive'],
            'mid_range' => ['mid range', 'mid-range', 'moderate', 'reasonable'],
            'premium' => ['premium', 'upscale'],
            'luxury' => ['luxurious', 'high end', 'high-end', 'expensive', 'five star', '5 star'],
        ];
    }

    private function semanticConceptMap(): array
    {
        return array_replace(
            $this->vibeConceptMap(),
            $this->foodConceptMap(),
            $this->occasionConceptMap(),
            $this->audienceConceptMap(),
            $this->activityConceptMap(),
            $this->timeConceptMap(),
            $this->budgetConceptMap()
        );
    }

    private function conceptOppositionMap(): array
    {
        return [
            'relaxing' => ['lively', 'nightlife'],
            'lively' => ['relaxing', 'cozy'],
            'nightlife' => ['relaxing', 'family', 'business'],
            'romantic' => ['family', 'business'],
            'family' => ['nightlife', 'romantic'],
            'business' => ['nightlife'],
            'budget' => ['luxury'],
            'luxury' => ['budget'],
            'breakfast' => ['dinner'],
            'dinner' => ['breakfast'],
        ];
    }

    private function stopWords(): array
    {
        return [
            'with', 'that', 'this', 'from', 'have', 'would', 'about', 'there', 'their',
            'place', 'places', 'need', 'want', 'looking', 'recommend', 'please', 'trip',
            'plan', 'hotel', 'restaurants', 'restaurant', 'activities', 'activity',
        ];
    }

    private function normalizeText(?string $text): string
    {
        $text = Str::lower($text ?? '');
        $text = $this->normalizeCommonUserTypos($text);
        $text = preg_replace('/[^\pL\pN\s]+/u', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    private function normalizeCommonUserTypos(string $text): string
    {
        $replacements = [
            '/\bhotelsin\b/u' => 'hotels in',
            '/\bhotelin\b/u' => 'hotel in',
            '/\brestaurantsin\b/u' => 'restaurants in',
            '/\brestaurantin\b/u' => 'restaurant in',
            '/\brestuarantsin\b/u' => 'restaurants in',
            '/\brestuarantin\b/u' => 'restaurant in',
            '/\bresturantsin\b/u' => 'restaurants in',
            '/\bresturantin\b/u' => 'restaurant in',
            '/\brestrauntsin\b/u' => 'restaurants in',
            '/\brestrauntin\b/u' => 'restaurant in',
            '/\brestuarants?\b/u' => 'restaurants',
            '/\bresturants?\b/u' => 'restaurants',
            '/\brestraunts?\b/u' => 'restaurants',
            '/\brestuarant\b/u' => 'restaurant',
            '/\bresturant\b/u' => 'restaurant',
            '/\brestraunt\b/u' => 'restaurant',
            '/\breccomendations?\b/u' => 'recommendations',
            '/\breccomend\b/u' => 'recommend',
            '/\brecomendations?\b/u' => 'recommendations',
            '/\brecomend\b/u' => 'recommend',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text) ?? $text;
        }

        return $text;
    }
}
