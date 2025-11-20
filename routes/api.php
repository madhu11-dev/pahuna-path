<?php

use App\Http\Controllers\EmailController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\AuthAdmin;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PlaceController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [UserController::class, 'register']);
    Route::get('/verify-email/{id}/{hash}', [EmailController::class, 'verification'])
        ->name('verification.verify');
    Route::post('/login', [UserController::class, 'login']);
});

Route::prefix('places')->controller(PlaceController::class)->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'store');
    Route::put('/{place}', 'update');
    Route::delete('/{place}', 'destroy');
});

Route::prefix('accommodations')->controller(AccommodationController::class)->group(function () {
    Route::get('/', 'index');
});

// Admin routes only 
Route::middleware(['auth', AuthAdmin::class])->group(function () {
    
});
