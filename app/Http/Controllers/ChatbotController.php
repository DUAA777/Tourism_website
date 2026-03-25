<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Services\RecommendationService;

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

        $recommendations = $this->recommendationService->buildResponseData($message);

        try {
            $pythonResponse = Http::timeout(60)->post('http://127.0.0.1:5000/chat', [
                'session_id' => $session->id,
                'message' => $message,
                'history' => $history,
                'intent' => $recommendations['intent'],
                'hotels' => $recommendations['hotels'],
                'restaurants' => $recommendations['restaurants'],
                'trip_plan' => $recommendations['trip_plan'],
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
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'reply' => 'Could not connect to the chatbot service.',
                'debug' => $e->getMessage(),
            ], 500);
        }
    }
}