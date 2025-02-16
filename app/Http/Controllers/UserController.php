<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the coaches.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCoaches()
    {
        $coaches = User::where('role', 'coach')->get();

        return response()->json([
            'success' => true,
            'data' => $coaches
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $users = User::all();
        return response()->json($users);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:user,coach,admin', // Assuming roles are user, coach, admin
        ]);

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => bcrypt($validatedData['password']),
            'role' => $validatedData['role'],
        ]);

        return response()->json($user, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $validatedData = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
                'password' => 'sometimes|required|string|min:8',
                'role' => 'sometimes|required|string|in:user,coach,admin', // Assuming roles are user, coach, admin
                'experience_years' => 'sometimes|required|integer|min:0',
                'brief_bio' => 'sometimes|required|string|max:1000',
                'clients_coached' => 'sometimes|required|integer|min:0',
                'rating' => 'sometimes|required|numeric|min:0|max:5',
                'client_reviews' => 'sometimes|required|json',
                'photo' => 'sometimes|required|string|max:255',
                'additional_info' => 'sometimes|required|string|max:1000',
                'ai_chat_user_id' => 'sometimes|nullable'
            ]);

            // Convert ai_chat_user_id to string if it exists
            if (isset($validatedData['ai_chat_user_id'])) {
                $validatedData['ai_chat_user_id'] = (string) $validatedData['ai_chat_user_id'];
            }

            if (isset($validatedData['password'])) {
                $validatedData['password'] = bcrypt($validatedData['password']);
            }

            $user->update($validatedData);

            return response()->json([
                'success' => true,
                'data' => $user
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(null, 204);
    }
}
