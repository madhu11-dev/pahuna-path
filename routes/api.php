<?php

use App\Http\Controllers\EmailController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\AuthAdmin;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\AccommodationController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [UserController::class, 'register']);
    Route::get('/verify-email/{id}/{hash}', [EmailController::class, 'verification'])
        ->name('verification.verify');
    Route::post('/login', [UserController::class, 'login']);
    Route::post('/forgot-password', [UserController::class, 'forgotPassword']);
    Route::post('/reset-password', [UserController::class, 'resetPassword']);
});

Route::prefix('places')->controller(PlaceController::class)->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'store');
    Route::put('/{place}', 'update');
    Route::delete('/{place}', 'destroy');
});

Route::prefix('accommodations')->controller(AccommodationController::class)->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'store');
    Route::delete('/{accommodation}', 'destroy');
});

// Admin routes only 
Route::middleware(['auth', AuthAdmin::class])->group(function () {
    
});
