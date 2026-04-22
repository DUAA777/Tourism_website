<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Hotel;
use App\Models\Restaurant;
use App\Services\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationConversationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_conversation_flow_matrix_covers_50_human_turns(): void
    {
        $this->seedConversationCatalog();

        $flows = $this->conversationFlows();
        $this->assertSame(50, array_sum(array_map('count', $flows)));

        $service = app(RecommendationService::class);

        foreach ($flows as $flowLabel => $turns) {
            $sessionContext = null;

            foreach ($turns as $turnIndex => $turn) {
                $context = sprintf(
                    'Conversation %s turn %d "%s"',
                    $flowLabel,
                    $turnIndex + 1,
                    $turn['prompt']
                );

                $results = $service->buildResponseData($turn['prompt'], $sessionContext);
                $intent = $results['intent'];

                $this->assertSame($turn['hold'] ?? false, $intent['should_hold_results'], $context . ' hold mismatch.');
                $this->assertSame($turn['trip'] ?? false, $intent['wants_trip_plan'], $context . ' trip mismatch.');

                if (array_key_exists('city', $turn)) {
                    $this->assertSame($turn['city'], $intent['resolved_city'], $context . ' city mismatch.');
                }

                if (array_key_exists('day_count', $turn)) {
                    $this->assertSame($turn['day_count'], $intent['day_count'], $context . ' day count mismatch.');
                }

                if (array_key_exists('budget', $turn)) {
                    $this->assertSame($turn['budget'], $intent['budget'], $context . ' budget mismatch.');
                }

                foreach ($turn['requires'] ?? [] as $category) {
                    $this->assertContains($category, $intent['requested_categories'], $context . " missing category {$category}.");
                }

                foreach ($turn['forbids'] ?? [] as $category) {
                    $this->assertNotContains($category, $intent['requested_categories'], $context . " should not include {$category}.");
                }

                if ($turn['hold'] ?? false) {
                    $this->assertSame([], $results['hotels'], $context . ' held prompt returned hotels.');
                    $this->assertSame([], $results['restaurants'], $context . ' held prompt returned restaurants.');
                    $this->assertSame([], $results['activities'], $context . ' held prompt returned activities.');
                    $this->assertNull($results['trip_plan'], $context . ' held prompt built a trip.');
                    $sessionContext = ['intent' => $intent];
                    continue;
                }

                foreach ($turn['non_empty'] ?? [] as $category) {
                    $this->assertNotEmpty($results[$category] ?? [], $context . " expected {$category} results.");
                }

                if ($turn['only'] ?? false) {
                    foreach (['hotels', 'restaurants', 'activities'] as $category) {
                        if (!in_array($category, $turn['non_empty'] ?? [], true)) {
                            $this->assertSame([], $results[$category] ?? [], $context . " should not return {$category}.");
                        }
                    }
                }

                if (array_key_exists('top_city', $turn)) {
                    foreach ($turn['non_empty'] ?? [] as $category) {
                        $this->assertSame($turn['top_city'], $results[$category][0]['city'] ?? null, $context . " top {$category} city mismatch.");
                    }
                }

                if (array_key_exists('max_hotel_price', $turn)) {
                    $price = (string) ($results['hotels'][0]['price_per_night'] ?? '');
                    preg_match('/\d+/', $price, $matches);
                    $this->assertNotEmpty($matches, $context . ' expected hotel price.');
                    $this->assertLessThanOrEqual($turn['max_hotel_price'], (int) $matches[0], $context . ' hotel exceeds max price.');
                }

                if ($turn['trip'] ?? false) {
                    $tripPlan = $results['trip_plan'];
                    $this->assertIsArray($tripPlan, $context . ' expected trip plan.');

                    if (array_key_exists('day_count', $turn)) {
                        $this->assertCount($turn['day_count'], $tripPlan['days'] ?? [], $context . ' trip day count mismatch.');
                    }

                    if (array_key_exists('trip_city', $turn)) {
                        $this->assertSame($this->cityDisplay($turn['trip_city']), $tripPlan['days'][0]['location'] ?? null, $context . ' trip city mismatch.');
                    }

                    if (array_key_exists('route', $turn)) {
                        $days = $tripPlan['days'] ?? [];
                        $this->assertSame($this->cityDisplay($turn['route'][0]), $days[0]['location'] ?? null, $context . ' route start mismatch.');
                        $this->assertSame($this->cityDisplay($turn['route'][count($turn['route']) - 1]), $days[count($days) - 1]['location'] ?? null, $context . ' route end mismatch.');
                    }

                    if ($turn['expect_stay'] ?? false) {
                        $this->assertTrue($this->tripPlanContainsSlot($tripPlan, 'stay'), $context . ' expected stay.');
                    }

                    if ($turn['expect_no_stay'] ?? false) {
                        $this->assertFalse($this->tripPlanContainsSlot($tripPlan, 'stay'), $context . ' should not include stay.');
                    }
                } else {
                    $this->assertNull($results['trip_plan'], $context . ' non-trip built a trip.');
                }

                $sessionContext = ['intent' => $intent];
            }
        }
    }

    private function conversationFlows(): array
    {
        return [
            'hotel-refinement' => [
                ['prompt' => 'Find me a romantic hotel in Byblos', 'city' => 'byblos', 'requires' => ['hotels'], 'non_empty' => ['hotels'], 'only' => true, 'top_city' => 'byblos'],
                ['prompt' => 'make it cheaper', 'city' => 'byblos', 'budget' => 'budget', 'requires' => ['hotels'], 'non_empty' => ['hotels'], 'only' => true, 'top_city' => 'byblos', 'max_hotel_price' => 90],
                ['prompt' => 'same vibe but in Tyre', 'city' => 'tyre', 'requires' => ['hotels'], 'non_empty' => ['hotels'], 'only' => true, 'top_city' => 'tyre'],
                ['prompt' => 'show restaurants instead', 'city' => 'tyre', 'requires' => ['restaurants'], 'non_empty' => ['restaurants'], 'only' => true, 'top_city' => 'tyre'],
                ['prompt' => 'no actually just hotels', 'city' => 'tyre', 'requires' => ['hotels'], 'non_empty' => ['hotels'], 'only' => true, 'top_city' => 'tyre'],
            ],
            'trip-stay-control' => [
                ['prompt' => 'Plan 2 days in Batroun with seafood and sunset', 'city' => 'batroun', 'trip' => true, 'day_count' => 2, 'requires' => ['trip_plan', 'hotels'], 'non_empty' => ['hotels', 'restaurants', 'activities'], 'trip_city' => 'batroun', 'expect_stay' => true],
                ['prompt' => 'make it 4 days', 'city' => 'batroun', 'trip' => true, 'day_count' => 4, 'requires' => ['trip_plan', 'hotels'], 'non_empty' => ['hotels', 'restaurants', 'activities'], 'trip_city' => 'batroun', 'expect_stay' => true],
                ['prompt' => 'without a stay', 'city' => 'batroun', 'trip' => true, 'day_count' => 4, 'requires' => ['trip_plan'], 'forbids' => ['hotels'], 'non_empty' => ['restaurants', 'activities'], 'trip_city' => 'batroun', 'expect_no_stay' => true],
                ['prompt' => 'add hotel back', 'city' => 'batroun', 'trip' => true, 'day_count' => 4, 'requires' => ['trip_plan', 'hotels'], 'non_empty' => ['hotels', 'restaurants', 'activities'], 'trip_city' => 'batroun', 'expect_stay' => true],
                ['prompt' => 'make it in Byblos', 'city' => 'byblos', 'trip' => true, 'day_count' => 4, 'requires' => ['trip_plan', 'hotels'], 'non_empty' => ['hotels', 'restaurants', 'activities'], 'trip_city' => 'byblos', 'expect_stay' => true],
            ],
            'restaurant-to-activity' => [
                ['prompt' => 'romantic wine bar in Beirut for date night', 'city' => 'beirut', 'requires' => ['restaurants'], 'non_empty' => ['restaurants'], 'only' => true, 'top_city' => 'beirut'],
                ['prompt' => 'not premium, something affordable', 'city' => 'beirut', 'budget' => 'budget', 'requires' => ['restaurants'], 'non_empty' => ['restaurants'], 'only' => true, 'top_city' => 'beirut'],
                ['prompt' => 'same city but sushi', 'city' => 'beirut', 'requires' => ['restaurants'], 'non_empty' => ['restaurants'], 'only' => true, 'top_city' => 'beirut'],
                ['prompt' => 'switch to lunch not dinner', 'city' => 'beirut', 'requires' => ['restaurants'], 'non_empty' => ['restaurants'], 'only' => true, 'top_city' => 'beirut'],
                ['prompt' => 'actually show activities instead', 'city' => 'beirut', 'requires' => ['activities'], 'non_empty' => ['activities'], 'only' => true, 'top_city' => 'beirut'],
            ],
            'route-editing' => [
                ['prompt' => 'plan 3 days from Beirut to Byblos to Tyre with food stops', 'city' => 'beirut', 'trip' => true, 'day_count' => 3, 'requires' => ['trip_plan', 'hotels'], 'non_empty' => ['hotels', 'restaurants', 'activities'], 'route' => ['beirut', 'byblos', 'tyre'], 'expect_stay' => true],
                ['prompt' => 'make it 2 days instead', 'city' => 'beirut', 'trip' => true, 'day_count' => 2, 'requires' => ['trip_plan', 'hotels'], 'non_empty' => ['hotels', 'restaurants', 'activities'], 'route' => ['beirut', 'tyre'], 'expect_stay' => true],
                ['prompt' => 'no hotels', 'city' => 'beirut', 'trip' => true, 'day_count' => 2, 'requires' => ['trip_plan'], 'forbids' => ['hotels'], 'non_empty' => ['restaurants', 'activities'], 'route' => ['beirut', 'tyre'], 'expect_no_stay' => true],
                ['prompt' => 'add hotels back', 'city' => 'beirut', 'trip' => true, 'day_count' => 2, 'requires' => ['trip_plan', 'hotels'], 'non_empty' => ['hotels', 'restaurants', 'activities'], 'route' => ['beirut', 'tyre'], 'expect_stay' => true],
                ['prompt' => 'only restaurants instead', 'city' => 'beirut', 'requires' => ['restaurants'], 'non_empty' => ['restaurants'], 'only' => true],
            ],
            'aliases-and-one-day' => [
                ['prompt' => 'romantic dinner in Jbeil', 'city' => 'byblos', 'requires' => ['restaurants'], 'non_empty' => ['restaurants'], 'only' => true, 'top_city' => 'byblos'],
                ['prompt' => 'same but in Sur', 'city' => 'tyre', 'requires' => ['restaurants'], 'non_empty' => ['restaurants'], 'only' => true, 'top_city' => 'tyre'],
                ['prompt' => 'make it a 1 day plan', 'city' => 'tyre', 'trip' => true, 'day_count' => 1, 'requires' => ['trip_plan'], 'non_empty' => ['restaurants', 'activities'], 'trip_city' => 'tyre', 'expect_no_stay' => true],
                ['prompt' => 'add a stay', 'city' => 'tyre', 'trip' => true, 'day_count' => 1, 'requires' => ['trip_plan', 'hotels'], 'non_empty' => ['hotels', 'restaurants', 'activities'], 'trip_city' => 'tyre', 'expect_stay' => true],
                ['prompt' => 'remove stay again', 'city' => 'tyre', 'trip' => true, 'day_count' => 1, 'requires' => ['trip_plan'], 'forbids' => ['hotels'], 'non_empty' => ['restaurants', 'activities'], 'trip_city' => 'tyre', 'expect_no_stay' => true],
            ],
            'negative-and-category-switching' => [
                ['prompt' => 'plan 2 days in Tripoli with food and no nightlife', 'city' => 'tripoli', 'trip' => true, 'day_count' => 2, 'requires' => ['trip_plan', 'hotels'], 'non_empty' => ['hotels', 'restaurants', 'activities'], 'trip_city' => 'tripoli', 'expect_stay' => true],
                ['prompt' => 'just activities', 'city' => 'tripoli', 'requires' => ['activities'], 'non_empty' => ['activities'], 'only' => true, 'top_city' => 'tripoli'],
                ['prompt' => 'cultural places only', 'city' => 'tripoli', 'requires' => ['activities'], 'non_empty' => ['activities'], 'only' => true, 'top_city' => 'tripoli'],
                ['prompt' => 'show restaurants instead, Lebanese lunch', 'city' => 'tripoli', 'requires' => ['restaurants'], 'non_empty' => ['restaurants'], 'only' => true, 'top_city' => 'tripoli'],
                ['prompt' => 'not a trip, just a restaurant', 'city' => 'tripoli', 'requires' => ['restaurants'], 'forbids' => ['trip_plan'], 'non_empty' => ['restaurants'], 'only' => true, 'top_city' => 'tripoli'],
            ],
            'city-change' => [
                ['prompt' => 'family hotel in Beirut under $100', 'city' => 'beirut', 'requires' => ['hotels'], 'non_empty' => ['hotels'], 'only' => true, 'top_city' => 'beirut', 'max_hotel_price' => 100],
                ['prompt' => 'same for Byblos', 'city' => 'byblos', 'requires' => ['hotels'], 'non_empty' => ['hotels'], 'only' => true, 'top_city' => 'byblos', 'max_hotel_price' => 100],
                ['prompt' => 'actually make it restaurants', 'city' => 'byblos', 'requires' => ['restaurants'], 'non_empty' => ['restaurants'], 'only' => true, 'top_city' => 'byblos'],
                ['prompt' => 'now plan 2 days there', 'city' => 'byblos', 'trip' => true, 'day_count' => 2, 'requires' => ['trip_plan', 'hotels'], 'non_empty' => ['hotels', 'restaurants', 'activities'], 'trip_city' => 'byblos', 'expect_stay' => true],
                ['prompt' => 'change to Batroun', 'city' => 'batroun', 'trip' => true, 'day_count' => 2, 'requires' => ['trip_plan', 'hotels'], 'non_empty' => ['hotels', 'restaurants', 'activities'], 'trip_city' => 'batroun', 'expect_stay' => true],
            ],
            'one-day-edits' => [
                ['prompt' => 'one relaxed day in Saida with lunch and cultural walk no hotel', 'city' => 'saida', 'trip' => true, 'day_count' => 1, 'requires' => ['trip_plan', 'restaurants', 'activities'], 'forbids' => ['hotels'], 'non_empty' => ['restaurants', 'activities'], 'trip_city' => 'saida', 'expect_no_stay' => true],
                ['prompt' => 'make it in Tyre', 'city' => 'tyre', 'trip' => true, 'day_count' => 1, 'requires' => ['trip_plan'], 'forbids' => ['hotels'], 'non_empty' => ['restaurants', 'activities'], 'trip_city' => 'tyre', 'expect_no_stay' => true],
                ['prompt' => 'add dinner', 'city' => 'tyre', 'trip' => true, 'day_count' => 1, 'requires' => ['trip_plan', 'restaurants'], 'forbids' => ['hotels'], 'non_empty' => ['restaurants', 'activities'], 'trip_city' => 'tyre', 'expect_no_stay' => true],
                ['prompt' => 'make it two days', 'city' => 'tyre', 'trip' => true, 'day_count' => 2, 'requires' => ['trip_plan', 'hotels'], 'non_empty' => ['hotels', 'restaurants', 'activities'], 'trip_city' => 'tyre', 'expect_stay' => true],
                ['prompt' => 'without hotel', 'city' => 'tyre', 'trip' => true, 'day_count' => 2, 'requires' => ['trip_plan'], 'forbids' => ['hotels'], 'non_empty' => ['restaurants', 'activities'], 'trip_city' => 'tyre', 'expect_no_stay' => true],
            ],
            'mixed-category-containment' => [
                ['prompt' => 'show me hotel dinner and things to do in Broumana', 'city' => 'broumana', 'requires' => ['hotels', 'restaurants', 'activities'], 'non_empty' => ['hotels', 'restaurants', 'activities'], 'top_city' => 'broumana'],
                ['prompt' => 'just hotels', 'city' => 'broumana', 'requires' => ['hotels'], 'non_empty' => ['hotels'], 'only' => true, 'top_city' => 'broumana'],
                ['prompt' => 'only things to do', 'city' => 'broumana', 'requires' => ['activities'], 'non_empty' => ['activities'], 'only' => true, 'top_city' => 'broumana'],
                ['prompt' => 'restaurants instead', 'city' => 'broumana', 'requires' => ['restaurants'], 'non_empty' => ['restaurants'], 'only' => true, 'top_city' => 'broumana'],
                ['prompt' => 'plan it as 3 days', 'city' => 'broumana', 'trip' => true, 'day_count' => 3, 'requires' => ['trip_plan', 'hotels'], 'non_empty' => ['hotels', 'restaurants', 'activities'], 'trip_city' => 'broumana', 'expect_stay' => true],
            ],
            'vague-to-specific' => [
                ['prompt' => 'hello', 'hold' => true],
                ['prompt' => "I'm not sure what I want yet", 'hold' => true],
                ['prompt' => 'maybe a quiet seaside place in Zahle', 'city' => 'zahle', 'requires' => ['activities'], 'non_empty' => ['activities'], 'only' => true, 'top_city' => 'zahle'],
                ['prompt' => 'turn that into a weekend with food', 'city' => 'zahle', 'trip' => true, 'day_count' => 2, 'requires' => ['trip_plan', 'hotels'], 'non_empty' => ['hotels', 'restaurants', 'activities'], 'trip_city' => 'zahle', 'expect_stay' => true],
                ['prompt' => 'actually no hotel, just the day ideas', 'city' => 'zahle', 'trip' => true, 'day_count' => 2, 'requires' => ['trip_plan'], 'forbids' => ['hotels'], 'non_empty' => ['restaurants', 'activities'], 'trip_city' => 'zahle', 'expect_no_stay' => true],
            ],
        ];
    }

    private function seedConversationCatalog(): void
    {
        foreach ($this->cities() as $cityKey => $cityName) {
            Hotel::create([
                'hotel_name' => "{$cityName} Budget Family Stay",
                'address' => "{$cityName} Harbor",
                'distance_from_beach' => '350 m',
                'rating_score' => 8.5,
                'review_count' => 180,
                'price_per_night' => '82$',
                'description' => "Affordable family hotel in {$cityName} close to the water.",
                'stay_details' => 'Good value rooms for families and practical stays.',
                'vibe_tags' => ['cozy', 'family', 'relaxing'],
                'audience_tags' => ['family'],
                'search_text' => "{$cityKey} budget family hotel affordable cheap kids value harbor stay",
            ]);

            Hotel::create([
                'hotel_name' => "{$cityName} Romantic Sunset Hotel",
                'address' => "{$cityName} Waterfront",
                'distance_from_beach' => '120 m',
                'rating_score' => 9.1,
                'review_count' => 240,
                'price_per_night' => '165$',
                'description' => "Romantic seaside hotel in {$cityName} with sunset views.",
                'stay_details' => 'Good for couples, calm weekends, and sea-view evenings.',
                'vibe_tags' => ['romantic', 'sunset', 'beach', 'relaxing'],
                'audience_tags' => ['couple'],
                'search_text' => "{$cityKey} romantic seaside hotel sunset couple beach quiet weekend",
            ]);

            foreach ([
                ['Seafood Sunset Table', 'Waterfront', 'Seafood', 'Premium', ['romantic', 'beach', 'sunset'], ['date', 'dinner'], 'seafood dinner romantic fish sea view sunset waterfront'],
                ['Old Town Lebanese Kitchen', 'Old Town', 'Lebanese', 'Mid-range', ['cultural', 'casual'], ['lunch', 'casual'], 'lebanese lunch casual local food old town mezze'],
                ['Morning Green Cafe', 'Harbor', 'Healthy, Cafe', 'Budget', ['cozy', 'relaxing'], ['breakfast', 'casual'], 'healthy breakfast cafe brunch coffee bowls organic affordable'],
                ['Sushi Atelier', 'Center', 'Japanese', 'Premium', ['romantic', 'city'], ['date', 'dinner'], 'japanese sushi dinner date night premium'],
                ['Rooftop Wine Bar', 'Rooftop', 'Wine', 'Premium', ['romantic', 'nightlife', 'scenic'], ['date', 'night-out', 'dinner'], 'wine bar romantic date night rooftop drinks premium'],
            ] as [$name, $location, $food, $price, $vibes, $occasions, $search]) {
                Restaurant::create([
                    'restaurant_name' => "{$cityName} {$name}",
                    'location' => "{$cityName} {$location}",
                    'rating' => 4.7,
                    'price_tier' => $price,
                    'food_type' => $food,
                    'description' => "{$name} in {$cityName}.",
                    'vibe_tags' => $vibes,
                    'occasion_tags' => $occasions,
                    'search_text' => "{$cityKey} {$search}",
                ]);
            }

            foreach ([
                ['Sunset Waterfront Walk', 'scenic', 'Waterfront', 'sunset', ['relaxing', 'sunset', 'beach', 'scenic'], ['date', 'casual'], 'sunset walk waterfront scenic seaside golden hour'],
                ['Heritage Souk Walk', 'cultural', 'Old Town', 'morning', ['cultural', 'casual'], ['casual'], 'heritage cultural old town souk walk history morning'],
                ['Hidden Garden Escape', 'hidden_gem', 'Hillside', 'afternoon', ['relaxing', 'hidden_gem'], ['casual'], 'hidden gem quiet relaxing offbeat garden escape'],
                ['Rooftop Night Out', 'nightlife', 'Rooftop', 'evening', ['lively', 'nightlife', 'scenic'], ['friends', 'night-out'], 'nightlife rooftop drinks evening party'],
            ] as [$name, $category, $location, $bestTime, $vibes, $occasions, $search]) {
                Activity::create([
                    'name' => "{$cityName} {$name}",
                    'city' => $cityKey,
                    'category' => $category,
                    'description' => "{$name} in {$cityName}.",
                    'location' => "{$cityName} {$location}",
                    'best_time' => $bestTime,
                    'duration_estimate' => '1 hour',
                    'price_type' => $category === 'nightlife' ? 'mid' : 'free',
                    'vibe_tags' => $vibes,
                    'occasion_tags' => $occasions,
                    'search_text' => "{$cityKey} {$search}",
                ]);
            }
        }
    }

    private function cities(): array
    {
        return [
            'beirut' => 'Beirut',
            'byblos' => 'Byblos',
            'batroun' => 'Batroun',
            'tyre' => 'Tyre',
            'tripoli' => 'Tripoli',
            'broumana' => 'Broumana',
            'zahle' => 'Zahle',
            'saida' => 'Saida',
        ];
    }

    private function cityDisplay(string $city): string
    {
        return [
            'byblos' => 'Byblos',
            'tyre' => 'Tyre',
            'saida' => 'Saida',
        ][$city] ?? ucfirst($city);
    }

    private function tripPlanContainsSlot(array $tripPlan, string $slot): bool
    {
        foreach ($tripPlan['days'] ?? [] as $day) {
            if (isset($day['flow'][$slot])) {
                return true;
            }
        }

        return false;
    }
}
