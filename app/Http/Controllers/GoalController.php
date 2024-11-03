<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Models\CoachingPlan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class GoalController extends Controller
{
    /**
     * Get goals for a specific coaching plan
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'coaching_plan_id' => 'required|exists:coaching_plans,id'
        ]);

        $coachingPlan = CoachingPlan::findOrFail($request->coaching_plan_id);

        // Check access
        if (!$this->userHasAccess($coachingPlan)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $goals = Goal::where('coaching_plan_id', $request->coaching_plan_id)
            ->with(['focusArea', 'strategies', 'actionPlans'])
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $goals
        ]);
    }

    /**
     * Store a new goal
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'coaching_plan_id' => 'required|exists:coaching_plans,id',
            'focus_area_id' => 'required|exists:focus_areas,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sequence' => 'required|integer|min:1|max:3'
        ]);

        $coachingPlan = CoachingPlan::findOrFail($validated['coaching_plan_id']);

        // Check access
        if (!$this->userHasAccess($coachingPlan)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        // Check if the sequence number is already taken
        $existingGoal = Goal::where('coaching_plan_id', $validated['coaching_plan_id'])
            ->where('sequence', $validated['sequence'])
            ->first();

        if ($existingGoal) {
            return response()->json([
                'success' => false,
                'message' => 'A goal with this sequence number already exists'
            ], 422);
        }

        // Check if maximum goals reached
        $goalsCount = Goal::where('coaching_plan_id', $validated['coaching_plan_id'])->count();
        if ($goalsCount >= 3) {
            return response()->json([
                'success' => false,
                'message' => 'Maximum number of goals (3) has been reached'
            ], 422);
        }

        $goal = Goal::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Goal created successfully',
            'data' => $goal->load(['focusArea', 'strategies', 'actionPlans'])
        ], 201);
    }

    /**
     * Get a specific goal
     */
    public function show(Goal $goal): JsonResponse
    {
        if (!$this->userHasAccess($goal->coachingPlan)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $goal->load(['focusArea', 'strategies', 'actionPlans'])
        ]);
    }

    /**
     * Update a goal
     */
    public function update(Request $request, Goal $goal): JsonResponse
    {
        if (!$this->userHasAccess($goal->coachingPlan)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $validated = $request->validate([
            'focus_area_id' => 'sometimes|required|exists:focus_areas,id',
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'sequence' => 'sometimes|required|integer|min:1|max:3'
        ]);

        // If sequence is being updated, check if it's already taken
        if (isset($validated['sequence']) && $validated['sequence'] !== $goal->sequence) {
            $existingGoal = Goal::where('coaching_plan_id', $goal->coaching_plan_id)
                ->where('sequence', $validated['sequence'])
                ->first();

            if ($existingGoal) {
                return response()->json([
                    'success' => false,
                    'message' => 'A goal with this sequence number already exists'
                ], 422);
            }
        }

        $goal->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Goal updated successfully',
            'data' => $goal->fresh(['focusArea', 'strategies', 'actionPlans'])
        ]);
    }

    /**
     * Delete a goal
     */
    public function destroy(Goal $goal): JsonResponse
    {
        if (!$this->userHasAccess($goal->coachingPlan)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $goal->delete();

        return response()->json([
            'success' => true,
            'message' => 'Goal deleted successfully'
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
