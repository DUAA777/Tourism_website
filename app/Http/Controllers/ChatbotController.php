<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Hotel;
use App\Models\Restaurant;
use App\Models\ChatSession;
use App\Models\ChatMessage;

class ChatbotController extends Controller
{
    public function index(Request $request)
    {
        return view('chatbot');
    }

    public function newSession(Request $request)
    {
        $session = ChatSession::create([
            'user_id' => auth()->id(),
            'title' => 'New Chat',
        ]);

        return response()->json([
            'session_id' => $session->id
        ]);
    }

    public function send(Request $request)
    {
        $message = trim($request->input('message', ''));
        $sessionId = $request->input('session_id');

        if (!$message) {
            return response()->json([
                'reply' => 'Please type a message first.'
            ], 422);
        }

        if (!$sessionId) {
            $session = ChatSession::create([
                'user_id' => auth()->id(),
                'title' => substr($message, 0, 50),
            ]);
        } else {
            $session = ChatSession::find($sessionId);

            if (!$session) {
                $session = ChatSession::create([
                    'user_id' => auth()->id(),
                    'title' => substr($message, 0, 50),
                ]);
            }
        }

        ChatMessage::create([
            'chat_session_id' => $session->id,
            'role' => 'user',
            'message' => $message,
        ]);

        $lowerMessage = strtolower($message);

        $hotelsQuery = Hotel::query();
        $restaurantsQuery = Restaurant::query();

        if (str_contains($lowerMessage, 'beirut')) {
            $hotelsQuery->where('address', 'like', '%beirut%');
            $restaurantsQuery->where('location', 'like', '%beirut%');
        }

        if (str_contains($lowerMessage, 'beach')) {
            $hotelsQuery->whereNotNull('distance_from_beach');
        }

        if (str_contains($lowerMessage, 'seafood')) {
            $restaurantsQuery->where('food_type', 'like', '%seafood%');
        }

        if (str_contains($lowerMessage, 'lebanese')) {
            $restaurantsQuery->where('food_type', 'like', '%lebanese%');
        }

        $hotels = $hotelsQuery
            ->orderByDesc('rating_score')
            ->limit(8)
            ->get([
                'hotel_name',
                'address',
                'distance_from_center',
                'nearby_landmark',
                'distance_from_beach',
                'rating_score',
                'price_per_night',
                'review_count',
                'description',
                'stay_details'
            ])
            ->map(function ($hotel) {
                return [
                    'hotel_name' => $hotel->hotel_name,
                    'address' => $hotel->address,
                    'distance_from_center' => $hotel->distance_from_center,
                    'nearby_landmark' => $hotel->nearby_landmark,
                    'distance_from_beach' => $hotel->distance_from_beach,
                    'rating_score' => $hotel->rating_score,
                    'price_per_night' => $hotel->price_per_night,
                    'review_count' => $hotel->review_count,
                    'description' => $hotel->description,
                    'stay_details' => $hotel->stay_details,
                ];
            })
            ->values()
            ->toArray();

        $restaurants = $restaurantsQuery
            ->orderByDesc('rating')
            ->limit(8)
            ->get([
                'restaurant_name',
                'location',
                'rating',
                'restaurant_type',
                'tags',
                'description',
                'price_tier',
                'food_type',
                'opening_hours'
            ])
            ->map(function ($restaurant) {
                return [
                    'restaurant_name' => $restaurant->restaurant_name,
                    'location' => $restaurant->location,
                    'rating' => $restaurant->rating,
                    'restaurant_type' => $restaurant->restaurant_type,
                    'tags' => $restaurant->tags,
                    'description' => $restaurant->description,
                    'price_tier' => $restaurant->price_tier,
                    'food_type' => $restaurant->food_type,
                    'opening_hours' => $restaurant->opening_hours,
                ];
            })
            ->values()
            ->toArray();

        $history = ChatMessage::where('chat_session_id', $session->id)
            ->orderBy('created_at', 'asc')
            ->get(['role', 'message'])
            ->map(function ($msg) {
                return [
                    'role' => $msg->role,
                    'message' => $msg->message,
                ];
            })
            ->toArray();

        try {
            $pythonResponse = Http::timeout(60)->post('http://127.0.0.1:5000/chat', [
                'session_id' => $session->id,
                'message' => $message,
                'history' => $history,
                'hotels' => $hotels,
                'restaurants' => $restaurants,
            ]);

            if (!$pythonResponse->successful()) {
                return response()->json([
                    'reply' => 'The chatbot service is currently unavailable.',
                    'debug' => [
                        'status' => $pythonResponse->status(),
                        'body' => $pythonResponse->body(),
                    ]
                ], 500);
            }

            $json = $pythonResponse->json();
            $reply = $json['reply'] ?? 'No reply returned.';

            ChatMessage::create([
                'chat_session_id' => $session->id,
                'role' => 'assistant',
                'message' => $reply,
            ]);

            return response()->json([
                'reply' => $reply,
                'session_id' => $session->id,
                'debug' => $json['debug'] ?? null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'reply' => 'Could not connect to the chatbot service.',
                'debug' => $e->getMessage(),
            ], 500);
        }
    }
}