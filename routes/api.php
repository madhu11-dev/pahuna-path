<?php

use App\Http\Controllers\UserController;
use Illuminate\Routing\Route;

Route::prefix('auth')->group(function(){

    Route::post('/register', [UserController::class, 'register']);
     


});