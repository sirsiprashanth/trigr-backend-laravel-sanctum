<?php

namespace App\Http\Controllers;

use App\Models\FocusArea;
use Illuminate\Http\Request;

class FocusAreaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(FocusArea::all());
    }

    /**
     * Display a listing of the prepopulated focus areas.
     */
    public function prepopulatedFocusAreas()
    {
        $focusAreas = FocusArea::where('is_user_created', false)->get();
        return response()->json($focusAreas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $focusArea = FocusArea::create($request->all());
        return response()->json($focusArea, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(FocusArea $focusArea)
    {
        return response()->json($focusArea);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FocusArea $focusArea)
    {
        $focusArea->update($request->all());
        return response()->json($focusArea);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FocusArea $focusArea)
    {
        $focusArea->delete();
        return response()->json(null, 204);
    }
}
