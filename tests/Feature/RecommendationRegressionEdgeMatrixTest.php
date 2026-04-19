<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Hotel;
use App\Models\Restaurant;
use App\Services\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationRegressionEdgeMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_recommendation_edge_regression_matrix_covers_another_100_prompts(): void
    {
        $this->seedAliasRegressionCatalog();

        $cases = $this->edgeRegressionPromptMatrix();
        $this->assertCount(100, $cases);

        $service = app(RecommendationService::class);

        foreach ($cases as $index => $case) {
            $context = sprintf(
                'Edge case %d [%s] prompt "%s"',
                $index + 1,
                $case['label'],
                $case['prompt']
            );

            $results = $service->buildResponseData($case['prompt']);
            $intent = $results['intent'];

            $this->assertSame($case['hold'], $intent['should_hold_results'], $context . ' should_hold_results mismatch.');
            $this->assertSame($case['trip_plan'], $intent['wants_trip_plan'], $context . ' trip plan routing mismatch.');
            $this->assertSame($case['day_count'], $intent['day_count'], $context . ' day count mismatch.');
            $this->assertSame(
                $case['hold'] ? 'needs_guidance' : false,
                $case['hold'] ? ($results['diagnostics']['confidence']['overall'] ?? null) : false,
                $context . ' held prompts should report needs_guidance confidence.'
            );

            if (!$case['hold']) {
                $this->assertNotSame('needs_guidance', $results['diagnostics']['confidence']['overall'] ?? null, $context . ' confident prompt should not report needs_guidance.');
            }

            if ($case['city'] !== null) {
                $this->assertSame($case['city'], $intent['city'], $context . ' extracted city mismatch.');
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
                    $this->assertFalse($this->tripPlanContainsSlot($tripPlan, 'stay'), $context . ' trip should not include stay.');
                }
            } else {
                $this->assertNull($results['trip_plan'], $context . ' non-trip request should not build a trip plan.');
            }
        }
    }

    private function seedAliasRegressionCatalog(): void
    {
        foreach ($this->aliasCities() as $cityKey => $cityData) {
            $display = $cityData['display'];

            Hotel::create([
                'hotel_name' => "{$display} Budget Family Stay",
                'address' => "{$display} Central District",
                'distance_from_beach' => '700 m',
                'rating_score' => 4.3,
                'review_count' => 140,
                'price_per_night' => '88$',
                'description' => "A budget family-friendly stay in {$display}.",
                'stay_details' => 'Good value rooms for families and relaxed breaks.',
                'vibe_tags' => ['cozy', 'family'],
                'audience_tags' => ['family'],
                'search_text' => "{$cityKey} budget family hotel value stay kids",
            ]);

            Hotel::create([
                'hotel_name' => "{$display} Romantic Garden Retreat",
                'address' => "{$display} Hillside",
                'distance_from_beach' => '1.2 km',
                'rating_score' => 4.7,
                'review_count' => 190,
                'price_per_night' => '158$',
                'description' => "A romantic retreat in {$display} with quiet evenings and charming views.",
                'stay_details' => 'Ideal for couples and special weekends.',
                'vibe_tags' => ['romantic', 'relaxing', 'cozy'],
                'audience_tags' => ['couple'],
                'search_text' => "{$cityKey} romantic relaxing retreat couple weekend views",
            ]);

            Hotel::create([
                'hotel_name' => "{$display} Business Grand Hotel",
                'address' => "{$display} Downtown",
                'distance_from_beach' => '2 km',
                'rating_score' => 4.8,
                'review_count' => 230,
                'price_per_night' => '245$',
                'description' => "A premium city hotel in {$display} suited for business trips.",
                'stay_details' => 'Good for meetings, clients, and premium city access.',
                'vibe_tags' => ['luxury', 'city'],
                'audience_tags' => ['business'],
                'search_text' => "{$cityKey} business hotel meetings premium downtown city center",
            ]);

            Restaurant::create([
                'restaurant_name' => "{$display} Sushi House",
                'location' => "{$display} Center",
                'rating' => 4.7,
                'price_tier' => 'Premium',
                'food_type' => 'Japanese',
                'description' => "A polished Japanese dinner spot in {$display}.",
                'vibe_tags' => ['romantic', 'city'],
                'occasion_tags' => ['date', 'dinner'],
                'search_text' => "{$cityKey} sushi japanese dinner romantic date night",
            ]);

            Restaurant::create([
                'restaurant_name' => "{$display} Stone Pizza Oven",
                'location' => "{$display} Old Town",
                'rating' => 4.5,
                'price_tier' => 'Mid-range',
                'food_type' => 'Pizza',
                'description' => "A casual pizza lunch and dinner place in {$display}.",
                'vibe_tags' => ['casual', 'cultural'],
                'occasion_tags' => ['lunch', 'casual'],
                'search_text' => "{$cityKey} pizza italian casual lunch old town",
            ]);

            Restaurant::create([
                'restaurant_name' => "{$display} Green Bowl Cafe",
                'location' => "{$display} Garden Quarter",
                'rating' => 4.4,
                'price_tier' => 'Budget',
                'food_type' => 'Healthy, Cafe',
                'description' => "A healthy breakfast and brunch cafe in {$display}.",
                'vibe_tags' => ['cozy', 'relaxing'],
                'occasion_tags' => ['breakfast', 'casual'],
                'search_text' => "{$cityKey} healthy breakfast brunch cafe coffee bowls cozy",
            ]);

            Restaurant::create([
                'restaurant_name' => "{$display} Sunset Wine Bar",
                'location' => "{$display} Rooftop",
                'rating' => 4.6,
                'price_tier' => 'Premium',
                'food_type' => 'Wine',
                'description' => "A romantic wine bar in {$display} for sunset drinks and date nights.",
                'vibe_tags' => ['romantic', 'nightlife', 'scenic'],
                'occasion_tags' => ['date', 'night-out', 'dinner'],
                'search_text' => "{$cityKey} wine bar sunset drinks romantic date night",
            ]);

            Activity::create([
                'name' => "{$display} Heritage Souk Walk",
                'city' => $cityKey,
                'category' => 'cultural',
                'description' => "A heritage walk through the old town of {$display}.",
                'location' => "{$display} Old Town",
                'best_time' => 'morning',
                'duration_estimate' => '1 hour',
                'price_type' => 'free',
                'vibe_tags' => ['cultural', 'casual'],
                'occasion_tags' => ['casual'],
                'search_text' => "{$cityKey} heritage cultural old town souk walk morning",
            ]);

            Activity::create([
                'name' => "{$display} Quiet Garden Escape",
                'city' => $cityKey,
                'category' => 'hidden_gem',
                'description' => "A quiet hidden garden escape in {$display}.",
                'location' => "{$display} Hillside",
                'best_time' => 'afternoon',
                'duration_estimate' => '1 hour',
                'price_type' => 'free',
                'vibe_tags' => ['relaxing', 'hidden_gem'],
                'occasion_tags' => ['casual'],
                'search_text' => "{$cityKey} quiet hidden gem relaxing garden escape afternoon",
            ]);

            Activity::create([
                'name' => "{$display} Family Park Stop",
                'city' => $cityKey,
                'category' => 'city',
                'description' => "A family-friendly city park stop in {$display}.",
                'location' => "{$display} Park",
                'best_time' => 'afternoon',
                'duration_estimate' => '90 minutes',
                'price_type' => 'low',
                'vibe_tags' => ['family', 'casual'],
                'occasion_tags' => ['family'],
                'search_text' => "{$cityKey} family city park activity kids afternoon",
            ]);

            Activity::create([
                'name' => "{$display} Rooftop Night Out",
                'city' => $cityKey,
                'category' => 'nightlife',
                'description' => "A lively rooftop nightlife stop in {$display}.",
                'location' => "{$display} Center",
                'best_time' => 'evening',
                'duration_estimate' => '2 hours',
                'price_type' => 'premium',
                'vibe_tags' => ['lively', 'nightlife'],
                'occasion_tags' => ['friends', 'night-out'],
                'search_text' => "{$cityKey} rooftop nightlife lively drinks party evening",
            ]);
        }
    }

    private function edgeRegressionPromptMatrix(): array
    {
        $cases = [];
        $cities = $this->aliasCities();

        foreach ($cities as $cityKey => $cityData) {
            $display = $cityData['display'];
            $alias = $cityData['alias'];

            $cases[] = $this->case(
                "{$cityKey}-alias-hotel-budget",
                "Find me a budget family hotel in {$alias} under \$100",
                $cityKey,
                false,
                null,
                false,
                ['hotels'],
                [],
                'hotels',
                false,
                false,
                100
            );
            $cases[] = $this->case("{$cityKey}-alias-hotel-romantic", "Find me a romantic stay in {$alias} for a quiet weekend", $cityKey, false, 2, false, ['hotels'], [], 'hotels');

            $cases[] = $this->case("{$cityKey}-alias-sushi-dinner", "Find me a japanese sushi dinner in {$alias}", $cityKey, false, null, false, ['restaurants'], [], 'restaurants');
            $cases[] = $this->case("{$cityKey}-alias-pizza-lunch", "Find me a casual pizza lunch in {$alias}", $cityKey, false, null, false, ['restaurants'], [], 'restaurants');
            $cases[] = $this->case("{$cityKey}-alias-healthy-breakfast", "Recommend a healthy breakfast cafe in {$alias}", $cityKey, false, null, false, ['restaurants'], [], 'restaurants');

            $cases[] = $this->case("{$cityKey}-alias-cultural-walk", "I want a cultural heritage walk in {$alias}", $cityKey, false, null, false, ['activities'], [], 'activities');
            $cases[] = $this->case("{$cityKey}-alias-hidden-gem", "Give me a quiet hidden gem place in {$alias}", $cityKey, false, null, false, ['activities'], [], 'activities');
            $cases[] = $this->case("{$cityKey}-alias-nightlife-activity", "Show me nightlife activities in {$alias}", $cityKey, false, null, false, ['activities'], [], 'activities');

            $cases[] = $this->case("{$cityKey}-alias-trip-2day", "Plan a 2 day trip in {$alias} with japanese food and quiet places", $cityKey, false, 2, true, ['trip_plan', 'hotels'], [], 'trip_plan', true, false);
        }

        $routeCases = [
            ['jbeil', 'sur', 'byblos', 'tyre'],
            ['sur', 'sidon', 'tyre', 'saida'],
            ['sidon', 'trablos', 'saida', 'tripoli'],
            ['trablos', 'brummana', 'tripoli', 'broumana'],
            ['brummana', 'zahleh', 'broumana', 'zahle'],
            ['zahleh', 'jbeil', 'zahle', 'byblos'],
        ];

        foreach ($routeCases as [$fromAlias, $toAlias, $fromCity, $toCity]) {
            $cases[] = $this->case(
                "{$fromCity}-to-{$toCity}-alias-route",
                "Plan a 3 day trip from {$fromAlias} to {$toAlias} with quiet food stops",
                $fromCity,
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

        $strongSemanticCases = [
            ['semantic-sushi', 'Find me a japanese sushi dinner', 'restaurants', ['restaurants'], [], null],
            ['semantic-pizza', 'Find me a casual pizza lunch', 'restaurants', ['restaurants'], [], null],
            ['semantic-breakfast', 'Recommend a healthy breakfast cafe', 'restaurants', ['restaurants'], [], null],
            ['semantic-wine-date', 'Find me a romantic wine bar for a date night', 'restaurants', ['restaurants'], [], null],
            ['semantic-budget-family-stay', 'Find me a budget family hotel under $100', 'hotels', ['hotels'], [], 100],
            ['semantic-business-stay', 'I need a business hotel for meetings', 'hotels', ['hotels'], [], null],
            ['semantic-cultural', 'I want a cultural heritage walk', 'activities', ['activities'], [], null],
            ['semantic-hidden', 'Give me a quiet hidden gem place', 'activities', ['activities'], [], null],
            ['semantic-family-activity', 'Find a family activity for kids', 'activities', ['activities'], [], null],
            ['semantic-nightlife-activity', 'Show me a nightlife rooftop activity', 'activities', ['activities'], [], null],
            ['semantic-sushi-2', 'I want sushi for dinner tonight', 'restaurants', ['restaurants'], [], null],
            ['semantic-pizza-2', 'Recommend pizza for lunch', 'restaurants', ['restaurants'], [], null],
            ['semantic-breakfast-2', 'I need brunch and coffee', 'restaurants', ['restaurants'], [], null],
            ['semantic-wine-date-2', 'Suggest drinks for a romantic date night', 'restaurants', ['restaurants'], [], null],
            ['semantic-budget-hotel-2', 'Need an affordable family stay', 'hotels', ['hotels'], [], null],
            ['semantic-business-hotel-2', 'Premium hotel for clients and meetings', 'hotels', ['hotels'], [], null],
            ['semantic-cultural-2', 'Show me heritage places to walk around', 'activities', ['activities'], [], null],
            ['semantic-hidden-2', 'I want somewhere quiet and less touristy', 'activities', ['activities'], [], null],
            ['semantic-family-2', 'Kids friendly place to spend the afternoon', 'activities', ['activities'], [], null],
            ['semantic-nightlife-2', 'I want a lively rooftop night out', 'activities', ['activities'], [], null],
        ];

        foreach ($strongSemanticCases as [$label, $prompt, $mainCategory, $required, $forbidden, $budgetMax]) {
            $cases[] = $this->case(
                $label,
                $prompt,
                null,
                false,
                null,
                false,
                $required,
                $forbidden,
                $mainCategory,
                false,
                false,
                $budgetMax
            );
        }

        $guidanceCases = [
            ['guide-hi', 'hi', null, false, []],
            ['guide-help', 'help', null, false, []],
            ['guide-something', 'recommend something', null, false, []],
            ['guide-options', 'show me options', null, false, []],
            ['guide-unsure', "i'm not sure", null, false, []],
            ['guide-where', 'where should i go', null, false, []],
            ['guide-hotel', 'hotel', null, false, ['hotels']],
            ['guide-restaurants', 'restaurants', null, false, ['restaurants']],
            ['guide-activities', 'activities', null, false, ['activities']],
            ['guide-what-do', 'what should i do', null, false, []],
        ];

        foreach ($guidanceCases as [$label, $prompt, $dayCount, $tripPlan, $requiredCategories]) {
            $cases[] = $this->case($label, $prompt, null, true, $dayCount, $tripPlan, $requiredCategories, [], 'none');
        }

        $tripGuidanceCases = [
            ['guide-weekend', 'Plan a weekend escape', 2],
            ['guide-2day-romantic', 'Plan a 2 day romantic getaway', 2],
            ['guide-3day-family', 'I want a 3 day family trip', 3],
            ['guide-2night', 'Build me a 2 night plan', 2],
            ['guide-weekend-quiet', 'Organize a quiet weekend', 2],
            ['guide-3day-escape', 'Plan a 3 day escape', 3],
            ['guide-seaside-weekend', 'Plan a seaside weekend', 2],
            ['guide-date-weekend', 'Plan a date weekend', 2],
            ['guide-holiday', 'Plan a family holiday for 2 days', 2],
            ['guide-trip', 'I need a 2 day trip idea', 2],
        ];

        foreach ($tripGuidanceCases as [$label, $prompt, $dayCount]) {
            $cases[] = $this->case($label, $prompt, null, true, $dayCount, true, ['trip_plan'], [], 'none');
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
        ?int $budgetMax = null,
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
            'budget_max' => $budgetMax,
        ];
    }

    private function aliasCities(): array
    {
        return [
            'byblos' => ['display' => 'Byblos', 'alias' => 'Jbeil'],
            'tyre' => ['display' => 'Tyre', 'alias' => 'Sur'],
            'saida' => ['display' => 'Saida', 'alias' => 'Sidon'],
            'tripoli' => ['display' => 'Tripoli', 'alias' => 'Trablos'],
            'broumana' => ['display' => 'Broumana', 'alias' => 'Brummana'],
            'zahle' => ['display' => 'Zahle', 'alias' => 'Zahleh'],
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
