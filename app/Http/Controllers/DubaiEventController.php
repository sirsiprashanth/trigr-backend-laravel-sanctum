<?php

namespace App\Http\Controllers;

use App\Models\DubaiEventScan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\VitalScanResults;

class DubaiEventController extends Controller
{
    public function logFaceScan(Request $request)
    {
        // Log all incoming data
        Log::info('Dubai Event Face Scan Data', [
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'data' => $request->all(),
            'headers' => $request->headers->all(),
            'ip' => $request->ip()
        ]);

        try {
            // Save to database
            $scan = DubaiEventScan::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'headers' => $request->headers->all(),
                'ip_address' => $request->ip(),
                'additional_data' => $request->except(['name', 'email', 'phone'])
            ]);

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Face scan data logged and saved successfully',
                'data' => $scan
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error saving Dubai Event Face Scan', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error saving face scan data'
            ], 500);
        }
    }

    public function logUpdate(Request $request)
    {
        // Log all incoming update data
        Log::info('Dubai Event Face Scan Update Data', [
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'data' => $request->all(),
            'headers' => $request->headers->all(),
            'ip' => $request->ip(),
            'method' => $request->method(),
            'url' => $request->fullUrl()
        ]);

        try {
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
            
            // Find the most recent record for this IP address
            $scan = DubaiEventScan::where('ip_address', $request->ip())
                                ->latest()
                                ->first();
            
            if (!$scan) {
                return response()->json([
                    'success' => false,
                    'message' => 'No existing record found for this IP address'
                ], 404);
            }
            
            // Update the record with validated data
            $scan->update($validatedData);

            // Log the updated record
            Log::info('Updated dubai event scan', ['scan' => $scan->toArray()]);

            // Send email with vital scan results
            try {
                Log::info('Attempting to send vital scan results email', [
                    'recipient_email' => $scan->email,
                    'recipient_name' => $scan->name,
                    'data' => $validatedData
                ]);

                // Check if we have a valid email
                if (empty($scan->email)) {
                    Log::error('Cannot send email - no email address found in scan record', [
                        'scan_id' => $scan->id
                    ]);
                    throw new \Exception('No email address found in scan record');
                }

                // Log mail configuration
                Log::info('Mail configuration', [
                    'driver' => config('mail.default'),
                    'from_address' => config('mail.from.address'),
                    'from_name' => config('mail.from.name')
                ]);

                Mail::to($scan->email)->send(new VitalScanResults($validatedData));
                
                Log::info('Vital scan results email sent successfully', [
                    'email' => $scan->email,
                    'scan_id' => $scan->id
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send vital scan results email', [
                    'error' => $e->getMessage(),
                    'error_trace' => $e->getTraceAsString(),
                    'email' => $scan->email ?? 'not set',
                    'scan_id' => $scan->id
                ]);
                // Don't return error response as the data was saved successfully
            }

            return response()->json([
                'success' => true,
                'message' => 'Update data logged and saved successfully',
                'data' => $scan
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error saving Dubai Event Face Scan Update', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error saving update data: ' . $e->getMessage()
            ], 500);
        }
    }
}
