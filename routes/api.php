<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CoachingPlanController;
use App\Http\Controllers\CoachingRequestController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\FocusAreaController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\StrategyController;
use App\Http\Controllers\ActionPlanController;
use App\Http\Controllers\HealthConnectController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::get('/get-coaches', [UserController::class, 'getCoaches'])->middleware('auth:sanctum');

Route::apiResource('users', UserController::class);

Route::apiResource('coaching-plans', CoachingPlanController::class)->middleware('auth:sanctum');

Route::apiResource('coaching-requests', CoachingRequestController::class);

Route::get('/fetch-coaching-requests', [CoachingRequestController::class, 'fetchCoachingRequests']);

Route::apiResource('appointments', AppointmentController::class);

Route::get('/fetch-user-appointments/{id}', [AppointmentController::class, 'fetchUserAppointments']);

Route::apiResource('focus-areas', FocusAreaController::class);

Route::get('/prepopulated-focus-areas', [FocusAreaController::class, 'prepopulatedFocusAreas']);

Route::apiResource('goals', GoalController::class);

Route::apiResource('strategies', StrategyController::class);
Route::post('strategies/reorder', [StrategyController::class, 'reorder']);

Route::apiResource('action-plans', ActionPlanController::class);
Route::post('action-plans/reorder', [ActionPlanController::class, 'reorder']);
Route::patch('action-plans/{actionPlan}/status', [ActionPlanController::class, 'updateStatus']);
// New route for receiving data from Google Health Connect
Route::post('/health-connect', [HealthConnectController::class, 'store']);
