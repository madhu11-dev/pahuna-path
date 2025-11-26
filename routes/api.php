<?php

use App\Http\Controllers\EmailController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Middleware\AuthAdmin;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\PlaceReviewController;
use App\Http\Controllers\AccommodationController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [UserController::class, 'register']);
    Route::get('/verify-email/{id}/{hash}', [EmailController::class, 'verification'])
        ->name('verification.verify');
    Route::post('/login', [UserController::class, 'login']);
    Route::post('/forgot-password', [UserController::class, 'forgotPassword']);
    Route::post('/reset-password', [UserController::class, 'resetPassword']);
    Route::post('/logout', [UserController::class, 'logout'])->middleware('auth:sanctum');
});

Route::prefix('places')->controller(PlaceController::class)->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'store')->middleware('auth:sanctum');
    Route::get('/{place}', 'show');
    Route::put('/{place}', 'update')->middleware('auth:sanctum');
    Route::delete('/{place}', 'destroy')->middleware('auth:sanctum');
});

Route::prefix('places/{place}/reviews')->controller(PlaceReviewController::class)->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'store')->middleware('auth:sanctum');
    Route::put('/{review}', 'update')->middleware('auth:sanctum');
    Route::delete('/{review}', 'destroy')->middleware('auth:sanctum');
});

Route::prefix('accommodations')->controller(AccommodationController::class)->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'store')->middleware('auth:sanctum');
    Route::delete('/{accommodation}', 'destroy')->middleware('auth:sanctum');
});

// Protected admin routes - using regular auth:sanctum middleware
// Admin authorization is checked within each controller method
Route::prefix('admin')->controller(AdminController::class)->middleware('auth:sanctum')->group(function () {
    Route::post('/logout', 'logout');
    Route::get('/me', 'getAdminInfo');
    Route::get('/dashboard/stats', 'getDashboardStats');
    Route::get('/users', 'getAllUsers');
    Route::get('/places', 'getAllPlaces');
    Route::delete('/places/{place}', 'deletePlace');
    Route::post('/places/merge', 'mergePlaces');
    Route::get('/hotels', 'getAllHotels');
});
