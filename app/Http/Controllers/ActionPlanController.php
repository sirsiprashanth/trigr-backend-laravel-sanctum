<?php

namespace App\Http\Controllers;

use App\Models\ActionPlan;
use App\Models\Goal;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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

        // Get the count of goals in the coaching plan
        $goalCount = Goal::where('coaching_plan_id', $goal->coaching_plan_id)->count();

        return response()->json([
            'success' => true,
            'message' => 'Action plan created successfully',
            'data' => $actionPlan,
            'goal_count' => $goalCount, // Total number of goals in the coaching plan
            'coaching_plan_id' => $goal->coaching_plan_id
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

    public function getUserActionPlans(Request $request, $user_id): JsonResponse
    {
        Log::info('getUserActionPlans called', [
            'user_id' => $user_id,
            'request' => $request->all()
        ]);

        try {
            // Validate the user ID
            $validator = Validator::make(['user_id' => $user_id], [
                'user_id' => 'required|exists:users,id'
            ]);

            if ($validator->fails()) {
                Log::error('Validation failed', [
                    'errors' => $validator->errors()->toArray()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid user ID',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get all action plans where the user is either the client or coach
            $actionPlans = ActionPlan::with(['goal.coachingPlan'])
                ->whereHas('goal.coachingPlan', function ($query) use ($user_id) {
                    $query->where(function ($q) use ($user_id) {
                        $q->where('user_id', $user_id)
                            ->orWhere('coach_id', $user_id);
                    });
                })
                ->get();

            Log::info('Action plans fetched', [
                'count' => $actionPlans->count(),
                'user_id' => $user_id
            ]);

            // Group action plans by status
            $notStarted = $actionPlans->where('status', ActionPlan::STATUS_NOT_STARTED);
            $inProgress = $actionPlans->where('status', ActionPlan::STATUS_IN_PROGRESS);
            $completed = $actionPlans->where('status', ActionPlan::STATUS_COMPLETED);

            // Calculate summary
            $summary = [
                'total_count' => $actionPlans->count(),
                'not_started_count' => $notStarted->count(),
                'in_progress_count' => $inProgress->count(),
                'completed_count' => $completed->count(),
                'overdue_count' => $actionPlans
                    ->where('status', '!=', ActionPlan::STATUS_COMPLETED)
                    ->where('due_date', '<', now())
                    ->count(),
            ];

            // Transform the data to include goal and coaching plan information
            $transformActionPlans = function ($plans) {
                return $plans->map(function ($plan) {
                    return [
                        'id' => $plan->id,
                        'title' => $plan->title,
                        'description' => $plan->description,
                        'due_date' => $plan->due_date ? $plan->due_date->format('Y-m-d') : null,
                        'status' => $plan->status,
                        'sequence' => $plan->sequence,
                        'goal' => $plan->goal ? [
                            'id' => $plan->goal->id,
                            'title' => $plan->goal->title,
                            'coaching_plan' => $plan->goal->coachingPlan ? [
                                'id' => $plan->goal->coachingPlan->id,
                                'title' => $plan->goal->coachingPlan->title
                            ] : null
                        ] : null
                    ];
                })->values();
            };

            return response()->json([
                'success' => true,
                'data' => [
                    'action_plans' => [
                        'not_started' => $transformActionPlans($notStarted),
                        'in_progress' => $transformActionPlans($inProgress),
                        'completed' => $transformActionPlans($completed),
                    ],
                    'summary' => $summary,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching action plans', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching action plans: ' . $e->getMessage(),
            ], 500);
        }
    }
}
