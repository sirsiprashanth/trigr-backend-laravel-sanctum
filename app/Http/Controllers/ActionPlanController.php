<?php

namespace App\Http\Controllers;

use App\Models\ActionPlan;
use App\Models\Goal;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ActionPlanController extends Controller
{
    /**
     * Get action plans for a specific goal
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'goal_id' => 'required|exists:goals,id'
        ]);

        $goal = Goal::findOrFail($request->goal_id);

        // Check access
        if (!$this->userHasAccess($goal)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $actionPlans = ActionPlan::where('goal_id', $request->goal_id)
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $actionPlans
        ]);
    }

    /**
     * Store a new action plan
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'goal_id' => 'required|exists:goals,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'required|date|after:today',
            'status' => 'sometimes|in:not_started,in_progress,completed'
        ]);

        $goal = Goal::findOrFail($validated['goal_id']);

        // Check access
        if (!$this->userHasAccess($goal)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        // Set sequence number
        $lastSequence = ActionPlan::where('goal_id', $validated['goal_id'])
            ->max('sequence') ?? 0;
        $validated['sequence'] = $lastSequence + 1;

        // Set default status if not provided
        if (!isset($validated['status'])) {
            $validated['status'] = ActionPlan::STATUS_NOT_STARTED;
        }

        $actionPlan = ActionPlan::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Action plan created successfully',
            'data' => $actionPlan
        ], 201);
    }

    /**
     * Get a specific action plan
     */
    public function show(ActionPlan $actionPlan): JsonResponse
    {
        if (!$this->userHasAccess($actionPlan->goal)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $actionPlan
        ]);
    }

    /**
     * Update an action plan
     */
    public function update(Request $request, ActionPlan $actionPlan): JsonResponse
    {
        if (!$this->userHasAccess($actionPlan->goal)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'sometimes|required|date',
            'status' => 'sometimes|required|in:not_started,in_progress,completed',
            'sequence' => 'sometimes|required|integer|min:1'
        ]);

        // If sequence is being updated, reorder other action plans
        if (isset($validated['sequence']) && $validated['sequence'] !== $actionPlan->sequence) {
            ActionPlan::where('goal_id', $actionPlan->goal_id)
                ->where('sequence', '>=', $validated['sequence'])
                ->increment('sequence');
        }

        $actionPlan->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Action plan updated successfully',
            'data' => $actionPlan
        ]);
    }

    /**
     * Delete an action plan
     */
    public function destroy(ActionPlan $actionPlan): JsonResponse
    {
        if (!$this->userHasAccess($actionPlan->goal)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        // Reorder remaining action plans
        ActionPlan::where('goal_id', $actionPlan->goal_id)
            ->where('sequence', '>', $actionPlan->sequence)
            ->decrement('sequence');

        $actionPlan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Action plan deleted successfully'
        ]);
    }

    /**
     * Reorder action plans
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'goal_id' => 'required|exists:goals,id',
            'action_plans' => 'required|array',
            'action_plans.*.id' => 'required|exists:action_plans,id',
            'action_plans.*.sequence' => 'required|integer|min:1'
        ]);

        $goal = Goal::findOrFail($request->goal_id);

        if (!$this->userHasAccess($goal)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        foreach ($request->action_plans as $actionPlanData) {
            ActionPlan::where('id', $actionPlanData['id'])
                ->where('goal_id', $request->goal_id)
                ->update(['sequence' => $actionPlanData['sequence']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Action plans reordered successfully',
            'data' => ActionPlan::where('goal_id', $request->goal_id)
                ->ordered()
                ->get()
        ]);
    }

    /**
     * Update action plan status
     */
    public function updateStatus(Request $request, ActionPlan $actionPlan): JsonResponse
    {
        if (!$this->userHasAccess($actionPlan->goal)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:not_started,in_progress,completed'
        ]);

        $actionPlan->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Action plan status updated successfully',
            'data' => $actionPlan
        ]);
    }

    /**
     * Check if the authenticated user has access to the goal
     */
    private function userHasAccess(Goal $goal): bool
    {
        // $coachingPlan = $goal->coachingPlan;
        // return Auth::id() === $coachingPlan->user_id || Auth::id() === $coachingPlan->coach_id;
        return true;
    }
}
