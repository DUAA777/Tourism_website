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
        return view('chatbot_fullscreen');
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

        $previousContext = $this->normalizeSessionContext($session->context_payload);

        try {
            $recommendations = $this->recommendationService->buildResponseData($message, $previousContext);
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

        $structuredPayload = $this->buildStructuredPayload($recommendations);

        $this->logChatbotRecommendationPrepared($session, $message, $recommendations);

        $pythonReply = $this->requestPythonReply($session, $message, $history, $recommendations, $previousContext);
        $reply = trim((string) ($pythonReply['reply'] ?? ''));
        $replySource = $reply !== '' ? 'python' : 'fallback';
        $fallbackReason = $replySource === 'fallback'
            ? (string) ($pythonReply['failure_reason'] ?? 'unknown')
            : null;

        if ($reply === '') {
            $reply = $this->buildFallbackReply($recommendations);
        }

        $reply = $this->alignReplyWithStructuredPayload($reply, $structuredPayload);
        $structuredPayload = $this->syncStructuredPayloadWithReply($structuredPayload, $reply);

        $session->forceFill([
            'context_payload' => $this->buildSessionContextPayload(
                $previousContext,
                $message,
                $recommendations,
                $structuredPayload,
                $reply
            ),
        ])->save();

        ChatMessage::create([
            'chat_session_id' => $session->id,
            'role' => 'assistant',
            'message' => $reply,
        ]);

        $this->logChatbotReplyDelivered(
            $session,
            $recommendations,
            $structuredPayload,
            $reply,
            $replySource,
            $fallbackReason
        );

        return response()->json([
            'reply' => $reply,
            'session_id' => $session->id,
            'entity_links' => $this->buildEntityLinks($recommendations),
            'structured' => $structuredPayload,
        ]);
    }

    private function logChatbotRecommendationPrepared(ChatSession $session, string $message, array $recommendations): void
    {
        if (!(bool) config('services.chatbot.audit_logging', true)) {
            return;
        }

        Log::info('Chatbot recommendation payload prepared.', [
            'session_id' => $session->id,
            'user_id' => auth()->id(),
            'message_preview' => $this->sanitizeLogPreview($message),
            'message_length' => mb_strlen($message),
            'message_type' => data_get($recommendations, 'intent.message_type'),
            'primary_city' => $this->recommendationPrimaryCity($recommendations),
            'route_cities' => $this->recommendationRouteCities($recommendations),
            'hold_results' => (bool) data_get($recommendations, 'diagnostics.guidance.should_hold_results'),
            'confidence_overall' => data_get($recommendations, 'diagnostics.confidence.overall'),
            'summary_chips' => array_slice((array) data_get($recommendations, 'diagnostics.summary_chips', []), 0, 6),
            'result_counts' => $this->recommendationResultCounts($recommendations),
        ]);
    }

    private function logChatbotReplyDelivered(
        ChatSession $session,
        array $recommendations,
        array $structuredPayload,
        string $reply,
        string $replySource,
        ?string $fallbackReason = null
    ): void {
        if (!(bool) config('services.chatbot.audit_logging', true)) {
            return;
        }

        Log::info('Chatbot reply delivered.', [
            'session_id' => $session->id,
            'user_id' => auth()->id(),
            'reply_source' => $replySource,
            'fallback_reason' => $fallbackReason,
            'reply_length' => mb_strlen($reply),
            'message_type' => data_get($recommendations, 'intent.message_type'),
            'primary_city' => $this->recommendationPrimaryCity($recommendations),
            'route_cities' => $this->recommendationRouteCities($recommendations),
            'hold_results' => (bool) data_get($recommendations, 'diagnostics.guidance.should_hold_results'),
            'confidence_overall' => data_get($recommendations, 'diagnostics.confidence.overall'),
            'result_counts' => $this->recommendationResultCounts($recommendations),
            'structured_sections' => count((array) ($structuredPayload['sections'] ?? [])),
            'trip_days' => count((array) data_get($structuredPayload, 'trip_plan.days', [])),
        ]);
    }

    private function recommendationResultCounts(array $recommendations): array
    {
        return [
            'hotels' => count((array) ($recommendations['hotels'] ?? [])),
            'restaurants' => count((array) ($recommendations['restaurants'] ?? [])),
            'activities' => count((array) ($recommendations['activities'] ?? [])),
            'trip_days' => count((array) data_get($recommendations, 'trip_plan.days', [])),
        ];
    }

    private function recommendationPrimaryCity(array $recommendations): ?string
    {
        if ($this->recommendationRouteCities($recommendations) !== null) {
            return null;
        }

        $city = data_get($recommendations, 'intent.resolved_city')
            ?? data_get($recommendations, 'intent.city')
            ?? data_get($recommendations, 'intent.start_city');

        return is_string($city) && trim($city) !== '' ? trim($city) : null;
    }

    private function recommendationRouteCities(array $recommendations): ?array
    {
        $cities = array_values(array_filter(
            (array) data_get($recommendations, 'intent.mentioned_cities', []),
            fn ($city) => is_string($city) && trim($city) !== ''
        ));

        return count($cities) > 1 ? $cities : null;
    }

    private function sanitizeLogPreview(string $message): string
    {
        $preview = mb_substr($message, 0, 120);
        $patterns = [
            '/AIza[0-9A-Za-z\-_]{20,}/u',
            '/\bsk-[A-Za-z0-9]{16,}\b/u',
            '/\b[A-Za-z0-9_\-]{32,}\b/u',
        ];

        foreach ($patterns as $pattern) {
            $preview = preg_replace($pattern, '[redacted]', $preview) ?? $preview;
        }

        return $preview;
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

    private function requestPythonReply(
        ChatSession $session,
        string $message,
        array $history,
        array $recommendations,
        ?array $previousContext = null
    ): array
    {
        $chatbotBaseUrl = rtrim((string) config('services.chatbot.base_url', 'http://127.0.0.1:5000'), '/');

        try {
            $pythonResponse = Http::connectTimeout((int) config('services.chatbot.connect_timeout', 3))
                ->timeout((int) config('services.chatbot.timeout', 45))
                ->retry(2, 250, null, false)
                ->post($chatbotBaseUrl . '/chat', [
                    'session_id' => $session->id,
                    'message' => $message,
                    'history' => $history,
                    'intent' => $recommendations['intent'] ?? [],
                    'hotels' => $recommendations['hotels'] ?? [],
                    'restaurants' => $recommendations['restaurants'] ?? [],
                    'activities' => $recommendations['activities'] ?? [],
                    'trip_plan' => $recommendations['trip_plan'] ?? null,
                    'diagnostics' => $recommendations['diagnostics'] ?? [],
                    'session_context' => $previousContext ?? [],
                ]);
        } catch (\Throwable $e) {
            Log::warning('Could not reach chatbot Python service.', [
                'session_id' => $session->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return [
                'reply' => null,
                'failure_reason' => 'unreachable',
            ];
        }

        if (!$pythonResponse->successful()) {
            Log::warning('Chatbot Python service returned an unsuccessful response.', [
                'session_id' => $session->id,
                'status' => $pythonResponse->status(),
            ]);

            return [
                'reply' => null,
                'failure_reason' => 'bad_status_' . $pythonResponse->status(),
            ];
        }

        $reply = trim((string) data_get($pythonResponse->json(), 'reply', ''));

        if ($reply === '') {
            Log::warning('Chatbot Python service returned an empty reply.', [
                'session_id' => $session->id,
            ]);

            return [
                'reply' => null,
                'failure_reason' => 'empty_reply',
            ];
        }

        return [
            'reply' => $reply,
            'failure_reason' => null,
        ];
    }

    private function normalizeSessionContext(mixed $context): ?array
    {
        if (!is_array($context)) {
            return null;
        }

        $intent = is_array($context['intent'] ?? null) ? $context['intent'] : null;

        if ($intent === null) {
            return null;
        }

        return [
            'intent' => $intent,
            'diagnostics' => is_array($context['diagnostics'] ?? null) ? $context['diagnostics'] : [],
            'hotels' => array_values(array_filter((array) ($context['hotels'] ?? []), 'is_array')),
            'restaurants' => array_values(array_filter((array) ($context['restaurants'] ?? []), 'is_array')),
            'activities' => array_values(array_filter((array) ($context['activities'] ?? []), 'is_array')),
            'trip_plan' => is_array($context['trip_plan'] ?? null) ? $context['trip_plan'] : null,
            'last_user_message' => trim((string) ($context['last_user_message'] ?? '')),
            'last_reply' => trim((string) ($context['last_reply'] ?? '')),
            'updated_at' => trim((string) ($context['updated_at'] ?? '')),
        ];
    }

    private function buildSessionContextPayload(
        ?array $previousContext,
        string $message,
        array $recommendations,
        array $structuredPayload,
        string $reply
    ): ?array {
        if (!$this->shouldPersistRecommendationContext($recommendations)) {
            return $previousContext;
        }

        return [
            'intent' => is_array($recommendations['intent'] ?? null) ? $recommendations['intent'] : [],
            'diagnostics' => $this->compactSessionDiagnostics($recommendations['diagnostics'] ?? []),
            'hotels' => $this->compactSessionItems($recommendations['hotels'] ?? [], 3),
            'restaurants' => $this->compactSessionItems($recommendations['restaurants'] ?? [], 3),
            'activities' => $this->compactSessionItems($recommendations['activities'] ?? [], 6),
            'trip_plan' => $this->compactSessionTripPlan($recommendations['trip_plan'] ?? null),
            'summary_chips' => array_values(array_slice(
                array_filter((array) ($structuredPayload['summary_chips'] ?? []), fn ($chip) => is_string($chip) && trim($chip) !== ''),
                0,
                8
            )),
            'last_user_message' => $message,
            'last_reply' => mb_substr($reply, 0, 1600),
            'updated_at' => now()->toIso8601String(),
        ];
    }

    private function shouldPersistRecommendationContext(array $recommendations): bool
    {
        if (!empty(data_get($recommendations, 'intent.follow_up_context.is_follow_up'))) {
            return true;
        }

        if (!empty(data_get($recommendations, 'intent.has_travel_signal'))) {
            return true;
        }

        return !empty($recommendations['hotels'])
            || !empty($recommendations['restaurants'])
            || !empty($recommendations['activities'])
            || !empty(data_get($recommendations, 'trip_plan.days', []));
    }

    private function compactSessionDiagnostics(mixed $diagnostics): array
    {
        if (!is_array($diagnostics)) {
            return [];
        }

        return [
            'summary_chips' => array_values(array_slice(
                array_filter((array) ($diagnostics['summary_chips'] ?? []), fn ($chip) => is_string($chip) && trim($chip) !== ''),
                0,
                8
            )),
            'guidance' => is_array($diagnostics['guidance'] ?? null) ? $diagnostics['guidance'] : [],
            'confidence' => is_array($diagnostics['confidence'] ?? null) ? $diagnostics['confidence'] : [],
        ];
    }

    private function compactSessionItems(array $items, int $limit): array
    {
        return array_values(array_slice(array_filter($items, 'is_array'), 0, $limit));
    }

    private function compactSessionTripPlan(mixed $tripPlan): ?array
    {
        return is_array($tripPlan) ? $tripPlan : null;
    }

    private function buildFallbackReply(array $recommendations): string
    {
        if (data_get($recommendations, 'diagnostics.guidance.should_hold_results')) {
            $guidanceReply = trim((string) data_get($recommendations, 'diagnostics.guidance.fallback_reply', ''));

            if ($guidanceReply !== '') {
                return $guidanceReply;
            }
        }

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

    private function alignReplyWithStructuredPayload(string $reply, array $structuredPayload): string
    {
        $reply = trim($reply);

        if ($reply === '') {
            return $reply;
        }

        return $reply;
    }

    private function syncStructuredPayloadWithReply(array $structuredPayload, string $reply): array
    {
        if ($reply === '' || !empty(data_get($structuredPayload, 'trip_plan.days', []))) {
            return $structuredPayload;
        }

        $sections = [];

        foreach ((array) ($structuredPayload['sections'] ?? []) as $section) {
            if (!is_array($section)) {
                continue;
            }

            $sectionType = (string) ($section['type'] ?? '');
            if (!in_array($sectionType, ['hotels', 'restaurants'], true)) {
                $sections[] = $section;
                continue;
            }

            $items = array_values(array_filter(
                (array) ($section['items'] ?? []),
                'is_array'
            ));

            if (empty($items)) {
                continue;
            }

            $matchedItems = array_values(array_filter($items, function (array $item) use ($reply) {
                $title = trim((string) ($item['title'] ?? ''));

                return $title !== '' && stripos($reply, $title) !== false;
            }));

            if (!empty($matchedItems)) {
                $section['items'] = $matchedItems;
                $sections[] = $section;
                continue;
            }

            if ($this->replySignalsNoDirectMatch($reply)) {
                continue;
            }

            $sections[] = $section;
        }

        $structuredPayload['sections'] = $sections;

        return $structuredPayload;
    }

    private function replySignalsNoDirectMatch(string $reply): bool
    {
        $normalized = strtolower(trim($reply));
        $phrases = [
            "don't have any specific",
            'dont have any specific',
            "don't have specific",
            'dont have specific',
            "i don't have",
            'i dont have',
            'do not have',
            'not seeing a strong match',
            'would you be open',
            'open to exploring other types',
            'different city',
        ];

        foreach ($phrases as $phrase) {
            if (str_contains($normalized, $phrase)) {
                return true;
            }
        }

        return false;
    }

    private function renderTripPlanFallback(array $tripPlan): string
    {
        $lines = [];
        $title = trim((string) ($tripPlan['title'] ?? 'Trip Plan'));
        $summary = trim((string) ($tripPlan['summary'] ?? ''));

        $lines[] = $title !== '' ? $title : 'Trip Plan';
        $lines[] = $summary !== '' ? $summary : $this->buildTripPlanFallbackIntro($tripPlan);

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

            foreach ([
                $this->buildTripSlotNarrativeLine('Morning', $flow['morning'] ?? null),
                $this->buildTripMealNarrativeLine('Lunch', $flow['lunch'] ?? null, 'restaurant_name', 'location'),
                $this->buildTripSlotNarrativeLine('Afternoon', $flow['afternoon'] ?? null),
                $this->buildTripSlotNarrativeLine('Evening', $flow['evening'] ?? null),
                $this->buildTripMealNarrativeLine('Dinner', $flow['dinner'] ?? null, 'restaurant_name', 'location'),
                $this->buildTripMealNarrativeLine('Stay', $flow['stay'] ?? null, 'hotel_name', 'address'),
            ] as $line) {
                if ($line !== null) {
                    $lines[] = $line;
                }
            }
        }

        return implode("\n", $lines);
    }

    private function buildTripSlotNarrativeLine(string $label, mixed $slot): ?string
    {
        if (!is_array($slot)) {
            return null;
        }

        $activities = array_values(array_filter(
            (array) ($slot['activities'] ?? []),
            fn ($activity) => is_string($activity) && trim($activity) !== ''
        ));

        if (empty($activities)) {
            return null;
        }

        $title = trim((string) ($slot['title'] ?? ''));
        $activityText = $this->humanizeTripList($activities);
        if ($activityText === '') {
            return null;
        }

        $line = match (strtolower($label)) {
            'morning' => $this->tripActivityNarrative($activityText, 'Start with '),
            'afternoon' => $this->tripActivityNarrative($activityText, 'Spend the afternoon with '),
            'evening' => $this->tripActivityNarrative($activityText, 'Keep the evening for '),
            default => $this->ensureSentence($activityText),
        };

        if ($title !== '' && !$this->isGenericTripSlotTitle($label, $title)) {
            $line = $this->ensureSentence($title) . ' ' . $line;
        }

        return "{$label}: {$line}";
    }

    private function buildTripMealNarrativeLine(string $label, mixed $item, string $nameKey, string $locationKey): ?string
    {
        if (!is_array($item)) {
            return null;
        }

        $name = trim((string) ($item[$nameKey] ?? ''));
        if ($name === '') {
            $note = trim((string) ($item['note'] ?? ''));

            if ($label === 'Stay' && $note !== '') {
                return "{$label}: " . $this->ensureSentence($note);
            }

            return null;
        }

        $location = $this->cleanTripLocation(
            $name,
            trim((string) ($item[$locationKey] ?? ''))
        );
        $type = trim((string) ($item['food_type'] ?? ''));
        $price = trim((string) ($item['price_tier'] ?? ($item['price_per_night'] ?? '')));

        $line = $label === 'Stay' ? "Stay at {$name}" : "Head to {$name}";

        if ($location !== '') {
            $line .= " in {$location}";
        }

        if ($label === 'Stay' && $price !== '') {
            $line .= " with rates around {$price}";
        } elseif ($type !== '') {
            $line .= " for {$type}";
        }

        return "{$label}: " . $this->ensureSentence($line);
    }

    private function buildTripPlanFallbackIntro(array $tripPlan): string
    {
        $title = strtolower(trim((string) ($tripPlan['title'] ?? '')));

        if (str_contains($title, 'from ')) {
            return 'A grounded route built from the strongest confirmed stays, food spots, and activities I could keep.';
        }

        return 'A grounded itinerary built from the strongest confirmed stays, food spots, and activities I could keep.';
    }

    private function tripActivityNarrative(string $activityText, string $fallbackPrefix): string
    {
        if ($this->looksLikeTripNarrativePhrase($activityText)) {
            return $this->ensureSentence($activityText);
        }

        return $this->ensureSentence($fallbackPrefix . $activityText);
    }

    private function looksLikeTripNarrativePhrase(string $value): bool
    {
        $normalized = strtolower(trim($value));

        foreach ([
            'ease into',
            'start with',
            'spend ',
            'keep ',
            'wrap up',
            'leave ',
            'travel ',
            'enjoy ',
            'relax ',
            'explore ',
            'take ',
            'begin ',
            'head ',
        ] as $prefix) {
            if (str_starts_with($normalized, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function humanizeTripList(array $items): string
    {
        $items = array_values(array_filter(array_map(
            fn ($item) => trim((string) $item),
            $items
        )));

        $count = count($items);

        if ($count === 0) {
            return '';
        }

        if ($count === 1) {
            return $items[0];
        }

        if ($count === 2) {
            return $items[0] . ' and ' . $items[1];
        }

        $last = array_pop($items);

        return implode(', ', $items) . ', and ' . $last;
    }

    private function isGenericTripSlotTitle(string $label, string $title): bool
    {
        $normalizedLabel = strtolower(trim($label));
        $normalizedTitle = strtolower(trim($title));

        return str_starts_with($normalizedTitle, $normalizedLabel . ' in ');
    }

    private function cleanTripLocation(string $name, string $location): string
    {
        $location = trim($location, " \t\n\r\0\x0B,.;");
        if ($location === '') {
            return '';
        }

        $parts = array_values(array_filter(array_map(
            fn ($part) => trim($part),
            explode(',', $location)
        )));

        if (empty($parts)) {
            return $location;
        }

        $normalizedName = $this->normalizeTripComparisonText($name);
        $firstPart = $this->normalizeTripComparisonText($parts[0]);

        if (
            $firstPart === $normalizedName
            || str_starts_with($firstPart, $normalizedName)
            || str_starts_with($normalizedName, $firstPart)
            || $this->tripLocationPrefixContainsNameTokens($normalizedName, $firstPart)
        ) {
            array_shift($parts);
        }

        return trim(implode(', ', $parts), " \t\n\r\0\x0B,.;");
    }

    private function normalizeTripComparisonText(string $value): string
    {
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value);

        return trim((string) $value);
    }

    private function tripLocationPrefixContainsNameTokens(string $normalizedName, string $normalizedLocationPrefix): bool
    {
        $nameTokens = array_values(array_filter(explode(' ', $normalizedName)));
        $locationTokens = array_values(array_filter(explode(' ', $normalizedLocationPrefix)));

        if (count($nameTokens) < 2 || empty($locationTokens)) {
            return false;
        }

        return empty(array_diff($nameTokens, $locationTokens));
    }

    private function ensureSentence(string $value): string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');
        $value = str_replace('.;', '.', $value);
        $value = preg_replace('/;\s*\./', '.', $value) ?? $value;
        $value = preg_replace('/\.\.+/', '.', $value) ?? $value;
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (!in_array(substr($value, -1), ['.', '!', '?'], true)) {
            $value .= '.';
        }

        return $value;
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
            isset($hotel['rating_score']) ? 'rating ' . $hotel['rating_score'] . '/10' : null,
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
                'url' => $this->buildHotelUrl($hotelId),
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
                'url' => $this->buildRestaurantUrl($restaurantId),
            ];
        }

        return array_values($links);
    }

    private function buildStructuredPayload(array $recommendations): array
    {
        return [
            'summary_chips' => array_values(array_filter(
                (array) data_get($recommendations, 'diagnostics.summary_chips', []),
                fn ($chip) => is_string($chip) && trim($chip) !== ''
            )),
            'sections' => array_values(array_filter([
                $this->buildStructuredSection(
                    'hotels',
                    'Hotel Matches',
                    $recommendations['hotels'] ?? [],
                    fn (array $hotel) => [
                        'title' => trim((string) ($hotel['hotel_name'] ?? '')),
                        'subtitle' => trim((string) ($hotel['address'] ?? '')),
                        'meta' => array_values(array_filter([
                            trim((string) ($hotel['price_per_night'] ?? '')),
                            isset($hotel['rating_score']) ? 'Rating ' . $hotel['rating_score'] . '/10' : null,
                            !empty($hotel['budget_tier']) ? ucfirst(str_replace('_', ' ', (string) $hotel['budget_tier'])) : null,
                        ])),
                        'reasons' => array_slice((array) ($hotel['top_reasons'] ?? $hotel['match_reasons'] ?? []), 0, 3),
                        'url' => $this->buildHotelUrl($hotel['id'] ?? null),
                        'url_label' => 'Open hotel',
                    ]
                ),
                $this->buildStructuredSection(
                    'restaurants',
                    'Restaurant Matches',
                    $recommendations['restaurants'] ?? [],
                    fn (array $restaurant) => [
                        'title' => trim((string) ($restaurant['restaurant_name'] ?? '')),
                        'subtitle' => trim((string) ($restaurant['location'] ?? '')),
                        'meta' => array_values(array_filter([
                            trim((string) ($restaurant['food_type'] ?? '')),
                            trim((string) ($restaurant['price_tier'] ?? '')),
                            isset($restaurant['rating']) ? 'Rating ' . $restaurant['rating'] . '/5' : null,
                        ])),
                        'reasons' => array_slice((array) ($restaurant['top_reasons'] ?? $restaurant['match_reasons'] ?? []), 0, 3),
                        'url' => $this->buildRestaurantUrl($restaurant['id'] ?? null),
                        'url_label' => 'Open restaurant',
                    ]
                ),
                $this->buildStructuredSection(
                    'activities',
                    'Place and Activity Matches',
                    $recommendations['activities'] ?? [],
                    fn (array $activity) => [
                        'title' => trim((string) ($activity['name'] ?? '')),
                        'subtitle' => trim((string) ($activity['location'] ?? ($activity['city'] ?? ''))),
                        'meta' => array_values(array_filter([
                            trim((string) ($activity['category'] ?? '')),
                            trim((string) ($activity['best_time'] ?? '')),
                            trim((string) ($activity['budget_tier'] ?? '')),
                        ])),
                        'reasons' => array_slice((array) ($activity['top_reasons'] ?? $activity['match_reasons'] ?? []), 0, 3),
                        'url' => null,
                        'url_label' => null,
                    ]
                ),
            ])),
            'trip_plan' => $this->decorateTripPlanWithLinks($recommendations['trip_plan'] ?? null),
            'diagnostics' => $recommendations['diagnostics'] ?? [],
        ];
    }

    private function buildStructuredSection(string $type, string $title, array $items, callable $transformer): ?array
    {
        $items = array_values(array_filter($items, 'is_array'));
        if (empty($items)) {
            return null;
        }

        $entries = [];

        foreach (array_slice($items, 0, 3) as $item) {
            $entry = $transformer($item);
            $entry['title'] = trim((string) ($entry['title'] ?? ''));

            if ($entry['title'] === '') {
                continue;
            }

            $entry['subtitle'] = trim((string) ($entry['subtitle'] ?? ''));
            $entry['meta'] = array_values(array_filter(
                (array) ($entry['meta'] ?? []),
                fn ($meta) => is_string($meta) && trim($meta) !== ''
            ));
            $entry['reasons'] = array_values(array_filter(
                (array) ($entry['reasons'] ?? []),
                fn ($reason) => is_string($reason) && trim($reason) !== ''
            ));
            $entry['url'] = isset($entry['url']) && is_string($entry['url']) && trim($entry['url']) !== ''
                ? $entry['url']
                : null;
            $entry['url_label'] = isset($entry['url_label']) && is_string($entry['url_label']) && trim($entry['url_label']) !== ''
                ? $entry['url_label']
                : null;

            $entries[] = $entry;
        }

        if (empty($entries)) {
            return null;
        }

        return [
            'type' => $type,
            'title' => $title,
            'items' => $entries,
        ];
    }

    private function decorateTripPlanWithLinks(mixed $tripPlan): ?array
    {
        if (!is_array($tripPlan) || empty($tripPlan['days']) || !is_array($tripPlan['days'])) {
            return is_array($tripPlan) ? $tripPlan : null;
        }

        $tripPlan['days'] = array_map(function ($day) {
            if (!is_array($day)) {
                return $day;
            }

            $flow = is_array($day['flow'] ?? null) ? $day['flow'] : [];

            foreach (['lunch', 'dinner'] as $mealKey) {
                if (is_array($flow[$mealKey] ?? null) && !empty($flow[$mealKey]['id'])) {
                    $flow[$mealKey]['url'] = $this->buildRestaurantUrl($flow[$mealKey]['id']);
                }
            }

            if (is_array($flow['stay'] ?? null) && !empty($flow['stay']['id'])) {
                $flow['stay']['url'] = $this->buildHotelUrl($flow['stay']['id']);
            }

            $day['flow'] = $flow;

            return $day;
        }, $tripPlan['days']);

        return $tripPlan;
    }

    private function buildRestaurantUrl(mixed $restaurantId): ?string
    {
        $restaurantId = (int) $restaurantId;

        return $restaurantId > 0 ? url("/restaurants/{$restaurantId}") : null;
    }

    private function buildHotelUrl(mixed $hotelId): ?string
    {
        $hotelId = (int) $hotelId;

        return $hotelId > 0 ? url("/hotels/{$hotelId}") : null;
    }
}
