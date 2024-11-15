<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\CoachingPlan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class NoteController extends Controller
{
    /**
     * Get notes for a specific coaching plan
     * 
     * @param int $coaching_plan_id
     * @return JsonResponse
     */
    public function index($coaching_plan_id): JsonResponse
    {
        Log::info('Notes index method called', [
            'coaching_plan_id' => $coaching_plan_id,
            'user_id' => Auth::id(),
        ]);

        try {
            // Find coaching plan first
            $coachingPlan = CoachingPlan::find($coaching_plan_id);

            if (!$coachingPlan) {
                Log::error('Coaching plan not found', [
                    'coaching_plan_id' => $coaching_plan_id
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Coaching plan not found'
                ], 404);
            }

            Log::info('Coaching plan found', [
                'coaching_plan_id' => $coachingPlan->id,
                'coach_id' => $coachingPlan->coach_id,
                'user_id' => $coachingPlan->user_id
            ]);

            // Get notes query
            $notesQuery = Note::where('coaching_plan_id', $coaching_plan_id)
                ->with('user:id,name');

            // If user is not the coach, only show non-private notes
            if (Auth::id() !== $coachingPlan->coach_id) {
                Log::info('User is not coach, filtering private notes', [
                    'user_id' => Auth::id(),
                    'coach_id' => $coachingPlan->coach_id
                ]);
                $notesQuery->where('private', false);
            }

            $notes = $notesQuery->orderBy('created_at', 'desc')->get();

            Log::info('Notes retrieved successfully', [
                'notes_count' => $notes->count(),
                'coaching_plan_id' => $coaching_plan_id
            ]);

            return response()->json([
                'success' => true,
                'data' => $notes
            ]);
        } catch (\Exception $e) {
            Log::error('Error in notes index method', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'coaching_plan_id' => $coaching_plan_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching notes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new note
     */
    public function store(Request $request): JsonResponse
    {
        Log::info('Note store method called', [
            'request_all' => $request->all(),
            'user_id' => Auth::id()
        ]);

        try {
            $validated = $request->validate([
                'coaching_plan_id' => 'required|exists:coaching_plans,id',
                'content' => 'required|string',
                'private' => 'boolean'
            ]);

            $coachingPlan = CoachingPlan::findOrFail($validated['coaching_plan_id']);

            // Only allow coaches to create private notes
            if (isset($validated['private']) && $validated['private'] && Auth::id() !== $coachingPlan->coach_id) {
                Log::warning('Non-coach user attempted to create private note', [
                    'user_id' => Auth::id(),
                    'coach_id' => $coachingPlan->coach_id
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Only coaches can create private notes'
                ], 403);
            }

            $note = Note::create([
                ...$validated,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Note created successfully',
                'data' => $note->load('user:id,name')
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error in note store method', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error creating note: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a note
     */
    public function destroy(Note $note): JsonResponse
    {
        try {
            if (Auth::id() !== $note->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $note->delete();

            return response()->json([
                'success' => true,
                'message' => 'Note deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error in note delete method', [
                'error' => $e->getMessage(),
                'note_id' => $note->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error deleting note: ' . $e->getMessage()
            ], 500);
        }
    }
}
