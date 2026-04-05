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
                $text = $this->normalizeText(implode(' ', array_filter([
                    $hotel->hotel_name,
                    $hotel->address,
                    $hotel->nearby_landmark,
                    $hotel->distance_from_center,
                    $hotel->distance_from_beach,
                    $hotel->review_text,
                    $hotel->stay_details,
                    $hotel->description,
                    $hotel->price_per_night,
                ])));

                $vibeTags = [];
                $audienceTags = [];

                $this->addIfContainsAny($vibeTags, 'beach', $text, [
                    'beach', 'sea', 'seaside', 'coast', 'coastal', 'shore', 'waterfront'
                ]);

                $this->addIfContainsAny($vibeTags, 'relaxing', $text, [
                    'quiet', 'peaceful', 'calm', 'relax', 'relaxing', 'laid back', 'laid-back'
                ]);

                $this->addIfContainsAny($vibeTags, 'romantic', $text, [
                    'romantic', 'couple', 'anniversary', 'honeymoon', 'intimate'
                ]);

                $this->addIfContainsAny($vibeTags, 'family', $text, [
                    'family', 'kids', 'children', 'family friendly', 'family-friendly'
                ]);

                $this->addIfContainsAny($vibeTags, 'luxury', $text, [
                    'luxury', 'premium', 'elegant', 'upscale', 'five star', '5 star', 'luxurious'
                ]);

                $this->addIfContainsAny($vibeTags, 'budget', $text, [
                    'budget', 'affordable', 'cheap', 'economy', 'value', 'low cost', 'low-cost'
                ]);

                $this->addIfContainsAny($vibeTags, 'scenic', $text, [
                    'view', 'views', 'panoramic', 'panorama', 'photo', 'photos', 'mountain view', 'sea view'
                ]);

                $this->addIfContainsAny($vibeTags, 'city', $text, [
                    'city center', 'downtown', 'urban', 'central'
                ]);

                $this->addIfContainsAny($vibeTags, 'cultural', $text, [
                    'heritage', 'history', 'historical', 'old town', 'old city', 'souk', 'castle'
                ]);

                $this->addIfContainsAny($vibeTags, 'hidden_gem', $text, [
                    'hidden gem', 'hidden gems', 'offbeat', 'less touristy', 'quiet corner'
                ]);

                $this->addIfContainsAny($audienceTags, 'couple', $text, [
                    'romantic', 'couple', 'anniversary', 'honeymoon'
                ]);

                $this->addIfContainsAny($audienceTags, 'family', $text, [
                    'family', 'kids', 'children', 'family friendly', 'family-friendly'
                ]);

                $this->addIfContainsAny($audienceTags, 'friends', $text, [
                    'friends', 'group', 'groups'
                ]);

                $this->addIfContainsAny($audienceTags, 'business', $text, [
                    'business', 'work', 'meeting', 'client', 'conference'
                ]);

                $vibeTags = $this->uniqueClean($vibeTags);
                $audienceTags = $this->uniqueClean($audienceTags);

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

                $text = $this->normalizeText(implode(' ', array_filter([
                    $restaurant->restaurant_name,
                    $restaurant->location,
                    $restaurant->restaurant_type,
                    $restaurant->food_type,
                    $restaurant->price_tier,
                    $restaurant->description,
                    $restaurant->opening_hours,
                    $tagsText,
                ])));

                $vibeTags = [];
                $occasionTags = [];

                $this->addIfContainsAny($vibeTags, 'beach', $text, [
                    'beach', 'beachfront', 'sea', 'seaside', 'coast', 'coastal', 'waterfront'
                ]);

                $this->addIfContainsAny($vibeTags, 'romantic', $text, [
                    'romantic', 'date', 'intimate', 'candle', 'anniversary'
                ]);

                $this->addIfContainsAny($vibeTags, 'cozy', $text, [
                    'cozy', 'cosy', 'warm', 'casual', 'comfortable'
                ]);

                $this->addIfContainsAny($vibeTags, 'sunset', $text, [
                    'sunset', 'golden hour', 'sea view', 'view', 'views', 'rooftop'
                ]);

                $this->addIfContainsAny($vibeTags, 'family', $text, [
                    'family', 'kids', 'children', 'group'
                ]);

                $this->addIfContainsAny($vibeTags, 'lively', $text, [
                    'lively', 'music', 'buzzing', 'energetic'
                ]);

                $this->addIfContainsAny($vibeTags, 'nightlife', $text, [
                    'bar', 'bars', 'night', 'cocktail', 'cocktails', 'night out', 'night-out', 'party', 'club'
                ]);

                $this->addIfContainsAny($vibeTags, 'luxury', $text, [
                    'luxury', 'fine dining', 'premium', 'upscale', 'elegant'
                ]);

                $this->addIfContainsAny($vibeTags, 'budget', $text, [
                    'cheap', 'affordable', 'budget', 'low price', 'value'
                ]);

                $this->addIfContainsAny($vibeTags, 'scenic', $text, [
                    'view', 'views', 'sea view', 'panoramic', 'waterfront'
                ]);

                $this->addIfContainsAny($occasionTags, 'date', $text, [
                    'romantic', 'date', 'intimate', 'anniversary'
                ]);

                $this->addIfContainsAny($occasionTags, 'family', $text, [
                    'family', 'kids', 'children'
                ]);

                $this->addIfContainsAny($occasionTags, 'friends', $text, [
                    'friends', 'group', 'sharing plates', 'shareable'
                ]);

                $this->addIfContainsAny($occasionTags, 'business', $text, [
                    'business', 'meeting', 'client', 'formal lunch'
                ]);

                $this->addIfContainsAny($occasionTags, 'night-out', $text, [
                    'night out', 'night-out', 'bar', 'bars', 'cocktail', 'cocktails', 'drinks'
                ]);

                $this->addIfContainsAny($occasionTags, 'breakfast', $text, [
                    'breakfast', 'brunch', 'coffee', 'cafe'
                ]);

                $this->addIfContainsAny($occasionTags, 'lunch', $text, [
                    'lunch', 'midday'
                ]);

                $this->addIfContainsAny($occasionTags, 'dinner', $text, [
                    'dinner', 'fine dining', 'sunset dinner', 'evening dining'
                ]);

                $this->addIfContainsAny($occasionTags, 'casual', $text, [
                    'casual', 'relaxed', 'cozy', 'coffee', 'cafe'
                ]);

                $this->addIfContainsAny($occasionTags, 'anniversary', $text, [
                    'anniversary'
                ]);

                $vibeTags = $this->uniqueClean($vibeTags);
                $occasionTags = $this->uniqueClean($occasionTags);

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

    private function addIfContainsAny(array &$target, string $tag, string $text, array $keywords): void
    {
        foreach ($keywords as $keyword) {
            if (Str::contains($text, $this->normalizeText($keyword))) {
                $target[] = $tag;
                return;
            }
        }
    }

    private function uniqueClean(array $values): array
    {
        return array_values(array_unique(array_filter($values)));
    }

    private function normalizeText(?string $text): string
    {
        $text = Str::lower($text ?? '');
        $text = preg_replace('/[^\pL\pN\s\-]+/u', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }
}