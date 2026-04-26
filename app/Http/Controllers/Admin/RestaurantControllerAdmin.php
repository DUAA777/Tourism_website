<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RestaurantControllerAdmin extends Controller
{
    private function priceTierOptions(): array
    {
        $preferredOrder = ['Budget', 'Mid-range', 'Premium', 'Luxury'];

        $databaseTiers = Restaurant::query()
            ->whereNotNull('price_tier')
            ->where('price_tier', '!=', '')
            ->pluck('price_tier')
            ->map(fn ($tier) => trim((string) $tier))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $tiers = collect($preferredOrder)
            ->merge($databaseTiers)
            ->unique()
            ->values();

        return $tiers->mapWithKeys(fn ($tier) => [$tier => $tier])->all();
    }

    public function index(Request $request)
    {
        $query = Restaurant::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('restaurant_name', 'like', '%' . $request->search . '%')
                  ->orWhere('location', 'like', '%' . $request->search . '%')
                  ->orWhere('food_type', 'like', '%' . $request->search . '%');
            });
        }
        
        // Filters
        if ($request->filled('food_type')) {
            $query->where('food_type', $request->food_type);
        }
        
        if ($request->filled('price_tier')) {
            $query->where('price_tier', $request->price_tier);
        }
        
        if ($request->filled('min_rating')) {
            $query->where('rating', '>=', $request->min_rating);
        }
        
        if ($request->filled('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }
        
        // Sorting
        $sort = $request->get('sort', 'latest');
        switch($sort) {
            case 'latest':
                $query->latest();
                break;
            case 'oldest':
                $query->oldest();
                break;
            case 'rating_high':
                $query->orderBy('rating', 'desc');
                break;
            case 'rating_low':
                $query->orderBy('rating', 'asc');
                break;
            case 'name_asc':
                $query->orderBy('restaurant_name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('restaurant_name', 'desc');
                break;
        }
        
        $restaurants = $query->paginate(12)->withQueryString();
        
        // Get filter options
        $foodTypes = Restaurant::distinct('food_type')->whereNotNull('food_type')->pluck('food_type');
        $priceTiers = array_keys($this->priceTierOptions());
        $locations = Restaurant::distinct('location')->pluck('location');
        
        return view('admin.restaurants.index', compact('restaurants', 'foodTypes', 'priceTiers', 'locations'));
    }

    public function create()
    {
        $priceTiers = $this->priceTierOptions();
        $foodTypes = ['Italian', 'Chinese', 'Japanese', 'Mexican', 'Indian', 'Thai', 'French', 'American', 'Mediterranean', 'Seafood', 'Steakhouse', 'Vegetarian', 'Vegan', 'Fast Food', 'Cafe'];
        $restaurantTypes = ['Fine Dining', 'Casual Dining', 'Fast Food', 'Cafe', 'Buffet', 'Food Truck', 'Pub', 'Bar'];
        
        return view('admin.restaurants.create', compact('priceTiers', 'foodTypes', 'restaurantTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'restaurant_name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'rating' => 'nullable|numeric|min:0|max:5',
            'restaurant_type' => 'nullable|string|max:255',
            'tags' => 'nullable|string',
            'location' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price_tier' => 'nullable|string|max:255',
            'food_type' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:255',
            'opening_hours' => 'nullable|string',
            'website' => 'nullable|url',
            'directory_url' => 'nullable|url',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '_' . Str::slug($request->restaurant_name) . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('restaurants', $filename, 'public');
            $validated['image'] = '/storage/' . $path;
        }

        // Process tags
        if ($request->has('tags')) {
            $validated['tags'] = is_array($request->tags) ? implode(',', $request->tags) : $request->tags;
        }

        Restaurant::create($validated);

        return redirect()->route('admin.restaurants.index')
            ->with('success', 'Restaurant created successfully!');
    }

    public function show(Restaurant $restaurant)
    {
        return view('admin.restaurants.show', compact('restaurant'));
    }

    public function edit(Restaurant $restaurant)
    {
        $priceTiers = $this->priceTierOptions();
        $foodTypes = ['Italian', 'Chinese', 'Japanese', 'Mexican', 'Indian', 'Thai', 'French', 'American', 'Mediterranean', 'Seafood', 'Steakhouse', 'Vegetarian', 'Vegan', 'Fast Food', 'Cafe'];
        $restaurantTypes = ['Fine Dining', 'Casual Dining', 'Fast Food', 'Cafe', 'Buffet', 'Food Truck', 'Pub', 'Bar'];
        $tagsArray = $restaurant->tags_array;
        
        return view('admin.restaurants.edit', compact('restaurant', 'priceTiers', 'foodTypes', 'restaurantTypes', 'tagsArray'));
    }

    public function update(Request $request, Restaurant $restaurant)
    {
        $validated = $request->validate([
            'restaurant_name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'rating' => 'nullable|numeric|min:0|max:5',
            'restaurant_type' => 'nullable|string|max:255',
            'tags' => 'nullable|string',
            'location' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price_tier' => 'nullable|string|max:255',
            'food_type' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:255',
            'opening_hours' => 'nullable|string',
            'website' => 'nullable|url',
            'directory_url' => 'nullable|url',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($restaurant->image && Storage::disk('public')->exists(str_replace('/storage/', '', $restaurant->image))) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $restaurant->image));
            }
            
            $image = $request->file('image');
            $filename = time() . '_' . Str::slug($request->restaurant_name) . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('restaurants', $filename, 'public');
            $validated['image'] = '/storage/' . $path;
        }

        // Process tags
        if ($request->has('tags')) {
            $validated['tags'] = is_array($request->tags) ? implode(',', $request->tags) : $request->tags;
        }

        $restaurant->update($validated);

        return redirect()->route('admin.restaurants.index')
            ->with('success', 'Restaurant updated successfully!');
    }

    public function destroy(Restaurant $restaurant)
    {
        // Delete image
        if ($restaurant->image && Storage::disk('public')->exists(str_replace('/storage/', '', $restaurant->image))) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $restaurant->image));
        }
        
        $restaurant->delete();
        
        return redirect()->route('admin.restaurants.index')
            ->with('success', 'Restaurant deleted successfully!');
    }
    
    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        
        if (empty($ids)) {
            return response()->json(['error' => 'No items selected'], 400);
        }
        
        $restaurants = Restaurant::whereIn('id', $ids)->get();
        
        foreach ($restaurants as $restaurant) {
            if ($restaurant->image && Storage::disk('public')->exists(str_replace('/storage/', '', $restaurant->image))) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $restaurant->image));
            }
        }
        
        Restaurant::whereIn('id', $ids)->delete();
        
        return response()->json(['success' => 'Restaurants deleted successfully']);
    }
    
    public function export(Request $request)
    {
        $restaurants = Restaurant::all();
        
        $filename = 'restaurants_export_' . date('Y-m-d_His') . '.csv';
        $handle = fopen('php://output', 'w');
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // Add headers
        fputcsv($handle, ['ID', 'Name', 'Location', 'Food Type', 'Price Tier', 'Rating', 'Phone', 'Website']);
        
        // Add data
        foreach ($restaurants as $restaurant) {
            fputcsv($handle, [
                $restaurant->id,
                $restaurant->restaurant_name,
                $restaurant->location,
                $restaurant->food_type,
                $restaurant->price_tier,
                $restaurant->rating,
                $restaurant->phone_number,
                $restaurant->website
            ]);
        }
        
        fclose($handle);
        exit;
    }
}
