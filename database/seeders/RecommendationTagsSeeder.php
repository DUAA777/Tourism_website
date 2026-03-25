<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Hotel;
use App\Models\Restaurant;
use Illuminate\Support\Str;

class RecommendationTagsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedHotels();
        $this->seedRestaurants();

        $this->command->info('Recommendation tags seeded successfully.');
    }

    private function seedHotels(): void
    {
        Hotel::chunk(100, function ($hotels) {
            foreach ($hotels as $hotel) {
                $text = Str::lower(
                    trim(
                        implode(' ', array_filter([
                            $hotel->hotel_name,
                            $hotel->address,
                            $hotel->nearby_landmark,
                            $hotel->distance_from_center,
                            $hotel->distance_from_beach,
                            $hotel->review_text,
                            $hotel->stay_details,
                            $hotel->description,
                            $hotel->price_per_night,
                        ]))
                    )
                );

                $vibeTags = [];
                $audienceTags = [];

                if ($this->containsAny($text, ['beach', 'sea', 'seaside', 'coast'])) {
                    $vibeTags[] = 'beach';
                    $vibeTags[] = 'seaside';
                }

                if ($this->containsAny($text, ['quiet', 'peaceful', 'calm', 'relax', 'relaxing'])) {
                    $vibeTags[] = 'quiet';
                    $vibeTags[] = 'relaxing';
                }

                if ($this->containsAny($text, ['romantic', 'couple', 'anniversary', 'honeymoon'])) {
                    $vibeTags[] = 'romantic';
                    $audienceTags[] = 'couples';
                }

                if ($this->containsAny($text, ['family', 'kids', 'children'])) {
                    $vibeTags[] = 'family';
                    $audienceTags[] = 'families';
                }

                if ($this->containsAny($text, ['luxury', 'premium', 'elegant', 'upscale'])) {
                    $vibeTags[] = 'luxury';
                }

                if ($this->containsAny($text, ['budget', 'affordable', 'cheap', '$', 'economy'])) {
                    $vibeTags[] = 'budget';
                }

                if ($this->containsAny($text, ['studio', 'solo', 'single'])) {
                    $audienceTags[] = 'solo';
                    $audienceTags[] = 'short-stay';
                }

                if ($this->containsAny($text, ['weekend', 'short stay', 'overnight'])) {
                    $audienceTags[] = 'short-stay';
                }

                if ($this->containsAny($text, ['business', 'work', 'city center'])) {
                    $audienceTags[] = 'business';
                }

                if ($this->containsAny($text, ['batroun', 'tyre', 'jbeil', 'byblos', 'jounieh'])) {
                    $vibeTags[] = 'coastal';
                }

                $vibeTags = array_values(array_unique($vibeTags));
                $audienceTags = array_values(array_unique($audienceTags));

                $searchText = trim(implode(' ', array_filter([
                    "Hotel: {$hotel->hotel_name}",
                    $hotel->address ? "Address: {$hotel->address}" : null,
                    $hotel->nearby_landmark ? "Nearby landmark: {$hotel->nearby_landmark}" : null,
                    $hotel->distance_from_center ? "Distance from center: {$hotel->distance_from_center}" : null,
                    $hotel->distance_from_beach ? "Distance from beach: {$hotel->distance_from_beach}" : null,
                    $hotel->price_per_night ? "Price per night: {$hotel->price_per_night}" : null,
                    $hotel->rating_score ? "Rating: {$hotel->rating_score}" : null,
                    $hotel->description ? "Description: {$hotel->description}" : null,
                    $hotel->stay_details ? "Stay details: {$hotel->stay_details}" : null,
                    !empty($vibeTags) ? "Vibe tags: " . implode(', ', $vibeTags) : null,
                    !empty($audienceTags) ? "Audience tags: " . implode(', ', $audienceTags) : null,
                ])));

                $hotel->update([
                    'vibe_tags' => $vibeTags,
                    'audience_tags' => $audienceTags,
                    'search_text' => $searchText,
                ]);
            }
        });
    }

    private function seedRestaurants(): void
    {
        Restaurant::chunk(100, function ($restaurants) {
            foreach ($restaurants as $restaurant) {
                $tagsText = '';
                if (is_array($restaurant->tags)) {
                    $tagsText = implode(' ', $restaurant->tags);
                } elseif (is_string($restaurant->tags)) {
                    $tagsText = $restaurant->tags;
                }

                $text = Str::lower(
                    trim(
                        implode(' ', array_filter([
                            $restaurant->restaurant_name,
                            $restaurant->location,
                            $restaurant->restaurant_type,
                            $restaurant->food_type,
                            $restaurant->price_tier,
                            $restaurant->description,
                            $restaurant->opening_hours,
                            $tagsText,
                        ]))
                    )
                );

                $vibeTags = [];
                $occasionTags = [];

                if ($this->containsAny($text, ['seafood', 'sea', 'coast', 'beach', 'seaside'])) {
                    $vibeTags[] = 'seaside';
                }

                if ($this->containsAny($text, ['romantic', 'date', 'intimate', 'candle'])) {
                    $vibeTags[] = 'romantic';
                    $occasionTags[] = 'date';
                }

                if ($this->containsAny($text, ['cozy', 'warm', 'casual'])) {
                    $vibeTags[] = 'cozy';
                }

                if ($this->containsAny($text, ['sunset', 'view', 'sea view', 'rooftop'])) {
                    $vibeTags[] = 'sunset';
                    $occasionTags[] = 'dinner';
                }

                if ($this->containsAny($text, ['family', 'kids', 'group'])) {
                    $vibeTags[] = 'family';
                    $occasionTags[] = 'family';
                }

                if ($this->containsAny($text, ['lively', 'music', 'bar', 'night', 'cocktail'])) {
                    $vibeTags[] = 'lively';
                    $occasionTags[] = 'night-out';
                }

                if ($this->containsAny($text, ['luxury', 'fine dining', 'premium', 'upscale'])) {
                    $vibeTags[] = 'luxury';
                }

                if ($this->containsAny($text, ['cheap', 'affordable', 'budget', 'low price'])) {
                    $vibeTags[] = 'budget';
                }

                if ($this->containsAny($text, ['breakfast', 'brunch', 'coffee', 'cafe'])) {
                    $occasionTags[] = 'breakfast';
                    $occasionTags[] = 'casual';
                }

                if ($this->containsAny($text, ['lunch'])) {
                    $occasionTags[] = 'lunch';
                }

                if ($this->containsAny($text, ['dinner'])) {
                    $occasionTags[] = 'dinner';
                }

                $vibeTags = array_values(array_unique($vibeTags));
                $occasionTags = array_values(array_unique($occasionTags));

                $searchText = trim(implode(' ', array_filter([
                    "Restaurant: {$restaurant->restaurant_name}",
                    $restaurant->location ? "Location: {$restaurant->location}" : null,
                    $restaurant->restaurant_type ? "Type: {$restaurant->restaurant_type}" : null,
                    $restaurant->food_type ? "Food type: {$restaurant->food_type}" : null,
                    $restaurant->price_tier ? "Price tier: {$restaurant->price_tier}" : null,
                    $restaurant->rating ? "Rating: {$restaurant->rating}" : null,
                    $restaurant->description ? "Description: {$restaurant->description}" : null,
                    !empty($vibeTags) ? "Vibe tags: " . implode(', ', $vibeTags) : null,
                    !empty($occasionTags) ? "Occasion tags: " . implode(', ', $occasionTags) : null,
                ])));

                $restaurant->update([
                    'vibe_tags' => $vibeTags,
                    'occasion_tags' => $occasionTags,
                    'search_text' => $searchText,
                ]);
            }
        });
    }

    private function containsAny(string $text, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (Str::contains($text, Str::lower($keyword))) {
                return true;
            }
        }

        return false;
    }
}