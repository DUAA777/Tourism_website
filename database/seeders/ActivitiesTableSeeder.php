<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Activity;

class ActivitiesTableSeeder extends Seeder
{
    public function run(): void
    {
        $activities = [
            [
                'name' => 'Byblos Old Souk Walk',
                'city' => 'byblos',
                'category' => 'cultural',
                'description' => 'A relaxed walk through the historic souk with local shops, stone streets, and a traditional atmosphere.',
                'location' => 'Byblos Old Town',
                'best_time' => 'morning',
                'duration_estimate' => '1-2 hours',
                'price_type' => 'free',
                'vibe_tags' => ['relaxing', 'cultural', 'casual'],
                'occasion_tags' => ['friends', 'casual', 'family'],
            ],
            [
                'name' => 'Visit Byblos Castle',
                'city' => 'byblos',
                'category' => 'historical',
                'description' => 'A cultural stop with history, old architecture, and scenic views over Byblos.',
                'location' => 'Byblos Castle',
                'best_time' => 'morning',
                'duration_estimate' => '1 hour',
                'price_type' => 'medium',
                'vibe_tags' => ['cultural', 'scenic'],
                'occasion_tags' => ['family', 'casual'],
            ],
            [
                'name' => 'Byblos Port Sunset Walk',
                'city' => 'byblos',
                'category' => 'scenic',
                'description' => 'A calm seaside walk around the old port, especially beautiful at sunset.',
                'location' => 'Byblos Port',
                'best_time' => 'sunset',
                'duration_estimate' => '1 hour',
                'price_type' => 'free',
                'vibe_tags' => ['romantic', 'relaxing', 'seaside', 'sunset'],
                'occasion_tags' => ['date', 'casual'],
            ],
            [
                'name' => 'Beirut Corniche Walk',
                'city' => 'beirut',
                'category' => 'walking',
                'description' => 'A scenic coastal walk with sea views and a classic Beirut atmosphere.',
                'location' => 'Beirut Corniche',
                'best_time' => 'morning',
                'duration_estimate' => '1-2 hours',
                'price_type' => 'free',
                'vibe_tags' => ['relaxing', 'seaside', 'casual'],
                'occasion_tags' => ['friends', 'family', 'casual'],
            ],
            [
                'name' => 'Visit Raouche Rocks',
                'city' => 'beirut',
                'category' => 'scenic',
                'description' => 'A scenic Beirut landmark great for views, photos, and a quick stop by the sea.',
                'location' => 'Raouche',
                'best_time' => 'sunset',
                'duration_estimate' => '45 minutes',
                'price_type' => 'free',
                'vibe_tags' => ['scenic', 'sunset', 'romantic'],
                'occasion_tags' => ['date', 'casual'],
            ],
            [
                'name' => 'Explore Downtown Beirut',
                'city' => 'beirut',
                'category' => 'city',
                'description' => 'Walk through central Beirut for architecture, cafés, shopping, and a polished city atmosphere.',
                'location' => 'Downtown Beirut',
                'best_time' => 'afternoon',
                'duration_estimate' => '2 hours',
                'price_type' => 'low',
                'vibe_tags' => ['city', 'lively', 'casual'],
                'occasion_tags' => ['friends', 'business', 'casual'],
            ],
            [
                'name' => 'Gemmayze and Mar Mikhael Evening Walk',
                'city' => 'beirut',
                'category' => 'nightlife',
                'description' => 'A lively evening experience with bars, cafés, food spots, and Beirut nightlife energy.',
                'location' => 'Gemmayze / Mar Mikhael',
                'best_time' => 'evening',
                'duration_estimate' => '2-3 hours',
                'price_type' => 'medium',
                'vibe_tags' => ['lively', 'fun', 'nightlife'],
                'occasion_tags' => ['friends', 'night-out'],
            ],
            [
                'name' => 'Batroun Old Souk Walk',
                'city' => 'batroun',
                'category' => 'cultural',
                'description' => 'Explore Batroun’s charming streets, local shops, and coastal-town atmosphere.',
                'location' => 'Batroun Old Town',
                'best_time' => 'morning',
                'duration_estimate' => '1-2 hours',
                'price_type' => 'free',
                'vibe_tags' => ['casual', 'coastal', 'relaxing'],
                'occasion_tags' => ['friends', 'family', 'casual'],
            ],
            [
                'name' => 'Batroun Seaside Walk',
                'city' => 'batroun',
                'category' => 'scenic',
                'description' => 'A calm coastal walk with sea views and a relaxing Batroun vibe.',
                'location' => 'Batroun Seafront',
                'best_time' => 'sunset',
                'duration_estimate' => '1 hour',
                'price_type' => 'free',
                'vibe_tags' => ['romantic', 'relaxing', 'seaside', 'sunset'],
                'occasion_tags' => ['date', 'casual'],
            ],
            [
                'name' => 'Relax at a Batroun Beach Spot',
                'city' => 'batroun',
                'category' => 'beach',
                'description' => 'Spend time near the beach for a laid-back coastal experience.',
                'location' => 'Batroun Beach Area',
                'best_time' => 'afternoon',
                'duration_estimate' => '2 hours',
                'price_type' => 'low',
                'vibe_tags' => ['beach', 'relaxing', 'coastal'],
                'occasion_tags' => ['friends', 'family', 'casual'],
            ],
            [
                'name' => 'Tripoli Old City Walk',
                'city' => 'tripoli',
                'category' => 'historical',
                'description' => 'Walk through traditional streets and historic souks for a classic Tripoli experience.',
                'location' => 'Tripoli Old City',
                'best_time' => 'morning',
                'duration_estimate' => '2 hours',
                'price_type' => 'free',
                'vibe_tags' => ['cultural', 'casual'],
                'occasion_tags' => ['friends', 'family'],
            ],
            [
                'name' => 'Tripoli Café and Dessert Stop',
                'city' => 'tripoli',
                'category' => 'food',
                'description' => 'Enjoy a light food and dessert stop in the city after exploring.',
                'location' => 'Tripoli Center',
                'best_time' => 'afternoon',
                'duration_estimate' => '1 hour',
                'price_type' => 'low',
                'vibe_tags' => ['casual', 'cozy'],
                'occasion_tags' => ['friends', 'casual'],
            ],
        ];

        foreach ($activities as $activity) {
            $searchText = trim(implode(' ', array_filter([
                "Activity: {$activity['name']}",
                "City: {$activity['city']}",
                "Category: {$activity['category']}",
                "Description: {$activity['description']}",
                "Location: {$activity['location']}",
                "Best time: {$activity['best_time']}",
                "Duration: {$activity['duration_estimate']}",
                "Price: {$activity['price_type']}",
                "Vibe tags: " . implode(', ', $activity['vibe_tags']),
                "Occasion tags: " . implode(', ', $activity['occasion_tags']),
            ])));

            Activity::updateOrCreate(
                [
                    'name' => $activity['name'],
                    'city' => $activity['city'],
                ],
                array_merge($activity, [
                    'search_text' => $searchText,
                ])
            );
        }
    }
}