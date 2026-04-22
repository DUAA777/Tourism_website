<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Hotel;
use App\Models\Restaurant;
use App\Services\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationRegressionMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_recommendation_regression_matrix_covers_100_prompts(): void
    {
        $this->seedRegressionCatalog();

        $cases = $this->regressionPromptMatrix();
        $this->assertCount(100, $cases);

        $service = app(RecommendationService::class);

        foreach ($cases as $index => $case) {
            $context = sprintf(
                'Case %d [%s] prompt "%s"',
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
                $this->assertSame([], $results['hotels'], $context . ' vague request should not surface hotels.');
                $this->assertSame([], $results['restaurants'], $context . ' vague request should not surface restaurants.');
                $this->assertSame([], $results['activities'], $context . ' vague request should not surface activities.');
                $this->assertNull($results['trip_plan'], $context . ' vague request should not build a trip plan.');

                continue;
            }

            if ($case['main_category'] === 'hotels') {
                $this->assertNotEmpty($results['hotels'], $context . ' expected hotel results.');
                if ($case['city'] !== null) {
                    $this->assertSame($case['city'], $results['hotels'][0]['city'] ?? null, $context . ' top hotel city mismatch.');
                }
            }

            if ($case['main_category'] === 'restaurants') {
                $this->assertNotEmpty($results['restaurants'], $context . ' expected restaurant results.');
                if ($case['city'] !== null) {
                    $this->assertSame($case['city'], $results['restaurants'][0]['city'] ?? null, $context . ' top restaurant city mismatch.');
                }
            }

            if ($case['main_category'] === 'activities') {
                $this->assertNotEmpty($results['activities'], $context . ' expected activity results.');
                if ($case['city'] !== null) {
                    $this->assertSame($case['city'], $results['activities'][0]['city'] ?? null, $context . ' top activity city mismatch.');
                }
            }

            if ($case['trip_plan']) {
                $tripPlan = $results['trip_plan'];
                $this->assertIsArray($tripPlan, $context . ' expected a trip plan array.');
                $this->assertCount($case['day_count'], $tripPlan['days'] ?? [], $context . ' trip day count mismatch.');

                if ($case['city'] !== null) {
                    $this->assertSame(ucfirst($case['city']), $tripPlan['days'][0]['location'] ?? null, $context . ' trip location mismatch.');
                }

                if ($case['expect_stay']) {
                    $this->assertTrue($this->tripPlanContainsSlot($tripPlan, 'stay'), $context . ' expected stay in trip plan.');
                }

                if ($case['expect_no_stay']) {
                    $this->assertFalse($this->tripPlanContainsSlot($tripPlan, 'stay'), $context . ' one-day trip should not include stay.');
                }
            } else {
                $this->assertNull($results['trip_plan'], $context . ' non-trip request should not build trip plan.');
            }
        }
    }

    private function seedRegressionCatalog(): void
    {
        foreach ($this->cities() as $cityKey => $cityName) {
            Hotel::create([
                'hotel_name' => "{$cityName} Budget Bay Stay",
                'address' => "{$cityName} Waterfront",
                'distance_from_beach' => '150 m',
                'rating_score' => 4.3,
                'review_count' => 160,
                'price_per_night' => '82$',
                'description' => "A budget seaside hotel in {$cityName} with calm coastal views.",
                'stay_details' => 'Best for relaxed value stays near the beach.',
                'vibe_tags' => ['relaxing', 'beach'],
                'audience_tags' => ['friends', 'couple'],
                'search_text' => "{$cityKey} budget seaside hotel beach sunset affordable stay",
            ]);

            Hotel::create([
                'hotel_name' => "{$cityName} Sunset Retreat",
                'address' => "{$cityName} Coast",
                'distance_from_beach' => '120 m',
                'rating_score' => 4.7,
                'review_count' => 210,
                'price_per_night' => '165$',
                'description' => "A romantic seaside retreat in {$cityName} with sunset views.",
                'stay_details' => 'Ideal for couples, special occasions, and sea-view evenings.',
                'vibe_tags' => ['romantic', 'sunset', 'beach'],
                'audience_tags' => ['couple'],
                'search_text' => "{$cityKey} romantic seaside hotel sunset views couple retreat",
            ]);

            Hotel::create([
                'hotel_name' => "{$cityName} Family Harbor Hotel",
                'address' => "{$cityName} Harbor",
                'distance_from_beach' => '350 m',
                'rating_score' => 4.5,
                'review_count' => 180,
                'price_per_night' => '128$',
                'description' => "A family-friendly harbor hotel in {$cityName} with spacious rooms.",
                'stay_details' => 'Comfortable for children and longer family stays.',
                'vibe_tags' => ['cozy', 'family'],
                'audience_tags' => ['family'],
                'search_text' => "{$cityKey} family hotel kids comfortable harbor stay",
            ]);

            Hotel::create([
                'hotel_name' => "{$cityName} Business Grand Hotel",
                'address' => "{$cityName} Center",
                'distance_from_beach' => '2.5 km',
                'rating_score' => 4.8,
                'review_count' => 240,
                'price_per_night' => '255$',
                'description' => "A luxury business hotel in central {$cityName}.",
                'stay_details' => 'Good for meetings, premium comfort, and city access.',
                'vibe_tags' => ['luxury', 'city'],
                'audience_tags' => ['business'],
                'search_text' => "{$cityKey} luxury business hotel premium city center meetings",
            ]);

            Restaurant::create([
                'restaurant_name' => "{$cityName} Sea View Table",
                'location' => "{$cityName} Waterfront",
                'rating' => 4.7,
                'price_tier' => 'Mid-range',
                'food_type' => 'Seafood',
                'description' => "A romantic seafood dinner spot in {$cityName} with sea views and sunset tables.",
                'vibe_tags' => ['romantic', 'beach', 'relaxing'],
                'occasion_tags' => ['date', 'dinner'],
                'search_text' => "{$cityKey} seafood dinner romantic sea view sunset waterfront",
            ]);

            Restaurant::create([
                'restaurant_name' => "{$cityName} Old Town Lunch House",
                'location' => "{$cityName} Old Town",
                'rating' => 4.5,
                'price_tier' => 'Mid-range',
                'food_type' => 'Lebanese',
                'description' => "A casual Lebanese lunch place in the old town of {$cityName}.",
                'vibe_tags' => ['cultural', 'casual'],
                'occasion_tags' => ['lunch', 'casual'],
                'search_text' => "{$cityKey} lebanese lunch casual old town local food",
            ]);

            Restaurant::create([
                'restaurant_name' => "{$cityName} Harbor Breakfast Cafe",
                'location' => "{$cityName} Harbor",
                'rating' => 4.4,
                'price_tier' => 'Budget',
                'food_type' => 'Cafe',
                'description' => "A cozy breakfast cafe in {$cityName} for coffee and brunch.",
                'vibe_tags' => ['cozy'],
                'occasion_tags' => ['breakfast', 'casual'],
                'search_text' => "{$cityKey} breakfast cafe coffee brunch cozy harbor",
            ]);

            Restaurant::create([
                'restaurant_name' => "{$cityName} Night Burger Club",
                'location' => "{$cityName} Downtown",
                'rating' => 4.6,
                'price_tier' => 'Premium',
                'food_type' => 'Burgers',
                'description' => "A lively late-night burger and drinks spot in {$cityName}.",
                'vibe_tags' => ['lively', 'nightlife'],
                'occasion_tags' => ['friends', 'night-out'],
                'search_text' => "{$cityKey} burgers drinks nightlife lively party night out",
            ]);

            Activity::create([
                'name' => "{$cityName} Sunset Waterfront Walk",
                'city' => $cityKey,
                'category' => 'scenic',
                'description' => "A relaxed seaside walk in {$cityName} best enjoyed at sunset.",
                'location' => "{$cityName} Waterfront",
                'best_time' => 'sunset',
                'duration_estimate' => '1 hour',
                'price_type' => 'free',
                'vibe_tags' => ['relaxing', 'sunset', 'beach'],
                'occasion_tags' => ['date', 'casual'],
                'search_text' => "{$cityKey} sunset walk waterfront scenic seaside",
            ]);

            Activity::create([
                'name' => "{$cityName} Old Town Souk Walk",
                'city' => $cityKey,
                'category' => 'cultural',
                'description' => "A cultural morning walk through the old town and souk of {$cityName}.",
                'location' => "{$cityName} Old Town",
                'best_time' => 'morning',
                'duration_estimate' => '1 hour',
                'price_type' => 'free',
                'vibe_tags' => ['cultural', 'casual'],
                'occasion_tags' => ['friends', 'casual'],
                'search_text' => "{$cityKey} cultural old town souk walk heritage morning",
            ]);

            Activity::create([
                'name' => "{$cityName} Hidden Courtyard Spot",
                'city' => $cityKey,
                'category' => 'hidden_gem',
                'description' => "A quiet hidden gem in {$cityName} away from the busy spots.",
                'location' => "{$cityName} Backstreets",
                'best_time' => 'afternoon',
                'duration_estimate' => '45 minutes',
                'price_type' => 'free',
                'vibe_tags' => ['relaxing', 'hidden_gem'],
                'occasion_tags' => ['casual'],
                'search_text' => "{$cityKey} hidden gem quiet local courtyard relaxing",
            ]);

            Activity::create([
                'name' => "{$cityName} Family Beach Stop",
                'city' => $cityKey,
                'category' => 'beach',
                'description' => "A family-friendly beach stop in {$cityName}.",
                'location' => "{$cityName} Beachfront",
                'best_time' => 'afternoon',
                'duration_estimate' => '2 hours',
                'price_type' => 'low',
                'vibe_tags' => ['family', 'beach'],
                'occasion_tags' => ['family', 'casual'],
                'search_text' => "{$cityKey} family beach activity kids seaside afternoon",
            ]);

            Activity::create([
                'name' => "{$cityName} Rooftop Night Out",
                'city' => $cityKey,
                'category' => 'nightlife',
                'description' => "A lively nightlife rooftop experience in {$cityName}.",
                'location' => "{$cityName} Center",
                'best_time' => 'evening',
                'duration_estimate' => '2 hours',
                'price_type' => 'premium',
                'vibe_tags' => ['lively', 'nightlife'],
                'occasion_tags' => ['friends', 'night-out'],
                'search_text' => "{$cityKey} rooftop nightlife party drinks evening",
            ]);
        }
    }

    private function regressionPromptMatrix(): array
    {
        $cases = [];
        $cities = $this->cities();

        foreach ($cities as $cityKey => $cityName) {
            $cases[] = $this->case("{$cityKey}-hotel-budget", "Find me a budget hotel in {$cityName} under \$90 by the sea", $cityKey, false, null, false, ['hotels'], [], 'hotels');
            $cases[] = $this->case("{$cityKey}-hotel-romantic", "Find me a romantic seaside stay in {$cityName} with sunset views", $cityKey, false, null, false, ['hotels'], [], 'hotels');
            $cases[] = $this->case("{$cityKey}-hotel-family", "Find me a family hotel in {$cityName} near the beach", $cityKey, false, null, false, ['hotels'], [], 'hotels');
            $cases[] = $this->case("{$cityKey}-hotel-luxury", "Find me a luxury hotel in {$cityName} for a premium stay", $cityKey, false, null, false, ['hotels'], [], 'hotels');
            $cases[] = $this->case("{$cityKey}-hotel-business", "I need a business hotel in {$cityName} for meetings", $cityKey, false, null, false, ['hotels'], [], 'hotels');

            $cases[] = $this->case("{$cityKey}-restaurant-seafood", "Find me a seafood dinner in {$cityName}", $cityKey, false, null, false, ['restaurants'], [], 'restaurants');
            $cases[] = $this->case("{$cityKey}-restaurant-romantic", "Find a romantic dinner in {$cityName} with a sea view", $cityKey, false, null, false, ['restaurants'], [], 'restaurants');
            $cases[] = $this->case("{$cityKey}-restaurant-lunch", "Find a casual Lebanese lunch in {$cityName}", $cityKey, false, null, false, ['restaurants'], [], 'restaurants');
            $cases[] = $this->case("{$cityKey}-restaurant-breakfast", "Recommend a breakfast cafe in {$cityName}", $cityKey, false, null, false, ['restaurants'], [], 'restaurants');
            $cases[] = $this->case("{$cityKey}-restaurant-nightlife", "I want burgers and drinks in {$cityName} for a night out", $cityKey, false, null, false, ['restaurants'], [], 'restaurants');

            $cases[] = $this->case("{$cityKey}-activity-hidden", "Give me hidden gem places in {$cityName} for a quiet day", $cityKey, false, null, false, ['activities'], [], 'activities');
            $cases[] = $this->case("{$cityKey}-activity-sunset", "Find me a sunset walk in {$cityName}", $cityKey, false, null, false, ['activities'], [], 'activities');
            $cases[] = $this->case("{$cityKey}-activity-cultural", "I want a cultural old town walk in {$cityName}", $cityKey, false, null, false, ['activities'], [], 'activities');
            $cases[] = $this->case("{$cityKey}-activity-family", "Find a family beach activity in {$cityName}", $cityKey, false, null, false, ['activities'], [], 'activities');
            $cases[] = $this->case("{$cityKey}-activity-night", "Show me nightlife activities in {$cityName}", $cityKey, false, null, false, ['activities'], [], 'activities');

            $cases[] = $this->case("{$cityKey}-trip-2day", "Plan a 2 day seaside trip in {$cityName} with sunset and seafood", $cityKey, false, 2, true, ['trip_plan', 'hotels'], [], 'trip_plan', true, false);
            $cases[] = $this->case("{$cityKey}-trip-weekend", "Plan a romantic weekend in {$cityName} by the sea", $cityKey, false, 2, true, ['trip_plan', 'hotels'], [], 'trip_plan', true, false);
            $cases[] = $this->case("{$cityKey}-trip-family", "Plan a 3 day family trip in {$cityName}", $cityKey, false, 3, true, ['trip_plan', 'hotels'], [], 'trip_plan', true, false);
            $cases[] = $this->case("{$cityKey}-trip-1day", "Plan a 1 day cultural trip in {$cityName}", $cityKey, false, 1, true, ['trip_plan'], ['hotels'], 'trip_plan', false, true);
            $cases[] = $this->case("{$cityKey}-trip-2nights", "I want to stay in {$cityName} for 2 nights by the sea", $cityKey, false, 2, true, ['trip_plan', 'hotels'], [], 'trip_plan', true, false);
        }

        $routeCases = [
            ['batroun', 'beirut'],
            ['beirut', 'byblos'],
            ['byblos', 'jounieh'],
            ['jounieh', 'batroun'],
        ];

        foreach ($routeCases as [$fromKey, $toKey]) {
            $cases[] = $this->case(
                "{$fromKey}-to-{$toKey}",
                'Plan a 3 day trip from ' . $cities[$fromKey] . ' to ' . $cities[$toKey] . ' with seafood',
                $fromKey,
                false,
                3,
                true,
                ['trip_plan', 'hotels'],
                [],
                'trip_plan',
                true,
                false
            );
        }

        $semanticCases = [
            ['semantic-romantic-seafood', 'Find me a romantic seafood dinner with a sea view', false, null, false, ['restaurants'], [], 'restaurants'],
            ['semantic-budget-stay', 'Find me a budget seaside stay under $100', false, null, false, ['hotels'], [], 'hotels'],
            ['semantic-hidden-gem', 'Give me a quiet hidden gem place to visit', false, null, false, ['activities'], [], 'activities'],
            ['semantic-family-beach', 'Find me a family beach stay', false, null, false, ['hotels'], [], 'hotels'],
            ['semantic-nightlife-burgers', 'I want burgers and drinks for a lively night out', false, null, false, ['restaurants'], [], 'restaurants'],
            ['semantic-breakfast-cafe', 'Recommend a cozy breakfast cafe', false, null, false, ['restaurants'], [], 'restaurants'],
        ];

        foreach ($semanticCases as [$label, $prompt, $hold, $dayCount, $tripPlan, $required, $forbidden, $mainCategory]) {
            $cases[] = $this->case($label, $prompt, null, $hold, $dayCount, $tripPlan, $required, $forbidden, $mainCategory);
        }

        $guidanceCases = [
            ['guide-hello', 'hello', null, false, []],
            ['guide-help', 'help me', null, false, []],
            ['guide-hotel', 'recommend a hotel', null, false, ['hotels']],
            ['guide-restaurant', 'recommend a restaurant', null, false, ['restaurants']],
            ['guide-activities', 'recommend activities', null, false, ['activities']],
            ['guide-trip', 'plan a trip', 2, true, ['trip_plan']],
        ];

        foreach ($guidanceCases as [$label, $prompt, $dayCount, $tripPlan, $requiredCategories]) {
            $cases[] = $this->case($label, $prompt, null, true, $dayCount, $tripPlan, $requiredCategories, [], 'none');
        }

        $tripGuidanceCases = [
            ['guide-trip-no-city-3', 'I want a 3 day family trip', 3],
            ['guide-trip-no-city-4', 'Build me a 2 night getaway', 2],
        ];

        foreach ($tripGuidanceCases as [$label, $prompt, $dayCount]) {
            $cases[] = $this->case($label, $prompt, null, true, $dayCount, true, ['trip_plan'], [], 'none');
        }

        $strongCitylessTripCases = [
            ['cityless-strong-seaside-trip', 'Plan a 2 day seaside trip with sunset and seafood', 2],
            ['cityless-strong-romantic-sea-weekend', 'Plan a romantic weekend by the sea', 2],
        ];

        foreach ($strongCitylessTripCases as [$label, $prompt, $dayCount]) {
            $cases[] = $this->case($label, $prompt, null, false, $dayCount, true, ['trip_plan', 'hotels'], [], 'trip_plan', true, false);
        }

        return $cases;
    }

    private function case(
        string $label,
        string $prompt,
        ?string $city,
        bool $hold,
        ?int $dayCount,
        bool $tripPlan,
        array $requiredCategories,
        array $forbiddenCategories,
        string $mainCategory,
        bool $expectStay = false,
        bool $expectNoStay = false,
    ): array {
        return [
            'label' => $label,
            'prompt' => $prompt,
            'city' => $city,
            'hold' => $hold,
            'day_count' => $dayCount,
            'trip_plan' => $tripPlan,
            'required_categories' => $requiredCategories,
            'forbidden_categories' => $forbiddenCategories,
            'main_category' => $mainCategory,
            'expect_stay' => $expectStay,
            'expect_no_stay' => $expectNoStay,
        ];
    }

    private function cities(): array
    {
        return [
            'batroun' => 'Batroun',
            'beirut' => 'Beirut',
            'byblos' => 'Byblos',
            'jounieh' => 'Jounieh',
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
