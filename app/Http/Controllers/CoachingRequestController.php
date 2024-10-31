<?php

namespace App\Http\Controllers;

use App\Models\CoachingRequest;
use Illuminate\Http\Request;

class CoachingRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return CoachingRequest::all();
    }

    public function fetchCoachingRequests()
    {
        return CoachingRequest::where('status', 'pending')->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'topic' => 'required|string|max:255',
            'preferred_date' => 'required|date',
            'preferred_time' => 'required|date_format:H:i:s',
            'user_id' => 'required|exists:users,id',
            'status' => 'required|in:pending,accepted,cancelled',
        ]);

        $coachingRequest = CoachingRequest::create($validatedData);

        return response()->json($coachingRequest, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return CoachingRequest::findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $coachingRequest = CoachingRequest::findOrFail($id);

        if ($coachingRequest->status !== 'pending') {
            return response()->json(['error' => 'The appointment has already been picked up'], 400);
        }

        $validatedData = $request->validate([
            'topic' => 'sometimes|required|string|max:255',
            'preferred_date' => 'sometimes|required|date',
            'preferred_time' => 'sometimes|required|date_format:H:i:s',
            'user_id' => 'sometimes|required|exists:users,id',
            'coach_id' => 'sometimes|required|exists:users,id',
            'status' => 'sometimes|required|in:pending,accepted,cancelled',
        ]);

        $coachingRequest->update($validatedData);

        return response()->json($coachingRequest, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $coachingRequest = CoachingRequest::findOrFail($id);
        $coachingRequest->delete();

        return response()->json(null, 204);
    }
}
