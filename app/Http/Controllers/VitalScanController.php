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
        Log::info('Received vital scan data at ' . now()->format('Y-m-d H:i:s'), [
            'user_id' => Auth::id(),
            'data' => $request->all()
        ]);

        // Get the raw request data
        $rawData = $request->all();
        Log::info('Raw request data', ['rawData' => $rawData]);

        // Extract the data from the correct level
        $data = $rawData['data'] ?? [];
        Log::info('Extracted data', ['data' => $data]);

        // Map all fields from the incoming data
        $mappedData = [
            'pulse_rate' => $data['pulseRate'] ?? null,
            'mean_rri' => $data['meanRRI'] ?? null,
            'spo2' => $data['spo2'] ?? null,
            'oxygen_saturation' => $data['spo2'] ?? null,
            'respiration_rate' => $data['respirationRate'] ?? null,
            'stress_level' => $data['stressLevel'] ?? null,
            'sdnn' => $data['sdnn'] ?? null,
            'rmssd' => $data['rmssd'] ?? null,
            'stress_index' => $data['stressIndex'] ?? null,
            'blood_pressure' => $data['bloodPressure'] ?? null,
            'lfhf' => $data['lfhf'] ?? null,
            'pns_index' => $data['pnsIndex'] ?? null,
            'pns_zone' => $data['pnsZone'] ?? null,
            'prq' => $data['prq'] ?? null,
            'sd1' => $data['sd1'] ?? null,
            'sd2' => $data['sd2'] ?? null,
            'sns_index' => $data['snsIndex'] ?? null,
            'sns_zone' => $data['snsZone'] ?? null,
            'wellness_index' => $data['wellnessIndex'] ?? null,
            'wellness_level' => $data['wellnessLevel'] ?? null
        ];

        // Log the mapped data
        Log::info('Mapped data before validation', $mappedData);

        // Validate all fields
        $validatedData = validator($mappedData, [
            'pulse_rate' => 'nullable|numeric',
            'mean_rri' => 'nullable|numeric',
            'spo2' => 'nullable|numeric',
            'oxygen_saturation' => 'nullable|numeric',
            'respiration_rate' => 'nullable|numeric',
            'stress_level' => 'nullable|numeric',
            'sdnn' => 'nullable|numeric',
            'rmssd' => 'nullable|numeric',
            'stress_index' => 'nullable|numeric',
            'blood_pressure' => 'nullable|array',
            'lfhf' => 'nullable|numeric',
            'pns_index' => 'nullable|numeric',
            'pns_zone' => 'nullable',
            'prq' => 'nullable|numeric',
            'sd1' => 'nullable|numeric',
            'sd2' => 'nullable|numeric',
            'sns_index' => 'nullable|numeric',
            'sns_zone' => 'nullable',
            'wellness_index' => 'nullable|numeric',
            'wellness_level' => 'nullable'
        ])->validate();

        // Log the validated data
        Log::info('Validated data', $validatedData);

        // Create the record and log it
        $vitalScan = VitalScan::create(array_merge($validatedData, ['user_id' => Auth::id()]));
        Log::info('Created vital scan', ['vital_scan' => $vitalScan->toArray()]);

        return response()->json(['success' => true, 'data' => $vitalScan], 201);
    }

    public function history()
    {
        $vitalScans = VitalScan::where('user_id', Auth::id())->get();

        return response()->json(['success' => true, 'data' => $vitalScans], 200);
    }
}
