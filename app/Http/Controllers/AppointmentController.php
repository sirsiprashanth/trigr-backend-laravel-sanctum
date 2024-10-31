<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AppointmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Appointment::all();
    }

    /**
     * Fetch user appointments.
     */
    public function fetchUserAppointments($id)
    {
        $today = now()->toDateString();
        $appointments = Appointment::where(function ($query) use ($id) {
            $query->where('created_by_user_id', $id)
                ->orWhere('created_for_user_id', $id);
        })->where('preferred_date', '>=', $today)->get();

        return response()->json($appointments, 200);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::info('Received appointment creation request', [
            'data' => $request->all(),
            'timestamp' => now()->toDateTimeString()
        ]);
        try {
            $validatedData = $request->validate([
                'topic' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|required|string',
                'meeting_link' => 'sometimes|required|string|max:255',
                'preferred_date' => 'sometimes|required|date',
                'preferred_time' => 'sometimes|required|date_format:H:i:s',
                'created_by_user_id' => 'sometimes|required|exists:users,id',
                'created_for_user_id' => 'sometimes|required|exists:users,id',
                'status' => 'sometimes|required|in:pending,scheduled,confirmed,cancelled,completed,no show,rescheduled',
                'coaching_plan_id' => 'sometimes|required|exists:coaching_plans,id',
            ]);

            $appointment = Appointment::create($validatedData);
            return response()->json($appointment, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed: ' . json_encode($e->errors()));
            return response()->json(['error' => 'Validation failed', 'messages' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Appointment creation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Appointment creation failed'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return Appointment::findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $appointment = Appointment::findOrFail($id);

        $validatedData = $request->validate([
            'topic' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'meeting_link' => 'sometimes|required|string|max:255',
            'preferred_date' => 'sometimes|required|date',
            'preferred_time' => 'sometimes|required|date_format:H:i:s',
            'created_by_user_id' => 'sometimes|required|exists:users,id',
            'created_for_user_id' => 'sometimes|required|exists:users,id',
            'status' => 'sometimes|required|in:pending,scheduled,confirmed,cancelled,completed,no show,rescheduled',
            'coaching_plan_id' => 'sometimes|required|exists:coaching_plans,id',
        ]);

        $appointment->update($validatedData);

        return response()->json($appointment, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->delete();

        return response()->json(null, 204);
    }
}
