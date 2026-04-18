<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RestaurantControllerAdmin;
use App\Http\Controllers\Admin\HotelControllerAdmin;

// Static Pages
Route::get('/', [HomeController::class, 'index'])->name('home');

// Admin Routes (with authentication middleware)
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // User Management
    Route::resource('users', UserController::class);
    
    // Restaurant Management
    Route::resource('restaurants', RestaurantControllerAdmin::class);
    Route::post('/restaurants/bulk-delete', [RestaurantControllerAdmin::class, 'bulkDelete'])->name('restaurants.bulk-delete');
    Route::get('/restaurants/export', [RestaurantControllerAdmin::class, 'export'])->name('restaurants.export');
    
    // Hotel Management
    Route::resource('hotels', HotelControllerAdmin::class);
    Route::post('/hotels/bulk-delete', [HotelControllerAdmin::class, 'bulkDelete'])->name('hotels.bulk-delete');
    Route::post('/hotels/bulk-restore', [HotelControllerAdmin::class, 'bulkRestore'])->name('hotels.bulk-restore');
    Route::post('hotels/{id}/restore', [HotelControllerAdmin::class, 'restore'])->name('hotels.restore');
    Route::delete('hotels/{id}/force-delete', [HotelControllerAdmin::class, 'forceDelete'])->name('hotels.force-delete');
});

// Chatbot routes (requires authentication)
Route::middleware(['auth'])->group(function () {
    Route::get('/chatbot', [ChatbotController::class, 'index'])->name('chatbot');
    Route::post('/chatbot/message', [ChatbotController::class, 'send'])->name('chatbot.send');
    Route::post('/chatbot/new-session', [ChatbotController::class, 'newSession'])->name('chatbot.newSession');
    Route::post('/reviews', [HomeController::class, 'storeReview'])->name('reviews.store');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/password/request', [ProfileController::class, 'requestPasswordReset'])->name('profile.password.request');
    Route::delete('/profile/photo', [ProfileController::class, 'removePhoto'])->name('profile.photo.delete');
});
Route::get('/aboutUs', function () { return view('aboutUs'); })->name('aboutUs');
Route::get('/contactUs', [ContactController::class, 'index'])->name('contactUs');
Route::post('/contactUs', [ContactController::class, 'send'])->name('contactUs.send');
Route::get('/destinations', function () { return view('destinations'); })->name('destinations');

Route::prefix('hotels')->name('hotels.')->group(function () {
    Route::get('/', [HotelController::class, 'index'])->name('index');
    Route::get('/{id}', [HotelController::class, 'show'])->whereNumber('id')->name('show');
});

Route::prefix('restaurants')->name('restaurants.')->group(function () {
    Route::get('/', [RestaurantController::class, 'index'])->name('index');
    Route::get('/{id}', [RestaurantController::class, 'show'])->whereNumber('id')->name('show');
});

// Search Destinations Route
Route::get('/search-destinations', [HomeController::class, 'searchDestinations'])->name('search.destinations');

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'store'])->name('password.email');
});

Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');

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
