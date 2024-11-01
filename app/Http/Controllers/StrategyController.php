<?php

namespace App\Http\Controllers;

use App\Models\Strategy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StrategyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Strategy::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::info('Incoming request data:', $request->all());

        $validatedData = $request->validate([
            'names' => 'required|array',
            'names.*' => 'required|string|max:255',
        ]);

        $strategies = [];
        foreach ($validatedData['names'] as $name) {
            $strategyData = ['name' => $name];
            $strategies[] = Strategy::create($strategyData);
        }

        return response()->json($strategies, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Strategy $strategy)
    {
        return response()->json($strategy);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Strategy $strategy)
    {
        $strategy->update($request->all());
        return response()->json($strategy);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Strategy $strategy)
    {
        $strategy->delete();
        return response()->json(null, 204);
    }
}
