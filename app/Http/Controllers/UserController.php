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
}
