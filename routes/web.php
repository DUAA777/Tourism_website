<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\HomeController;

// Static Pages
Route::get('/', [HomeController::class, 'index'])->name('home'); // Updated to use HomeController
Route::get('/aboutUs', function () { return view('aboutUs'); })->name('aboutUs');
Route::get('/chatbot', function () { return view('chatbot'); })->name('chatbot');
Route::get('/contactUs', function () { return view('contactUs'); })->name('contactUs');
Route::get('/destinations', function () { return view('destinations'); })->name('destinations');
Route::get('/hotels', function () { return view('hotels'); })->name('hotels');
Route::get('/restaurants', function () { return view('restaurants'); })->name('restaurants');

// Restaurant and Hotel Routes
Route::get('/restaurants/{id}', [RestaurantController::class, 'show'])->name('restaurants.show');
Route::get('/hotels/{id}', [HotelController::class, 'show'])->name('hotels.show');

// Search Destinations Route
Route::get('/search-destinations', [HomeController::class, 'searchDestinations'])->name('search.destinations');

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () { return view('dashboard'); });
});

Route::get('auth/google', [AuthController::class, 'redirectToGoogle']);
Route::get('auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
Route::get('/profile', function () { return view('profile'); })->name('profile');

/*
|--------------------------------------------------------------------------
| PLACES DATA (Static)
|--------------------------------------------------------------------------
*/

$places = [
    // Your existing places data here...
];

/*
|--------------------------------------------------------------------------
| PLACES LIST PAGE
|--------------------------------------------------------------------------
*/

Route::get('/places', function (Request $request) use ($places) {
    $type = $request->query('type');
    $search = trim((string) $request->query('search', ''));

    $filtered = collect($places)->filter(function ($place) use ($type, $search) {
        $matchesType = !$type || $place['type'] === $type || $place['category'] === $type;
        $matchesSearch = !$search
            || str_contains(strtolower($place['name']), strtolower($search))
            || str_contains(strtolower($place['location']), strtolower($search))
            || str_contains(strtolower($place['category']), strtolower($search));

        return $matchesType && $matchesSearch;
    })->values();

    return view('places', [
        'places' => $filtered,
        'activeType' => $type,
        'search' => $search
    ]);
})->name('places.index');

/*
|--------------------------------------------------------------------------
| SINGLE PLACE PAGE
|--------------------------------------------------------------------------
*/

Route::get('/places/{slug}', function ($slug) use ($places) {
    $place = collect($places)->firstWhere('slug', $slug);
    abort_if(!$place, 404);
    return view('place-details', ['place' => $place]);
})->name('places.show');