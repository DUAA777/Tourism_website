<?php 

namespace App\Http\Controllers;
use App\Models\Hotel;
use Illuminate\Http\Request;
class HotelController extends Controller
{
    public function index()
    {
        $hotels = Hotel::all();
        return view('hotels', compact('hotels'));
    }

    public function show($id)
    {
        $hotel = Hotel::findOrFail($id);
        return view('hotels.show', compact('hotel'));
    }
}