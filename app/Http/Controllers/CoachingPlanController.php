<?php

namespace App\Http\Controllers;

use App\Models\CoachingPlan;
use App\Models\CoachingPlanTarget;
use Illuminate\Http\Request;

class CoachingPlanController extends Controller
{
    public function index()
    {
        return CoachingPlan::with('targets')->get();
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'contract_terms' => 'nullable|string',
            'price' => 'nullable|numeric',
            'status' => 'required|in:pending,in progress,completed,cancelled',
            'coach_id' => 'required|exists:users,id',
            'user_id' => 'required|exists:users,id',
            'targets' => 'nullable|array',
            'targets.*.focus_area_id' => 'nullable|exists:focus_areas,id',
            'targets.*.goal_id' => 'nullable|exists:goals,id',
            'targets.*.strategy_id' => 'nullable|exists:strategies,id',
            'targets.*.action_plan_id' => 'nullable|exists:action_plans,id',
        ]);

        $coachingPlan = CoachingPlan::create($validatedData);

        if (isset($validatedData['targets'])) {
            foreach ($validatedData['targets'] as $target) {
                $target['coaching_plan_id'] = $coachingPlan->id;
                CoachingPlanTarget::create($target);
            }
        }

        return response()->json($coachingPlan->load('targets'), 201);
    }

    public function show($id)
    {
        return CoachingPlan::with('targets')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date',
            'contract_terms' => 'sometimes|required|string',
            'price' => 'sometimes|required|numeric',
            'status' => 'sometimes|required|in:pending,in progress,completed,cancelled',
            'coach_id' => 'sometimes|required|exists:users,id',
            'user_id' => 'sometimes|required|exists:users,id',
            'targets' => 'nullable|array',
            'targets.*.id' => 'nullable|exists:coaching_plan_targets,id',
            'targets.*.focus_area_id' => 'nullable|exists:focus_areas,id',
            'targets.*.goal_id' => 'nullable|exists:goals,id',
            'targets.*.strategy_id' => 'nullable|array', // Validate as array
            'targets.*.action_plan_id' => 'nullable|exists:action_plans,id',
        ]);

        $coachingPlan = CoachingPlan::findOrFail($id);
        $coachingPlan->update($validatedData);

        if (isset($validatedData['targets'])) {
            foreach ($validatedData['targets'] as $target) {
                if (isset($target['id'])) {
                    $coachingPlanTarget = CoachingPlanTarget::findOrFail($target['id']);
                    if (isset($target['strategy_id']) && is_array($target['strategy_id'])) {
                        // Delete the existing target if strategy_id is an array
                        $coachingPlanTarget->delete();
                        // Create multiple records with the same focus_area_id and goal_id
                        foreach ($target['strategy_id'] as $strategyId) {
                            CoachingPlanTarget::create([
                                'coaching_plan_id' => $coachingPlan->id,
                                'focus_area_id' => $coachingPlanTarget->focus_area_id,
                                'goal_id' => $coachingPlanTarget->goal_id,
                                'strategy_id' => $strategyId,
                                'action_plan_id' => $target['action_plan_id'] ?? null,
                            ]);
                        }
                    } else {
                        $coachingPlanTarget->update($target);
                    }
                } else {
                    if (isset($target['strategy_id']) && is_array($target['strategy_id'])) {
                        // Create multiple records with the same focus_area_id and goal_id
                        foreach ($target['strategy_id'] as $strategyId) {
                            CoachingPlanTarget::create([
                                'coaching_plan_id' => $coachingPlan->id,
                                'focus_area_id' => $target['focus_area_id'],
                                'goal_id' => $target['goal_id'],
                                'strategy_id' => $strategyId,
                                'action_plan_id' => $target['action_plan_id'] ?? null,
                            ]);
                        }
                    } else {
                        $target['coaching_plan_id'] = $coachingPlan->id;
                        CoachingPlanTarget::create($target);
                    }
                }
            }
        }

        return response()->json($coachingPlan->load('targets'));
    }

    public function destroy($id)
    {
        $coachingPlan = CoachingPlan::findOrFail($id);
        $coachingPlan->delete();

        return response()->json(null, 204);
    }
}
