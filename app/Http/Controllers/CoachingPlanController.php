<?php

namespace App\Http\Controllers;

use App\Models\CoachingPlan;
use Illuminate\Http\Request;

class CoachingPlanController extends Controller
{
    public function index()
    {
        return CoachingPlan::all();
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
            'focus_area_id' => 'nullable|exists:focus_areas,id',
            'goal_id' => 'nullable|exists:goals,id',
            'strategy_id' => 'nullable|exists:strategies,id',
            'action_plan_id' => 'nullable|exists:action_plans,id',
        ]);

        $coachingPlan = CoachingPlan::create($validatedData);

        return response()->json($coachingPlan, 201);
    }

    public function show($id)
    {
        return CoachingPlan::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $coachingPlan = CoachingPlan::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date',
            'contract_terms' => 'sometimes|required|string',
            'price' => 'sometimes|required|numeric',
            'status' => 'sometimes|required|in:pending,in progress,completed,cancelled',
            'coach_id' => 'sometimes|required|exists:coaches,id',
            'user_id' => 'sometimes|required|exists:users,id',
            'focus_area_id' => 'nullable|exists:focus_areas,id',
            'goal_id' => 'nullable|exists:goals,id',
            'strategy_id' => 'nullable|exists:strategies,id',
            'action_plan_id' => 'nullable|exists:action_plans,id',
        ]);

        $coachingPlan->update($validatedData);

        return response()->json($coachingPlan, 200);
    }

    public function destroy($id)
    {
        $coachingPlan = CoachingPlan::findOrFail($id);
        $coachingPlan->delete();

        return response()->json(null, 204);
    }
}
