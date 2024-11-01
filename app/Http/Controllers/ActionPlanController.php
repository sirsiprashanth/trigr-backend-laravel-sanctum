<?php

namespace App\Http\Controllers;

use App\Models\ActionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ActionPlanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(ActionPlan::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::info('Request data:', $request->all());

        $validatedData = $request->validate([
            'actionPlans' => 'required|array',
            'actionPlans.*.days_of_week' => 'nullable|array',
            'actionPlans.*.days_of_week.*' => 'nullable|string',
            'actionPlans.*.description' => 'required|string',
            'actionPlans.*.frequency' => 'nullable|string',
            'actionPlans.*.name' => 'required|string|max:255',
            'actionPlans.*.specific_datetime' => 'nullable|string',
        ]);

        $actionPlans = [];

        foreach ($validatedData['actionPlans'] as $data) {
            $data['days_of_week'] = json_encode($data['days_of_week']); // Encode days_of_week as JSON
            $actionPlan = ActionPlan::create($data);
            $actionPlans[] = $actionPlan;
        }

        return response()->json($actionPlans, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ActionPlan $actionPlan)
    {
        return response()->json($actionPlan);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ActionPlan $actionPlan)
    {
        $actionPlan->update($request->all());
        return response()->json($actionPlan);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ActionPlan $actionPlan)
    {
        $actionPlan->delete();
        return response()->json(null, 204);
    }
}
