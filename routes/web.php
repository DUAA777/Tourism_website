<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::get('/abouttUs', function () {
    return view('aboutUs');
});