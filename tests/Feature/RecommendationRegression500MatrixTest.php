<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Hotel;
use App\Models\Restaurant;
use App\Services\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationRegression500MatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_recommendation_regression_matrix_covers_500_prompts(): void
    {
        $this->seedMegaRegressionCatalog();

        $cases = $this->megaRegressionPromptMatrix();
        $this->assertCount(500, $cases);

        $service = app(RecommendationService::class);

        foreach ($cases as $index => $case) {
            $context = sprintf(
                '500-case %d [%s] prompt "%s"',
                $index + 1,
                $case['label'],
                $case['prompt']
            );

            $results = $service->buildResponseData($case['prompt']);
            $intent = $results['intent'];

            $this->assertSame($case['hold'], $intent['should_hold_results'], $context . ' should_hold_results mismatch.');
            $this->assertSame($case['trip_plan'], $intent['wants_trip_plan'], $context . ' trip plan routing mismatch.');
            $this->assertSame($case['day_count'], $intent['day_count'], $context . ' day count mismatch.');
            $this->assertNotEmpty($results['diagnostics']['summary_chips'] ?? [], $context . ' diagnostics summary chips should not be empty.');
            $this->assertSame(
                $case['hold'],
                (bool) data_get($results, 'diagnostics.guidance.should_hold_results'),
                $context . ' guidance diagnostics mismatch.'
            );

            if ($case['city'] !== null) {
                $this->assertSame($case['city'], $intent['city'], $context . ' extracted city mismatch.');
            }

            if ($case['end_city'] !== null) {
                $this->assertSame($case['city'], $intent['start_city'], $context . ' extracted start city mismatch.');
                $this->assertSame($case['end_city'], $intent['end_city'], $context . ' extracted end city mismatch.');
            }

            if ($case['budget_max'] !== null) {
                $this->assertSame($case['budget_max'], $intent['budget_max'], $context . ' budget ceiling mismatch.');
            }

            foreach ($case['required_categories'] as $category) {
                $this->assertContains($category, $intent['requested_categories'], $context . " missing requested category {$category}.");
            }

            foreach ($case['forbidden_categories'] as $category) {
                $this->assertNotContains($category, $intent['requested_categories'], $context . " should not request category {$category}.");
            }

            $this->assertUniqueItems($results['hotels'] ?? [], 'hotel_name', $context . ' duplicate hotel results.');
            $this->assertUniqueItems($results['restaurants'] ?? [], 'restaurant_name', $context . ' duplicate restaurant results.');
            $this->assertUniqueItems($results['activities'] ?? [], 'name', $context . ' duplicate activity results.');

            if ($case['hold']) {
                $this->assertSame([], $results['hotels'], $context . ' held prompt should not return hotels.');
                $this->assertSame([], $results['restaurants'], $context . ' held prompt should not return restaurants.');
                $this->assertSame([], $results['activities'], $context . ' held prompt should not return activities.');
                $this->assertNull($results['trip_plan'], $context . ' held prompt should not build a trip plan.');

                continue;
            }

            foreach ($case['expected_non_empty'] as $category) {
                $this->assertNotEmpty($results[$category] ?? [], $context . " expected {$category} results.");
            }

            if ($case['only_expected_results']) {
                foreach (['hotels', 'restaurants', 'activities'] as $category) {
                    if (!in_array($category, $case['expected_non_empty'], true)) {
                        $this->assertSame([], $results[$category] ?? [], $context . " {$category} should be empty.");
                    }
                }
            }

            if ($case['trip_plan']) {
                $tripPlan = $results['trip_plan'];
                $this->assertIsArray($tripPlan, $context . ' expected a trip plan array.');
                $this->assertCount($case['day_count'], $tripPlan['days'] ?? [], $context . ' trip day count mismatch.');

                if ($case['end_city'] !== null) {
                    $this->assertSame($this->cityDisplay($case['city']), $tripPlan['days'][0]['location'] ?? null, $context . ' route start location mismatch.');
                    $this->assertSame($this->cityDisplay($case['end_city']), $tripPlan['days'][$case['day_count'] - 1]['location'] ?? null, $context . ' route end location mismatch.');
                } elseif ($case['city'] !== null) {
                    $this->assertSame($this->cityDisplay($case['city']), $tripPlan['days'][0]['location'] ?? null, $context . ' trip start location mismatch.');
                    $this->assertSame($this->cityDisplay($case['city']), $tripPlan['days'][$case['day_count'] - 1]['location'] ?? null, $context . ' trip end location mismatch.');
                }

                if ($case['expect_stay']) {
                    $this->assertTrue($this->tripPlanContainsSlot($tripPlan, 'stay'), $context . ' expected stay in trip plan.');
                }

                if ($case['expect_no_stay']) {
                    $this->assertFalse($this->tripPlanContainsSlot($tripPlan, 'stay'), $context . ' trip should not include stay.');
                }
            } else {
                $this->assertNull($results['trip_plan'], $context . ' non-trip request should not build a trip plan.');
            }

            if ($case['city'] !== null && $case['end_city'] === null) {
                foreach ($case['expected_non_empty'] as $category) {
                    $resultCity = $results[$category][0]['city'] ?? null;
                    $this->assertSame($case['city'], $resultCity, $context . " top {$category} city mismatch.");
                }
            }

            if ($case['ensure_route_coverage']) {
                $requiredCities = [$case['city'], $case['end_city']];
                $this->assertResultCitiesInclude($results['hotels'] ?? [], $requiredCities, $context . ' hotel route coverage mismatch.');
                $this->assertResultCitiesInclude($results['restaurants'] ?? [], $requiredCities, $context . ' restaurant route coverage mismatch.');
                $this->assertResultCitiesInclude($results['activities'] ?? [], $requiredCities, $context . ' activity route coverage mismatch.');
            }
        }
    }

    private function seedMegaRegressionCatalog(): void
    {
        foreach ($this->canonicalCities() as $cityKey => $cityName) {
            Hotel::create([
                'hotel_name' => "{$cityName} Budget Family Stay",
                'address' => "{$cityName} Harbor",
                'distance_from_beach' => '350 m',
                'rating_score' => 4.3,
                'review_count' => 180,
                'price_per_night' => '92$',
                'description' => "A budget family-friendly hotel in {$cityName} close to the water.",
                'stay_details' => 'Good value rooms for families, relaxed weekends, and practical stays.',
                'vibe_tags' => ['cozy', 'family', 'relaxing'],
                'audience_tags' => ['family'],
                'search_text' => "{$cityKey} budget family hotel affordable kids value harbor stay",
            ]);

            Hotel::create([
                'hotel_name' => "{$cityName} Sunset Retreat",
                'address' => "{$cityName} Waterfront",
                'distance_from_beach' => '120 m',
                'rating_score' => 4.8,
                'review_count' => 240,
                'price_per_night' => '168$',
                'description' => "A romantic seaside retreat in {$cityName} with sunset views and quiet evenings.",
                'stay_details' => 'Ideal for couples looking for a romantic coastal escape.',
                'vibe_tags' => ['romantic', 'sunset', 'beach', 'relaxing'],
                'audience_tags' => ['couple'],
                'search_text' => "{$cityKey} romantic seaside hotel sunset views couple beach quiet weekend",
            ]);

            Hotel::create([
                'hotel_name' => "{$cityName} Business Grand Hotel",
                'address' => "{$cityName} Central District",
                'distance_from_beach' => '2 km',
                'rating_score' => 4.7,
                'review_count' => 210,
                'price_per_night' => '240$',
                'description' => "A premium business hotel in central {$cityName} for meetings and client stays.",
                'stay_details' => 'Good for meetings, premium comfort, and business travel.',
                'vibe_tags' => ['luxury', 'city'],
                'audience_tags' => ['business'],
                'search_text' => "{$cityKey} business hotel premium city center meetings clients luxury",
            ]);

            Hotel::create([
                'hotel_name' => "{$cityName} Seaside Premium Suites",
                'address' => "{$cityName} Coastline",
                'distance_from_beach' => '80 m',
                'rating_score' => 4.9,
                'review_count' => 260,
                'price_per_night' => '285$',
                'description' => "A luxury seaside hotel in {$cityName} with spacious suites and premium comfort.",
                'stay_details' => 'Best for upscale beach stays, special occasions, and sea views.',
                'vibe_tags' => ['luxury', 'beach', 'sunset', 'romantic'],
                'audience_tags' => ['couple', 'business'],
                'search_text' => "{$cityKey} luxury seaside hotel premium suites beach sea view upscale",
            ]);

            Restaurant::create([
                'restaurant_name' => "{$cityName} Seafood Sunset Table",
                'location' => "{$cityName} Waterfront",
                'rating' => 4.8,
                'price_tier' => 'Premium',
                'food_type' => 'Seafood',
                'description' => "A romantic seafood dinner spot in {$cityName} with sea views and sunset tables.",
                'vibe_tags' => ['romantic', 'beach', 'sunset', 'relaxing'],
                'occasion_tags' => ['date', 'dinner'],
                'search_text' => "{$cityKey} seafood dinner romantic sea view sunset waterfront premium",
            ]);

            Restaurant::create([
                'restaurant_name' => "{$cityName} Old Town Lebanese Kitchen",
                'location' => "{$cityName} Old Town",
                'rating' => 4.5,
                'price_tier' => 'Mid-range',
                'food_type' => 'Lebanese',
                'description' => "A casual Lebanese lunch restaurant in the old town of {$cityName}.",
                'vibe_tags' => ['cultural', 'casual'],
                'occasion_tags' => ['lunch', 'casual'],
                'search_text' => "{$cityKey} lebanese lunch casual old town local food",
            ]);

            Restaurant::create([
                'restaurant_name' => "{$cityName} Morning Green Cafe",
                'location' => "{$cityName} Harbor",
                'rating' => 4.4,
                'price_tier' => 'Budget',
                'food_type' => 'Healthy, Cafe',
                'description' => "A healthy breakfast cafe in {$cityName} with bowls, coffee, and brunch.",
                'vibe_tags' => ['cozy', 'relaxing'],
                'occasion_tags' => ['breakfast', 'casual'],
                'search_text' => "{$cityKey} healthy breakfast cafe brunch coffee bowls organic",
            ]);

            Restaurant::create([
                'restaurant_name' => "{$cityName} Sushi Atelier",
                'location' => "{$cityName} Center",
                'rating' => 4.7,
                'price_tier' => 'Premium',
                'food_type' => 'Japanese',
                'description' => "A polished Japanese dinner spot in {$cityName} for sushi and date nights.",
                'vibe_tags' => ['romantic', 'city'],
                'occasion_tags' => ['date', 'dinner'],
                'search_text' => "{$cityKey} japanese sushi dinner romantic date night premium",
            ]);

            Restaurant::create([
                'restaurant_name' => "{$cityName} Stone Pizza Oven",
                'location' => "{$cityName} Market Street",
                'rating' => 4.6,
                'price_tier' => 'Mid-range',
                'food_type' => 'Pizza',
                'description' => "A casual pizza place in {$cityName} that works well for lunch.",
                'vibe_tags' => ['casual', 'fun'],
                'occasion_tags' => ['lunch', 'casual'],
                'search_text' => "{$cityKey} pizza lunch casual italian pizzeria",
            ]);

            Restaurant::create([
                'restaurant_name' => "{$cityName} Rooftop Wine Bar",
                'location' => "{$cityName} Rooftop",
                'rating' => 4.7,
                'price_tier' => 'Premium',
                'food_type' => 'Wine',
                'description' => "A romantic wine bar in {$cityName} for date nights and evening drinks.",
                'vibe_tags' => ['romantic', 'nightlife', 'scenic'],
                'occasion_tags' => ['date', 'night-out', 'dinner'],
                'search_text' => "{$cityKey} wine bar romantic date night rooftop drinks",
            ]);

            Restaurant::create([
                'restaurant_name' => "{$cityName} Night Burger Club",
                'location' => "{$cityName} Downtown",
                'rating' => 4.5,
                'price_tier' => 'Mid-range',
                'food_type' => 'Burgers',
                'description' => "A lively late-night burger and drinks spot in {$cityName}.",
                'vibe_tags' => ['lively', 'nightlife', 'fun'],
                'occasion_tags' => ['friends', 'night-out', 'dinner'],
                'search_text' => "{$cityKey} burgers drinks lively nightlife friends night out",
            ]);

            Activity::create([
                'name' => "{$cityName} Sunset Waterfront Walk",
                'city' => $cityKey,
                'category' => 'scenic',
                'description' => "A relaxed waterfront walk in {$cityName} best enjoyed at sunset.",
                'location' => "{$cityName} Waterfront",
                'best_time' => 'sunset',
                'duration_estimate' => '1 hour',
                'price_type' => 'free',
                'vibe_tags' => ['relaxing', 'sunset', 'beach', 'scenic'],
                'occasion_tags' => ['date', 'casual'],
                'search_text' => "{$cityKey} sunset walk waterfront scenic seaside golden hour",
            ]);

            Activity::create([
                'name' => "{$cityName} Heritage Souk Walk",
                'city' => $cityKey,
                'category' => 'cultural',
                'description' => "A cultural morning walk through the old town and souk of {$cityName}.",
                'location' => "{$cityName} Old Town",
                'best_time' => 'morning',
                'duration_estimate' => '1 hour',
                'price_type' => 'free',
                'vibe_tags' => ['cultural', 'casual'],
                'occasion_tags' => ['casual'],
                'search_text' => "{$cityKey} heritage cultural old town souk walk morning",
            ]);

            Activity::create([
                'name' => "{$cityName} Hidden Garden Escape",
                'city' => $cityKey,
                'category' => 'hidden_gem',
                'description' => "A quiet hidden gem in {$cityName} away from the busy spots.",
                'location' => "{$cityName} Hillside",
                'best_time' => 'afternoon',
                'duration_estimate' => '1 hour',
                'price_type' => 'free',
                'vibe_tags' => ['relaxing', 'hidden_gem'],
                'occasion_tags' => ['casual'],
                'search_text' => "{$cityKey} hidden gem quiet relaxing offbeat garden escape",
            ]);

            Activity::create([
                'name' => "{$cityName} Family Park Stop",
                'city' => $cityKey,
                'category' => 'city',
                'description' => "A family-friendly park stop in {$cityName}.",
                'location' => "{$cityName} Park",
                'best_time' => 'afternoon',
                'duration_estimate' => '90 minutes',
                'price_type' => 'low',
                'vibe_tags' => ['family', 'casual'],
                'occasion_tags' => ['family'],
                'search_text' => "{$cityKey} family activity kids park afternoon",
            ]);

            Activity::create([
                'name' => "{$cityName} Rooftop Night Out",
                'city' => $cityKey,
                'category' => 'nightlife',
                'description' => "A lively rooftop evening in {$cityName} with drinks and city energy.",
                'location' => "{$cityName} Rooftop",
                'best_time' => 'evening',
                'duration_estimate' => '2 hours',
                'price_type' => 'mid',
                'vibe_tags' => ['lively', 'nightlife', 'scenic'],
                'occasion_tags' => ['friends', 'night-out'],
                'search_text' => "{$cityKey} nightlife rooftop drinks evening party",
            ]);
        }
    }

    private function megaRegressionPromptMatrix(): array
    {
        return array_merge(
            $this->canonicalCityCases(),
            $this->aliasCityCases(),
            $this->routeCases(),
            $this->semanticCases(),
            $this->guidanceCases(),
            $this->tripGuidanceCases(),
            $this->mixedCategoryCases(),
        );
    }

    private function canonicalCityCases(): array
    {
        $cases = [];

        foreach ($this->canonicalCities() as $cityKey => $cityName) {
            $cases[] = $this->case("{$cityKey}-hotel-budget", "Find me a budget family hotel in {$cityName} under \$95 near the beach", $cityKey, null, false, null, false, ['hotels'], [], ['hotels'], true, false, false, 95);
            $cases[] = $this->case("{$cityKey}-hotel-romantic", "Find me a romantic quiet stay in {$cityName} with sunset views", $cityKey, null, false, null, false, ['hotels'], [], ['hotels']);
            $cases[] = $this->case("{$cityKey}-hotel-business", "I need a premium business hotel in {$cityName} for meetings", $cityKey, null, false, null, false, ['hotels'], [], ['hotels']);
            $cases[] = $this->case("{$cityKey}-hotel-luxury", "Show me a luxury seaside hotel in {$cityName}", $cityKey, null, false, null, false, ['hotels'], [], ['hotels']);
            $cases[] = $this->case("{$cityKey}-hotel-family", "Recommend a cozy family stay in {$cityName}", $cityKey, null, false, null, false, ['hotels'], [], ['hotels']);

            $cases[] = $this->case("{$cityKey}-restaurant-seafood", "Find me a seafood dinner in {$cityName} with a sea view", $cityKey, null, false, null, false, ['restaurants'], [], ['restaurants']);
            $cases[] = $this->case("{$cityKey}-restaurant-wine", "Recommend a romantic wine bar in {$cityName} for date night", $cityKey, null, false, null, false, ['restaurants'], [], ['restaurants']);
            $cases[] = $this->case("{$cityKey}-restaurant-sushi", "I want a Japanese sushi dinner in {$cityName}", $cityKey, null, false, null, false, ['restaurants'], [], ['restaurants']);
            $cases[] = $this->case("{$cityKey}-restaurant-pizza", "Find a casual pizza lunch in {$cityName}", $cityKey, null, false, null, false, ['restaurants'], [], ['restaurants']);
            $cases[] = $this->case("{$cityKey}-restaurant-breakfast", "Recommend a healthy breakfast cafe in {$cityName}", $cityKey, null, false, null, false, ['restaurants'], [], ['restaurants']);

            $cases[] = $this->case("{$cityKey}-activity-cultural", "I want a cultural heritage walk in {$cityName}", $cityKey, null, false, null, false, ['activities'], [], ['activities']);
            $cases[] = $this->case("{$cityKey}-activity-hidden", "Show me a quiet hidden gem place in {$cityName}", $cityKey, null, false, null, false, ['activities'], [], ['activities']);
            $cases[] = $this->case("{$cityKey}-activity-family", "Find a family activity for kids in {$cityName}", $cityKey, null, false, null, false, ['activities'], [], ['activities']);
            $cases[] = $this->case("{$cityKey}-activity-nightlife", "Show me a rooftop nightlife activity in {$cityName}", $cityKey, null, false, null, false, ['activities'], [], ['activities']);
            $cases[] = $this->case("{$cityKey}-activity-sunset", "Find me a sunset waterfront walk in {$cityName}", $cityKey, null, false, null, false, ['activities'], [], ['activities']);

            $cases[] = $this->case("{$cityKey}-trip-2day", "Plan a 2 day seaside trip in {$cityName} with sunset and seafood", $cityKey, null, false, 2, true, ['trip_plan', 'hotels'], [], ['hotels', 'restaurants', 'activities'], true, true);
            $cases[] = $this->case("{$cityKey}-trip-weekend", "Plan a romantic weekend in {$cityName} by the sea", $cityKey, null, false, 2, true, ['trip_plan', 'hotels'], [], ['hotels', 'restaurants', 'activities'], true, true);
            $cases[] = $this->case("{$cityKey}-trip-family", "Plan a 3 day family trip in {$cityName}", $cityKey, null, false, 3, true, ['trip_plan', 'hotels'], [], ['hotels', 'restaurants', 'activities'], true, true);
            $cases[] = $this->case("{$cityKey}-trip-1day", "Plan a 1 day cultural trip in {$cityName}", $cityKey, null, false, 1, true, ['trip_plan'], ['hotels'], ['hotels', 'restaurants', 'activities'], true, false, true);
            $cases[] = $this->case("{$cityKey}-trip-2night", "Build me a 2 night beach escape in {$cityName} with good food", $cityKey, null, false, 2, true, ['trip_plan', 'hotels'], [], ['hotels', 'restaurants', 'activities'], true, true);
        }

        return $cases;
    }

    private function aliasCityCases(): array
    {
        $cases = [];

        foreach ($this->aliasCities() as $cityKey => $alias) {
            $cases[] = $this->case("{$cityKey}-alias-hotel-budget", "Find me a budget family hotel in {$alias} under \$100", $cityKey, null, false, null, false, ['hotels'], [], ['hotels'], true, false, false, 100);
            $cases[] = $this->case("{$cityKey}-alias-hotel-romantic", "Find me a romantic stay in {$alias} for a quiet weekend", $cityKey, null, false, 2, false, ['hotels'], [], ['hotels']);
            $cases[] = $this->case("{$cityKey}-alias-sushi", "Find me a Japanese sushi dinner in {$alias}", $cityKey, null, false, null, false, ['restaurants'], [], ['restaurants']);
            $cases[] = $this->case("{$cityKey}-alias-pizza", "Find me a casual pizza lunch in {$alias}", $cityKey, null, false, null, false, ['restaurants'], [], ['restaurants']);
            $cases[] = $this->case("{$cityKey}-alias-breakfast", "Recommend a healthy breakfast cafe in {$alias}", $cityKey, null, false, null, false, ['restaurants'], [], ['restaurants']);
            $cases[] = $this->case("{$cityKey}-alias-cultural", "I want a cultural heritage walk in {$alias}", $cityKey, null, false, null, false, ['activities'], [], ['activities']);
            $cases[] = $this->case("{$cityKey}-alias-hidden", "Show me a quiet hidden gem place in {$alias}", $cityKey, null, false, null, false, ['activities'], [], ['activities']);
            $cases[] = $this->case("{$cityKey}-alias-nightlife", "Show me a rooftop nightlife activity in {$alias}", $cityKey, null, false, null, false, ['activities'], [], ['activities']);
            $cases[] = $this->case("{$cityKey}-alias-trip-2day", "Plan a 2 day trip in {$alias} with Japanese food and quiet places", $cityKey, null, false, 2, true, ['trip_plan', 'hotels'], [], ['hotels', 'restaurants', 'activities'], true, true);
            $cases[] = $this->case("{$cityKey}-alias-trip-1day", "Plan a 1 day cultural trip in {$alias}", $cityKey, null, false, 1, true, ['trip_plan'], ['hotels'], ['hotels', 'restaurants', 'activities'], true, false, true);
        }

        return $cases;
    }

    private function routeCases(): array
    {
        $cases = [];

        foreach ($this->routePairs() as [$fromCity, $toCity]) {
            $fromLabel = $this->routeCityLabel($fromCity, 'canonical');
            $toLabel = $this->routeCityLabel($toCity, 'canonical');
            $fromAlias = $this->routeCityLabel($fromCity, 'alias');
            $toAlias = $this->routeCityLabel($toCity, 'alias');

            $cases[] = $this->case("{$fromCity}-to-{$toCity}-route-3day", "Plan a 3 day trip from {$fromLabel} to {$toLabel} with quiet food stops", $fromCity, $toCity, false, 3, true, ['trip_plan', 'hotels'], [], ['hotels', 'restaurants', 'activities'], true, true, false, null, true);
            $cases[] = $this->case("{$fromCity}-to-{$toCity}-route-2day", "Build me a 2 day route from {$fromAlias} to {$toAlias} with sunset and seafood", $fromCity, $toCity, false, 2, true, ['trip_plan', 'hotels'], [], ['hotels', 'restaurants', 'activities'], true, true, false, null, true);
            $cases[] = $this->case("{$fromCity}-to-{$toCity}-route-4day", "Organize a 4 day road trip from {$fromLabel} to {$toAlias} for a relaxed couple getaway", $fromCity, $toCity, false, 4, true, ['trip_plan', 'hotels'], [], ['hotels', 'restaurants', 'activities'], true, true, false, null, true);
            $cases[] = $this->case("{$fromCity}-to-{$toCity}-route-weekend", "Plan a weekend escape from {$fromAlias} to {$toLabel} with beach time and good food", $fromCity, $toCity, false, 2, true, ['trip_plan', 'hotels'], [], ['hotels', 'restaurants', 'activities'], true, true, false, null, true);
        }

        return $cases;
    }

    private function semanticCases(): array
    {
        $cases = [];

        $definitions = [
            [
                'slug' => 'hotel-budget',
                'required' => ['hotels'],
                'expected' => ['hotels'],
                'budget_max' => 95,
                'prompts' => [
                    'Find me a budget seaside stay under $95.',
                    'Recommend an affordable beach hotel under $95.',
                    'I need a low cost stay by the sea under $95.',
                    'Show me a value seaside hotel under $95.',
                    'Need a budget coastal stay under $95.',
                    'Help me find a cheap hotel near the water under $95.',
                    'Looking for an affordable seaside hotel under $95.',
                    'Give me a budget hotel by the beach under $95.',
                ],
            ],
            [
                'slug' => 'hotel-romantic',
                'required' => ['hotels'],
                'expected' => ['hotels'],
                'prompts' => [
                    'Find me a romantic quiet seaside stay.',
                    'Recommend a calm romantic hotel by the sea.',
                    'I want a peaceful couple stay with sunset views.',
                    'Show me a romantic coastal retreat.',
                    'Need a quiet romantic hotel for a couple getaway.',
                    'Give me a lovely beach stay for a date escape.',
                    'Help me find a seaside hotel with a romantic vibe.',
                    'Looking for a calm sunset hotel for couples.',
                ],
            ],
            [
                'slug' => 'hotel-business',
                'required' => ['hotels'],
                'expected' => ['hotels'],
                'prompts' => [
                    'I need a business hotel for meetings.',
                    'Show me a premium hotel for client meetings.',
                    'Recommend a hotel suited for business travel.',
                    'Find me a city hotel for meetings and work.',
                    'Need a professional hotel for business travel.',
                    'Looking for a meeting-friendly premium hotel.',
                    'Suggest a hotel that works well for clients and meetings.',
                    'Help me find a business-focused city stay.',
                ],
            ],
            [
                'slug' => 'restaurant-seafood',
                'required' => ['restaurants'],
                'expected' => ['restaurants'],
                'prompts' => [
                    'Find me a seafood dinner with a sea view.',
                    'Recommend fresh fish for dinner by the water.',
                    'I want a romantic seafood place for dinner.',
                    'Show me a coastal seafood spot for tonight.',
                    'Need a seafood restaurant with sunset views.',
                    'Help me find fish and mezze by the sea.',
                    'Looking for a waterfront seafood dinner.',
                    'Give me a sea view seafood restaurant.',
                ],
            ],
            [
                'slug' => 'restaurant-wine',
                'required' => ['restaurants'],
                'expected' => ['restaurants'],
                'prompts' => [
                    'Find me a romantic wine bar for date night.',
                    'Recommend a cozy wine bar for a couple evening.',
                    'I want a romantic wine bar with a cozy setting.',
                    'Show me a date night spot with wine and views.',
                    'Need a wine bar for a special evening.',
                    'Help me find a romantic place for wine and dinner.',
                    'Looking for an elegant wine bar for a date.',
                    'Give me a sunset wine bar with a romantic mood.',
                ],
            ],
            [
                'slug' => 'restaurant-sushi',
                'required' => ['restaurants'],
                'expected' => ['restaurants'],
                'prompts' => [
                    'Find me a Japanese sushi dinner.',
                    'Recommend sushi for dinner tonight.',
                    'I want a Japanese place for a date dinner.',
                    'Show me a sushi restaurant for the evening.',
                    'Need a polished Japanese dinner spot.',
                    'Help me find good sushi tonight.',
                    'Looking for a premium sushi dinner.',
                    'Give me a Japanese restaurant for dinner.',
                ],
            ],
            [
                'slug' => 'restaurant-breakfast',
                'required' => ['restaurants'],
                'expected' => ['restaurants'],
                'prompts' => [
                    'Recommend a healthy breakfast cafe.',
                    'Find me a brunch cafe with coffee and bowls.',
                    'I want a cozy breakfast spot.',
                    'Show me a healthy place for breakfast.',
                    'Need a cafe for brunch and coffee.',
                    'Help me find a breakfast cafe with a calm vibe.',
                    'Looking for a healthy brunch place.',
                    'Give me a breakfast cafe with good coffee.',
                ],
            ],
            [
                'slug' => 'activity-cultural',
                'required' => ['activities'],
                'expected' => ['activities'],
                'prompts' => [
                    'I want a cultural heritage walk.',
                    'Show me old town places to walk around.',
                    'Recommend a heritage walk with local charm.',
                    'Find me a heritage stroll.',
                    'Need a cultural place to explore on foot.',
                    'Help me find a souk walk with history.',
                    'Looking for a relaxed historical walk.',
                    'Give me a cultural walking activity.',
                ],
            ],
            [
                'slug' => 'activity-hidden',
                'required' => ['activities'],
                'expected' => ['activities'],
                'prompts' => [
                    'Show me a quiet hidden gem place.',
                    'Find me somewhere less touristy and calm.',
                    'I want a peaceful offbeat spot to visit.',
                    'Recommend a relaxing hidden gem.',
                    'Need a quiet place away from the busy areas.',
                    'Help me find a calm local hidden spot.',
                    'Looking for an offbeat place to unwind.',
                    'Give me a quiet hidden place to visit.',
                ],
            ],
            [
                'slug' => 'activity-nightlife',
                'required' => ['activities'],
                'expected' => ['activities'],
                'prompts' => [
                    'Show me a rooftop nightlife activity.',
                    'Find me a lively night out with drinks.',
                    'I want a rooftop evening with good energy.',
                    'Recommend a nightlife activity for friends.',
                    'Need a fun rooftop spot for the evening.',
                    'Help me find a lively place for a night out.',
                    'Looking for a rooftop drinks scene.',
                    'Give me a nightlife place with a lively vibe.',
                ],
            ],
        ];

        foreach ($definitions as $definition) {
            foreach ($definition['prompts'] as $index => $prompt) {
                $cases[] = $this->case(
                    $definition['slug'] . '-' . ($index + 1),
                    $prompt,
                    null,
                    null,
                    false,
                    null,
                    false,
                    $definition['required'],
                    [],
                    $definition['expected'],
                    true,
                    false,
                    false,
                    $definition['budget_max'] ?? null
                );
            }
        }

        return $cases;
    }

    private function guidanceCases(): array
    {
        $cases = [];

        foreach ($this->generalGuidancePrompts() as $index => $prompt) {
            $cases[] = $this->case('guide-general-' . ($index + 1), $prompt, null, null, true, null, false, [], [], []);
        }

        foreach ($this->hotelGuidancePrompts() as $index => $prompt) {
            $cases[] = $this->case('guide-hotels-' . ($index + 1), $prompt, null, null, true, null, false, ['hotels'], [], []);
        }

        foreach ($this->restaurantGuidancePrompts() as $index => $prompt) {
            $cases[] = $this->case('guide-restaurants-' . ($index + 1), $prompt, null, null, true, null, false, ['restaurants'], [], []);
        }

        foreach ($this->activityGuidancePrompts() as $index => $prompt) {
            $cases[] = $this->case('guide-activities-' . ($index + 1), $prompt, null, null, true, null, false, ['activities'], [], []);
        }

        return $cases;
    }

    private function tripGuidanceCases(): array
    {
        $cases = [];

        foreach ($this->tripGuidancePrompts() as $index => $config) {
            $cases[] = $this->case(
                'guide-trip-' . ($index + 1),
                $config['prompt'],
                null,
                null,
                true,
                $config['day_count'],
                true,
                ['trip_plan'],
                [],
                []
            );
        }

        return $cases;
    }

    private function mixedCategoryCases(): array
    {
        $cases = [];

        $templates = [
            [
                'slug' => 'hotel-dinner',
                'prompt' => 'Find me a romantic hotel and dinner in %s.',
                'required' => ['hotels', 'restaurants'],
                'expected' => ['hotels', 'restaurants'],
            ],
            [
                'slug' => 'stay-kids-activity',
                'prompt' => 'Recommend a family stay and kids activity in %s.',
                'required' => ['hotels', 'activities'],
                'expected' => ['hotels', 'activities'],
            ],
            [
                'slug' => 'seafood-sunset',
                'prompt' => 'Show me a seafood dinner and sunset walk in %s.',
                'required' => ['restaurants', 'activities'],
                'expected' => ['restaurants', 'activities'],
            ],
            [
                'slug' => 'business-dinner',
                'prompt' => 'I need a business hotel and dinner spot in %s.',
                'required' => ['hotels', 'restaurants'],
                'expected' => ['hotels', 'restaurants'],
            ],
            [
                'slug' => 'quiet-stay-hidden',
                'prompt' => 'Find me a quiet stay and hidden gem in %s.',
                'required' => ['hotels', 'activities'],
                'expected' => ['hotels', 'activities'],
            ],
            [
                'slug' => 'breakfast-cultural',
                'prompt' => 'Recommend breakfast and a cultural walk in %s.',
                'required' => ['restaurants', 'activities'],
                'expected' => ['restaurants', 'activities'],
            ],
            [
                'slug' => 'wine-stay',
                'prompt' => 'I want a romantic wine bar and seaside stay in %s.',
                'required' => ['hotels', 'restaurants'],
                'expected' => ['hotels', 'restaurants'],
            ],
            [
                'slug' => 'pizza-scenic',
                'prompt' => 'Find me pizza for lunch and somewhere scenic to walk in %s.',
                'required' => ['restaurants', 'activities'],
                'expected' => ['restaurants', 'activities'],
            ],
            [
                'slug' => 'full-city-stack',
                'prompt' => 'Show me a hotel, dinner, and things to do in %s.',
                'required' => ['hotels', 'restaurants', 'activities'],
                'expected' => ['hotels', 'restaurants', 'activities'],
            ],
            [
                'slug' => 'budget-full-stack',
                'prompt' => 'Need a budget stay under $100, a breakfast cafe, and a quiet hidden gem in %s.',
                'required' => ['hotels', 'restaurants', 'activities'],
                'expected' => ['hotels', 'restaurants', 'activities'],
                'budget_max' => 100,
            ],
        ];

        foreach ($this->mixedCities() as $cityKey) {
            $cityName = $this->cityDisplay($cityKey);

            foreach ($templates as $template) {
                $cases[] = $this->case(
                    "{$cityKey}-mixed-{$template['slug']}",
                    sprintf($template['prompt'], $cityName),
                    $cityKey,
                    null,
                    false,
                    null,
                    false,
                    $template['required'],
                    [],
                    $template['expected'],
                    true,
                    false,
                    false,
                    $template['budget_max'] ?? null
                );
            }
        }

        return $cases;
    }

    private function case(
        string $label,
        string $prompt,
        ?string $city,
        ?string $endCity,
        bool $hold,
        ?int $dayCount,
        bool $tripPlan,
        array $requiredCategories,
        array $forbiddenCategories,
        array $expectedNonEmpty,
        bool $onlyExpectedResults = true,
        bool $expectStay = false,
        bool $expectNoStay = false,
        ?int $budgetMax = null,
        bool $ensureRouteCoverage = false,
    ): array {
        return [
            'label' => $label,
            'prompt' => $prompt,
            'city' => $city,
            'end_city' => $endCity,
            'hold' => $hold,
            'day_count' => $dayCount,
            'trip_plan' => $tripPlan,
            'required_categories' => $requiredCategories,
            'forbidden_categories' => $forbiddenCategories,
            'expected_non_empty' => $expectedNonEmpty,
            'only_expected_results' => $onlyExpectedResults,
            'expect_stay' => $expectStay,
            'expect_no_stay' => $expectNoStay,
            'budget_max' => $budgetMax,
            'ensure_route_coverage' => $ensureRouteCoverage,
        ];
    }

    private function canonicalCities(): array
    {
        return [
            'beirut' => 'Beirut',
            'batroun' => 'Batroun',
            'tyre' => 'Tyre',
            'byblos' => 'Byblos',
            'tripoli' => 'Tripoli',
            'jounieh' => 'Jounieh',
            'saida' => 'Saida',
            'broumana' => 'Broumana',
            'zahle' => 'Zahle',
        ];
    }

    private function aliasCities(): array
    {
        return [
            'tyre' => 'Sur',
            'byblos' => 'Jbeil',
            'tripoli' => 'Trablos',
            'saida' => 'Sidon',
            'broumana' => 'Brummana',
            'zahle' => 'Zahleh',
        ];
    }

    private function mixedCities(): array
    {
        return ['beirut', 'byblos', 'batroun', 'tyre', 'tripoli', 'zahle'];
    }

    private function routePairs(): array
    {
        return [
            ['beirut', 'byblos'],
            ['byblos', 'tyre'],
            ['batroun', 'beirut'],
            ['tripoli', 'jounieh'],
            ['saida', 'tyre'],
            ['broumana', 'zahle'],
            ['beirut', 'batroun'],
            ['jounieh', 'saida'],
            ['zahle', 'byblos'],
            ['tripoli', 'beirut'],
        ];
    }

    private function routeCityLabel(string $cityKey, string $style): string
    {
        if ($style === 'alias' && array_key_exists($cityKey, $this->aliasCities())) {
            return $this->aliasCities()[$cityKey];
        }

        return $this->cityDisplay($cityKey);
    }

    private function cityDisplay(string $cityKey): string
    {
        return $this->canonicalCities()[$cityKey] ?? ucfirst($cityKey);
    }

    private function generalGuidancePrompts(): array
    {
        return [
            'hello',
            'hi there',
            'help me',
            'can you help',
            'show me ideas',
            'recommend something',
            'give me options',
            "i'm not sure",
            'surprise me',
            'what do you have',
        ];
    }

    private function hotelGuidancePrompts(): array
    {
        return [
            'hotel',
            'hotels',
            'recommend a hotel',
            'show me hotels',
            'i need a stay',
            'some hotel ideas',
            'place to stay',
            'accommodation',
            'find me a hotel',
            'somewhere to stay',
        ];
    }

    private function restaurantGuidancePrompts(): array
    {
        return [
            'restaurant',
            'restaurants',
            'recommend a restaurant',
            'show me restaurants',
            'dinner ideas',
            'lunch ideas',
            'breakfast ideas',
            'place to eat',
            'food spots',
            'where should i eat',
        ];
    }

    private function activityGuidancePrompts(): array
    {
        return [
            'activities',
            'things to do',
            'places to visit',
            'show me places',
            'what can i visit',
            'activity suggestions',
            'activity ideas',
            'activity options',
            'visit ideas',
            'some activity ideas',
        ];
    }

    private function tripGuidancePrompts(): array
    {
        return [
            ['prompt' => 'Plan a 1 day trip.', 'day_count' => 1],
            ['prompt' => 'Give me a day trip plan.', 'day_count' => 1],
            ['prompt' => 'Build a 1 day itinerary.', 'day_count' => 1],
            ['prompt' => 'Organize a single day trip.', 'day_count' => 1],
            ['prompt' => 'I need a 1 day trip plan.', 'day_count' => 1],
            ['prompt' => 'Create a 1 day plan for me.', 'day_count' => 1],
            ['prompt' => 'Help me plan a 1 day trip.', 'day_count' => 1],
            ['prompt' => 'Suggest a 1 day itinerary.', 'day_count' => 1],
            ['prompt' => 'I want a 1 day travel plan.', 'day_count' => 1],
            ['prompt' => 'Need a one day getaway plan.', 'day_count' => 1],
            ['prompt' => 'Plan a 2 day trip.', 'day_count' => 2],
            ['prompt' => 'Build me a 2 day getaway.', 'day_count' => 2],
            ['prompt' => 'Organize a 2 day seaside escape.', 'day_count' => 2],
            ['prompt' => 'I need a weekend plan.', 'day_count' => 2],
            ['prompt' => 'Plan a romantic weekend.', 'day_count' => 2],
            ['prompt' => 'I want a 2 night trip.', 'day_count' => 2],
            ['prompt' => 'Give me a 2 day itinerary.', 'day_count' => 2],
            ['prompt' => 'Help me plan 2 days away.', 'day_count' => 2],
            ['prompt' => 'Create a 2 day coastal plan.', 'day_count' => 2],
            ['prompt' => 'Need a weekend itinerary.', 'day_count' => 2],
            ['prompt' => 'Plan a 3 day trip.', 'day_count' => 3],
            ['prompt' => 'Build a 3 day family getaway.', 'day_count' => 3],
            ['prompt' => 'Organize a 3 day holiday.', 'day_count' => 3],
            ['prompt' => 'I need a 3 day travel plan.', 'day_count' => 3],
            ['prompt' => 'Create a 3 day itinerary.', 'day_count' => 3],
            ['prompt' => 'Help me plan 3 days away.', 'day_count' => 3],
            ['prompt' => 'Suggest a 3 day seaside trip.', 'day_count' => 3],
            ['prompt' => 'I want a 3 day romantic trip.', 'day_count' => 3],
            ['prompt' => 'Give me a 3 day trip itinerary.', 'day_count' => 3],
            ['prompt' => 'Need a 3 day family itinerary.', 'day_count' => 3],
            ['prompt' => 'Plan a 4 day trip.', 'day_count' => 4],
            ['prompt' => 'Build me a 4 day route.', 'day_count' => 4],
            ['prompt' => 'Organize a 4 day coastal holiday.', 'day_count' => 4],
            ['prompt' => 'I need a 4 day escape.', 'day_count' => 4],
            ['prompt' => 'Create a 4 day travel plan.', 'day_count' => 4],
            ['prompt' => 'Help me plan 4 days away.', 'day_count' => 4],
            ['prompt' => 'Suggest a 4 day itinerary.', 'day_count' => 4],
            ['prompt' => 'I want a 4 day vacation plan.', 'day_count' => 4],
            ['prompt' => 'Give me a 4 day trip idea.', 'day_count' => 4],
            ['prompt' => 'Need a 4 day trip plan.', 'day_count' => 4],
        ];
    }

    private function assertUniqueItems(array $items, string $key, string $message): void
    {
        $values = array_values(array_filter(array_map(
            fn ($item) => is_array($item) ? trim((string) ($item[$key] ?? '')) : '',
            $items
        )));

        $this->assertSameSize(array_unique($values), $values, $message);
    }

    private function assertResultCitiesInclude(array $items, array $requiredCities, string $message): void
    {
        $cities = array_values(array_unique(array_filter(array_map(
            fn ($item) => is_array($item) ? strtolower(trim((string) ($item['city'] ?? ''))) : '',
            $items
        ))));

        foreach ($requiredCities as $city) {
            $this->assertContains($city, $cities, $message . " Missing city {$city}.");
        }
    }

    private function tripPlanContainsSlot(array $tripPlan, string $slot): bool
    {
        foreach ((array) ($tripPlan['days'] ?? []) as $day) {
            if (is_array($day) && array_key_exists($slot, (array) ($day['flow'] ?? []))) {
                return true;
            }
        }

        return false;
    }
}
