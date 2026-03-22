<?php

use Illuminate\Support\Facades\Route;

// Landing page (takes priority over SPA catch-all)
Route::get('/', function () {
    return view('landing');
});

// SPA catch-all (for /login, /register, /app/* etc.)
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
