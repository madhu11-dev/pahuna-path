<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {

    Route::post('/register', [UserController::class, 'register']);
    Route::get('/verify/{id}/{hash}', [UserController::class, 'verifyEmail'])
        ->name('verification.verify');
});
