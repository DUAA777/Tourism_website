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

    public function test_it_holds_results_for_under_specified_category_only_queries(): void
    {
        Hotel::create([
            'hotel_name' => 'Any City Stay',
            'address' => 'Beirut',
            'distance_from_beach' => '500 m',
            'rating_score' => 4.5,
            'review_count' => 120,
            'price_per_night' => '110$',
            'description' => 'A general stay option.',
            'stay_details' => 'Works for many travelers.',
            'vibe_tags' => ['cozy'],
            'audience_tags' => ['friends'],
            'search_text' => 'general beirut hotel stay',
        ]);

        $service = app(RecommendationService::class);
        $results = $service->buildResponseData('Recommend a hotel');

        $this->assertTrue($results['intent']['should_hold_results']);
        $this->assertSame([], $results['hotels']);
        $this->assertSame([], $results['restaurants']);
        $this->assertSame([], $results['activities']);
        $this->assertSame(true, $results['diagnostics']['guidance']['should_hold_results']);
    }

    public function test_it_can_recommend_from_semantic_signal_without_a_city(): void
    {
        Restaurant::create([
            'restaurant_name' => 'Sunset Cove Table',
            'location' => 'Batroun Waterfront',
            'rating' => 4.7,
            'price_tier' => 'Mid-range',
            'food_type' => 'Seafood',
            'description' => 'A romantic seafood dinner spot by the sea with sunset views.',
            'vibe_tags' => ['romantic', 'relaxing', 'beach'],
            'occasion_tags' => ['date', 'dinner'],
            'search_text' => 'romantic seafood dinner sunset sea view',
        ]);

        Restaurant::create([
            'restaurant_name' => 'City Burger Noise',
            'location' => 'Beirut Downtown',
            'rating' => 4.8,
            'price_tier' => 'Premium',
            'food_type' => 'Burgers',
            'description' => 'A loud late-night burger place for party groups.',
            'vibe_tags' => ['lively', 'nightlife'],
            'occasion_tags' => ['friends', 'night-out'],
            'search_text' => 'burgers nightlife party drinks',
        ]);

        $service = app(RecommendationService::class);
        $results = $service->buildResponseData('Find me a romantic seafood dinner with a sea view');

        $this->assertFalse($results['intent']['should_hold_results']);
        $this->assertNotEmpty($results['restaurants']);
        $this->assertSame('Sunset Cove Table', $results['restaurants'][0]['restaurant_name']);
    }

    public function test_it_can_build_a_grounded_trip_without_an_explicit_city_when_the_prompt_is_strong(): void
    {
        Hotel::create([
            'hotel_name' => 'Batroun Calm Stay',
            'address' => 'Batroun Seafront',
            'distance_from_beach' => '120 m',
            'rating_score' => 4.7,
            'review_count' => 180,
            'price_per_night' => '115$',
            'description' => 'A calm romantic seaside stay with sunset views.',
            'stay_details' => 'Quiet rooms close to the water.',
            'vibe_tags' => ['romantic', 'relaxing', 'beach'],
            'audience_tags' => ['couple'],
            'search_text' => 'batroun romantic calm beach sunset hotel',
        ]);

        Restaurant::create([
            'restaurant_name' => 'Batroun Sunset Table',
            'location' => 'Batroun Harbor',
            'rating' => 4.8,
            'price_tier' => 'Mid-range',
            'food_type' => 'Seafood',
            'description' => 'A romantic seafood dinner with calm sunset views.',
            'vibe_tags' => ['romantic', 'relaxing', 'beach'],
            'occasion_tags' => ['date', 'dinner'],
            'search_text' => 'batroun romantic seafood sunset dinner',
        ]);

        Activity::create([
            'name' => 'Batroun Sunset Walk',
            'city' => 'batroun',
            'category' => 'scenic',
            'description' => 'A peaceful seaside sunset walk.',
            'location' => 'Batroun Seafront',
            'best_time' => 'sunset',
            'duration_estimate' => '1 hour',
            'price_type' => 'free',
            'vibe_tags' => ['relaxing', 'beach', 'sunset'],
            'occasion_tags' => ['date', 'casual'],
            'search_text' => 'batroun peaceful beach sunset walk',
        ]);

        Hotel::create([
            'hotel_name' => 'Beirut Business Stay',
            'address' => 'Beirut Downtown',
            'distance_from_beach' => '2 km',
            'rating_score' => 4.6,
            'review_count' => 150,
            'price_per_night' => '145$',
            'description' => 'A business-oriented city stay.',
            'stay_details' => 'Good for meetings.',
            'vibe_tags' => ['business'],
            'audience_tags' => ['business'],
            'search_text' => 'beirut business downtown hotel',
        ]);

        $service = app(RecommendationService::class);
        $results = $service->buildResponseData('Plan me something romantic, beachy, and calm for 2 days');

        $this->assertFalse($results['intent']['should_hold_results']);
        $this->assertSame('trip_plan', $results['intent']['message_type']);
        $this->assertSame('batroun', $results['intent']['resolved_city']);
        $this->assertSame('2-Day Trip in Batroun', $results['trip_plan']['title']);
        $this->assertSame('Batroun Calm Stay', data_get($results, 'trip_plan.days.0.flow.stay.hotel_name'));
    }

    public function test_it_keeps_low_signal_no_city_activity_results_compact_and_grounded(): void
    {
        Activity::create([
            'name' => 'Byblos Quiet Harbor Walk',
            'city' => 'byblos',
            'category' => 'walking',
            'description' => 'A quiet walk by the harbor.',
            'location' => 'Byblos Harbor',
            'best_time' => 'morning',
            'duration_estimate' => '1 hour',
            'price_type' => 'free',
            'vibe_tags' => ['relaxing'],
            'occasion_tags' => ['casual'],
            'search_text' => 'byblos quiet harbor walk peaceful day',
        ]);

        Activity::create([
            'name' => 'Byblos Old Town Pause',
            'city' => 'byblos',
            'category' => 'cultural',
            'description' => 'A calm old-town stroll.',
            'location' => 'Byblos Old Town',
            'best_time' => 'afternoon',
            'duration_estimate' => '1 hour',
            'price_type' => 'free',
            'vibe_tags' => ['relaxing'],
            'occasion_tags' => ['casual'],
            'search_text' => 'byblos quiet old town peaceful day',
        ]);

        Activity::create([
            'name' => 'Byblos Seafront Break',
            'city' => 'byblos',
            'category' => 'scenic',
            'description' => 'A calm seafront stop.',
            'location' => 'Byblos Seafront',
            'best_time' => 'sunset',
            'duration_estimate' => '45 minutes',
            'price_type' => 'free',
            'vibe_tags' => ['relaxing', 'seaside'],
            'occasion_tags' => ['casual'],
            'search_text' => 'byblos quiet seaside stop peaceful day',
        ]);

        Activity::create([
            'name' => 'Beirut Busy Rooftop',
            'city' => 'beirut',
            'category' => 'nightlife',
            'description' => 'A lively city rooftop.',
            'location' => 'Beirut',
            'best_time' => 'evening',
            'duration_estimate' => '2 hours',
            'price_type' => 'premium',
            'vibe_tags' => ['lively'],
            'occasion_tags' => ['friends'],
            'search_text' => 'beirut nightlife rooftop party',
        ]);

        $service = app(RecommendationService::class);
        $results = $service->buildResponseData('Find me places in Lebanon for a quiet day');

        $this->assertFalse($results['intent']['should_hold_results']);
        $this->assertLessThanOrEqual(3, count($results['activities']));
        $this->assertSame(
            ['byblos'],
            array_values(array_unique(array_filter(array_column($results['activities'], 'city'))))
        );
    }

    public function test_it_diversifies_no_city_semantic_results_across_cities(): void
    {
        foreach ([
            ['name' => 'Beirut Velvet Wine Bar', 'location' => 'Beirut Marina'],
            ['name' => 'Batroun Sunset Wine Bar', 'location' => 'Batroun Waterfront'],
            ['name' => 'Byblos Harbor Wine Bar', 'location' => 'Byblos Port'],
        ] as $row) {
            Restaurant::create([
                'restaurant_name' => $row['name'],
                'location' => $row['location'],
                'rating' => 4.7,
                'price_tier' => 'Premium',
                'food_type' => 'Wine',
                'description' => 'A romantic wine bar for date nights and evening drinks.',
                'vibe_tags' => ['romantic', 'nightlife', 'scenic'],
                'occasion_tags' => ['date', 'night-out', 'dinner'],
                'search_text' => 'romantic wine bar date night drinks',
            ]);
        }

        $service = app(RecommendationService::class);
        $results = $service->buildResponseData('Find me a romantic wine bar for date night');

        $cities = array_values(array_unique(array_filter(array_column($results['restaurants'], 'city'))));

        $this->assertFalse($results['intent']['should_hold_results']);
        $this->assertGreaterThanOrEqual(2, count($cities));
    }

    public function test_it_drops_restaurant_results_when_a_specific_cuisine_has_no_real_city_match(): void
    {
        Restaurant::create([
            'restaurant_name' => 'Harbor Wine Bar',
            'location' => 'Byblos Port',
            'rating' => 4.7,
            'price_tier' => 'Premium',
            'food_type' => 'Wine',
            'description' => 'A romantic wine bar by the port.',
            'vibe_tags' => ['romantic', 'nightlife'],
            'occasion_tags' => ['date', 'night-out'],
            'search_text' => 'byblos romantic wine bar sunset drinks',
        ]);

        Restaurant::create([
            'restaurant_name' => 'Old Town Deli',
            'location' => 'Byblos Old Town',
            'rating' => 4.4,
            'price_tier' => 'Mid-range',
            'food_type' => 'Cafe',
            'description' => 'A casual deli and cafe in the old town.',
            'vibe_tags' => ['casual'],
            'occasion_tags' => ['lunch'],
            'search_text' => 'byblos casual lunch cafe deli',
        ]);

        $service = app(RecommendationService::class);
        $results = $service->buildResponseData('Find me a japanese sushi dinner in Jbeil');

        $this->assertFalse($results['intent']['should_hold_results']);
        $this->assertSame('byblos', $results['intent']['city']);
        $this->assertSame(['japanese'], $results['intent']['food_preferences']);
        $this->assertSame([], $results['restaurants']);
    }

    public function test_it_holds_cityless_hotel_requests_even_with_budget_or_audience_hints(): void
    {
        Hotel::create([
            'hotel_name' => 'Harbor Suites',
            'address' => 'Beirut Marina',
            'distance_from_beach' => '200 m',
            'rating_score' => 4.7,
            'review_count' => 160,
            'price_per_night' => '130$',
            'description' => 'A relaxing seaside hotel in Beirut.',
            'stay_details' => 'Calm rooms close to the waterfront.',
            'vibe_tags' => ['relaxing', 'beach'],
            'audience_tags' => ['couple'],
            'search_text' => 'beirut relaxing seaside hotel marina stay',
        ]);

        Hotel::create([
            'hotel_name' => 'Harbor Suites',
            'address' => 'Byblos Port',
            'distance_from_beach' => '150 m',
            'rating_score' => 4.6,
            'review_count' => 140,
            'price_per_night' => '125$',
            'description' => 'A relaxing harbor stay in Byblos.',
            'stay_details' => 'Quiet port rooms for couples.',
            'vibe_tags' => ['relaxing', 'beach'],
            'audience_tags' => ['couple'],
            'search_text' => 'byblos relaxing seaside hotel port stay',
        ]);

        $service = app(RecommendationService::class);
        $results = $service->buildResponseData('Recommend an affordable family stay');

        $this->assertTrue($results['intent']['should_hold_results']);
        $this->assertSame('guidance', $results['intent']['message_type']);
        $this->assertSame([], $results['hotels']);
    }

    public function test_it_cleans_redundant_result_locations_in_results_and_trip_plan(): void
    {
        Hotel::create([
            'hotel_name' => 'Sands Hotel',
            'address' => 'Sands Hotel, Byblos, Lebanon',
            'distance_from_beach' => '150 m',
            'rating_score' => 4.7,
            'review_count' => 180,
            'price_per_night' => '86$',
            'description' => 'A coastal stay right by the harbor.',
            'stay_details' => 'Walkable old-town base.',
            'vibe_tags' => ['romantic', 'beach'],
            'audience_tags' => ['couple'],
            'search_text' => 'byblos romantic seaside hotel harbor stay',
        ]);

        Restaurant::create([
            'restaurant_name' => 'Papillon Byblos',
            'location' => 'Papillon Byblos, Byblos, Lebanon',
            'rating' => 4.6,
            'price_tier' => 'Mid-range',
            'food_type' => 'Seafood',
            'description' => 'A seaside lunch and dinner spot in Byblos.',
            'vibe_tags' => ['romantic', 'seaside'],
            'occasion_tags' => ['lunch', 'dinner', 'date'],
            'search_text' => 'byblos seafood lunch dinner romantic by the sea',
        ]);

        Activity::create([
            'name' => 'Byblos Harbor Walk',
            'city' => 'byblos',
            'category' => 'walking',
            'description' => 'A relaxed harbor walk.',
            'location' => 'Byblos Harbor',
            'best_time' => 'morning',
            'duration_estimate' => '1 hour',
            'price_type' => 'free',
            'vibe_tags' => ['relaxing', 'seaside'],
            'occasion_tags' => ['casual'],
            'search_text' => 'byblos harbor walk morning seaside',
        ]);

        $service = app(RecommendationService::class);
        $results = $service->buildResponseData('Plan a 2 day romantic trip in Byblos by the sea');

        $this->assertSame('Byblos, Lebanon', $results['hotels'][0]['address']);
        $this->assertSame('Byblos, Lebanon', $results['restaurants'][0]['location']);
        $this->assertSame('Byblos, Lebanon', data_get($results, 'trip_plan.days.0.flow.stay.address'));
        $this->assertSame('Byblos, Lebanon', data_get($results, 'trip_plan.days.0.flow.lunch.location'));
    }

    public function test_it_strips_partial_repeated_place_names_from_restaurant_locations(): void
    {
        Restaurant::create([
            'restaurant_name' => 'HAÏ Rooftop',
            'location' => 'HAÏ Beirut Rooftop, HAÏ Beirut - A Tower, Antelias Road, Beirut, Lebanon',
            'rating' => 4.7,
            'price_tier' => 'Premium',
            'food_type' => 'Italian, Japanese',
            'description' => 'A rooftop dinner spot with city views.',
            'vibe_tags' => ['romantic', 'nightlife'],
            'occasion_tags' => ['dinner', 'date'],
            'search_text' => 'beirut rooftop romantic dinner japanese italian',
        ]);

        $service = app(RecommendationService::class);
        $results = $service->buildResponseData('Find me a romantic rooftop dinner in Beirut');

        $this->assertSame(
            'HAÏ Beirut - A Tower, Antelias Road, Beirut, Lebanon',
            $results['restaurants'][0]['location']
        );
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

    public function test_it_uses_a_richer_restaurant_pool_to_reduce_trip_repetition(): void
    {
        Hotel::create([
            'hotel_name' => 'Batroun Trip Base',
            'address' => 'Batroun Harbor',
            'distance_from_beach' => '200 m',
            'rating_score' => 4.6,
            'review_count' => 180,
            'price_per_night' => '110$',
            'description' => 'A comfortable coastal stay in Batroun.',
            'stay_details' => 'Good for multi-day stays by the sea.',
            'vibe_tags' => ['relaxing', 'beach'],
            'audience_tags' => ['couple', 'friends'],
            'search_text' => 'batroun hotel beach stay coastal',
        ]);

        foreach (range(1, 6) as $index) {
            Restaurant::create([
                'restaurant_name' => "Batroun Meal Spot {$index}",
                'location' => 'Batroun Waterfront',
                'rating' => 4.7,
                'price_tier' => 'Mid-range',
                'food_type' => 'Seafood',
                'description' => "Seafood stop {$index} in Batroun.",
                'vibe_tags' => ['relaxing', 'beach'],
                'occasion_tags' => ['lunch', 'dinner'],
                'search_text' => "batroun seafood meal {$index} waterfront",
            ]);
        }

        Activity::create([
            'name' => 'Batroun Harbor Walk',
            'city' => 'batroun',
            'category' => 'walking',
            'description' => 'A calm morning harbor walk.',
            'location' => 'Batroun Harbor',
            'best_time' => 'morning',
            'duration_estimate' => '1 hour',
            'price_type' => 'free',
            'vibe_tags' => ['relaxing'],
            'occasion_tags' => ['casual'],
            'search_text' => 'batroun harbor walk morning',
        ]);

        $service = app(RecommendationService::class);
        $results = $service->buildResponseData('Plan a 3 day seaside trip in Batroun with seafood');

        $tripPlan = $results['trip_plan'];
        $mealNames = array_values(array_filter([
            data_get($tripPlan, 'days.0.flow.lunch.restaurant_name'),
            data_get($tripPlan, 'days.0.flow.dinner.restaurant_name'),
            data_get($tripPlan, 'days.1.flow.lunch.restaurant_name'),
            data_get($tripPlan, 'days.1.flow.dinner.restaurant_name'),
            data_get($tripPlan, 'days.2.flow.lunch.restaurant_name'),
        ]));

        $this->assertCount(4, $results['restaurants']);
        $this->assertCount(5, $mealNames);
        $this->assertCount(5, array_unique($mealNames));
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
        $this->assertSame([], $results['hotels']);
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

    public function test_it_scopes_restaurants_by_city_across_searchable_metadata(): void
    {
        Restaurant::create([
            'restaurant_name' => 'Marina Table',
            'location' => 'Waterfront District',
            'rating' => 4.7,
            'price_tier' => 'Mid-range',
            'food_type' => 'Seafood',
            'description' => 'A refined seaside dinner option popular for Beirut date nights.',
            'vibe_tags' => ['romantic', 'seaside'],
            'occasion_tags' => ['date', 'dinner'],
            'search_text' => 'beirut waterfront seafood dinner with sea view',
        ]);

        Restaurant::create([
            'restaurant_name' => 'Wrong City Table',
            'location' => 'Byblos Port',
            'rating' => 4.9,
            'price_tier' => 'Mid-range',
            'food_type' => 'Seafood',
            'description' => 'A strong seafood option, but not in Beirut.',
            'vibe_tags' => ['romantic', 'seaside'],
            'occasion_tags' => ['date', 'dinner'],
            'search_text' => 'byblos seafood dinner by the port',
        ]);

        $service = app(RecommendationService::class);
        $results = $service->buildResponseData('Find me a romantic seafood dinner in Beirut with a sea view');

        $this->assertSame('Marina Table', $results['restaurants'][0]['restaurant_name']);
        $this->assertSame('beirut', $results['restaurants'][0]['city']);
    }

    public function test_it_scopes_activities_by_city_even_with_case_or_location_variation(): void
    {
        Activity::create([
            'name' => 'Hidden Harbor Corner',
            'city' => 'Batroun',
            'category' => 'hidden_gem',
            'description' => 'A quiet coastal corner that feels offbeat and less touristy.',
            'location' => 'Old Port Lane',
            'best_time' => 'afternoon',
            'duration_estimate' => '1 hour',
            'price_type' => 'free',
            'vibe_tags' => ['hidden_gem', 'relaxing'],
            'occasion_tags' => ['casual'],
            'search_text' => 'batroun hidden gem quiet place near the coast',
        ]);

        Activity::create([
            'name' => 'Busy City Plaza',
            'city' => 'beirut',
            'category' => 'city',
            'description' => 'A busy urban stop in Beirut.',
            'location' => 'Downtown Beirut',
            'best_time' => 'evening',
            'duration_estimate' => '1 hour',
            'price_type' => 'free',
            'vibe_tags' => ['lively'],
            'occasion_tags' => ['friends'],
            'search_text' => 'beirut busy downtown activity',
        ]);

        $service = app(RecommendationService::class);
        $results = $service->buildResponseData('Give me hidden gem places in Batroun for a quiet day');

        $this->assertSame('Hidden Harbor Corner', $results['activities'][0]['name']);
        $this->assertSame('Batroun', $results['activities'][0]['city']);
    }
}
