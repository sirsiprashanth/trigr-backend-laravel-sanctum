<?php

namespace App\Http\Controllers;

use App\Models\FocusArea;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FocusAreaController extends Controller
{
    /**
     * Get all focus areas, with option to filter predefined ones
     */
    public function index(Request $request): JsonResponse
    {
        $query = FocusArea::query();

        // Filter by type if specified
        if ($request->has('type')) {
            if ($request->type === 'predefined') {
                $query->predefined();
            } elseif ($request->type === 'custom') {
                $query->custom();
            }
        }

        $focusAreas = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $focusAreas
        ]);
    }

    /**
     * Store a new focus area
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:focus_areas,name',
            'description' => 'nullable|string',
            'is_predefined' => 'boolean'
        ]);

        // Default to custom focus area if not specified
        if (!isset($validated['is_predefined'])) {
            $validated['is_predefined'] = false;
        }

        $focusArea = FocusArea::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Focus area created successfully',
            'data' => $focusArea
        ], 201);
    }

    /**
     * Get a specific focus area
     */
    public function show(FocusArea $focusArea): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $focusArea->load('goals')
        ]);
    }

    /**
     * Update a focus area
     */
    public function update(Request $request, FocusArea $focusArea): JsonResponse
    {
        // Only allow updating custom focus areas
        if ($focusArea->is_predefined) {
            return response()->json([
                'success' => false,
                'message' => 'Predefined focus areas cannot be modified'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:focus_areas,name,' . $focusArea->id,
            'description' => 'nullable|string'
        ]);

        $focusArea->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Focus area updated successfully',
            'data' => $focusArea
        ]);
    }

    /**
     * Delete a focus area
     */
    public function destroy(FocusArea $focusArea): JsonResponse
    {
        // Only allow deleting custom focus areas
        if ($focusArea->is_predefined) {
            return response()->json([
                'success' => false,
                'message' => 'Predefined focus areas cannot be deleted'
            ], 403);
        }

        // Check if focus area is being used in any goals
        if ($focusArea->goals()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Focus area is in use and cannot be deleted'
            ], 422);
        }

        $focusArea->delete();

        return response()->json([
            'success' => true,
            'message' => 'Focus area deleted successfully'
        ]);
    }
}
