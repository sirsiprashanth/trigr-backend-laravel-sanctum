<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Support;

class SupportController extends Controller
{
    public function store(Request $request)
    {
        Log::info('Support request received', $request->all());

        try {
            $validator = Validator::make($request->all(), [
                'name' => 'nullable|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'required|string|max:20',
                'subject' => 'nullable|string|max:255',
                'message' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $support = Support::create($validator->validated());

            // You might want to send an email notification here

            return response()->json([
                'success' => true,
                'message' => 'Support request submitted successfully',
                'data' => $support
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error in support submission', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error submitting support request'
            ], 500);
        }
    }
}
