<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Hotel;
use App\Models\Restaurant;
use App\Services\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationHumanPrompt200Test extends TestCase
{
    use RefreshDatabase;

    public function test_human_prompt_matrix_covers_200_realistic_inputs(): void
    {
        $this->seedHumanCatalog();

        $cases = $this->humanPromptCases();
        $this->assertCount(200, $cases);

        $service = app(RecommendationService::class);

        foreach ($cases as $index => $case) {
            $context = sprintf(
                'Human prompt %d [%s] "%s"',
                $index + 1,
                $case['label'],
                $case['prompt']
            );

            $sessionContext = null;
            if (!empty($case['previous_prompt'])) {
                $previousResults = $service->buildResponseData($case['previous_prompt']);
                $sessionContext = ['intent' => $previousResults['intent']];
            }

            $results = $service->buildResponseData($case['prompt'], $sessionContext);
            $intent = $results['intent'];

            $this->assertSame($case['hold'], $intent['should_hold_results'], $context . ' hold mismatch.');
            $this->assertSame($case['trip_plan'], $intent['wants_trip_plan'], $context . ' trip routing mismatch.');
            $this->assertSame($case['day_count'], $intent['day_count'], $context . ' day count mismatch.');
            $this->assertNotEmpty($results['diagnostics']['summary_chips'] ?? [], $context . ' diagnostics missing.');

            if ($case['city'] !== null) {
                $this->assertSame($case['city'], $intent['city'], $context . ' city mismatch.');
                $this->assertSame($case['city'], $intent['resolved_city'], $context . ' resolved city mismatch.');
            }

            if (!empty($case['route'])) {
                $this->assertSame($case['route'], $intent['mentioned_cities'], $context . ' route city list mismatch.');
                $this->assertSame($case['route'][0], $intent['start_city'], $context . ' route start mismatch.');
                $this->assertSame($case['route'][count($case['route']) - 1], $intent['end_city'], $context . ' route end mismatch.');
            }

            foreach ($case['required_categories'] as $category) {
                $this->assertContains($category, $intent['requested_categories'], $context . " missing category {$category}.");
            }

            foreach ($case['forbidden_categories'] as $category) {
                $this->assertNotContains($category, $intent['requested_categories'], $context . " should not include {$category}.");
            }

            $this->assertUniqueItems($results['hotels'] ?? [], 'hotel_name', $context . ' duplicate hotels.');
            $this->assertUniqueItems($results['restaurants'] ?? [], 'restaurant_name', $context . ' duplicate restaurants.');
            $this->assertUniqueItems($results['activities'] ?? [], 'name', $context . ' duplicate activities.');

            if ($case['hold']) {
                $this->assertSame([], $results['hotels'], $context . ' held prompt returned hotels.');
                $this->assertSame([], $results['restaurants'], $context . ' held prompt returned restaurants.');
                $this->assertSame([], $results['activities'], $context . ' held prompt returned activities.');
                $this->assertNull($results['trip_plan'], $context . ' held prompt built a trip.');
                continue;
            }

            foreach ($case['expected_non_empty'] as $category) {
                $this->assertNotEmpty($results[$category] ?? [], $context . " expected {$category}.");
            }

            if ($case['only_expected_results']) {
                foreach (['hotels', 'restaurants', 'activities'] as $category) {
                    if (!in_array($category, $case['expected_non_empty'], true)) {
                        $this->assertSame([], $results[$category] ?? [], $context . " should not return {$category}.");
                    }
                }
            }

            if ($case['city'] !== null && empty($case['route'])) {
                foreach ($case['expected_non_empty'] as $category) {
                    $this->assertSame($case['city'], $results[$category][0]['city'] ?? null, $context . " top {$category} city mismatch.");
                }
            }

            if ($case['trip_plan']) {
                $tripPlan = $results['trip_plan'];
                $this->assertIsArray($tripPlan, $context . ' expected trip plan.');
                $this->assertCount($case['day_count'], $tripPlan['days'] ?? [], $context . ' trip day count mismatch.');

                if ($case['city'] !== null && empty($case['route'])) {
                    $this->assertSame($this->cityDisplay($case['city']), $tripPlan['days'][0]['location'] ?? null, $context . ' trip city mismatch.');
                }

                if (!empty($case['route'])) {
                    if ($case['day_count'] === 1) {
                        $this->assertSame($this->cityDisplay($case['route'][count($case['route']) - 1]), $tripPlan['days'][0]['location'] ?? null, $context . ' one-day route destination mismatch.');
                    } else {
                        $this->assertSame($this->cityDisplay($case['route'][0]), $tripPlan['days'][0]['location'] ?? null, $context . ' route first day mismatch.');
                        $this->assertSame($this->cityDisplay($case['route'][count($case['route']) - 1]), $tripPlan['days'][$case['day_count'] - 1]['location'] ?? null, $context . ' route final day mismatch.');
                    }
                }

                if ($case['expect_stay']) {
                    $this->assertTrue($this->tripPlanContainsSlot($tripPlan, 'stay'), $context . ' expected stay slot.');
                }

                if ($case['expect_no_stay']) {
                    $this->assertFalse($this->tripPlanContainsSlot($tripPlan, 'stay'), $context . ' should not include stay.');
                }
            } else {
                $this->assertNull($results['trip_plan'], $context . ' non-trip built a trip plan.');
            }
        }
    }

    private function seedHumanCatalog(): void
    {
        foreach ($this->cities() as $cityKey => $cityName) {
            Hotel::create([
                'hotel_name' => "{$cityName} Budget Family Stay",
                'address' => "{$cityName} Harbor",
                'distance_from_beach' => '350 m',
                'rating_score' => 8.5,
                'review_count' => 180,
                'price_per_night' => '88$',
                'description' => "A budget family-friendly hotel in {$cityName} close to the water.",
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
                'description' => "A romantic seaside hotel in {$cityName} with sunset views and quiet evenings.",
                'stay_details' => 'Good for couples, calm weekends, and sea-view evenings.',
                'vibe_tags' => ['romantic', 'sunset', 'beach', 'relaxing'],
                'audience_tags' => ['couple'],
                'search_text' => "{$cityKey} romantic seaside hotel sunset couple beach quiet weekend",
            ]);

            Hotel::create([
                'hotel_name' => "{$cityName} Business Grand Hotel",
                'address' => "{$cityName} Central District",
                'distance_from_beach' => '2 km',
                'rating_score' => 9.3,
                'review_count' => 220,
                'price_per_night' => '235$',
                'description' => "A premium business hotel in central {$cityName} for meetings and client stays.",
                'stay_details' => 'Good for meetings, business travel, and city access.',
                'vibe_tags' => ['luxury', 'city'],
                'audience_tags' => ['business'],
                'search_text' => "{$cityKey} business hotel premium highest rated meetings clients luxury",
            ]);

            Hotel::create([
                'hotel_name' => "{$cityName} Luxury Seaside Suites",
                'address' => "{$cityName} Coastline",
                'distance_from_beach' => '80 m',
                'rating_score' => 9.5,
                'review_count' => 260,
                'price_per_night' => '285$',
                'description' => "A luxury seaside hotel in {$cityName} with suites and premium comfort.",
                'stay_details' => 'Best for upscale beach stays and special occasions.',
                'vibe_tags' => ['luxury', 'beach', 'sunset', 'romantic'],
                'audience_tags' => ['couple', 'business'],
                'search_text' => "{$cityKey} luxury expensive seaside hotel premium suites beach sea view upscale",
            ]);

            foreach ([
                ['Seafood Sunset Table', 'Waterfront', 'Seafood', 'Premium', ['romantic', 'beach', 'sunset'], ['date', 'dinner'], 'seafood dinner romantic fish sea view sunset waterfront'],
                ['Old Town Lebanese Kitchen', 'Old Town', 'Lebanese', 'Mid-range', ['cultural', 'casual'], ['lunch', 'casual'], 'lebanese lunch casual local food old town mezze'],
                ['Morning Green Cafe', 'Harbor', 'Healthy, Cafe', 'Budget', ['cozy', 'relaxing'], ['breakfast', 'casual'], 'healthy breakfast cafe brunch coffee bowls organic'],
                ['Sushi Atelier', 'Center', 'Japanese', 'Premium', ['romantic', 'city'], ['date', 'dinner'], 'japanese sushi dinner date night premium'],
                ['Stone Pizza Oven', 'Market Street', 'Pizza', 'Mid-range', ['casual', 'fun'], ['lunch', 'casual'], 'pizza lunch casual pizzeria italian'],
                ['Rooftop Wine Bar', 'Rooftop', 'Wine', 'Premium', ['romantic', 'nightlife', 'scenic'], ['date', 'night-out', 'dinner'], 'wine bar romantic date night rooftop drinks'],
                ['Night Burger Club', 'Downtown', 'Burgers', 'Mid-range', ['lively', 'nightlife', 'fun'], ['friends', 'night-out', 'dinner'], 'burgers drinks lively nightlife friends night out'],
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
                ['Family Park Stop', 'city', 'Park', 'afternoon', ['family', 'casual'], ['family'], 'family activity kids park afternoon'],
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

    private function humanPromptCases(): array
    {
        return array_merge(
            $this->canonicalHumanCases(),
            $this->aliasAndTypoCases(),
            $this->semanticNoCityCases(),
            $this->guidanceCases(),
            $this->routeCases(),
            $this->followUpCases(),
        );
    }

    private function canonicalHumanCases(): array
    {
        $cases = [];

        foreach ($this->cities() as $cityKey => $cityName) {
            $cases[] = $this->case("{$cityKey}-cheap-family-hotel", "can you find me a cheap family hotel in {$cityName} close-ish to the beach?", $cityKey, false, null, false, ['hotels'], [], ['hotels']);
            $cases[] = $this->case("{$cityKey}-best-rated-hotel", "i need the best rated hotel in {$cityName}, not a trip", $cityKey, false, null, false, ['hotels'], ['trip_plan'], ['hotels']);
            $cases[] = $this->case("{$cityKey}-romantic-weekend-stay", "where can we stay in {$cityName} for a romantic weekend?", $cityKey, false, 2, false, ['hotels'], ['trip_plan'], ['hotels']);
            $cases[] = $this->case("{$cityKey}-seafood-tonight", "seafood dinner in {$cityName} tonight?", $cityKey, false, null, false, ['restaurants'], [], ['restaurants']);
            $cases[] = $this->case("{$cityKey}-local-lunch", "somewhere casual for lunch in {$cityName}, local food", $cityKey, false, null, false, ['restaurants'], [], ['restaurants']);
            $cases[] = $this->case("{$cityKey}-breakfast", "healthy breakfast cafe in {$cityName} please", $cityKey, false, null, false, ['restaurants'], [], ['restaurants']);
            $cases[] = $this->case("{$cityKey}-hidden", "what are quiet hidden gem places in {$cityName}?", $cityKey, false, null, false, ['activities'], [], ['activities']);
            $cases[] = $this->case("{$cityKey}-cultural", "anything cultural to walk around in {$cityName}?", $cityKey, false, null, false, ['activities'], [], ['activities']);
            $cases[] = $this->case("{$cityKey}-two-day", "plan us 2 days in {$cityName} with food and sunset", $cityKey, false, 2, true, ['trip_plan', 'hotels'], [], ['hotels', 'restaurants', 'activities'], false, true);
            $cases[] = $this->case("{$cityKey}-one-day-no-hotel", "one day in {$cityName}, no hotel, just food and places", $cityKey, false, 1, true, ['trip_plan', 'restaurants', 'activities'], ['hotels'], ['restaurants', 'activities'], true, false, null, true);
        }

        return $cases;
    }

    private function aliasAndTypoCases(): array
    {
        $cases = [];

        foreach ($this->aliases() as $cityKey => $alias) {
            $cases[] = $this->case("{$cityKey}-alias-hotels", "cheap hotels in {$alias}", $cityKey, false, null, false, ['hotels'], [], ['hotels']);
            $cases[] = $this->case("{$cityKey}-typo-restuarants", "restuarants {$alias}", $cityKey, false, null, false, ['restaurants'], [], ['restaurants']);
            $cases[] = $this->case("{$cityKey}-typo-resturants", "resturants in {$alias} for dinner", $cityKey, false, null, false, ['restaurants'], [], ['restaurants']);
            $cases[] = $this->case("{$cityKey}-sushi", "sushi dinner in {$alias}", $cityKey, false, null, false, ['restaurants'], [], ['restaurants']);
            $cases[] = $this->case("{$cityKey}-hidden", "hidden places {$alias}", $cityKey, false, null, false, ['activities'], [], ['activities']);
            $cases[] = $this->case("{$cityKey}-two-day", "2 day trip in {$alias}", $cityKey, false, 2, true, ['trip_plan', 'hotels'], [], ['hotels', 'restaurants', 'activities'], false, true);
            $cases[] = $this->case("{$cityKey}-hotelsin", "Hotelsin {$alias}", $cityKey, false, null, false, ['hotels'], [], ['hotels']);
            $cases[] = $this->case("{$cityKey}-things-to-do", "things to do around {$alias}", $cityKey, false, null, false, ['activities'], [], ['activities']);
        }

        return $cases;
    }

    private function semanticNoCityCases(): array
    {
        return [
            $this->case('semantic-affordable-family-stay', 'we need an affordable family stay under 100 dollars', null, false, null, false, ['hotels'], [], ['hotels'], true, false, null, false, 100),
            $this->case('semantic-premium-business', 'premium hotel for meetings with clients', null, false, null, false, ['hotels'], [], ['hotels']),
            $this->case('semantic-romantic-seaside-stay', 'romantic seaside stay with sunset views', null, false, null, false, ['hotels'], [], ['hotels']),
            $this->case('semantic-luxury-suite', 'show me a luxury beach hotel suite', null, false, null, false, ['hotels'], [], ['hotels']),
            $this->case('semantic-cozy-stay', 'cozy quiet stay for two people', null, false, null, false, ['hotels'], [], ['hotels']),
            $this->case('semantic-seafood', 'i want fresh fish by the sea for dinner', null, false, null, false, ['restaurants'], [], ['restaurants']),
            $this->case('semantic-sushi', 'good sushi for dinner tonight', null, false, null, false, ['restaurants'], [], ['restaurants']),
            $this->case('semantic-pizza', 'casual pizza lunch with friends', null, false, null, false, ['restaurants'], [], ['restaurants']),
            $this->case('semantic-wine', 'romantic wine bar for a date', null, false, null, false, ['restaurants'], [], ['restaurants']),
            $this->case('semantic-breakfast', 'healthy brunch cafe with coffee', null, false, null, false, ['restaurants'], [], ['restaurants']),
            $this->case('semantic-lebanese', 'local lebanese lunch spot', null, false, null, false, ['restaurants'], [], ['restaurants']),
            $this->case('semantic-burgers', 'lively burgers and drinks tonight', null, false, null, false, ['restaurants'], [], ['restaurants']),
            $this->case('semantic-hidden-gem', 'somewhere quiet and less touristy', null, false, null, false, ['activities'], [], ['activities']),
            $this->case('semantic-cultural-walk', 'old souk walk with history', null, false, null, false, ['activities'], [], ['activities']),
            $this->case('semantic-nightlife', 'rooftop night out with drinks', null, false, null, false, ['activities'], [], ['activities']),
            $this->case('semantic-family-activity', 'kid friendly place for the afternoon', null, false, null, false, ['activities'], [], ['activities']),
            $this->case('semantic-sunset-walk', 'sunset walk by the water', null, false, null, false, ['activities'], [], ['activities']),
            $this->case('semantic-strong-trip', 'plan a romantic 2 day seaside escape with seafood', null, false, 2, true, ['trip_plan', 'hotels'], [], ['hotels', 'restaurants', 'activities'], false, true),
            $this->case('semantic-family-trip', 'build a 3 day family beach trip with affordable stay', null, false, 3, true, ['trip_plan', 'hotels'], [], ['hotels', 'restaurants', 'activities'], false, true),
            $this->case('semantic-one-day', 'give me one relaxed day with lunch and a hidden gem', null, false, 1, true, ['trip_plan', 'restaurants', 'activities'], ['hotels'], ['restaurants', 'activities'], true, false, null, true),
        ];
    }

    private function guidanceCases(): array
    {
        $prompts = [
            'hello',
            'hey there',
            'help',
            'help me plan',
            'recommend something',
            'what do you suggest',
            "i don't know where to go",
            'anything good?',
            'show me options',
            'surprise me',
            'hotel',
            'recommend a hotel',
            'restaurant',
            'food please',
            'activities',
            'places to visit',
            'trip',
            'plan a trip',
            '2+2',
            'nice',
        ];

        return array_map(
            fn ($prompt, $index) => $this->case('guidance-' . ($index + 1), $prompt, null, true, str_contains($prompt, 'trip') ? 2 : null, str_contains($prompt, 'trip'), $this->guidanceRequiredCategories($prompt), [], []),
            $prompts,
            array_keys($prompts)
        );
    }

    private function routeCases(): array
    {
        return [
            $this->case('route-beirut-byblos', 'can you make me a 3 day route from Beirut to Byblos with seafood?', 'beirut', false, 3, true, ['trip_plan', 'hotels'], [], ['hotels', 'restaurants', 'activities'], false, true, ['beirut', 'byblos']),
            $this->case('route-jbeil-sur', '3 days from Jbeil to Sur, quiet food stops please', 'byblos', false, 3, true, ['trip_plan', 'hotels'], [], ['hotels', 'restaurants', 'activities'], false, true, ['byblos', 'tyre']),
            $this->case('route-batroun-beirut', 'weekend from Batroun to Beirut, sunset and dinner', 'batroun', false, 2, true, ['trip_plan', 'hotels'], [], ['hotels', 'restaurants', 'activities'], false, true, ['batroun', 'beirut']),
            $this->case('route-saida-tyre', 'Plan a 2 night trip from Saida to Tyre', 'saida', false, 2, true, ['trip_plan', 'hotels'], [], ['hotels', 'restaurants', 'activities'], false, true, ['saida', 'tyre']),
            $this->case('route-trablos-brummana', 'I want a 4 day road trip from Trablos to Brummana', 'tripoli', false, 4, true, ['trip_plan', 'hotels'], [], ['hotels', 'restaurants', 'activities'], false, true, ['tripoli', 'broumana']),
            $this->case('route-broumana-zahle', 'organize Broumana to Zahleh in 2 days with cafes', 'broumana', false, 2, true, ['trip_plan', 'hotels'], [], ['hotels', 'restaurants', 'activities'], false, true, ['broumana', 'zahle']),
            $this->case('route-three-city', 'plan 3 days from Beirut to Byblos to Tyre with relaxed stops', 'beirut', false, 3, true, ['trip_plan', 'hotels'], [], ['hotels', 'restaurants', 'activities'], false, true, ['beirut', 'byblos', 'tyre']),
            $this->case('route-three-city-alias', 'from Jbeil to Sidon to Sur for 3 days', 'byblos', false, 3, true, ['trip_plan', 'hotels'], [], ['hotels', 'restaurants', 'activities'], false, true, ['byblos', 'saida', 'tyre']),
            $this->case('route-four-day-three-city', 'build a 4 day route Beirut then Jounieh then Batroun', 'beirut', false, 4, true, ['trip_plan', 'hotels'], [], ['hotels', 'restaurants', 'activities'], false, true, ['beirut', 'jounieh', 'batroun']),
            $this->case('route-relaxed-couple', 'relaxed couple getaway from Zahle to Byblos for 2 nights', 'zahle', false, 2, true, ['trip_plan', 'hotels'], [], ['hotels', 'restaurants', 'activities'], false, true, ['zahle', 'byblos']),
            $this->case('route-family', 'family trip from Beirut to Tripoli for 3 days', 'beirut', false, 3, true, ['trip_plan', 'hotels'], [], ['hotels', 'restaurants', 'activities'], false, true, ['beirut', 'tripoli']),
            $this->case('route-culture', '1 day from Sidon to Tyre just cultural places and food', 'saida', false, 1, true, ['trip_plan', 'restaurants', 'activities'], ['hotels'], ['restaurants', 'activities'], true, false, ['saida', 'tyre'], true),
        ];
    }

    private function followUpCases(): array
    {
        return [
            $this->case('follow-cheaper-hotel', 'make it cheaper', 'byblos', false, null, false, ['hotels'], [], ['hotels'], true, false, null, false, null, false, 'Find me a romantic hotel in Byblos'),
            $this->case('follow-more-expensive-hotel', 'more expensive', 'byblos', false, null, false, ['hotels'], [], ['hotels'], true, false, null, false, null, false, 'Recommend a budget hotel in Byblos near the old town'),
            $this->case('follow-same-vibe-tyre', 'same vibe but in Tyre', 'tyre', false, null, false, ['hotels'], [], ['hotels'], true, false, null, false, null, false, 'Find me a romantic hotel in Byblos'),
            $this->case('follow-trip-four-days', 'make it 4 days', 'batroun', false, 4, true, ['trip_plan', 'hotels'], [], ['hotels', 'restaurants', 'activities'], false, true, null, false, null, false, 'Plan a 2 day trip in Batroun with seafood'),
            $this->case('follow-trip-route', 'now make it a 3 day route from Beirut to Byblos to Tyre', 'beirut', false, 3, true, ['trip_plan', 'hotels'], [], ['hotels', 'restaurants', 'activities'], false, true, ['beirut', 'byblos', 'tyre'], false, null, false, 'Plan a 2 day trip in Batroun with seafood'),
            $this->case('follow-restaurants-instead', 'show restaurants instead', 'beirut', false, null, false, ['restaurants'], [], ['restaurants'], true, false, null, false, null, false, 'Find me a hotel in Beirut'),
            $this->case('follow-hotels-only', 'just hotels', 'beirut', false, null, false, ['hotels'], [], ['hotels'], true, false, null, false, null, false, 'Show me a hotel, dinner, and things to do in Beirut'),
            $this->case('follow-activities-only', 'only things to do', 'batroun', false, null, false, ['activities'], [], ['activities'], true, false, null, false, null, false, 'Find me a hotel and dinner in Batroun'),
            $this->case('follow-smalltalk', 'nice', null, true, null, false, [], [], [], true, false, null, false, null, false, 'Find me a hotel in Beirut'),
            $this->case('follow-change-city-restaurant', 'make it in Byblos', 'byblos', false, null, false, ['restaurants'], [], ['restaurants'], true, false, null, false, null, false, 'Find me a romantic dinner in Beirut'),
        ];
    }

    private function case(
        string $label,
        string $prompt,
        ?string $city,
        bool $hold,
        ?int $dayCount,
        bool $tripPlan,
        array $requiredCategories,
        array $forbiddenCategories,
        array $expectedNonEmpty,
        bool $onlyExpectedResults = true,
        bool $expectStay = false,
        ?array $route = null,
        bool $expectNoStay = false,
        ?int $budgetMax = null,
        bool $countryScope = false,
        ?string $previousPrompt = null,
    ): array {
        return [
            'label' => $label,
            'prompt' => $prompt,
            'city' => $city,
            'hold' => $hold,
            'day_count' => $dayCount,
            'trip_plan' => $tripPlan,
            'required_categories' => $requiredCategories,
            'forbidden_categories' => $forbiddenCategories,
            'expected_non_empty' => $expectedNonEmpty,
            'only_expected_results' => $onlyExpectedResults,
            'expect_stay' => $expectStay,
            'route' => $route,
            'expect_no_stay' => $expectNoStay,
            'budget_max' => $budgetMax,
            'country_scope' => $countryScope,
            'previous_prompt' => $previousPrompt,
        ];
    }

    private function guidanceRequiredCategories(string $prompt): array
    {
        $prompt = strtolower($prompt);

        if (str_contains($prompt, 'hotel')) {
            return ['hotels'];
        }

        if (str_contains($prompt, 'restaurant') || str_contains($prompt, 'food')) {
            return ['restaurants'];
        }

        if (str_contains($prompt, 'activit') || str_contains($prompt, 'places')) {
            return ['activities'];
        }

        if (str_contains($prompt, 'trip')) {
            return ['trip_plan'];
        }

        return [];
    }

    private function cities(): array
    {
        return [
            'beirut' => 'Beirut',
            'batroun' => 'Batroun',
            'tyre' => 'Tyre',
            'byblos' => 'Byblos',
            'tripoli' => 'Tripoli',
            'jounieh' => 'Jounieh',
            'saida' => 'Saida',
            'broumana' => 'Broumana',
            'zahle' => 'Zahle',
        ];
    }

    private function aliases(): array
    {
        return [
            'byblos' => 'Jbeil',
            'tyre' => 'Sur',
            'saida' => 'Sidon',
            'tripoli' => 'Trablos',
            'broumana' => 'Brummana',
            'zahle' => 'Zahleh',
        ];
    }

    private function cityDisplay(string $city): string
    {
        return $this->cities()[$city] ?? ucfirst($city);
    }

    private function assertUniqueItems(array $items, string $key, string $message): void
    {
        $values = array_values(array_filter(array_map(
            fn ($item) => is_array($item) ? trim((string) ($item[$key] ?? '')) : '',
            $items
        )));

        $this->assertSameSize(array_unique($values), $values, $message);
    }

    private function tripPlanContainsSlot(array $tripPlan, string $slot): bool
    {
        foreach ((array) ($tripPlan['days'] ?? []) as $day) {
            if (is_array($day) && array_key_exists($slot, (array) ($day['flow'] ?? []))) {
                return true;
            }
        }

        return false;
    }
}
