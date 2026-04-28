<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use Illuminate\Http\Request;

class HotelController extends Controller
{
    public function index(Request $request)
    {
        $query = Hotel::query();
        $priceExpression = Hotel::numericPriceExpression();

        // Room Type - search within comma-separated values
        if ($request->filled('room_type')) {
            $query->where('room_type', 'LIKE', '%' . $request->room_type . '%');
        }

        // Bed Info - search within comma-separated values
        if ($request->filled('bed_info')) {
            $query->where('bed_info', 'LIKE', '%' . $request->bed_info . '%');
        }

        // Price Tier filter
        if ($request->filled('price_tier')) {
            switch ($request->price_tier) {
                case 'budget':
                    $query->whereRaw($priceExpression . ' < ?', [80]);
                    break;

                case 'mid':
                    $query->whereRaw($priceExpression . ' BETWEEN ? AND ?', [80, 150]);
                    break;

                case 'luxury':
                    $query->whereRaw($priceExpression . ' > ?', [150]);
                    break;
            }
        }

        // Max Price filter
        if ($request->filled('max_price')) {
            $query->whereRaw($priceExpression . ' <= ?', [(float) $request->max_price]);
        }

        // Minimum Rating
        if ($request->filled('min_rating')) {
            $query->where('rating_score', '>=', (float) $request->min_rating);
        }

        // Location
        if ($request->filled('address')) {
            $query->where('address', 'LIKE', '%' . $request->address . '%');
        }

        // Nearby Landmark
        if ($request->filled('nearby_landmark')) {
            $query->where('nearby_landmark', 'LIKE', '%' . $request->nearby_landmark . '%');
        }

        // Distance from Beach
        if ($request->filled('distance_from_beach')) {
            $query->where('distance_from_beach', 'LIKE', '%' . $request->distance_from_beach . '%');
        }

        // Distance from Center
        if ($request->filled('distance_from_center')) {
            $query->where('distance_from_center', 'LIKE', '%' . $request->distance_from_center . '%');
        }

        // Keyword search
        if ($request->filled('keyword')) {
            $query->where(function ($q) use ($request) {
                $q->where('hotel_name', 'LIKE', '%' . $request->keyword . '%')
                    ->orWhere('description', 'LIKE', '%' . $request->keyword . '%')
                    ->orWhere('room_type', 'LIKE', '%' . $request->keyword . '%')
                    ->orWhere('bed_info', 'LIKE', '%' . $request->keyword . '%')
                    ->orWhere('nearby_landmark', 'LIKE', '%' . $request->keyword . '%');
            });
        }

        // Sorting
        switch ($request->get('sort', 'rating')) {
            case 'price_low':
                $query->orderByRaw($priceExpression . ' ASC');
                break;

            case 'price_high':
                $query->orderByRaw($priceExpression . ' DESC');
                break;

            case 'popular':
                $query->orderBy('review_count', 'desc');
                break;

            default:
                $query->orderBy('rating_score', 'desc');
                break;
        }

        $hotels = $query->paginate(12);

        // Get filter options from database - split by commas
        $allRoomTypes = Hotel::whereNotNull('room_type')
            ->where('room_type', '!=', '')
            ->pluck('room_type')
            ->toArray();

        $uniqueRoomTypes = [];

        foreach ($allRoomTypes as $roomTypeString) {
            $parts = array_map('trim', explode(',', $roomTypeString));

            foreach ($parts as $part) {
                if (!empty($part) && !in_array($part, $uniqueRoomTypes)) {
                    $uniqueRoomTypes[] = $part;
                }
            }
        }

        sort($uniqueRoomTypes);

        $allBedTypes = Hotel::whereNotNull('bed_info')
            ->where('bed_info', '!=', '')
            ->pluck('bed_info')
            ->toArray();

        $uniqueBedTypes = [];

        foreach ($allBedTypes as $bedTypeString) {
            $parts = array_map('trim', explode(',', $bedTypeString));

            foreach ($parts as $part) {
                if (!empty($part) && !in_array($part, $uniqueBedTypes)) {
                    $uniqueBedTypes[] = $part;
                }
            }
        }

        sort($uniqueBedTypes);

        $locations = Hotel::whereNotNull('address')
            ->distinct()
            ->pluck('address')
            ->filter()
            ->toArray();

        sort($locations);

        $landmarks = Hotel::whereNotNull('nearby_landmark')
            ->distinct()
            ->pluck('nearby_landmark')
            ->filter()
            ->toArray();

        sort($landmarks);

        $beachDistances = Hotel::whereNotNull('distance_from_beach')
            ->distinct()
            ->pluck('distance_from_beach')
            ->filter()
            ->toArray();

        sort($beachDistances);

        $centerDistances = Hotel::whereNotNull('distance_from_center')
            ->distinct()
            ->pluck('distance_from_center')
            ->filter()
            ->toArray();

        sort($centerDistances);

        return view('hotels', compact(
            'hotels',
            'uniqueRoomTypes',
            'uniqueBedTypes',
            'locations',
            'landmarks',
            'beachDistances',
            'centerDistances'
        ));
    }

    public function show($id)
    {
        $hotel = Hotel::findOrFail($id);

        return view('hotels.show', compact('hotel'));
    }
}