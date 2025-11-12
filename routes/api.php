<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Middleware\PreventDoubleBooking;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/push-token', [AuthController::class, 'updatePushToken']);
        Route::post('/push-token/clear', [AuthController::class, 'clearPushToken']);
    });
});

Route::prefix('events')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [EventController::class, 'index']);
    Route::get('/{id}', [EventController::class, 'show']);
    Route::middleware('role:organizer')->group(function () {
        Route::post('/', [EventController::class, 'store']);
        Route::put('/{id}', [EventController::class, 'update']);
        Route::delete('/{id}', [EventController::class, 'destroy']);
        Route::post('/{event_id}/tickets', [TicketController::class, 'store']);
    });
});

Route::prefix('tickets')->middleware('auth:sanctum')->group(function () {
    Route::middleware('role:organizer')->group(function () {
        Route::put('/{id}', [TicketController::class, 'update']);
        Route::delete('/{id}', [TicketController::class, 'destroy']);
    });
    Route::post('/{id}/bookings', [BookingController::class, 'store'])->middleware('role:customer', PreventDoubleBooking::class);
});

Route::prefix('bookings')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [BookingController::class, 'index']);
    Route::middleware('role:customer')->group(function () {
        Route::put('/{id}/cancel', [BookingController::class, 'cancel']);
        Route::post('/{id}/payment', [PaymentController::class, 'store']);
    });
});
Route::prefix('payments')->middleware('auth:sanctum')->group(function () {
    Route::get('/{id}', [PaymentController::class, 'show']);
});
