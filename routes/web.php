<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController; // Don't forget to import the controller!

// Static Pages
Route::get('/', function () { return view('home'); })->name('home');
Route::get('/aboutUs', function () { return view('aboutUs'); })->name('aboutUs');
Route::get('/chatbot', function () { return view('chatbot'); })->name('chatbot');
Route::get('/contactUs', function () { return view('contactUs'); })->name('contactUs');
Route::get('/destinations', function () { return view('destinations'); })->name('destinations');
Route::get('/hotels', function () { return view('hotels'); })->name('hotels');
Route::get('/restaurants', function () { return view('restaurants'); })->name('restaurants');
// Authentication Routes
// 1. Show the forms
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');

// 2. Handle the form submissions (POST)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// 3. Logout
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

<<<<<<< HEAD
Route::middleware(['auth'])->group(function () {
    // Routes that only logged-in users can see
    Route::get('/dashboard', function () { return view('dashboard'); });
});
Route::get('auth/google', [AuthController::class, 'redirectToGoogle']);
Route::get('auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
=======
Route::get('/contactUs', function () {
    return view('contactUs');
})->name('contactUs');

Route::get('/profile', function () {
    return view('profile');
})->name('profile');


/*
|--------------------------------------------------------------------------
| SAMPLE PLACES DATA
|--------------------------------------------------------------------------
*/

$places = [

    [
        'slug' => 'roman-temple-baalbek',
        'name' => 'Baalbek',
        'type' => 'attraction',
        'category' => 'historic',
        'location' => 'Baalbek, Lebanon',
        'rating' => '4.9',
        'price' => 'Entry Ticket',
        'cover' => 'images/destination-1.jpg',
        'gallery' => [
            'images/destination-1.jpg',
            'images/showcase-bg.jpg',
            'images/destination-3.jpg'
        ],
        'short_description' => 'Historic Roman architecture and ancient ruins.',
        'description' => 'Baalbek is one of Lebanon’s most iconic historic destinations, known for its grand Roman temples, monumental architecture, and unforgettable atmosphere for travelers who love culture and history.',
        'features' => ['Historic Site', 'Photography', 'Guided Tours']
    ],

    [
        'slug' => 'sidon-sea-castle',
        'name' => 'Sidon',
        'type' => 'attraction',
        'category' => 'beach',
        'location' => 'Sidon, Lebanon',
        'rating' => '4.7',
        'price' => 'Entry Ticket',
        'cover' => 'images/destination-2.jpg',
        'gallery' => [
            'images/destination-2.jpg',
            'images/showcase-bg.jpg',
            'images/destination-1.jpg'
        ],
        'short_description' => 'A coastal destination rich with history and sea views.',
        'description' => 'Sidon combines coastal scenery with cultural heritage, making it a strong choice for travelers looking for beachside exploration and historic atmosphere.',
        'features' => ['Sea View', 'Historic Spot', 'Photography']
    ],

    [
        'slug' => 'downtown-beirut',
        'name' => 'Downtown Beirut',
        'type' => 'attraction',
        'category' => 'city',
        'location' => 'Beirut, Lebanon',
        'rating' => '4.6',
        'price' => 'Free Access',
        'cover' => 'images/destination-3.jpg',
        'gallery' => [
            'images/destination-3.jpg',
            'images/showcase-bg.jpg',
            'images/destination-2.jpg'
        ],
        'short_description' => 'A vibrant city destination with culture, shopping, and nightlife.',
        'description' => 'Downtown Beirut offers a lively urban experience with beautiful streets, restaurants, architecture, and city energy.',
        'features' => ['City Walks', 'Shopping', 'Dining']
    ],

    [
        'slug' => 'mountain-escape',
        'name' => 'Mountain Escape',
        'type' => 'attraction',
        'category' => 'nature',
        'location' => 'North Lebanon',
        'rating' => '4.8',
        'price' => 'Free Access',
        'cover' => 'images/showcase-bg.jpg',
        'gallery' => [
            'images/showcase-bg.jpg',
            'images/destination-1.jpg',
            'images/destination-2.jpg'
        ],
        'short_description' => 'A peaceful mountain destination for scenic views and relaxation.',
        'description' => 'Mountain Escape is perfect for travelers who want fresh air, wide views, and a calm getaway surrounded by Lebanon’s natural beauty.',
        'features' => ['Nature', 'Hiking', 'Photography']
    ],

    [
        'slug' => 'coastal-sunset-batroun',
        'name' => 'Coastal Sunset',
        'type' => 'attraction',
        'category' => 'beach',
        'location' => 'Batroun, Lebanon',
        'rating' => '4.8',
        'price' => 'Free Access',
        'cover' => 'images/destination-2.jpg',
        'gallery' => [
            'images/destination-2.jpg',
            'images/showcase-bg.jpg',
            'images/destination-3.jpg'
        ],
        'short_description' => 'A beautiful seaside escape with sunset views and a relaxing vibe.',
        'description' => 'Coastal Sunset in Batroun is ideal for visitors looking for a beachside atmosphere, relaxing walks, and memorable sea views.',
        'features' => ['Beach', 'Sunset View', 'Relaxation']
    ],

    [
        'slug' => 'temple-view-beqaa',
        'name' => 'Temple View',
        'type' => 'attraction',
        'category' => 'historic',
        'location' => 'Beqaa, Lebanon',
        'rating' => '4.7',
        'price' => 'Entry Ticket',
        'cover' => 'images/destination-1.jpg',
        'gallery' => [
            'images/destination-1.jpg',
            'images/destination-3.jpg',
            'images/showcase-bg.jpg'
        ],
        'short_description' => 'A historic destination with timeless architecture and scenic surroundings.',
        'description' => 'Temple View offers visitors a memorable historical experience, ideal for travelers interested in architecture, heritage, and open-air landmarks.',
        'features' => ['Historic Site', 'Open Air', 'Photography']
    ],

    [
        'slug' => 'city-lights-beirut',
        'name' => 'City Lights',
        'type' => 'attraction',
        'category' => 'city',
        'location' => 'Beirut, Lebanon',
        'rating' => '4.5',
        'price' => 'Free Access',
        'cover' => 'images/destination-3.jpg',
        'gallery' => [
            'images/destination-3.jpg',
            'images/showcase-bg.jpg',
            'images/destination-2.jpg'
        ],
        'short_description' => 'A modern Beirut experience filled with nightlife and urban energy.',
        'description' => 'City Lights gives travelers a vibrant city experience with Beirut’s evening charm, popular streets, and modern atmosphere.',
        'features' => ['Nightlife', 'City View', 'Urban Experience']
    ],

    [
        'slug' => 'nature-retreat-chouf',
        'name' => 'Nature Retreat',
        'type' => 'attraction',
        'category' => 'nature',
        'location' => 'Chouf, Lebanon',
        'rating' => '4.9',
        'price' => 'Free Access',
        'cover' => 'images/showcase-bg.jpg',
        'gallery' => [
            'images/showcase-bg.jpg',
            'images/destination-2.jpg',
            'images/destination-1.jpg'
        ],
        'short_description' => 'A serene green escape ideal for travelers seeking peace and landscapes.',
        'description' => 'Nature Retreat in Chouf is the perfect destination for visitors looking for calm nature, greenery, and a refreshing escape from busy city life.',
        'features' => ['Nature', 'Relaxation', 'Scenic Views']
    ],

    [
        'slug' => 'olive-garden-restaurant',
        'name' => 'Olive Garden Restaurant',
        'type' => 'restaurant',
        'category' => 'food',
        'location' => 'Byblos, Lebanon',
        'rating' => '4.7',
        'price' => '$$$',
        'cover' => 'images/destination-2.jpg',
        'gallery' => [
            'images/destination-2.jpg',
            'images/destination-1.jpg',
            'images/showcase-bg.jpg'
        ],
        'short_description' => 'A warm Lebanese dining experience with authentic flavors.',
        'description' => 'Olive Garden Restaurant is designed for visitors who want local Lebanese cuisine, a welcoming atmosphere, and memorable dining moments.',
        'features' => ['Lebanese Food', 'Outdoor Seating', 'Family Friendly']
    ],

    [
        'slug' => 'cedar-boutique-hotel',
        'name' => 'Cedar Boutique Hotel',
        'type' => 'hotel',
        'category' => 'popular',
        'location' => 'Batroun, Lebanon',
        'rating' => '4.8',
        'price' => '$120 / night',
        'cover' => 'images/showcase-bg.jpg',
        'gallery' => [
            'images/showcase-bg.jpg',
            'images/destination-1.jpg',
            'images/destination-2.jpg'
        ],
        'short_description' => 'A stylish boutique hotel with sea views.',
        'description' => 'Cedar Boutique Hotel offers comfortable rooms, a calm boutique atmosphere, and a premium stay experience for travelers visiting Batroun.',
        'features' => ['Sea View', 'WiFi', 'Breakfast', 'Pool']
    ],

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

    return view('place-details', [
        'place' => $place
    ]);

})->name('places.show');
>>>>>>> ef04397ac5f9b5aaa837d40accd44563fe94b238
