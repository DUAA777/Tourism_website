<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\Restaurant;
use Illuminate\Support\Str;

class RecommendationService
{
    public function buildResponseData(string $message): array
    {
        $intent = $this->extractIntent($message);

        $hotels = Hotel::query()
            ->when($intent['city'], fn ($q) => $q->where('address', 'like', '%' . $intent['city'] . '%'))
            ->orderByDesc('rating_score')
            ->get();

        $restaurants = Restaurant::query()
            ->when($intent['city'], fn ($q) => $q->where('location', 'like', '%' . $intent['city'] . '%'))
            ->orderByDesc('rating')
            ->get();

        $rankedHotels = $hotels
            ->map(fn ($hotel) => [
                'item' => $hotel,
                'score' => $this->scoreHotel($hotel, $intent, $message),
            ])
            ->sortByDesc('score')
            ->take(3)
            ->values();

        $rankedRestaurants = $restaurants
            ->map(fn ($restaurant) => [
                'item' => $restaurant,
                'score' => $this->scoreRestaurant($restaurant, $intent, $message),
            ])
            ->sortByDesc('score')
            ->take(3)
            ->values();

        $topHotels = $rankedHotels->map(fn ($row) => $this->transformHotel($row['item'], $row['score']))->all();
        $topRestaurants = $rankedRestaurants->map(fn ($row) => $this->transformRestaurant($row['item'], $row['score']))->all();

        $tripPlan = null;

        if ($intent['wants_trip_plan']) {
            $tripPlan = $this->buildTripPlan($intent, $topHotels, $topRestaurants);
        }

        return [
            'intent' => $intent,
            'hotels' => $topHotels,
            'restaurants' => $topRestaurants,
            'trip_plan' => $tripPlan,
        ];
    }

    private function extractIntent(string $message): array
    {
        $text = Str::lower($message);

        $city = null;
        foreach (['beirut', 'batroun', 'tyre', 'byblos', 'tripoli', 'jounieh'] as $candidate) {
            if (Str::contains($text, $candidate)) {
                $city = $candidate;
                break;
            }
        }

        $vibeTags = [];
        foreach ([
            'romantic', 'quiet', 'calm', 'relaxing', 'lively', 'cozy',
            'luxury', 'budget', 'family', 'sunset', 'seaside', 'beach'
        ] as $tag) {
            if (Str::contains($text, $tag)) {
                $vibeTags[] = $tag;
            }
        }

        $foodType = null;
        foreach (['seafood', 'lebanese', 'italian', 'cafe'] as $food) {
            if (Str::contains($text, $food)) {
                $foodType = $food;
                break;
            }
        }

        $budget = null;
        foreach (['cheap', 'budget', 'affordable', 'luxury'] as $budgetWord) {
            if (Str::contains($text, $budgetWord)) {
                $budget = $budgetWord;
                break;
            }
        }

        $duration = null;
        if (Str::contains($text, ['one day', '1 day', 'day trip'])) {
            $duration = '1_day';
        } elseif (Str::contains($text, ['two days', '2 days', 'weekend'])) {
            $duration = '2_days';
        } elseif (Str::contains($text, ['three days', '3 days'])) {
            $duration = '3_days';
        }

        return [
            'city' => $city,
            'vibe_tags' => $vibeTags,
            'food_type' => $foodType,
            'budget' => $budget,
            'duration' => $duration,
            'wants_hotel' => Str::contains($text, ['hotel', 'stay', 'room']),
            'wants_restaurant' => Str::contains($text, ['restaurant', 'food', 'dinner', 'lunch', 'cafe']),
            'wants_trip_plan' => Str::contains($text, [
                'plan', 'trip', 'itinerary', 'weekend', 'one day', '1 day',
                'two days', '2 days', 'three days', '3 days'
            ]),
        ];
    }

    private function scoreHotel($hotel, array $intent, string $message): float
    {
        $score = 0;

        if ($intent['city'] && $hotel->address && Str::contains(Str::lower($hotel->address), $intent['city'])) {
            $score += 25;
        }

        if (!empty($intent['vibe_tags']) && is_array($hotel->vibe_tags ?? null)) {
            $score += count(array_intersect($intent['vibe_tags'], $hotel->vibe_tags)) * 15;
        }

        if ($intent['budget'] && $hotel->search_text && Str::contains(Str::lower($hotel->search_text), $intent['budget'])) {
            $score += 10;
        }

        if ($hotel->rating_score) {
            $score += (float) $hotel->rating_score * 5;
        }

        if ($hotel->search_text) {
            foreach (explode(' ', Str::lower($message)) as $word) {
                if (strlen($word) > 3 && Str::contains(Str::lower($hotel->search_text), $word)) {
                    $score += 1;
                }
            }
        }

        return $score;
    }

    private function scoreRestaurant($restaurant, array $intent, string $message): float
    {
        $score = 0;

        if ($intent['city'] && $restaurant->location && Str::contains(Str::lower($restaurant->location), $intent['city'])) {
            $score += 25;
        }

        if ($intent['food_type'] && $restaurant->food_type && Str::contains(Str::lower($restaurant->food_type), $intent['food_type'])) {
            $score += 20;
        }

        if (!empty($intent['vibe_tags']) && is_array($restaurant->vibe_tags ?? null)) {
            $score += count(array_intersect($intent['vibe_tags'], $restaurant->vibe_tags)) * 15;
        }

        if ($restaurant->rating) {
            $score += (float) $restaurant->rating * 5;
        }

        if ($restaurant->search_text) {
            foreach (explode(' ', Str::lower($message)) as $word) {
                if (strlen($word) > 3 && Str::contains(Str::lower($restaurant->search_text), $word)) {
                    $score += 1;
                }
            }
        }

        return $score;
    }

    private function buildTripPlan(array $intent, array $hotels, array $restaurants): array
    {
        $duration = $intent['duration'] ?? '1_day';

        return match ($duration) {
            '2_days' => [
                'duration' => '2_days',
                'hotel' => $hotels[0] ?? null,
                'day_1' => [
                    'stay' => $hotels[0] ?? null,
                    'meal' => $restaurants[0] ?? null,
                    'backup_meal' => $restaurants[1] ?? null,
                ],
                'day_2' => [
                    'stay' => $hotels[0] ?? null,
                    'meal' => $restaurants[1] ?? ($restaurants[0] ?? null),
                ],
            ],
            '3_days' => [
                'duration' => '3_days',
                'hotel' => $hotels[0] ?? null,
                'day_1' => [
                    'stay' => $hotels[0] ?? null,
                    'meal' => $restaurants[0] ?? null,
                ],
                'day_2' => [
                    'stay' => $hotels[0] ?? null,
                    'meal' => $restaurants[1] ?? ($restaurants[0] ?? null),
                ],
                'day_3' => [
                    'stay' => $hotels[1] ?? ($hotels[0] ?? null),
                    'meal' => $restaurants[2] ?? ($restaurants[0] ?? null),
                ],
            ],
            default => [
                'duration' => '1_day',
                'hotel' => $hotels[0] ?? null,
                'day_1' => [
                    'meal' => $restaurants[0] ?? null,
                    'backup_meal' => $restaurants[1] ?? null,
                ],
            ],
        };
    }

    private function transformHotel($hotel, float $score): array
    {
        return [
            'hotel_name' => $hotel->hotel_name,
            'address' => $hotel->address,
            'distance_from_beach' => $hotel->distance_from_beach,
            'rating_score' => $hotel->rating_score,
            'price_per_night' => $hotel->price_per_night,
            'description' => $hotel->description,
            'stay_details' => $hotel->stay_details,
            'vibe_tags' => $hotel->vibe_tags,
            'audience_tags' => $hotel->audience_tags,
            'score' => $score,
        ];
    }

    private function transformRestaurant($restaurant, float $score): array
    {
        return [
            'restaurant_name' => $restaurant->restaurant_name,
            'location' => $restaurant->location,
            'rating' => $restaurant->rating,
            'food_type' => $restaurant->food_type,
            'price_tier' => $restaurant->price_tier,
            'description' => $restaurant->description,
            'opening_hours' => $restaurant->opening_hours,
            'vibe_tags' => $restaurant->vibe_tags,
            'occasion_tags' => $restaurant->occasion_tags,
            'score' => $score,
        ];
    }
}