<?php

use App\Http\Controllers\EmailController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ExtraServiceController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\PlaceReviewController;
use App\Http\Controllers\AccommodationController;
use App\Http\Controllers\AccommodationReviewController;

/*
|--------------------------------------------------------------------------
| API Routes - Refactored & Organized
|--------------------------------------------------------------------------
| Routes are grouped by domain for better organization and maintainability
| Middleware is applied at group level where appropriate
*/

Route::prefix('auth')->group(function () {
    Route::post('/register', [UserController::class, 'register']);
    Route::post('/staff/register', [StaffController::class, 'register']);
    Route::post('/login', [UserController::class, 'login']);
    Route::post('/forgot-password', [UserController::class, 'forgotPassword']);
    Route::post('/reset-password', [UserController::class, 'resetPassword']);

    Route::get('/verify-email/{id}/{hash}', [EmailController::class, 'verification'])
        ->name('verification.verify');

    // Protected authentication routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [UserController::class, 'logout']);
        Route::get('/staff/status', [StaffController::class, 'checkApprovalStatus']);
    });
});

// user profile 
Route::prefix('user')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [UserController::class, 'profile']);
    Route::patch('/', [UserController::class, 'updateProfile']);
    Route::post('/change-password', [UserController::class, 'changePassword']);
});

// staff dashabord
Route::prefix('staff')->middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [StaffController::class, 'logout']);
    Route::get('/dashboard', [StaffController::class, 'getDashboardData']);
    Route::post('/profile/update', [StaffController::class, 'updateProfile']);
});


// places
Route::prefix('places')->group(function () {
    // Public routes
    Route::get('/', [PlaceController::class, 'index']);
    Route::get('/images', [PlaceController::class, 'getPlaceImages']);
    Route::get('/{place}', [PlaceController::class, 'show']);

    // Protected routes (authenticated users can create places)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [PlaceController::class, 'store']);
        Route::put('/{place}', [PlaceController::class, 'update']);
        Route::delete('/{place}', [PlaceController::class, 'destroy']);
    });

    // Place Reviews
    Route::prefix('{place}/reviews')->group(function () {
        Route::get('/', [PlaceReviewController::class, 'index']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/', [PlaceReviewController::class, 'store']);
            Route::put('/{review}', [PlaceReviewController::class, 'update']);
            Route::delete('/{review}', [PlaceReviewController::class, 'destroy']);
        });
    });
});

// accomodatons
Route::prefix('accommodations')->group(function () {
    // Public routes
    Route::get('/', [AccommodationController::class, 'index']);
    Route::get('/{accommodation}', [AccommodationController::class, 'show']);

    // Protected routes (staff only)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [AccommodationController::class, 'store']);
        Route::put('/{accommodation}', [AccommodationController::class, 'update']);
        Route::post('/{accommodation}', [AccommodationController::class, 'update']); // Support POST for multipart
        Route::delete('/{accommodation}', [AccommodationController::class, 'destroy']);
        Route::post('/{accommodation}/pay-verification', [AccommodationController::class, 'payVerificationFee']);
    });

    // Accommodation Rooms
    Route::prefix('{accommodation}/rooms')->group(function () {
        Route::get('/', [RoomController::class, 'index']);
        Route::post('/{room}/availability', [RoomController::class, 'checkAvailability']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/', [RoomController::class, 'store']);
            Route::put('/{room}', [RoomController::class, 'update']);
            Route::post('/{room}', [RoomController::class, 'update']); // Support POST for multipart
            Route::delete('/{room}', [RoomController::class, 'destroy']);
        });
    });

    // Accommodation Extra Services
    Route::prefix('{accommodation}/services')->group(function () {
        Route::get('/', [ExtraServiceController::class, 'index']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/', [ExtraServiceController::class, 'store']);
            Route::put('/{service}', [ExtraServiceController::class, 'update']);
            Route::delete('/{service}', [ExtraServiceController::class, 'destroy']);
        });
    });

    // Accommodation Reviews
    Route::prefix('{accommodation}/reviews')->group(function () {
        Route::get('/', [AccommodationReviewController::class, 'index']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/', [AccommodationReviewController::class, 'store']);
            Route::put('/{review}', [AccommodationReviewController::class, 'update']);
            Route::delete('/{review}', [AccommodationReviewController::class, 'destroy']);
        });
    });
});

// bookings
Route::prefix('bookings')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [BookingController::class, 'index']);
    Route::get('/{booking}', [BookingController::class, 'show']);
    Route::post('/', [BookingController::class, 'store']);
    Route::patch('/{booking}/status', [BookingController::class, 'updateStatus']);
    Route::patch('/{booking}/cancel', [BookingController::class, 'cancel']);
});

// payment
Route::prefix('payments')->middleware('auth:sanctum')->group(function () {
    // Rate limited to prevent abuse
    Route::post('/verify', [PaymentController::class, 'verifyPayment'])
        ->middleware('throttle:5,1');
    Route::post('/refund/{booking}', [PaymentController::class, 'initiateRefund'])
        ->middleware('throttle:5,1');

    Route::get('/booking/{booking}', [PaymentController::class, 'getBookingPaymentInfo']);
});

// transactions
Route::prefix('transactions')->middleware('auth:sanctum')->group(function () {
    Route::get('/user', [TransactionController::class, 'getUserTransactions']);
    Route::get('/staff', [TransactionController::class, 'getStaffTransactions']);
    Route::get('/{transaction}', [TransactionController::class, 'getTransactionDetails']);
});

// admin panel
Route::prefix('admin')->middleware('auth:sanctum')->group(function () {
    // Admin authentication & profile
    Route::post('/logout', [AdminController::class, 'logout']);
    Route::get('/me', [AdminController::class, 'getAdminInfo']);
    Route::get('/dashboard/stats', [AdminController::class, 'getDashboardStats']);

    // User management
    Route::get('/users', [AdminController::class, 'getAllUsers']);
    Route::delete('/users/{user}', [AdminController::class, 'deleteUser']);

    // Place management
    Route::get('/places', [AdminController::class, 'getAllPlaces']);
    Route::delete('/places/{place}', [AdminController::class, 'deletePlace']);
    Route::patch('/places/{place}/verify', [AdminController::class, 'toggleVerifyPlace']);
    Route::post('/places/merge', [AdminController::class, 'mergePlaces']);

    // Staff management
    Route::get('/staff', [AdminController::class, 'getAllStaff']);

    // Accommodation management
    Route::get('/accommodations', [AccommodationController::class, 'indexAll']);
    Route::patch('/accommodations/{accommodation}/verify', [AccommodationController::class, 'verify']);
});
