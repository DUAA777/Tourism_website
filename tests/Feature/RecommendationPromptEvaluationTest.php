<?php

namespace Tests\Feature;

use App\Services\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RecommendationPromptEvaluationTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('evaluationPromptProvider')]
    public function test_prompt_evaluation_suite_covers_core_tourism_queries(
        string $prompt,
        ?string $expectedCity,
        ?string $expectedBudget,
        ?int $expectedBudgetMax,
        ?int $expectedDayCount,
        bool $expectedTripPlan,
        array $expectedCategories,
        array $expectedFoodPreferences,
        array $expectedVibeTags
    ): void {
        $results = app(RecommendationService::class)->buildResponseData($prompt);
        $intent = $results['intent'];

        $this->assertSame($expectedCity, $intent['city']);
        $this->assertSame($expectedBudget, $intent['budget']);
        $this->assertSame($expectedBudgetMax, $intent['budget_max']);
        $this->assertSame($expectedDayCount, $intent['day_count']);
        $this->assertSame($expectedTripPlan, $intent['wants_trip_plan']);

        foreach ($expectedCategories as $category) {
            $this->assertContains($category, $intent['requested_categories']);
        }

        foreach ($expectedFoodPreferences as $foodPreference) {
            $this->assertContains($foodPreference, $intent['food_preferences']);
        }

        foreach ($expectedVibeTags as $vibeTag) {
            $this->assertContains($vibeTag, $intent['vibe_tags']);
        }

        $this->assertNotEmpty($results['diagnostics']['summary_chips']);
    }

    public static function evaluationPromptProvider(): array
    {
        return [
            'budget hotel in byblos' => [
                'prompt' => 'Find me a budget hotel in Byblos under $80',
                'expectedCity' => 'byblos',
                'expectedBudget' => 'budget',
                'expectedBudgetMax' => 80,
                'expectedDayCount' => null,
                'expectedTripPlan' => false,
                'expectedCategories' => ['hotels'],
                'expectedFoodPreferences' => [],
                'expectedVibeTags' => [],
            ],
            'seaside batroun trip' => [
                'prompt' => 'Plan a 2 day seaside trip in Batroun with sunset and seafood',
                'expectedCity' => 'batroun',
                'expectedBudget' => null,
                'expectedBudgetMax' => null,
                'expectedDayCount' => 2,
                'expectedTripPlan' => true,
                'expectedCategories' => ['hotels', 'restaurants', 'trip_plan'],
                'expectedFoodPreferences' => ['seafood'],
                'expectedVibeTags' => ['beach', 'sunset'],
            ],
            'romantic dinner in beirut' => [
                'prompt' => 'Find a romantic dinner in Beirut with a sea view',
                'expectedCity' => 'beirut',
                'expectedBudget' => null,
                'expectedBudgetMax' => null,
                'expectedDayCount' => null,
                'expectedTripPlan' => false,
                'expectedCategories' => ['restaurants'],
                'expectedFoodPreferences' => [],
                'expectedVibeTags' => ['romantic'],
            ],
            'three night stay in beirut' => [
                'prompt' => 'I want to stay in Beirut for 3 nights by the sea',
                'expectedCity' => 'beirut',
                'expectedBudget' => null,
                'expectedBudgetMax' => null,
                'expectedDayCount' => 3,
                'expectedTripPlan' => true,
                'expectedCategories' => ['hotels', 'trip_plan'],
                'expectedFoodPreferences' => [],
                'expectedVibeTags' => ['beach'],
            ],
            'hidden gem day in batroun' => [
                'prompt' => 'Give me hidden gem places in Batroun for a quiet day',
                'expectedCity' => 'batroun',
                'expectedBudget' => null,
                'expectedBudgetMax' => null,
                'expectedDayCount' => null,
                'expectedTripPlan' => false,
                'expectedCategories' => ['activities'],
                'expectedFoodPreferences' => [],
                'expectedVibeTags' => ['hidden_gem', 'relaxing'],
            ],
            'plan dinner is not a trip' => [
                'prompt' => 'Can you plan a romantic dinner in Beirut with a sea view',
                'expectedCity' => 'beirut',
                'expectedBudget' => null,
                'expectedBudgetMax' => null,
                'expectedDayCount' => null,
                'expectedTripPlan' => false,
                'expectedCategories' => ['restaurants'],
                'expectedFoodPreferences' => [],
                'expectedVibeTags' => ['romantic', 'beach'],
            ],
        ];
    }
}
