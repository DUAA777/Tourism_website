<?php

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

Route::middleware(['auth'])->group(function () {
    // Routes that only logged-in users can see
    Route::get('/dashboard', function () { return view('dashboard'); });
});
Route::get('auth/google', [AuthController::class, 'redirectToGoogle']);
Route::get('auth/google/callback', [AuthController::class, 'handleGoogleCallback']);