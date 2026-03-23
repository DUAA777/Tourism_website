<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    public function index()
    {
        $restaurants = Restaurant::all();
        return view('restaurants', compact('restaurants'));
    }

    public function show($id)
    {
        $restaurant = Restaurant::findOrFail($id);
        $similarRestaurants = $restaurant->getSimilarRestaurants(3);
        
        return view('restaurants.show', compact('restaurant', 'similarRestaurants'));
    }
}