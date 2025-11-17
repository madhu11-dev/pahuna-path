<?php

use App\Http\Controllers\EmailController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\AuthAdmin;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {

    Route::post('/register', [UserController::class, 'register']);
    Route::get('/verify-email/{id}/{hash}', [EmailController::class, 'verification'])
        ->name('verification.verify');
    Route::post('/login', [UserController::class, 'login']);
});


// Admin routes only 
Route::middleware(['auth', AuthAdmin::class])->group(function () {
    
});
