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

        $getDaily = new GetDaily();
        $getDaily->reference_id = $user['reference_id'];
        $getDaily->user_id = $user['user_id'];
        $getDaily->distance_in_meters = $data['distance_in_meters'] ?? null;
        $getDaily->swimming_strokes = $data['swimming_strokes'] ?? null;
        $getDaily->steps = $data['steps'] ?? null;
        $getDaily->burned_calories = $data['burned_calories'] ?? null;
        $getDaily->net_activity_calories = $data['net_activity_calories'] ?? null;
        $getDaily->BMR_calories = $data['BMR_calories'] ?? null;
        $getDaily->max_hr_bpm = $data['max_hr_bpm'] ?? null;
        $getDaily->min_hr_bpm = $data['min_hr_bpm'] ?? null;
        $getDaily->avg_hr_bpm = $data['avg_hr_bpm'] ?? null;
        $getDaily->active_duration_in_sec = $data['active_duration_in_sec'] ?? null;
        $getDaily->avg_saturation_percentage = $data['avg_saturation_percentage'] ?? null;
        $getDaily->avg_stress_level = $data['avg_stress_level'] ?? null;
        $getDaily->scores = json_encode($data['scores'] ?? []);
        $getDaily->save();

        return response()->json(['message' => 'Data received and logged successfully'], 200);
    }
}
