<?php

namespace App\Http\Controllers;

use App\Models\VitalScan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VitalScanController extends Controller
{
    public function store(Request $request)
    {
        // Log the received data
        Log::info('Received vital scan data', [
            'user_id' => Auth::id(),
            'data' => $request->all()
        ]);

        // Extract the nested data correctly
        $data = $request->input('data', []);

        // Normalize the keys to match the database column names
        $data = array_change_key_case($data, CASE_LOWER);

        // Log the extracted data with more detail
        Log::info('Extracted data for validation', ['data' => $data]);

        // Create a new request with the extracted data for validation
        $validatedData = validator($data, [
            'pulse_rate' => 'nullable|numeric',
            'mean_rri' => 'nullable|numeric',
            'oxygen_saturation' => 'nullable|numeric',
            'respiration_rate' => 'nullable|numeric',
            'stress_level' => 'nullable|numeric',
            'sdnn' => 'nullable|numeric',
            'rmssd' => 'nullable|numeric',
            'stress_index' => 'nullable|numeric',
            'blood_pressure' => 'nullable|string',
            'lfhf' => 'nullable|numeric',
            'pns_index' => 'nullable|numeric',
            'pns_zone' => 'nullable|integer',
            'prq' => 'nullable|numeric',
            'sd1' => 'nullable|numeric',
            'sd2' => 'nullable|numeric',
            'sns_index' => 'nullable|numeric',
            'sns_zone' => 'nullable|integer',
            'wellness_index' => 'nullable|numeric',
            'wellness_level' => 'nullable|integer',
        ])->validate();

        // Log the validated data
        Log::info('Validated data', $validatedData);

        $vitalScan = VitalScan::create(array_merge($validatedData, ['user_id' => Auth::id()]));

        return response()->json(['success' => true, 'data' => $vitalScan], 201);
    }

    public function history()
    {
        $vitalScans = VitalScan::where('user_id', Auth::id())->get();

        return response()->json(['success' => true, 'data' => $vitalScans], 200);
    }
}
