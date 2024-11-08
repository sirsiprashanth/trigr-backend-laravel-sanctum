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

            $response = Http::withHeaders([
                'accept' => 'application/json',
                'dev-id' => config('terra.dev_id'),
                'x-api-key' => config('terra.api_key'),
                'content-type' => 'application/json',
            ])->post(config('terra.api_url') . '/auth/generateAuthToken', [
                'reference_id' => $request->reference_id,
                'resource' => ['HEALTH'],
                'language' => 'en',
                'auth_success_redirect_url' => config('app.url') . '/auth-success',
                'auth_failure_redirect_url' => config('app.url') . '/auth-failure',
            ]);

            \Log::info('Terra API Response', [
                'status' => $response->status(),
                'body' => $response->json()
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
                'error' => $response->json()
            ], 500);
        } catch (\Exception $e) {
            Log::error('Terra Token Generation Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate Terra token',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
