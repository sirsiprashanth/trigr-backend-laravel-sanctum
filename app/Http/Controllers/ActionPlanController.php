<?php

namespace App\Http\Controllers;

use App\Models\ActionPlan;
use Illuminate\Http\Request;

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
        $actionPlan = ActionPlan::create($request->all());
        return response()->json($actionPlan, 201);
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
