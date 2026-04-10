<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
public function index(Request $request)
{
    $query = Restaurant::query();
    
    // Handle cuisine type - search within comma-separated values
    if ($request->filled('food_type')) {
        $query->where('food_type', 'LIKE', '%' . $request->food_type . '%');
    }
    
    if ($request->filled('location')) {
        $query->where('location', $request->location);
    }
    
    if ($request->filled('price_tier')) {
        $query->where('price_tier', $request->price_tier);
    }
    
    if ($request->filled('min_rating')) {
        $query->where('rating', '>=', (float)$request->min_rating);
    }
    
    if ($request->filled('restaurant_type')) {
        $query->where('restaurant_type', $request->restaurant_type);
    }
    
    if ($request->filled('tags')) {
        $query->where('tags', 'LIKE', '%' . $request->tags . '%');
    }
    
    if ($request->filled('keyword')) {
        $query->where(function($q) use ($request) {
            $q->where('restaurant_name', 'LIKE', '%' . $request->keyword . '%')
              ->orWhere('tags', 'LIKE', '%' . $request->keyword . '%')
              ->orWhere('description', 'LIKE', '%' . $request->keyword . '%')
              ->orWhere('food_type', 'LIKE', '%' . $request->keyword . '%');
        });
    }
    
    // Sorting
    switch($request->get('sort', 'smart')) {
        case 'rating':
            $query->orderBy('rating', 'desc');
            break;
        case 'popular':
            $query->orderBy('rating', 'desc'); // Or use a popularity column if available
            break;
        default: // smart or default
            $query->orderBy('rating', 'desc');
    }
    
    $restaurants = $query->paginate(12);
    
    // Get filter options from database - split cuisines by commas and get unique values
    $allCuisines = Restaurant::whereNotNull('food_type')
        ->where('food_type', '!=', '')
        ->pluck('food_type')
        ->toArray();
    
    // Split all cuisine strings by comma and get unique values
    $uniqueCuisines = [];
    foreach($allCuisines as $cuisineString) {
        $parts = array_map('trim', explode(',', $cuisineString));
        foreach($parts as $part) {
            if(!empty($part) && !in_array($part, $uniqueCuisines)) {
                $uniqueCuisines[] = $part;
            }
        }
    }
    sort($uniqueCuisines);
    
    // Get unique locations
    $locations = Restaurant::whereNotNull('location')
        ->distinct()
        ->pluck('location')
        ->filter()
        ->toArray();
    sort($locations);
    
    // Get unique restaurant types
    $restaurantTypes = Restaurant::whereNotNull('restaurant_type')
        ->distinct()
        ->pluck('restaurant_type')
        ->filter()
        ->toArray();
    sort($restaurantTypes);
    
    // Atmosphere options (can be extracted from tags or predefined)
    $atmospheres = ['romantic', 'beach', 'family-friendly', 'live-music', 'upscale', 'casual', 'business'];
    
    return view('restaurants', compact(
        'restaurants', 
        'uniqueCuisines', 
        'locations', 
        'restaurantTypes', 
        'atmospheres'
    ));
}
    public function show($id)
    {
        $restaurant = Restaurant::findOrFail($id);
        $similarRestaurants = $restaurant->getSimilarRestaurants(3);
        
        return view('restaurants.show', compact('restaurant', 'similarRestaurants'));
    }
}