<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TerraController extends Controller
{
    public function generateToken(Request $request)
    {
        try {
            $request->validate([
                'reference_id' => 'required|string'
            ]);

            // Log the request parameters
            Log::info('Terra Token Request', [
                'reference_id' => $request->reference_id,
                'dev_id' => config('terra.dev_id'),
                'api_key' => 'exists: ' . !empty(config('terra.api_key'))
            ]);

            $payload = [
                'reference_id' => $request->reference_id,
                'resource' => ['HEALTH'],
                'language' => 'en',
                'auth_success_redirect_url' => config('app.url') . '/auth-success',
                'auth_failure_redirect_url' => config('app.url') . '/auth-failure',
            ];

            // Log the full request details
            Log::info('Terra API Request', [
                'url' => config('terra.api_url') . '/auth/generateAuthToken',
                'headers' => [
                    'dev-id' => config('terra.dev_id'),
                    'x-api-key' => substr(config('terra.api_key'), 0, 5) . '...',  // Log only first 5 chars for security
                ],
                'payload' => $payload
            ]);

            $response = Http::withHeaders([
                'accept' => 'application/json',
                'dev-id' => config('terra.dev_id'),
                'x-api-key' => config('terra.api_key'),
                'content-type' => 'application/json',
            ])->post(config('terra.api_url') . '/auth/generateAuthToken', $payload);

            // Log the full response
            Log::info('Terra API Response', [
                'status' => $response->status(),
                'body' => $response->json(),
                'raw' => $response->body()
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'token' => $response->json('token')
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate Terra token',
                'error' => [
                    'status' => $response->status(),
                    'body' => $response->json(),
                    'raw' => $response->body()
                ]
            ], $response->status());
        } catch (\Exception $e) {
            Log::error('Terra Token Generation Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate Terra token',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
