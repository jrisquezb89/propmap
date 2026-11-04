<?php

use Illuminate\Support\Facades\Route;

// API-only app: the root just points callers at the API.
Route::get('/', fn () => response()->json([
    'name' => config('app.name'),
    'docs' => 'See /api/properties and /api/health',
]));
