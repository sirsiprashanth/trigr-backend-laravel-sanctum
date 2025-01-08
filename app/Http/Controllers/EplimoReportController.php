<?php

namespace App\Http\Controllers;

use App\Models\EplimoReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

class EplimoReportController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'report_data' => 'required|json',
            'pdf_report' => 'required|file|mimes:pdf|max:10240', // max 10MB
        ]);

        // Store PDF file
        $pdfPath = $request->file('pdf_report')->store('eplimo-reports', 'public');

        // Create report record
        $report = EplimoReport::create([
            'user_id' => Auth::id(),
            'report_data' => json_decode($request->report_data, true),
            'pdf_path' => $pdfPath
        ]);

        return response()->json([
            'success' => true,
            'data' => $report
        ], 201);
    }

    public function show()
    {
        $report = EplimoReport::where('user_id', Auth::id())
            ->latest()
            ->first();

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'No report found'
            ], 404);
        }

        // Get the base URL without /api
        $baseUrl = url('/');
        if (str_ends_with($baseUrl, '/api')) {
            $baseUrl = substr($baseUrl, 0, -4);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'report_data' => $report->report_data,
                'pdf_url' => $baseUrl . '/storage/' . $report->pdf_path
            ]
        ]);
    }

    public function downloadPdf()
    {
        $report = EplimoReport::where('user_id', Auth::id())
            ->latest()
            ->first();

        if (!$report || !Storage::disk('public')->exists($report->pdf_path)) {
            return response()->json([
                'success' => false,
                'message' => 'PDF report not found'
            ], 404);
        }

        return Storage::disk('public')->download($report->pdf_path, 'eplimo-report.pdf');
    }
}
