<?php

namespace Tests\Feature;

use App\Models\ChatSession;
use App\Models\User;
use App\Services\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class ChatbotControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser('tester@example.com');
        $this->actingAs($this->user);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_rejects_whitespace_only_messages(): void
    {
        $response = $this->postJson(route('chatbot.send'), [
            'message' => '   ',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('reply', 'Please type a message first.');

        $this->assertDatabaseCount('chat_sessions', 0);
        $this->assertDatabaseCount('chat_messages', 0);
    }

    public function test_it_falls_back_to_a_structured_trip_plan_when_python_service_fails(): void
    {
        $this->mockRecommendationService($this->tripPlanPayload());

        Http::fake([
            $this->chatbotServiceUrl() => Http::response(['error' => 'service unavailable'], 500),
        ]);

        $response = $this->postJson(route('chatbot.send'), [
            'message' => 'Plan me a 2 day trip in Batroun',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['reply', 'session_id']);

        $reply = $response->json('reply');

        $this->assertStringContainsString('2-Day Trip in Batroun', $reply);
        $this->assertStringContainsString('Day 1 - Batroun', $reply);
        $this->assertStringContainsString('Day 2 - Batroun', $reply);
        $this->assertDatabaseHas('chat_messages', [
            'chat_session_id' => $response->json('session_id'),
            'role' => 'assistant',
            'message' => $reply,
        ]);
    }

    public function test_it_falls_back_when_python_service_returns_an_empty_reply(): void
    {
        $this->mockRecommendationService($this->recommendationPayload());

        Http::fake([
            $this->chatbotServiceUrl() => Http::response(['reply' => '   '], 200),
        ]);

        $response = $this->postJson(route('chatbot.send'), [
            'message' => 'Recommend a hotel and dinner in Beirut',
        ]);

        $response->assertOk();

        $reply = $response->json('reply');

        $this->assertStringContainsString('Here are grounded suggestions based on your request:', $reply);
        $this->assertStringContainsString('Hotels', $reply);
        $this->assertStringContainsString('Harbor Stay', $reply);
        $this->assertStringContainsString('Restaurants', $reply);
        $this->assertStringContainsString('Sea Deck', $reply);
    }

    public function test_it_returns_clickable_entity_links_for_recommended_hotels_and_restaurants(): void
    {
        $this->mockRecommendationService($this->recommendationPayload());

        Http::fake([
            $this->chatbotServiceUrl() => Http::response([
                'reply' => 'You should look at Harbor Stay and Sea Deck.',
            ], 200),
        ]);

        $response = $this->postJson(route('chatbot.send'), [
            'message' => 'Recommend a hotel and dinner in Beirut',
        ]);

        $response->assertOk()
            ->assertJsonFragment([
                'type' => 'hotel',
                'name' => 'Harbor Stay',
                'url' => url('/hotels/82'),
            ])
            ->assertJsonFragment([
                'type' => 'restaurant',
                'name' => 'Sea Deck',
                'url' => url('/restaurants/17'),
            ]);
    }

    public function test_it_aligns_reply_text_with_structured_recommendation_cards(): void
    {
        $this->mockRecommendationService($this->recommendationPayload());

        Http::fake([
            $this->chatbotServiceUrl() => Http::response([
                'reply' => 'I found strong Beirut options that fit your request well.',
            ], 200),
        ]);

        $response = $this->postJson(route('chatbot.send'), [
            'message' => 'Recommend a hotel and dinner in Beirut',
        ]);

        $response->assertOk();

        $reply = $response->json('reply');

        $this->assertStringContainsString('Top hotel matches: Harbor Stay.', $reply);
        $this->assertStringContainsString('Top restaurant matches: Sea Deck.', $reply);
        $this->assertStringNotContainsString('Top place matches:', $reply);
        $this->assertStringContainsString('I found strong Beirut options that fit your request well.', $reply);
    }

    public function test_it_returns_structured_payload_for_rich_frontend_rendering(): void
    {
        $this->mockRecommendationService($this->tripPlanPayload());

        Http::fake([
            $this->chatbotServiceUrl() => Http::response([
                'reply' => 'Here is a 2 day Batroun plan with grounded picks.',
            ], 200),
        ]);

        $response = $this->postJson(route('chatbot.send'), [
            'message' => 'Plan me a 2 day trip in Batroun',
        ]);

        $response->assertOk()
            ->assertJsonPath('structured.summary_chips.0', 'City: Batroun')
            ->assertJsonPath('structured.sections.0.type', 'hotels')
            ->assertJsonPath('structured.sections.0.items.0.url', url('/hotels/12'))
            ->assertJsonPath('structured.sections.1.type', 'restaurants')
            ->assertJsonPath('structured.sections.1.items.0.url', url('/restaurants/24'))
            ->assertJsonPath('structured.trip_plan.days.0.flow.lunch.url', url('/restaurants/24'))
            ->assertJsonPath('structured.trip_plan.days.0.flow.stay.url', url('/hotels/12'));
    }

    public function test_it_keeps_trip_reply_copy_from_python_without_prepending_match_labels(): void
    {
        $this->mockRecommendationService($this->tripPlanPayload());

        Http::fake([
            $this->chatbotServiceUrl() => Http::response([
                'reply' => "2-Day Trip in Batroun\nA warm seaside escape with good food and sunset stops.\nDay 1 in Batroun:\nMorning: Start slowly along the harbor.",
            ], 200),
        ]);

        $response = $this->postJson(route('chatbot.send'), [
            'message' => 'Plan me a 2 day trip in Batroun',
        ]);

        $response->assertOk();

        $reply = $response->json('reply');

        $this->assertStringStartsWith('2-Day Trip in Batroun', $reply);
        $this->assertStringNotContainsString('Top hotel matches:', $reply);
        $this->assertStringNotContainsString('Top restaurant matches:', $reply);
        $this->assertStringNotContainsString('Top place matches:', $reply);
    }

    public function test_it_creates_a_new_session_when_the_requested_session_belongs_to_someone_else(): void
    {
        $owner = $this->createUser('owner@example.com');
        $requester = $this->createUser('requester@example.com');
        $foreignSession = ChatSession::create([
            'user_id' => $owner->id,
            'title' => 'Private Session',
        ]);

        $this->mockRecommendationService($this->recommendationPayload());

        Http::fake([
            $this->chatbotServiceUrl() => Http::response(['reply' => 'Safe reply'], 200),
        ]);

        $response = $this->actingAs($requester)->postJson(route('chatbot.send'), [
            'message' => 'Find me a hotel in Beirut',
            'session_id' => $foreignSession->id,
        ]);

        $response->assertOk();

        $reply = $response->json('reply');

        $this->assertStringContainsString('Safe reply', $reply);
        $this->assertStringContainsString('Harbor Stay', $reply);
        $this->assertStringContainsString('Sea Deck', $reply);

        $newSessionId = (int) $response->json('session_id');

        $this->assertNotSame($foreignSession->id, $newSessionId);
        $this->assertDatabaseHas('chat_sessions', [
            'id' => $newSessionId,
            'user_id' => $requester->id,
        ]);
        $this->assertDatabaseMissing('chat_messages', [
            'chat_session_id' => $foreignSession->id,
            'message' => 'Find me a hotel in Beirut',
        ]);
    }

    public function test_it_uses_the_configured_chatbot_service_base_url(): void
    {
        config()->set('services.chatbot.base_url', 'http://127.0.0.1:5999');

        $this->mockRecommendationService($this->recommendationPayload());

        Http::fake([
            $this->chatbotServiceUrl() => Http::response(['reply' => 'Configured chatbot service reply'], 200),
        ]);

        $response = $this->postJson(route('chatbot.send'), [
            'message' => 'Recommend a hotel in Beirut',
        ]);

        $response->assertOk();

        Http::assertSent(function (HttpRequest $request) {
            return $request->url() === 'http://127.0.0.1:5999/chat';
        });

        $this->assertStringContainsString('Configured chatbot service reply', $response->json('reply'));
    }

    private function mockRecommendationService(array $payload): void
    {
        $service = Mockery::mock(RecommendationService::class);
        $service->shouldReceive('buildResponseData')
            ->once()
            ->andReturn($payload);

        $this->app->instance(RecommendationService::class, $service);
    }

    private function createUser(string $email): User
    {
        return User::create([
            'name' => strtok($email, '@'),
            'email' => $email,
            'password' => bcrypt('password'),
        ]);
    }

    private function chatbotServiceUrl(string $path = '/chat'): string
    {
        return rtrim((string) config('services.chatbot.base_url', 'http://127.0.0.1:5000'), '/') . $path;
    }

    private function recommendationPayload(): array
    {
        return [
            'intent' => [
                'city' => 'beirut',
                'mentioned_cities' => ['beirut'],
                'wants_trip_plan' => false,
            ],
            'hotels' => [
                [
                    'id' => 82,
                    'hotel_name' => 'Harbor Stay',
                    'address' => 'Beirut Marina',
                    'price_per_night' => '120$',
                    'rating_score' => 4.6,
                    'match_reasons' => ['close to the sea', 'strong rating'],
                ],
            ],
            'restaurants' => [
                [
                    'id' => 17,
                    'restaurant_name' => 'Sea Deck',
                    'location' => 'Beirut Waterfront',
                    'food_type' => 'Seafood',
                    'price_tier' => 'Mid-range',
                    'rating' => 4.5,
                    'match_reasons' => ['matches food preference', 'fits occasion'],
                ],
            ],
            'activities' => [
                [
                    'name' => 'Corniche Walk',
                    'location' => 'Beirut Corniche',
                    'category' => 'walking',
                    'best_time' => 'sunset',
                    'match_reasons' => ['fits timing'],
                ],
            ],
            'trip_plan' => null,
            'diagnostics' => [
                'summary_chips' => ['City: Beirut', 'Looking for: Hotels, Restaurants, Activities'],
                'top_matches' => [
                    'hotel' => ['name' => 'Harbor Stay', 'reasons' => ['close to the sea', 'strong rating']],
                    'restaurant' => ['name' => 'Sea Deck', 'reasons' => ['matches food preference', 'fits occasion']],
                ],
            ],
        ];
    }

    private function tripPlanPayload(): array
    {
        return [
            'intent' => [
                'city' => 'batroun',
                'mentioned_cities' => ['batroun'],
                'wants_trip_plan' => true,
                'day_count' => 2,
            ],
            'hotels' => [
                [
                    'id' => 12,
                    'hotel_name' => 'Sunset Stay',
                    'address' => 'Batroun Seafront',
                    'price_per_night' => '110$',
                    'match_reasons' => ['close to the beach'],
                ],
            ],
            'restaurants' => [
                [
                    'id' => 24,
                    'restaurant_name' => 'Harbor Lunch',
                    'location' => 'Batroun Port',
                    'food_type' => 'Seafood',
                    'price_tier' => 'Mid-range',
                ],
                [
                    'id' => 25,
                    'restaurant_name' => 'Old Town Dinner',
                    'location' => 'Batroun Old Town',
                    'food_type' => 'Lebanese',
                    'price_tier' => 'Mid-range',
                ],
            ],
            'activities' => [
                [
                    'name' => 'Batroun Seafront Walk',
                    'location' => 'Batroun',
                    'category' => 'walking',
                    'best_time' => 'morning',
                ],
                [
                    'name' => 'Phoenician Wall Stop',
                    'location' => 'Batroun',
                    'category' => 'cultural',
                    'best_time' => 'afternoon',
                ],
                [
                    'name' => 'Sunset by the Port',
                    'location' => 'Batroun',
                    'category' => 'scenic',
                    'best_time' => 'sunset',
                ],
            ],
            'trip_plan' => [
                'title' => '2-Day Trip in Batroun',
                'summary' => 'A practical itinerary built from recommended stays, food, and activities.',
                'days' => [
                    [
                        'day' => 1,
                        'location' => 'Batroun',
                        'flow' => [
                            'morning' => [
                                'title' => 'Morning in Batroun',
                                'activities' => ['Batroun Seafront Walk'],
                            ],
                            'lunch' => [
                                'id' => 24,
                                'restaurant_name' => 'Harbor Lunch',
                                'location' => 'Batroun Port',
                                'food_type' => 'Seafood',
                                'price_tier' => 'Mid-range',
                            ],
                            'afternoon' => [
                                'title' => 'Afternoon in Batroun',
                                'activities' => ['Phoenician Wall Stop'],
                            ],
                            'evening' => [
                                'title' => 'Evening in Batroun',
                                'activities' => ['Sunset by the Port'],
                            ],
                            'dinner' => [
                                'id' => 25,
                                'restaurant_name' => 'Old Town Dinner',
                                'location' => 'Batroun Old Town',
                                'food_type' => 'Lebanese',
                                'price_tier' => 'Mid-range',
                            ],
                            'stay' => [
                                'id' => 12,
                                'hotel_name' => 'Sunset Stay',
                                'address' => 'Batroun Seafront',
                                'price_per_night' => '110$',
                            ],
                        ],
                    ],
                    [
                        'day' => 2,
                        'location' => 'Batroun',
                        'flow' => [
                            'morning' => [
                                'title' => 'Final morning',
                                'activities' => ['Batroun Seafront Walk'],
                            ],
                            'lunch' => [
                                'id' => 24,
                                'restaurant_name' => 'Harbor Lunch',
                                'location' => 'Batroun Port',
                                'food_type' => 'Seafood',
                                'price_tier' => 'Mid-range',
                            ],
                            'afternoon' => [
                                'title' => 'Wrap up and explore',
                                'activities' => ['Phoenician Wall Stop'],
                            ],
                        ],
                    ],
                ],
            ],
            'diagnostics' => [
                'summary_chips' => ['City: Batroun', 'Duration: 2 days', 'Looking for: Hotels, Restaurants, Activities'],
                'top_matches' => [
                    'hotel' => ['name' => 'Sunset Stay', 'reasons' => ['close to the beach']],
                    'restaurant' => ['name' => 'Harbor Lunch', 'reasons' => ['fits lunch stop']],
                    'trip_plan' => ['title' => '2-Day Trip in Batroun', 'days' => 2],
                ],
            ],
        ];
    }
}
