<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HotelControllerAdmin extends Controller
{
    public function index(Request $request)
    {
        $query = Hotel::query();
        
        // Include trashed if requested
        if ($request->filled('trashed') && $request->trashed == 'true') {
            $query->onlyTrashed();
        }
        
        // Search functionality
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('hotel_name', 'like', '%' . $request->search . '%')
                  ->orWhere('address', 'like', '%' . $request->search . '%')
                  ->orWhere('nearby_landmark', 'like', '%' . $request->search . '%');
            });
        }
        
        // Filters
        if ($request->filled('min_rating')) {
            $query->where('rating_score', '>=', $request->min_rating);
        }
        
        if ($request->filled('room_type')) {
            $query->where('room_type', 'like', '%' . $request->room_type . '%');
        }
        
        if ($request->filled('has_beach_access')) {
            $query->whereNotNull('distance_from_beach');
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
                $query->orderBy('rating_score', 'desc');
                break;
            case 'rating_low':
                $query->orderBy('rating_score', 'asc');
                break;
            case 'name_asc':
                $query->orderBy('hotel_name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('hotel_name', 'desc');
                break;
        }
        
        $hotels = $query->paginate(12)->withQueryString();
        
        // Get unique room types for filter
        $roomTypes = Hotel::distinct('room_type')->whereNotNull('room_type')->pluck('room_type');
        
        return view('admin.hotels.index', compact('hotels', 'roomTypes'));
    }

    public function create()
    {
        $roomTypes = ['Single Room', 'Double Room', 'Twin Room', 'Suite', 'Family Room', 'Deluxe Room', 'Presidential Suite'];
        $bedTypes = ['Single Bed', 'Double Bed', 'Queen Bed', 'King Bed', '2 Single Beds', '2 Double Beds'];
        
        return view('admin.hotels.create', compact('roomTypes', 'bedTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'hotel_name' => 'required|string|max:255',
            'hotel_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'hotel_url' => 'nullable|url',
            'address' => 'nullable|string|max:255',
            'distance_from_center' => 'nullable|string|max:255',
            'nearby_landmark' => 'nullable|string|max:255',
            'distance_from_beach' => 'nullable|string|max:255',
            'rating_score' => 'nullable|numeric|min:0|max:5',
            'review_text' => 'nullable|string',
            'room_type' => 'nullable|string|max:255',
            'bed_info' => 'nullable|string|max:255',
            'price_per_night' => 'nullable|string|max:255',
            'taxes_fees' => 'nullable|string|max:255',
            'review_count' => 'nullable|integer|min:0',
            'stay_details' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        // Handle image upload
        if ($request->hasFile('hotel_image')) {
            $image = $request->file('hotel_image');
            $filename = time() . '_' . Str::slug($request->hotel_name) . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('hotels', $filename, 'public');
            $validated['hotel_image'] = '/storage/' . $path;
        }

        Hotel::create($validated);

        return redirect()->route('admin.hotels.index')
            ->with('success', 'Hotel created successfully!');
    }

    public function show(Hotel $hotel)
    {
        return view('admin.hotels.show', compact('hotel'));
    }

    public function edit(Hotel $hotel)
    {
        $roomTypes = ['Single Room', 'Double Room', 'Twin Room', 'Suite', 'Family Room', 'Deluxe Room', 'Presidential Suite'];
        $bedTypes = ['Single Bed', 'Double Bed', 'Queen Bed', 'King Bed', '2 Single Beds', '2 Double Beds'];
        
        return view('admin.hotels.edit', compact('hotel', 'roomTypes', 'bedTypes'));
    }

    public function update(Request $request, Hotel $hotel)
    {
        $validated = $request->validate([
            'hotel_name' => 'required|string|max:255',
            'hotel_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'hotel_url' => 'nullable|url',
            'address' => 'nullable|string|max:255',
            'distance_from_center' => 'nullable|string|max:255',
            'nearby_landmark' => 'nullable|string|max:255',
            'distance_from_beach' => 'nullable|string|max:255',
            'rating_score' => 'nullable|numeric|min:0|max:5',
            'review_text' => 'nullable|string',
            'room_type' => 'nullable|string|max:255',
            'bed_info' => 'nullable|string|max:255',
            'price_per_night' => 'nullable|string|max:255',
            'taxes_fees' => 'nullable|string|max:255',
            'review_count' => 'nullable|integer|min:0',
            'stay_details' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        // Handle image upload
        if ($request->hasFile('hotel_image')) {
            // Delete old image
            if ($hotel->hotel_image && Storage::disk('public')->exists(str_replace('/storage/', '', $hotel->hotel_image))) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $hotel->hotel_image));
            }
            
            $image = $request->file('hotel_image');
            $filename = time() . '_' . Str::slug($request->hotel_name) . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('hotels', $filename, 'public');
            $validated['hotel_image'] = '/storage/' . $path;
        }

        $hotel->update($validated);

        return redirect()->route('admin.hotels.index')
            ->with('success', 'Hotel updated successfully!');
    }

    public function destroy(Hotel $hotel)
    {
        $hotel->delete();
        
        return redirect()->route('admin.hotels.index')
            ->with('success', 'Hotel moved to trash successfully!');
    }

    public function restore($id)
    {
        $hotel = Hotel::withTrashed()->findOrFail($id);
        $hotel->restore();
        
        return redirect()->route('admin.hotels.index')
            ->with('success', 'Hotel restored successfully!');
    }

    public function forceDelete($id)
    {
        $hotel = Hotel::withTrashed()->findOrFail($id);
        
        // Delete image
        if ($hotel->hotel_image && Storage::disk('public')->exists(str_replace('/storage/', '', $hotel->hotel_image))) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $hotel->hotel_image));
        }
        
        $hotel->forceDelete();
        
        return redirect()->route('admin.hotels.index')
            ->with('success', 'Hotel permanently deleted!');
    }
    
    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        
        if (empty($ids)) {
            return response()->json(['error' => 'No items selected'], 400);
        }
        
        Hotel::whereIn('id', $ids)->delete();
        
        return response()->json(['success' => 'Hotels moved to trash successfully']);
    }
    
    public function bulkRestore(Request $request)
    {
        $ids = $request->input('ids', []);
        
        if (empty($ids)) {
            return response()->json(['error' => 'No items selected'], 400);
        }
        
        Hotel::withTrashed()->whereIn('id', $ids)->restore();
        
        return response()->json(['success' => 'Hotels restored successfully']);
    }
}