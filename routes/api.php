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
use App\Http\Controllers\SupportController;
use App\Http\Controllers\VitalScanController;
use App\Http\Controllers\EplimoReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DubaiEventController;
use App\Http\Controllers\RazorpayWebhookController;
use Illuminate\Support\Facades\Mail;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Password Reset Routes
Route::post('password/email', [App\Http\Controllers\Api\PasswordResetController::class, 'sendResetLinkEmail']);
Route::post('password/reset', [App\Http\Controllers\Api\PasswordResetController::class, 'reset']);

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
Route::post('support', [SupportController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/vitals', [VitalScanController::class, 'store']);
    Route::get('/vitals/history', [VitalScanController::class, 'history']);
});

// Eplimo Report Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/eplimo-report', [EplimoReportController::class, 'store']);
    Route::get('/eplimo-report', [EplimoReportController::class, 'show']);
    Route::get('/eplimo-report/download', [EplimoReportController::class, 'downloadPdf']);
});

// Notification Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications', [NotificationController::class, 'store']);
    Route::patch('/notifications/{id}/mark-as-read', [NotificationController::class, 'markAsRead']);
    Route::patch('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
});

// Dubai Event Routes
Route::post('/dubai-event-facescans', [DubaiEventController::class, 'logFaceScan']);
Route::post('/dubai-event-facescans/update', [DubaiEventController::class, 'logUpdate']);

// Razorpay Webhook Route
Route::post('/webhooks/razorpay', [RazorpayWebhookController::class, 'handleWebhook']);

Route::get('/test-mail', function () {
    try {
        $config = config('mail');
        $testData = [
            'driver' => $config['default'],
            'host' => $config['mailers']['smtp']['host'],
            'port' => $config['mailers']['smtp']['port'],
            'encryption' => $config['mailers']['smtp']['encryption'],
            'username' => $config['mailers']['smtp']['username'],
            'from_address' => $config['from']['address'],
            'from_name' => $config['from']['name'],
        ];
        
        Mail::raw('Test email from Laravel', function($message) {
            $message->to('prashanthsirsi@gmail.com')
                   ->subject('Test Email');
        });
        
        return response()->json([
            'success' => true,
            'message' => 'Mail sent successfully',
            'config' => $testData
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'config' => $testData ?? null,
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

Route::get('/test-face-scan-mail', function () {
    try {
        $sampleData = [
            'pulse_rate' => 72,
            'spo2' => 98,
            'blood_pressure' => [
                'systolic' => 120,
                'diastolic' => 80
            ],
            'respiration_rate' => 16,
            'stress_level' => 45,
            'sdnn' => 45,
            'lfhf' => 1.5,
            'wellness_index' => 85
        ];
        
        Mail::to('prashanthsirsi@gmail.com')->send(new \App\Mail\VitalScanResults($sampleData));
        
        return response()->json([
            'success' => true,
            'message' => 'Face scan report email sent successfully'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});
