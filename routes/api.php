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
use App\Http\Controllers\Api\TerraController;
use App\Http\Controllers\NoteController;

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
Route::get('/coaching-plans/{coaching_plan_id}/goals-count', [GoalController::class, 'getGoalsCount']);

Route::apiResource('strategies', StrategyController::class);
Route::post('strategies/reorder', [StrategyController::class, 'reorder']);

Route::apiResource('action-plans', ActionPlanController::class);
Route::post('action-plans/reorder', [ActionPlanController::class, 'reorder']);
Route::patch('action-plans/{actionPlan}/status', [ActionPlanController::class, 'updateStatus']);
Route::get('action-plans/user/{user_id}', [ActionPlanController::class, 'getUserActionPlans']);
Route::get('/action-plans/user/{user_id}', [ActionPlanController::class, 'getUserActionPlans'])
    ->name('action-plans.user');
// New route for receiving data from Google Health Connect
Route::post('/health-connect', [HealthConnectController::class, 'store']);
Route::post('/terra/generate-token', [TerraController::class, 'generateToken']);
Route::get('/health-connect/daily/{user_id}', [HealthConnectController::class, 'getDailyData']);

Route::get('coaching-plans/{coaching_plan_id}/notes', [NoteController::class, 'index'])
    ->where('coaching_plan_id', '[0-9]+'); // Ensure coaching_plan_id is numeric
Route::post('notes', [NoteController::class, 'store']);
Route::delete('notes/{note}', [NoteController::class, 'destroy']);
