<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Services\RecommendationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ChatbotController extends Controller
{
    public function __construct(private RecommendationService $recommendationService)
    {
    }

    public function index(Request $request)
    {
        return view('chatbot');
    }

    public function newSession(Request $request)
    {
        $session = $this->createSession('New Chat');

        return response()->json([
            'session_id' => $session->id,
        ]);
    }

    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => ['required', 'string', 'max:2000'],
            'session_id' => ['nullable', 'integer'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'reply' => 'Please type a message first.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $message = trim((string) ($validated['message'] ?? ''));
        $sessionId = isset($validated['session_id']) ? (int) $validated['session_id'] : null;

        if ($message === '') {
            return response()->json([
                'reply' => 'Please type a message first.',
            ], 422);
        }

        $session = $this->resolveSession($sessionId, $message);
        $this->updateSessionTitleIfNeeded($session, $message);

        ChatMessage::create([
            'chat_session_id' => $session->id,
            'role' => 'user',
            'message' => $message,
        ]);

        $history = ChatMessage::where('chat_session_id', $session->id)
            ->latest()
            ->limit(6)
            ->get(['role', 'message'])
            ->reverse()
            ->values()
            ->map(function ($msg) {
                return [
                    'role' => $msg->role,
                    'message' => $msg->message,
                ];
            })
            ->toArray();

        try {
            $recommendations = $this->recommendationService->buildResponseData($message);
        } catch (\Throwable $e) {
            Log::error('Failed to build chatbot recommendations.', [
                'session_id' => $session->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'reply' => 'Could not process your request right now. Please try again.',
                'session_id' => $session->id,
            ], 500);
        }

        $reply = $this->requestPythonReply($session, $message, $history, $recommendations);

        if ($reply === null) {
            $reply = $this->buildFallbackReply($recommendations);
        }

        ChatMessage::create([
            'chat_session_id' => $session->id,
            'role' => 'assistant',
            'message' => $reply,
        ]);

        return response()->json([
            'reply' => $reply,
            'session_id' => $session->id,
            'entity_links' => $this->buildEntityLinks($recommendations),
        ]);
    }

    private function createSession(string $title): ChatSession
    {
        return ChatSession::create([
            'user_id' => auth()->id(),
            'title' => $title,
        ]);
    }

    private function resolveSession(?int $sessionId, string $message): ChatSession
    {
        if ($sessionId !== null && $sessionId > 0) {
            $session = ChatSession::find($sessionId);

            if ($this->sessionIsAccessible($session)) {
                return $session;
            }

            if ($session) {
                Log::warning('Rejected inaccessible chatbot session.', [
                    'session_id' => $sessionId,
                    'requested_by_user_id' => auth()->id(),
                    'session_user_id' => $session->user_id,
                ]);
            }
        }

        return $this->createSession($this->buildSessionTitle($message));
    }

    private function sessionIsAccessible(?ChatSession $session): bool
    {
        if (!$session) {
            return false;
        }

        $userId = auth()->id();

        if ($userId === null) {
            return $session->user_id === null;
        }

        return (int) $session->user_id === (int) $userId;
    }

    private function updateSessionTitleIfNeeded(ChatSession $session, string $message): void
    {
        $currentTitle = trim((string) $session->title);

        if ($currentTitle !== '' && $currentTitle !== 'New Chat') {
            return;
        }

        $title = $this->buildSessionTitle($message);
        if ($title === $currentTitle) {
            return;
        }

        $session->forceFill(['title' => $title])->save();
    }

    private function buildSessionTitle(string $message): string
    {
        $title = trim(substr($message, 0, 50));

        return $title !== '' ? $title : 'New Chat';
    }

    private function requestPythonReply(ChatSession $session, string $message, array $history, array $recommendations): ?string
    {
        try {
            $pythonResponse = Http::connectTimeout(3)
                ->timeout(45)
                ->retry(2, 250, null, false)
                ->post('http://127.0.0.1:5000/chat', [
                    'session_id' => $session->id,
                    'message' => $message,
                    'history' => $history,
                    'intent' => $recommendations['intent'] ?? [],
                    'hotels' => $recommendations['hotels'] ?? [],
                    'restaurants' => $recommendations['restaurants'] ?? [],
                    'activities' => $recommendations['activities'] ?? [],
                    'trip_plan' => $recommendations['trip_plan'] ?? null,
                ]);
        } catch (\Throwable $e) {
            Log::warning('Could not reach chatbot Python service.', [
                'session_id' => $session->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        if (!$pythonResponse->successful()) {
            Log::warning('Chatbot Python service returned an unsuccessful response.', [
                'session_id' => $session->id,
                'status' => $pythonResponse->status(),
            ]);

            return null;
        }

        $reply = trim((string) data_get($pythonResponse->json(), 'reply', ''));

        if ($reply === '') {
            Log::warning('Chatbot Python service returned an empty reply.', [
                'session_id' => $session->id,
            ]);

            return null;
        }

        return $reply;
    }

    private function buildFallbackReply(array $recommendations): string
    {
        $tripPlan = $recommendations['trip_plan'] ?? null;
        if (is_array($tripPlan) && !empty($tripPlan['days'])) {
            return $this->renderTripPlanFallback($tripPlan);
        }

        $sections = [];

        $hotelSection = $this->renderRecommendationSection(
            'Hotels',
            $recommendations['hotels'] ?? [],
            fn (array $hotel) => $this->formatHotelRecommendation($hotel)
        );
        if ($hotelSection !== null) {
            $sections[] = $hotelSection;
        }

        $restaurantSection = $this->renderRecommendationSection(
            'Restaurants',
            $recommendations['restaurants'] ?? [],
            fn (array $restaurant) => $this->formatRestaurantRecommendation($restaurant)
        );
        if ($restaurantSection !== null) {
            $sections[] = $restaurantSection;
        }

        $activitySection = $this->renderRecommendationSection(
            'Activities',
            $recommendations['activities'] ?? [],
            fn (array $activity) => $this->formatActivityRecommendation($activity)
        );
        if ($activitySection !== null) {
            $sections[] = $activitySection;
        }

        if (empty($sections)) {
            return "I couldn't find a strong match for that request. Try adding a city, budget, or trip length.";
        }

        return implode("\n\n", array_merge(
            ['Here are grounded suggestions based on your request:'],
            $sections
        ));
    }

    private function renderTripPlanFallback(array $tripPlan): string
    {
        $lines = [];
        $title = trim((string) ($tripPlan['title'] ?? 'Trip Plan'));
        $summary = trim((string) ($tripPlan['summary'] ?? ''));

        $lines[] = $title !== '' ? $title : 'Trip Plan';
        if ($summary !== '') {
            $lines[] = $summary;
        }

        foreach ($tripPlan['days'] as $day) {
            if (!is_array($day)) {
                continue;
            }

            $dayNumber = (int) ($day['day'] ?? 0);
            $location = trim((string) ($day['location'] ?? ''));
            $heading = $dayNumber > 0 ? "Day {$dayNumber}" : 'Plan';

            if ($location !== '') {
                $heading .= " - {$location}";
            }

            $lines[] = '';
            $lines[] = $heading;

            $flow = is_array($day['flow'] ?? null) ? $day['flow'] : [];

            $this->appendTripSlotLine($lines, 'Morning', $flow['morning'] ?? null);
            $this->appendTripMealLine($lines, 'Lunch', $flow['lunch'] ?? null, 'restaurant_name', 'location');
            $this->appendTripSlotLine($lines, 'Afternoon', $flow['afternoon'] ?? null);
            $this->appendTripSlotLine($lines, 'Evening', $flow['evening'] ?? null);
            $this->appendTripMealLine($lines, 'Dinner', $flow['dinner'] ?? null, 'restaurant_name', 'location');
            $this->appendTripMealLine($lines, 'Stay', $flow['stay'] ?? null, 'hotel_name', 'address');
        }

        return implode("\n", $lines);
    }

    private function appendTripSlotLine(array &$lines, string $label, mixed $slot): void
    {
        if (!is_array($slot)) {
            return;
        }

        $activities = array_values(array_filter(
            (array) ($slot['activities'] ?? []),
            fn ($activity) => is_string($activity) && trim($activity) !== ''
        ));

        if (empty($activities)) {
            return;
        }

        $title = trim((string) ($slot['title'] ?? ''));
        $line = "{$label}: ";

        if ($title !== '') {
            $line .= "{$title} - ";
        }

        $line .= implode('; ', $activities);
        $lines[] = $line;
    }

    private function appendTripMealLine(array &$lines, string $label, mixed $item, string $nameKey, string $locationKey): void
    {
        if (!is_array($item)) {
            return;
        }

        $name = trim((string) ($item[$nameKey] ?? ''));
        if ($name === '') {
            return;
        }

        $details = [];

        $location = trim((string) ($item[$locationKey] ?? ''));
        if ($location !== '') {
            $details[] = $location;
        }

        $type = trim((string) ($item['food_type'] ?? ''));
        if ($type !== '') {
            $details[] = $type;
        }

        $price = trim((string) ($item['price_tier'] ?? ($item['price_per_night'] ?? '')));
        if ($price !== '') {
            $details[] = $price;
        }

        $line = "{$label}: {$name}";
        if (!empty($details)) {
            $line .= ' (' . implode(', ', $details) . ')';
        }

        $lines[] = $line;
    }

    private function renderRecommendationSection(string $heading, array $items, callable $formatter): ?string
    {
        $items = array_values(array_filter($items, 'is_array'));
        if (empty($items)) {
            return null;
        }

        $lines = [$heading];

        foreach (array_slice($items, 0, 3) as $index => $item) {
            $lines[] = ($index + 1) . '. ' . $formatter($item);
        }

        return implode("\n", $lines);
    }

    private function formatHotelRecommendation(array $hotel): string
    {
        $details = array_values(array_filter([
            trim((string) ($hotel['address'] ?? '')),
            trim((string) ($hotel['price_per_night'] ?? '')),
            isset($hotel['rating_score']) ? 'rating ' . $hotel['rating_score'] . '/5' : null,
        ]));

        return $this->buildRecommendationLine(
            (string) ($hotel['hotel_name'] ?? 'Recommended hotel'),
            $details,
            $hotel['match_reasons'] ?? []
        );
    }

    private function formatRestaurantRecommendation(array $restaurant): string
    {
        $details = array_values(array_filter([
            trim((string) ($restaurant['location'] ?? '')),
            trim((string) ($restaurant['food_type'] ?? '')),
            trim((string) ($restaurant['price_tier'] ?? '')),
            isset($restaurant['rating']) ? 'rating ' . $restaurant['rating'] . '/5' : null,
        ]));

        return $this->buildRecommendationLine(
            (string) ($restaurant['restaurant_name'] ?? 'Recommended restaurant'),
            $details,
            $restaurant['match_reasons'] ?? []
        );
    }

    private function formatActivityRecommendation(array $activity): string
    {
        $details = array_values(array_filter([
            trim((string) ($activity['location'] ?? ($activity['city'] ?? ''))),
            trim((string) ($activity['category'] ?? '')),
            trim((string) ($activity['best_time'] ?? '')),
        ]));

        return $this->buildRecommendationLine(
            (string) ($activity['name'] ?? 'Recommended activity'),
            $details,
            $activity['match_reasons'] ?? []
        );
    }

    private function buildRecommendationLine(string $name, array $details, array $reasons): string
    {
        $line = $name;

        if (!empty($details)) {
            $line .= ' - ' . implode(', ', $details);
        }

        $formattedReasons = $this->formatMatchReasons($reasons);
        if ($formattedReasons !== '') {
            $line .= '. Good match because ' . $formattedReasons . '.';
        }

        return $line;
    }

    private function formatMatchReasons(array $reasons): string
    {
        $reasons = array_values(array_filter(
            $reasons,
            fn ($reason) => is_string($reason) && trim($reason) !== ''
        ));

        if (empty($reasons)) {
            return '';
        }

        return implode(', ', array_slice($reasons, 0, 3));
    }

    private function buildEntityLinks(array $recommendations): array
    {
        $links = [];

        foreach (($recommendations['hotels'] ?? []) as $hotel) {
            $hotelId = $hotel['id'] ?? null;
            $hotelName = trim((string) ($hotel['hotel_name'] ?? ''));

            if (!$hotelId || $hotelName === '') {
                continue;
            }

            $links['hotel:' . $hotelName] = [
                'type' => 'hotel',
                'name' => $hotelName,
                'url' => route('hotels.show', ['id' => $hotelId]),
            ];
        }

        foreach (($recommendations['restaurants'] ?? []) as $restaurant) {
            $restaurantId = $restaurant['id'] ?? null;
            $restaurantName = trim((string) ($restaurant['restaurant_name'] ?? ''));

            if (!$restaurantId || $restaurantName === '') {
                continue;
            }

            $links['restaurant:' . $restaurantName] = [
                'type' => 'restaurant',
                'name' => $restaurantName,
                'url' => route('restaurants.show', ['id' => $restaurantId]),
            ];
        }

        return array_values($links);
    }
}
