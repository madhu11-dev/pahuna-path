<?php
use App\Http\Controllers\EmailController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
require __DIR__.'/PlaceRoutes.php';

Route::prefix('auth')->group(function () {
    Route::post('/register', [UserController::class, 'register']);
    Route::get('/verify-email/{id}/{hash}', [EmailController::class, 'verification'])
        ->name('verification.verify');
    Route::post('/login', [UserController::class, 'login']);
});
