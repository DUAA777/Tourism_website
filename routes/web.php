<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RestaurantControllerAdmin;
use App\Http\Controllers\Admin\HotelControllerAdmin;
// Admin Routes
Route::prefix('admin')->name('admin.')->group(function () {

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        
        // User Management
        Route::resource('users', UserController::class);
        
        // Restaurant Management
        Route::resource('restaurants', RestaurantControllerAdmin::class);
        // Add to routes/web.php inside admin group
Route::post('/restaurants/bulk-delete', [RestaurantControllerAdmin::class, 'bulkDelete'])->name('restaurants.bulk-delete');
Route::get('/restaurants/export', [RestaurantControllerAdmin::class, 'export'])->name('restaurants.export');

Route::post('/hotels/bulk-delete', [HotelControllerAdmin::class, 'bulkDelete'])->name('hotels.bulk-delete');
Route::post('/hotels/bulk-restore', [HotelControllerAdmin::class, 'bulkRestore'])->name('hotels.bulk-restore');
        // Hotel Management
        Route::resource('hotels', HotelControllerAdmin::class);
        Route::post('hotels/{id}/restore', [HotelControllerAdmin::class, 'restore'])->name('hotels.restore');
        Route::delete('hotels/{id}/force-delete', [HotelControllerAdmin::class, 'forceDelete'])->name('hotels.force-delete');

});
// Static Pages
Route::get('/', [HomeController::class, 'index'])->name('home'); // Updated to use HomeController
use App\Http\Controllers\ChatbotController;

Route::get('/chatbot', [ChatbotController::class, 'index'])->name('chatbot');
Route::post('/chatbot/message', [ChatbotController::class, 'send'])->name('chatbot.send');
Route::post('/chatbot/new-session', [ChatbotController::class, 'newSession'])->name('chatbot.newSession');
Route::get('/aboutUs', function () { return view('aboutUs'); })->name('aboutUs');
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