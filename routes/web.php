<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/abouttUs', function () {
    return view('aboutUs');
})->name('aboutUs');

Route::get('/chatbot', function () {
    return view('chatbot');
})->name('chatbot');

Route::get('/contactUs', function () {
    return view('contactUs');
})->name('contactUs');