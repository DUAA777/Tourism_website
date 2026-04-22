<?php

namespace Tests\Feature;

use App\Services\RecommendationService;
use Database\Seeders\ActivitiesTableSeeder;
use Database\Seeders\HotelsTableSeeder;
use Database\Seeders\RestaurantsTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationAcceptanceSetTest extends TestCase
{
    use RefreshDatabase;

    private RecommendationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            HotelsTableSeeder::class,
            RestaurantsTableSeeder::class,
            ActivitiesTableSeeder::class,
        ]);

        $this->service = app(RecommendationService::class);
    }

    public function test_final_acceptance_set_outputs_stay_grounded(): void
    {
        foreach ($this->independentAcceptancePrompts() as $case) {
            $result = $this->service->buildResponseData($case['prompt']);
            $this->assertAcceptanceCase($case, $result);
        }
    }

    public function test_final_acceptance_follow_ups_keep_context_without_leaking_categories(): void
    {
        $seed = $this->service->buildResponseData('Plan a 2 day seaside trip in Batroun with sunset and seafood');
        $context = ['intent' => $seed['intent']];

        foreach ($this->followUpAcceptancePrompts() as $case) {
            $result = $this->service->buildResponseData($case['prompt'], $context);
            $this->assertAcceptanceCase($case, $result);
            $context = ['intent' => $result['intent']];
        }
    }

    private function independentAcceptancePrompts(): array
    {
        return [
            ['prompt' => 'Hi', 'hold' => true],
            ['prompt' => 'Recommend a hotel', 'hold' => true, 'wants' => ['hotels']],
            ['prompt' => 'Find me restaurants', 'hold' => true, 'wants' => ['restaurants']],
            ['prompt' => 'What should I do in Lebanon?', 'hold' => true],
            ['prompt' => 'Recommend a hotel in Beirut', 'type' => 'hotel_recommendation', 'city' => 'beirut', 'has' => ['hotels'], 'only' => ['hotels']],
            ['prompt' => 'Find me a budget hotel in Byblos under $100', 'type' => 'hotel_recommendation', 'city' => 'byblos', 'has' => ['hotels'], 'only' => ['hotels'], 'max_hotel_price' => 100],
            ['prompt' => 'I want a cozy guesthouse in Byblos', 'type' => 'hotel_recommendation', 'city' => 'byblos', 'has' => ['hotels'], 'only' => ['hotels']],
            ['prompt' => 'Find me a premium business hotel in Beirut', 'type' => 'hotel_recommendation', 'city' => 'beirut', 'has' => ['hotels'], 'only' => ['hotels']],
            ['prompt' => 'Suggest a romantic stay in Batroun', 'type' => 'hotel_recommendation', 'city' => 'batroun', 'has' => ['hotels'], 'only' => ['hotels']],
            ['prompt' => 'Find me a seafood dinner in Batroun', 'type' => 'restaurant_recommendation', 'city' => 'batroun', 'has' => ['restaurants'], 'only' => ['restaurants']],
            ['prompt' => 'Recommend a romantic wine bar in Beirut', 'type' => 'restaurant_recommendation', 'city' => 'beirut', 'has' => ['restaurants'], 'only' => ['restaurants']],
            ['prompt' => 'I want sushi for dinner in Beirut', 'type' => 'restaurant_recommendation', 'city' => 'beirut', 'has' => ['restaurants'], 'only' => ['restaurants']],
            ['prompt' => 'Find me a healthy breakfast cafe in Beirut', 'type' => 'restaurant_recommendation', 'city' => 'beirut', 'has' => ['restaurants'], 'only' => ['restaurants']],
            ['prompt' => 'Casual lunch in Byblos', 'type' => 'restaurant_recommendation', 'city' => 'byblos', 'has' => ['restaurants'], 'only' => ['restaurants']],
            ['prompt' => 'Give me hidden gem places in Lebanon for a quiet day', 'type' => 'activity_recommendation', 'has' => ['activities'], 'only' => ['activities']],
            ['prompt' => 'I want something touristy and famous', 'type' => 'activity_recommendation', 'has' => ['activities'], 'only' => ['activities']],
            ['prompt' => 'Find me cultural old streets to explore', 'type' => 'activity_recommendation', 'has' => ['activities'], 'only' => ['activities']],
            ['prompt' => 'Give me family-friendly activities in Byblos', 'type' => 'activity_recommendation', 'city' => 'byblos', 'has' => ['activities'], 'only' => ['activities']],
            ['prompt' => 'I want a rooftop or nice view in Beirut', 'type' => 'activity_recommendation', 'city' => 'beirut', 'has' => ['activities'], 'only' => ['activities']],
            ['prompt' => 'Activities only, no restaurants', 'hold' => true, 'wants' => ['activities'], 'not_wants' => ['restaurants']],
            ['prompt' => 'Plan a 1 day trip in Tripoli', 'type' => 'trip_plan', 'city' => 'tripoli', 'days' => 1, 'has_trip' => true],
            ['prompt' => 'Plan a 2 day seaside trip in Batroun with sunset and seafood', 'type' => 'trip_plan', 'city' => 'batroun', 'days' => 2, 'has_trip' => true, 'has' => ['hotels', 'restaurants', 'activities']],
            ['prompt' => 'Plan a 3 day family trip in Zahle', 'type' => 'trip_plan', 'city' => 'zahle', 'days' => 3, 'has_trip' => true],
            ['prompt' => 'Plan a 2 day romantic weekend in Byblos', 'type' => 'trip_plan', 'city' => 'byblos', 'days' => 2, 'has_trip' => true],
            ['prompt' => 'Plan a 3 day trip from Byblos to Tyre', 'type' => 'trip_plan', 'route' => ['byblos', 'tyre'], 'days' => 3, 'has_trip' => true],
            ['prompt' => 'I want seafood, sunset, and a stay', 'type' => 'mixed_recommendation', 'not_hold' => true, 'has' => ['hotels', 'restaurants']],
            ['prompt' => 'I want old streets, coffee, and a calm hotel', 'type' => 'mixed_recommendation', 'not_hold' => true, 'has' => ['hotels', 'restaurants', 'activities']],
            ['prompt' => 'Recommend a hotel and dinner in Beirut', 'type' => 'mixed_recommendation', 'city' => 'beirut', 'has' => ['hotels', 'restaurants']],
            ['prompt' => 'Plan a quiet day with local food and culture', 'type' => 'mixed_recommendation', 'not_hold' => true, 'has' => ['restaurants', 'activities']],
            ['prompt' => 'I am with family and kids, where should we go?', 'type' => 'activity_recommendation', 'has' => ['activities'], 'only' => ['activities']],
            ['prompt' => 'I want a Japanese sushi dinner in Jbeil', 'city' => 'byblos', 'no_wrong_city_in' => ['restaurants']],
            ['prompt' => 'Find me pizza in Sur', 'city' => 'tyre', 'no_wrong_city_in' => ['restaurants']],
            ['prompt' => "I don't want Tripoli, suggest hidden gems elsewhere", 'type' => 'activity_recommendation', 'excluded' => ['tripoli'], 'no_city_in_results' => ['tripoli'], 'has' => ['activities']],
            ['prompt' => 'No hotel, just food and activities', 'not_wants' => ['hotels'], 'wants' => ['restaurants', 'activities'], 'not_has' => ['hotels']],
            ['prompt' => 'Not too expensive but still romantic', 'budget' => 'budget', 'not_concepts' => ['luxury'], 'not_hold' => true],
            ['prompt' => 'I want something relaxing, not nightlife', 'not_concepts' => ['nightlife'], 'not_hold' => true],
            ['prompt' => 'Plan a trip without restaurants', 'not_wants' => ['restaurants']],
        ];
    }

    private function followUpAcceptancePrompts(): array
    {
        return [
            ['prompt' => 'Make it cheaper', 'budget' => 'budget'],
            ['prompt' => 'Change it to Byblos', 'city' => 'byblos'],
            ['prompt' => 'Same vibe but in Batroun', 'city' => 'batroun'],
            ['prompt' => "I don't like that city, give me another", 'excluded' => ['batroun'], 'no_city_in_results' => ['batroun']],
            ['prompt' => 'Remove the hotel from the plan', 'not_wants' => ['hotels'], 'not_has' => ['hotels']],
            ['prompt' => 'Add a stay to the plan', 'wants' => ['hotels'], 'has' => ['hotels']],
            ['prompt' => 'Make the trip 3 days instead', 'type' => 'trip_plan', 'days' => 3],
            ['prompt' => 'Shorten it to one day', 'type' => 'trip_plan', 'days' => 1],
        ];
    }

    private function assertAcceptanceCase(array $case, array $result): void
    {
        $prompt = $case['prompt'];
        $intent = $result['intent'];

        if (!empty($case['hold'])) {
            $this->assertTrue((bool) ($intent['should_hold_results'] ?? false), "Expected guidance hold for: {$prompt}");
        }

        if (!empty($case['not_hold'])) {
            $this->assertFalse((bool) ($intent['should_hold_results'] ?? false), "Unexpected guidance hold for: {$prompt}");
        }

        if (isset($case['type'])) {
            $this->assertSame($case['type'], $intent['message_type'] ?? null, "Wrong message type for: {$prompt}");
        }

        if (isset($case['city'])) {
            $this->assertSame($case['city'], $intent['resolved_city'] ?? $intent['city'] ?? null, "Wrong resolved city for: {$prompt}");
            $this->assertOnlyCityResults($result, $case['city'], $prompt);
        }

        if (isset($case['route'])) {
            foreach ($case['route'] as $city) {
                $this->assertContains($city, $intent['mentioned_cities'] ?? [], "Route missing {$city} for: {$prompt}");
            }
        }

        if (isset($case['days'])) {
            $this->assertCount($case['days'], $result['trip_plan']['days'] ?? [], "Wrong trip day count for: {$prompt}");
        }

        if (!empty($case['has_trip'])) {
            $this->assertNotEmpty($result['trip_plan']['days'] ?? [], "Missing trip plan for: {$prompt}");
        }

        foreach (($case['has'] ?? []) as $category) {
            $this->assertNotEmpty($result[$category] ?? [], "Missing {$category} for: {$prompt}");
        }

        foreach (($case['not_has'] ?? []) as $category) {
            $this->assertEmpty($result[$category] ?? [], "Unexpected {$category} for: {$prompt}");
        }

        if (isset($case['only'])) {
            foreach (['hotels', 'restaurants', 'activities'] as $category) {
                if (!in_array($category, $case['only'], true)) {
                    $this->assertEmpty($result[$category] ?? [], "Unexpected {$category} for: {$prompt}");
                }
            }
        }

        foreach (($case['wants'] ?? []) as $category) {
            $this->assertTrue($this->intentWantsCategory($intent, $category), "Intent should want {$category} for: {$prompt}");
        }

        foreach (($case['not_wants'] ?? []) as $category) {
            $this->assertFalse($this->intentWantsCategory($intent, $category), "Intent should not want {$category} for: {$prompt}");
            $this->assertNotContains($category, $intent['requested_categories'] ?? [], "Requested categories should not include {$category} for: {$prompt}");
        }

        if (isset($case['budget'])) {
            $this->assertSame($case['budget'], $intent['budget'] ?? null, "Wrong budget for: {$prompt}");
        }

        foreach (($case['not_concepts'] ?? []) as $concept) {
            $this->assertNotContains($concept, $intent['semantic_concepts'] ?? [], "Unexpected concept {$concept} for: {$prompt}");
        }

        foreach (($case['excluded'] ?? []) as $city) {
            $this->assertContains($city, $intent['excluded_cities'] ?? [], "Missing excluded city {$city} for: {$prompt}");
        }

        foreach (($case['no_city_in_results'] ?? []) as $city) {
            $this->assertNotContains($city, $this->resultCities($result), "Returned excluded city {$city} for: {$prompt}");
        }

        if (isset($case['max_hotel_price'])) {
            foreach ($result['hotels'] ?? [] as $hotel) {
                $this->assertLessThanOrEqual($case['max_hotel_price'], (float) ($hotel['price_per_night'] ?? 999999), "Hotel over budget for: {$prompt}");
            }
        }

        foreach (($case['no_wrong_city_in'] ?? []) as $category) {
            foreach ($this->categoryCities($result, $category) as $city) {
                $this->assertSame($case['city'], $city, "Wrong {$category} city for unsupported request: {$prompt}");
            }
        }
    }

    private function intentWantsCategory(array $intent, string $category): bool
    {
        return match ($category) {
            'hotels' => !empty($intent['wants_hotel']) || !empty($intent['requires_stay']),
            'restaurants' => !empty($intent['wants_restaurant']),
            'activities' => !empty($intent['wants_activity']),
            default => false,
        };
    }

    private function assertOnlyCityResults(array $result, string $expectedCity, string $prompt): void
    {
        foreach (['hotels', 'restaurants', 'activities'] as $category) {
            foreach ($this->categoryCities($result, $category) as $city) {
                $this->assertSame($expectedCity, $city, "{$category} returned {$city}, expected {$expectedCity} for: {$prompt}");
            }
        }
    }

    private function resultCities(array $result): array
    {
        return array_values(array_unique(array_merge(
            $this->categoryCities($result, 'hotels'),
            $this->categoryCities($result, 'restaurants'),
            $this->categoryCities($result, 'activities')
        )));
    }

    private function categoryCities(array $result, string $category): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn (array $item) => strtolower((string) ($item['city'] ?? '')),
            $result[$category] ?? []
        ))));
    }
}
