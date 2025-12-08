<?php

use App\Http\Controllers\EmailController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\StaffController;
use App\Http\Middleware\AuthAdmin;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\PlaceReviewController;
use App\Http\Controllers\AccommodationController;
use App\Http\Controllers\AccommodationReviewController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [UserController::class, 'register']);
    Route::post('/staff/register', [StaffController::class, 'register']);
    Route::get('/verify-email/{id}/{hash}', [EmailController::class, 'verification'])
        ->name('verification.verify');
    Route::post('/login', [UserController::class, 'login']);
    Route::post('/forgot-password', [UserController::class, 'forgotPassword']);
    Route::post('/reset-password', [UserController::class, 'resetPassword']);
    Route::post('/logout', [UserController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/staff/status', [StaffController::class, 'checkApprovalStatus'])->middleware('auth:sanctum');
});

// Staff Dashboard Routes
Route::prefix('staff')->middleware('auth:sanctum')->controller(StaffController::class)->group(function () {
    Route::post('/logout', 'logout');
    Route::get('/dashboard', 'getDashboardData');
    Route::post('/profile/update', 'updateProfile');
});

Route::prefix('places')->controller(PlaceController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/images', 'getPlaceImages');
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
    Route::get('/{accommodation}', 'show');
    Route::post('/', 'store')->middleware('auth:sanctum');
    Route::put('/{accommodation}', 'update')->middleware('auth:sanctum');
    Route::delete('/{accommodation}', 'destroy')->middleware('auth:sanctum');
});

Route::prefix('accommodations/{accommodation}/reviews')->controller(AccommodationReviewController::class)->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'store')->middleware('auth:sanctum');
    Route::put('/{review}', 'update')->middleware('auth:sanctum');
    Route::delete('/{review}', 'destroy')->middleware('auth:sanctum');
});

// Protected admin routes - using regular auth:sanctum middleware
Route::prefix('admin')->controller(AdminController::class)->middleware('auth:sanctum')->group(function () {
    Route::post('/logout', 'logout');
    Route::get('/me', 'getAdminInfo');
    Route::get('/dashboard/stats', 'getDashboardStats');
    Route::get('/users', 'getAllUsers');
    Route::delete('/users/{user}', 'deleteUser');
    Route::get('/places', 'getAllPlaces');
    Route::delete('/places/{place}', 'deletePlace');
    Route::patch('/places/{place}/verify', 'toggleVerifyPlace');
    Route::post('/places/merge', 'mergePlaces');

    Route::get('/staff', 'getAllStaff');
    Route::get('/accommodations', [AccommodationController::class, 'indexAll']);
    Route::patch('/accommodations/{accommodation}/verify', [AccommodationController::class, 'verify']);
});

// User profile routes (authenticated)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [UserController::class, 'profile']);
    Route::patch('/user', [UserController::class, 'updateProfile']);
    Route::post('/user/change-password', [UserController::class, 'changePassword']);
});
