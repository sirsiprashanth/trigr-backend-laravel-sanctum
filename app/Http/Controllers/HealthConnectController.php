<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\GetDaily;

class HealthConnectController extends Controller
{
    /**
     * Store data from Google Health Connect.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Log the received data
        $dateTimeIndia = now()->setTimezone('Asia/Kolkata')->toDateTimeString();
        Log::info('[' . $dateTimeIndia . '] Received data from Google Health Connect:', $request->all());

        $data = $request->input('data');
        $user = $request->input('user');

        // Use updateOrCreate to check if reference_id exists and update or create a new record
        $getDaily = GetDaily::updateOrCreate(
            ['reference_id' => $user['reference_id']], // Condition to check if reference_id exists
            [
                'user_id' => $user['user_id'],
                'distance_in_meters' => $data['distance_in_meters'] ?? null,
                'swimming_strokes' => $data['swimming_strokes'] ?? null,
                'steps' => $data['steps'] ?? null,
                'burned_calories' => $data['burned_calories'] ?? null,
                'net_activity_calories' => $data['net_activity_calories'] ?? null,
                'BMR_calories' => $data['BMR_calories'] ?? null,
                'max_hr_bpm' => $data['max_hr_bpm'] ?? null,
                'min_hr_bpm' => $data['min_hr_bpm'] ?? null,
                'avg_hr_bpm' => $data['avg_hr_bpm'] ?? null,
                'active_duration_in_sec' => $data['active_duration_in_sec'] ?? null,
                'avg_saturation_percentage' => $data['avg_saturation_percentage'] ?? null,
                'avg_stress_level' => $data['avg_stress_level'] ?? null,
                'scores' => json_encode($data['scores'] ?? []),
            ]
        );

        return response()->json(['message' => 'Data received and logged successfully'], 200);
    }

    /**
     * Get daily data by user_id.
     *
     * @param  string  $user_id
     * @return \Illuminate\Http\Response
     */
    public function getDailyData($user_id)
    {
        // Log the received user_id
        $dateTimeIndia = now()->setTimezone('Asia/Kolkata')->toDateTimeString();
        Log::info('[' . $dateTimeIndia . '] Received request for user_id: ' . $user_id);
        // Fetch daily data for the specified user_id
        $dailyData = GetDaily::where('reference_id', $user_id)->get();

        if ($dailyData->isEmpty()) {
            return response()->json(['message' => 'No data found for the specified user_id'], 404);
        }

        return response()->json($dailyData, 200);
    }
}
