<?php

namespace Tests\Feature;

use App\Models\ChatSession;
use App\Models\User;
use App\Services\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class ChatbotControllerTest extends TestCase
{
    use RefreshDatabase;

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
            'http://127.0.0.1:5000/chat' => Http::response(['error' => 'service unavailable'], 500),
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
            'http://127.0.0.1:5000/chat' => Http::response(['reply' => '   '], 200),
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
            'http://127.0.0.1:5000/chat' => Http::response([
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
                'url' => route('hotels.show', ['id' => 82]),
            ])
            ->assertJsonFragment([
                'type' => 'restaurant',
                'name' => 'Sea Deck',
                'url' => route('restaurants.show', ['id' => 17]),
            ]);
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
            'http://127.0.0.1:5000/chat' => Http::response(['reply' => 'Safe reply'], 200),
        ]);

        $response = $this->actingAs($requester)->postJson(route('chatbot.send'), [
            'message' => 'Find me a hotel in Beirut',
            'session_id' => $foreignSession->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('reply', 'Safe reply');

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
            'password' => 'password',
        ]);
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
                                'restaurant_name' => 'Old Town Dinner',
                                'location' => 'Batroun Old Town',
                                'food_type' => 'Lebanese',
                                'price_tier' => 'Mid-range',
                            ],
                            'stay' => [
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
        ];
    }
}
