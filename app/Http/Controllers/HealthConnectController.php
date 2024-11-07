<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HealthConnectController extends Controller
{
    /**
     * Store data from Google Health Connect.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Log the received data
        $dateTimeIndia = now()->setTimezone('Asia/Kolkata')->toDateTimeString();
        Log::info('[' . $dateTimeIndia . '] Received data from Google Health Connect:', $request->all());

        return response()->json(['message' => 'Data received and logged successfully'], 200);
    }
}
