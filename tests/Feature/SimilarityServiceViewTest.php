<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimilarityServiceViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_restaurant_detail_page_uses_the_configured_similarity_service_url(): void
    {
        config()->set('services.similarity.base_url', 'http://127.0.0.1:5999');

        $restaurant = Restaurant::create([
            'restaurant_name' => 'Test Restaurant',
            'image' => 'images/test-restaurant.jpg',
            'rating' => 4.5,
            'restaurant_type' => 'Seaside',
            'tags' => json_encode(['romantic', 'sea view']),
            'location' => 'Batroun',
            'description' => 'A test restaurant entry.',
            'price_tier' => 'mid-range',
            'food_type' => 'Seafood',
            'phone_number' => '01 234 567',
            'opening_hours' => '10:00 - 23:00',
        ]);

        $response = $this->get(route('restaurants.show', ['id' => $restaurant->id]));

        $response->assertOk()
            ->assertSee('window.SIMILARITY_SERVICE_BASE_URL = "http:\/\/127.0.0.1:5999";', false);
    }

    public function test_hotel_detail_page_uses_the_configured_similarity_service_url(): void
    {
        config()->set('services.similarity.base_url', 'http://127.0.0.1:5999');

        $hotel = Hotel::create([
            'hotel_name' => 'Test Hotel',
            'hotel_image' => 'images/test-hotel.jpg',
            'address' => 'Byblos Old Town',
            'rating_score' => 4.6,
            'room_type' => 'Double Room',
            'bed_info' => 'Queen Bed',
            'price_per_night' => '$120',
            'taxes_fees' => '$10',
            'review_count' => 12,
            'stay_details' => 'Breakfast included',
            'description' => 'A test hotel entry.',
        ]);

        $response = $this->get(route('hotels.show', ['id' => $hotel->id]));

        $response->assertOk()
            ->assertSee('window.SIMILARITY_SERVICE_BASE_URL = "http:\/\/127.0.0.1:5999";', false);
    }
}
