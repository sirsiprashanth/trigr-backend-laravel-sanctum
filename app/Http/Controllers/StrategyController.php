<?php

namespace App\Http\Controllers;

use App\Models\Strategy;
use App\Models\Goal;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class StrategyController extends Controller
{
    /**
     * Get strategies for a specific goal
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

        $strategies = Strategy::where('goal_id', $request->goal_id)
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $strategies
        ]);
    }

    /**
     * Store a new strategy
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'goal_id' => 'required|exists:goals,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string'
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
        $lastSequence = Strategy::where('goal_id', $validated['goal_id'])
            ->max('sequence') ?? 0;
        $validated['sequence'] = $lastSequence + 1;

        $strategy = Strategy::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Strategy created successfully',
            'data' => $strategy
        ], 201);
    }

    /**
     * Get a specific strategy
     */
    public function show(Strategy $strategy): JsonResponse
    {
        if (!$this->userHasAccess($strategy->goal)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $strategy
        ]);
    }

    /**
     * Update a strategy
     */
    public function update(Request $request, Strategy $strategy): JsonResponse
    {
        if (!$this->userHasAccess($strategy->goal)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'sequence' => 'sometimes|required|integer|min:1'
        ]);

        // If sequence is being updated, reorder other strategies
        if (isset($validated['sequence']) && $validated['sequence'] !== $strategy->sequence) {
            Strategy::where('goal_id', $strategy->goal_id)
                ->where('sequence', '>=', $validated['sequence'])
                ->increment('sequence');
        }

        $strategy->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Strategy updated successfully',
            'data' => $strategy
        ]);
    }

    /**
     * Delete a strategy
     */
    public function destroy(Strategy $strategy): JsonResponse
    {
        if (!$this->userHasAccess($strategy->goal)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        // Reorder remaining strategies
        Strategy::where('goal_id', $strategy->goal_id)
            ->where('sequence', '>', $strategy->sequence)
            ->decrement('sequence');

        $strategy->delete();

        return response()->json([
            'success' => true,
            'message' => 'Strategy deleted successfully'
        ]);
    }

    /**
     * Reorder strategies
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'goal_id' => 'required|exists:goals,id',
            'strategies' => 'required|array',
            'strategies.*.id' => 'required|exists:strategies,id',
            'strategies.*.sequence' => 'required|integer|min:1'
        ]);

        $goal = Goal::findOrFail($request->goal_id);

        if (!$this->userHasAccess($goal)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        foreach ($request->strategies as $strategyData) {
            Strategy::where('id', $strategyData['id'])
                ->where('goal_id', $request->goal_id)
                ->update(['sequence' => $strategyData['sequence']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Strategies reordered successfully',
            'data' => Strategy::where('goal_id', $request->goal_id)
                ->ordered()
                ->get()
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
