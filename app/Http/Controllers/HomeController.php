<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Models\Hotel;
use App\Models\Review;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        // Get random 4 restaurants and 4 hotels for the 8 total items
        $restaurants = Restaurant::inRandomOrder()->take(4)->get();
        $hotels = Hotel::inRandomOrder()->take(4)->get();
        
        // Merge collections
        $places = $restaurants->merge($hotels);
        $reviews = Review::with('user')->inRandomOrder()->take(6)->get();
        $userReview = auth()->check() ? auth()->user()->review : null;
        
        return view('home', compact('places', 'reviews', 'userReview'));
    }

    public function storeReview(Request $request)
    {
        $user = $request->user();

        if ($user->review) {
            return redirect()
                ->route('home')
                ->with('review_error', 'You already submitted a review.');
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review_text' => 'required|string|min:10|max:1000',
        ]);

        $user->review()->create($validated);

        return redirect()
            ->route('home')
            ->with('review_success', 'Thanks! Your review has been added.');
    }
    
    public function searchDestinations(Request $request)
    {
        $searchTerm = trim((string) $request->get('search', ''));
        $filter = strtolower(trim((string) $request->get('filter', 'all')));

        $restaurantLimit = $filter === 'all' ? 4 : 8;
        $hotelLimit = $filter === 'all' ? 4 : 8;

        $places = collect();

        if ($filter === 'all' || $filter === 'restaurants') {
            $restaurants = Restaurant::query()
                ->when($searchTerm !== '', function ($q) use ($searchTerm) {
                    $q->where(function ($query) use ($searchTerm) {
                        $query->where('restaurant_name', 'like', "%{$searchTerm}%")
                            ->orWhere('location', 'like', "%{$searchTerm}%")
                            ->orWhere('food_type', 'like', "%{$searchTerm}%")
                            ->orWhere('restaurant_type', 'like', "%{$searchTerm}%");
                    });
                })
                ->inRandomOrder()
                ->limit($restaurantLimit)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'title' => $item->restaurant_name,
                        'image' => $item->image,
                        'location' => $item->location,
                        'rating' => $item->rating,
                        'category' => $item->restaurant_type,
                        'type' => 'restaurant',
                    ];
                });

            $places = $places->merge($restaurants);
        }

        if ($filter === 'all' || $filter === 'hotels') {
            $hotels = Hotel::query()
                ->when($searchTerm !== '', function ($q) use ($searchTerm) {
                    $q->where(function ($query) use ($searchTerm) {
                        $query->where('hotel_name', 'like', "%{$searchTerm}%")
                            ->orWhere('address', 'like', "%{$searchTerm}%")
                            ->orWhere('room_type', 'like', "%{$searchTerm}%");
                    });
                })
                ->inRandomOrder()
                ->limit($hotelLimit)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'title' => $item->hotel_name,
                        'image' => $item->hotel_image,
                        'location' => $item->address,
                        'rating' => $item->rating_score,
                        'category' => $item->room_type,
                        'type' => 'hotel',
                    ];
                });

            $places = $places->merge($hotels);
        }

        if ($filter === 'all') {
            $places = $places->values();

            $interleaved = collect();
            $restaurants = $places->where('type', 'restaurant')->values();
            $hotels = $places->where('type', 'hotel')->values();
            $maxRows = max($restaurants->count(), $hotels->count());

            for ($i = 0; $i < $maxRows; $i++) {
                if ($restaurants->has($i)) {
                    $interleaved->push($restaurants->get($i));
                }
                if ($hotels->has($i)) {
                    $interleaved->push($hotels->get($i));
                }
            }

            return response()
                ->json($interleaved->take(8)->values())
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        return response()
            ->json($places->take(8)->values())
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
    
    public function showRestaurant($id)
    {
        $restaurant = Restaurant::findOrFail($id);
        return view('restaurants.show', compact('restaurant'));
    }
    
    public function showHotel($id)
    {
        $hotel = Hotel::findOrFail($id);
        return view('hotels.show', compact('hotel'));
    }
}
