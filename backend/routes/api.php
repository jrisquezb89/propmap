<?php

use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\PropertyController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class)->name('health');

Route::controller(PropertyController::class)->group(function () {
    Route::get('/properties', 'index')->name('properties.index');
    Route::get('/properties/{property}', 'show')
        ->whereNumber('property')
        ->name('properties.show');
});
