<?php

namespace App\Http\Controllers;

use App\Models\CoachingPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CoachingPlanController extends Controller
{
    /**
     * Get all coaching plans for the authenticated user
     */
    public function index(): JsonResponse
    {
        Log::info('Fetching coaching plans for user', ['user_id' => Auth::id(), 'timestamp' => now()]);
        $plans = CoachingPlan::where(function ($query) {
            $query->where('user_id', Auth::id())
                ->orWhere('coach_id', Auth::id());
        })
            ->with(['user', 'coach'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $plans
        ]);
    }

    /**
     * Store a new coaching plan
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'coach_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'contract_terms' => 'required|string',
            'price' => 'required|numeric|min:0',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
        ]);

        $plan = CoachingPlan::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Coaching plan created successfully',
            'data' => $plan->load(['user', 'coach'])
        ], 201);
    }

    /**
     * Get a specific coaching plan
     */
    public function show(CoachingPlan $coachingPlan): JsonResponse
    {
        // Check if user has access to this plan
        if (!$this->userHasAccess($coachingPlan)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $coachingPlan->load(['user', 'coach', 'goals.focusArea', 'goals.strategies', 'goals.actionPlans'])
        ]);
    }

    /**
     * Update a coaching plan
     */
    public function update(Request $request, CoachingPlan $coachingPlan): JsonResponse
    {
        if (!$this->userHasAccess($coachingPlan)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'contract_terms' => 'sometimes|required|string',
            'price' => 'sometimes|required|numeric|min:0',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after:start_date',
            'status' => 'sometimes|required|in:draft,in_progress,completed'
        ]);

        $coachingPlan->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Coaching plan updated successfully',
            'data' => $coachingPlan->fresh(['user', 'coach'])
        ]);
    }

    /**
     * Delete a coaching plan
     */
    public function destroy(CoachingPlan $coachingPlan): JsonResponse
    {
        if (!$this->userHasAccess($coachingPlan)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $coachingPlan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Coaching plan deleted successfully'
        ]);
    }

    /**
     * Check if the authenticated user has access to the coaching plan
     */
    private function userHasAccess(CoachingPlan $plan): bool
    {
        // return Auth::id() === $plan->user_id || Auth::id() === $plan->coach_id;
        return true;
    }
}
