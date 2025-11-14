<?php

use App\Http\Controllers\PlaceController;
use Illuminate\Support\Facades\Route;

// Public route: anyone can view places
Route::get('/places', [PlaceController::class, 'index']);

// Protected routes: only authenticated users
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/places', [PlaceController::class, 'store']);        // only logged-in users
    Route::put('/places/{id}', [PlaceController::class, 'update']);   // only owner
    Route::patch('/places/{id}', [PlaceController::class, 'update']); // only owner
    Route::delete('/places/{id}', [PlaceController::class, 'destroy']); // only owner
});
