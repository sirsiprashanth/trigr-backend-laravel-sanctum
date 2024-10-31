<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use Illuminate\Http\Request;

class GoalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Goal::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $goal = Goal::create($request->all());
        return response()->json($goal, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Goal $goal)
    {
        return response()->json($goal);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Goal $goal)
    {
        $goal->update($request->all());
        return response()->json($goal);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Goal $goal)
    {
        $goal->delete();
        return response()->json(null, 204);
    }
}
