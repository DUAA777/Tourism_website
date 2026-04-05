<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Hotel;
use App\Models\Restaurant;
use App\Services\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uses_semantic_and_content_signals_for_restaurant_queries(): void
    {
        Restaurant::create([
            'restaurant_name' => 'Seaside Table',
            'location' => 'Batroun Seafront',
            'rating' => 4.7,
            'price_tier' => 'Mid-range',
            'food_type' => 'Seafood',
            'description' => 'A calm dining spot by the sea with sunset tables and a relaxed atmosphere.',
            'vibe_tags' => ['relaxing', 'seaside', 'romantic'],
            'occasion_tags' => ['date', 'dinner'],
            'search_text' => 'waterfront seafood dinner by the sea in batroun',
        ]);

        Restaurant::create([
            'restaurant_name' => 'Late Night Noise',
            'location' => 'Batroun Center',
            'rating' => 4.8,
            'price_tier' => 'Premium',
            'food_type' => 'Burgers',
            'description' => 'A loud and lively nightlife place for groups and late drinks.',
            'vibe_tags' => ['lively', 'nightlife'],
            'occasion_tags' => ['friends', 'night-out'],
            'search_text' => 'busy burgers cocktails and party vibes in batroun',
        ]);

        $service = app(RecommendationService::class);
        $results = $service->buildResponseData('Find me a quiet waterfront dinner in Batroun');

        $this->assertSame('Seaside Table', $results['restaurants'][0]['restaurant_name']);
        $this->assertSame([], $results['hotels']);
        $this->assertSame([], $results['activities']);
    }

    public function test_it_returns_diagnostics_summary_and_top_match_reasons(): void
    {
        Restaurant::create([
            'restaurant_name' => 'Sea Breeze Table',
            'location' => 'Batroun Waterfront',
            'rating' => 4.8,
            'price_tier' => 'Mid-range',
            'food_type' => 'Seafood',
            'description' => 'A romantic seafood dinner spot with sunset tables by the sea.',
            'vibe_tags' => ['romantic', 'relaxing', 'beach'],
            'occasion_tags' => ['date', 'dinner'],
            'search_text' => 'batroun sunset seafood romantic dinner sea view',
        ]);

        $service = app(RecommendationService::class);
        $results = $service->buildResponseData('Find me a romantic seafood dinner in Batroun under $120');

        $this->assertContains('City: Batroun', $results['diagnostics']['summary_chips']);
        $this->assertContains('Max: $120', $results['diagnostics']['summary_chips']);
        $this->assertSame('Sea Breeze Table', $results['diagnostics']['top_matches']['restaurant']['name']);
        $this->assertNotEmpty($results['diagnostics']['top_matches']['restaurant']['reasons']);
    }

    public function test_it_builds_budget_aware_trip_results_with_trip_plan_data(): void
    {
        Hotel::create([
            'hotel_name' => 'Budget Beach Lodge',
            'address' => 'Batroun Beach Road',
            'distance_from_beach' => '200 m',
            'rating_score' => 4.4,
            'review_count' => 220,
            'price_per_night' => '75$',
            'description' => 'Affordable coastal stay with a calm seaside vibe.',
            'stay_details' => 'Simple rooms close to the water.',
            'vibe_tags' => ['relaxing', 'beach'],
            'audience_tags' => ['couple', 'friends'],
            'search_text' => 'budget batroun hotel near beach and sunset views',
        ]);

        Hotel::create([
            'hotel_name' => 'Grand Inland Palace',
            'address' => 'Batroun Hills',
            'distance_from_beach' => '5 km',
            'rating_score' => 4.9,
            'review_count' => 180,
            'price_per_night' => '260$',
            'description' => 'Luxury inland stay far from the coast.',
            'stay_details' => 'High-end rooms and premium service.',
            'vibe_tags' => ['luxury'],
            'audience_tags' => ['business'],
            'search_text' => 'luxury batroun hilltop hotel premium stay',
        ]);

        Restaurant::create([
            'restaurant_name' => 'Harbor Lunch',
            'location' => 'Batroun Port',
            'rating' => 4.5,
            'price_tier' => 'Mid-range',
            'food_type' => 'Seafood',
            'description' => 'Casual seafood lunch by the port.',
            'vibe_tags' => ['relaxing', 'seaside'],
            'occasion_tags' => ['lunch', 'casual'],
            'search_text' => 'batroun seafood lunch by the port',
        ]);

        Activity::create([
            'name' => 'Batroun Sunset Walk',
            'city' => 'batroun',
            'category' => 'scenic',
            'description' => 'A calm waterfront walk that is best at sunset.',
            'location' => 'Batroun Seafront',
            'best_time' => 'sunset',
            'duration_estimate' => '1 hour',
            'price_type' => 'free',
            'vibe_tags' => ['relaxing', 'sunset', 'seaside'],
            'occasion_tags' => ['date', 'casual'],
            'search_text' => 'batroun sunset waterfront scenic walk',
        ]);

        Activity::create([
            'name' => 'Old Town Coffee Stop',
            'city' => 'batroun',
            'category' => 'food',
            'description' => 'A short coffee and pastry stop in the old town.',
            'location' => 'Batroun Old Town',
            'best_time' => 'morning',
            'duration_estimate' => '45 minutes',
            'price_type' => 'low',
            'vibe_tags' => ['cozy', 'casual'],
            'occasion_tags' => ['friends', 'casual'],
            'search_text' => 'batroun old town coffee stop',
        ]);

        $service = app(RecommendationService::class);
        $results = $service->buildResponseData('Plan a budget seaside weekend in Batroun under $90 with sunset');

        $this->assertSame('Budget Beach Lodge', $results['hotels'][0]['hotel_name']);
        $this->assertNotNull($results['trip_plan']);
        $this->assertSame('batroun', $results['intent']['city']);
        $this->assertSame('budget', $results['intent']['budget']);
        $this->assertSame(90, $results['intent']['budget_max']);
        $this->assertContains('Batroun Sunset Walk', array_column($results['activities'], 'name'));
    }

    public function test_it_treats_nights_as_multi_day_trip_requests(): void
    {
        Hotel::create([
            'hotel_name' => 'Corniche Stay',
            'address' => 'Beirut Corniche',
            'distance_from_beach' => '300 m',
            'rating_score' => 4.3,
            'review_count' => 150,
            'price_per_night' => '95$',
            'description' => 'A coastal stay in Beirut.',
            'stay_details' => 'Walkable location.',
            'vibe_tags' => ['relaxing', 'beach'],
            'audience_tags' => ['friends'],
            'search_text' => 'beirut coastal stay',
        ]);

        Restaurant::create([
            'restaurant_name' => 'Beirut Table',
            'location' => 'Beirut',
            'rating' => 4.4,
            'price_tier' => 'Mid-range',
            'food_type' => 'Lebanese',
            'description' => 'Classic Beirut lunch stop.',
            'vibe_tags' => ['casual'],
            'occasion_tags' => ['lunch'],
            'search_text' => 'beirut lebanese lunch',
        ]);

        Activity::create([
            'name' => 'Corniche Walk',
            'city' => 'beirut',
            'category' => 'walking',
            'description' => 'A scenic walk by the sea.',
            'location' => 'Beirut Corniche',
            'best_time' => 'morning',
            'duration_estimate' => '1 hour',
            'price_type' => 'free',
            'vibe_tags' => ['relaxing', 'seaside'],
            'occasion_tags' => ['friends', 'casual'],
            'search_text' => 'beirut corniche walk',
        ]);

        $service = app(RecommendationService::class);
        $results = $service->buildResponseData('I want to stay in Beirut for 2 nights by the sea');

        $this->assertTrue($results['intent']['wants_trip_plan']);
        $this->assertSame(2, $results['intent']['day_count']);
        $this->assertCount(2, $results['trip_plan']['days']);
    }

    public function test_it_can_build_four_day_itineraries(): void
    {
        Hotel::create([
            'hotel_name' => 'Byblos Harbor Stay',
            'address' => 'Byblos Port',
            'distance_from_beach' => '200 m',
            'rating_score' => 4.6,
            'review_count' => 210,
            'price_per_night' => '120$',
            'description' => 'Harbor stay with easy access to the old town.',
            'stay_details' => 'Comfortable base for a longer trip.',
            'vibe_tags' => ['relaxing', 'coastal'],
            'audience_tags' => ['couple', 'friends'],
            'search_text' => 'byblos harbor hotel coastal old town',
        ]);

        Restaurant::create([
            'restaurant_name' => 'Port Lunch',
            'location' => 'Byblos',
            'rating' => 4.5,
            'price_tier' => 'Mid-range',
            'food_type' => 'Seafood',
            'description' => 'Seafood by the harbor.',
            'vibe_tags' => ['seaside'],
            'occasion_tags' => ['lunch', 'casual'],
            'search_text' => 'byblos seafood lunch harbor',
        ]);

        Activity::create([
            'name' => 'Old Souk Walk',
            'city' => 'byblos',
            'category' => 'cultural',
            'description' => 'Historic streets and shops.',
            'location' => 'Byblos Old Town',
            'best_time' => 'morning',
            'duration_estimate' => '1 hour',
            'price_type' => 'free',
            'vibe_tags' => ['cultural', 'casual'],
            'occasion_tags' => ['friends', 'casual'],
            'search_text' => 'byblos old souk walk',
        ]);

        Activity::create([
            'name' => 'Port Sunset Walk',
            'city' => 'byblos',
            'category' => 'scenic',
            'description' => 'Relaxed harbor walk at sunset.',
            'location' => 'Byblos Port',
            'best_time' => 'sunset',
            'duration_estimate' => '1 hour',
            'price_type' => 'free',
            'vibe_tags' => ['relaxing', 'sunset'],
            'occasion_tags' => ['date', 'casual'],
            'search_text' => 'byblos sunset harbor walk',
        ]);

        $service = app(RecommendationService::class);
        $results = $service->buildResponseData('Plan me a 4 day trip in Byblos');

        $this->assertTrue($results['intent']['wants_trip_plan']);
        $this->assertSame(4, $results['intent']['day_count']);
        $this->assertCount(4, $results['trip_plan']['days']);
        $this->assertSame('4-Day Trip in Byblos', $results['trip_plan']['title']);
    }

    public function test_it_keeps_trip_stays_when_hotel_city_is_not_in_address(): void
    {
        Hotel::create([
            'hotel_name' => 'Batroun Bay Stay',
            'address' => 'Sea Road',
            'distance_from_beach' => '150 m',
            'rating_score' => 4.5,
            'review_count' => 120,
            'price_per_night' => '110$',
            'description' => 'A seaside stay close to Batroun sunsets.',
            'stay_details' => 'Small coastal rooms.',
            'vibe_tags' => ['relaxing', 'beach'],
            'audience_tags' => ['couple', 'friends'],
            'search_text' => 'batroun seaside stay near the old town and sunset spots',
        ]);

        Restaurant::create([
            'restaurant_name' => 'Harbor Seafood Table',
            'location' => 'Batroun Port',
            'rating' => 4.6,
            'price_tier' => 'Premium',
            'food_type' => 'Seafood',
            'description' => 'Fresh seafood by the water.',
            'vibe_tags' => ['beach', 'sunset'],
            'occasion_tags' => ['dinner', 'casual'],
            'search_text' => 'batroun seafood lunch dinner by the port',
        ]);

        Activity::create([
            'name' => 'Batroun Sunset Walk',
            'city' => 'batroun',
            'category' => 'scenic',
            'description' => 'A seaside sunset walk.',
            'location' => 'Batroun Seafront',
            'best_time' => 'sunset',
            'duration_estimate' => '1 hour',
            'price_type' => 'free',
            'vibe_tags' => ['relaxing', 'sunset', 'seaside'],
            'occasion_tags' => ['date', 'casual'],
            'search_text' => 'batroun scenic sunset walk',
        ]);

        $service = app(RecommendationService::class);
        $results = $service->buildResponseData('Plan a 2 day seaside trip in Batroun with sunset and seafood');

        $this->assertSame('Batroun Bay Stay', $results['hotels'][0]['hotel_name']);
        $this->assertSame('Batroun Bay Stay', $results['trip_plan']['days'][0]['flow']['stay']['hotel_name'] ?? null);
    }

    public function test_one_day_trip_does_not_force_a_hotel_stay_without_a_stay_request(): void
    {
        Hotel::create([
            'hotel_name' => 'Byblos Day Base',
            'address' => 'Byblos Port',
            'distance_from_beach' => '120 m',
            'rating_score' => 4.4,
            'review_count' => 90,
            'price_per_night' => '95$',
            'description' => 'A small harbor stay in Byblos.',
            'stay_details' => 'Walkable old town access.',
            'vibe_tags' => ['relaxing', 'coastal'],
            'audience_tags' => ['couple'],
            'search_text' => 'byblos harbor stay close to the old town',
        ]);

        Restaurant::create([
            'restaurant_name' => 'Port Lunch',
            'location' => 'Byblos Port',
            'rating' => 4.5,
            'price_tier' => 'Mid-range',
            'food_type' => 'Seafood',
            'description' => 'Seafood by the harbor.',
            'vibe_tags' => ['seaside'],
            'occasion_tags' => ['lunch', 'casual'],
            'search_text' => 'byblos seafood lunch harbor',
        ]);

        Activity::create([
            'name' => 'Old Souk Walk',
            'city' => 'byblos',
            'category' => 'cultural',
            'description' => 'Historic streets and shops.',
            'location' => 'Byblos Old Town',
            'best_time' => 'morning',
            'duration_estimate' => '1 hour',
            'price_type' => 'free',
            'vibe_tags' => ['cultural', 'casual'],
            'occasion_tags' => ['friends', 'casual'],
            'search_text' => 'byblos old souk walk',
        ]);

        $service = app(RecommendationService::class);
        $results = $service->buildResponseData('Plan a 1 day trip in Byblos');

        $this->assertFalse(in_array('hotels', $results['intent']['requested_categories'], true));
        $this->assertArrayNotHasKey('stay', $results['trip_plan']['days'][0]['flow']);
    }

    public function test_multi_city_trip_uses_city_appropriate_stays_and_restaurants(): void
    {
        Hotel::create([
            'hotel_name' => 'Beirut Corniche Stay',
            'address' => 'Beirut Corniche',
            'distance_from_beach' => '100 m',
            'rating_score' => 4.6,
            'review_count' => 160,
            'price_per_night' => '130$',
            'description' => 'A Beirut seaside base.',
            'stay_details' => 'Great for a city start.',
            'vibe_tags' => ['relaxing', 'beach'],
            'audience_tags' => ['couple', 'friends'],
            'search_text' => 'beirut seaside hotel corniche stay',
        ]);

        Hotel::create([
            'hotel_name' => 'Byblos Port Stay',
            'address' => 'Byblos Port',
            'distance_from_beach' => '90 m',
            'rating_score' => 4.7,
            'review_count' => 140,
            'price_per_night' => '125$',
            'description' => 'A harbor stay in Byblos.',
            'stay_details' => 'Best for old town evenings.',
            'vibe_tags' => ['relaxing', 'coastal'],
            'audience_tags' => ['couple', 'friends'],
            'search_text' => 'byblos port hotel old town stay',
        ]);

        Restaurant::create([
            'restaurant_name' => 'Beirut Harbor Lunch',
            'location' => 'Beirut Waterfront',
            'rating' => 4.5,
            'price_tier' => 'Mid-range',
            'food_type' => 'Seafood',
            'description' => 'Seafood lunch in Beirut.',
            'vibe_tags' => ['seaside'],
            'occasion_tags' => ['lunch', 'casual'],
            'search_text' => 'beirut seafood lunch waterfront',
        ]);

        Restaurant::create([
            'restaurant_name' => 'Byblos Port Dinner',
            'location' => 'Byblos Port',
            'rating' => 4.6,
            'price_tier' => 'Mid-range',
            'food_type' => 'Seafood',
            'description' => 'Seafood dinner in Byblos.',
            'vibe_tags' => ['seaside', 'sunset'],
            'occasion_tags' => ['dinner', 'date'],
            'search_text' => 'byblos seafood dinner harbor sunset',
        ]);

        Activity::create([
            'name' => 'Beirut Corniche Walk',
            'city' => 'beirut',
            'category' => 'walking',
            'description' => 'A scenic Beirut walk.',
            'location' => 'Beirut Corniche',
            'best_time' => 'morning',
            'duration_estimate' => '1 hour',
            'price_type' => 'free',
            'vibe_tags' => ['relaxing', 'seaside'],
            'occasion_tags' => ['casual'],
            'search_text' => 'beirut corniche walk',
        ]);

        Activity::create([
            'name' => 'Byblos Harbor Sunset',
            'city' => 'byblos',
            'category' => 'scenic',
            'description' => 'A sunset stop in Byblos.',
            'location' => 'Byblos Port',
            'best_time' => 'sunset',
            'duration_estimate' => '1 hour',
            'price_type' => 'free',
            'vibe_tags' => ['sunset', 'seaside'],
            'occasion_tags' => ['date'],
            'search_text' => 'byblos harbor sunset',
        ]);

        $service = app(RecommendationService::class);
        $results = $service->buildResponseData('Plan a 3 day trip from Beirut to Byblos with seafood');

        $this->assertSame('Beirut Corniche Stay', $results['trip_plan']['days'][0]['flow']['stay']['hotel_name'] ?? null);
        $this->assertSame('Byblos Port Stay', $results['trip_plan']['days'][1]['flow']['stay']['hotel_name'] ?? null);
        $this->assertSame('Byblos Port Dinner', $results['trip_plan']['days'][1]['flow']['dinner']['restaurant_name'] ?? null);
        $this->assertSame('Byblos', $results['trip_plan']['days'][2]['location']);
    }
}
