<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Models\Hotel;
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
        
        return view('home', compact('places'));
    }
    
public function searchDestinations(Request $request)
{
    $searchTerm = $request->get('search', '');
    $filter = $request->get('filter', 'all');
    
    $places = collect();

    // 1. Search in Restaurants
    if ($filter === 'all' || $filter === 'restaurants') {
        $restaurants = Restaurant::query()
            ->when($searchTerm, function($q) use ($searchTerm) {
                $q->where('restaurant_name', 'like', "%{$searchTerm}%")
                  ->orWhere('location', 'like', "%{$searchTerm}%");
            })
            ->limit(8)
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->restaurant_name,
                    'image' => $item->image,
                    'location' => $item->location,
                    'rating' => $item->rating,
                    'category' => $item->restaurant_type,
                    'type' => 'restaurant' // Helper to distinguish in UI
                ];
            });
        $places = $places->merge($restaurants);
    }

    // 2. Search in Hotels
    if ($filter === 'all' || $filter === 'hotels') {
        $hotels = Hotel::query()
            ->when($searchTerm, function($q) use ($searchTerm) {
                $q->where('hotel_name', 'like', "%{$searchTerm}%")
                  ->orWhere('address', 'like', "%{$searchTerm}%");
            })
            ->limit(8)
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->hotel_name,
                    'image' => $item->hotel_image,
                    'location' => $item->address,
                    'rating' => $item->rating_score,
                    'category' => $item->room_type,
                    'type' => 'hotel'
                ];
            });
        $places = $places->merge($hotels);
    }

    // Standardize the final collection
    $finalResults = $places->take(8)->values();

    return response()->json($finalResults);
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