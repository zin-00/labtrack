<?php

namespace App\Http\Controllers\computers;

use App\Http\Controllers\Controller;
use App\Models\ComputerLog;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ComputerLogController extends Controller
{
       public function index(Request $request){
        $query = ComputerLog::with('student', 'computer.laboratory');

        // Date filters
        if ($request->has('from') && !empty($request->from)) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->has('to') && !empty($request->to)) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        // Default to today if no filters
        if (!$request->has('from') && !$request->has('to')) {
            $today = Carbon::now();
            $query->whereBetween('created_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()]);
        }

        $computer_logs = $query->orderBy('created_at', 'desc')->paginate(7);
        $latestScans = ComputerLog::with(['student', 'computer.laboratory'])
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();

        return response()->json([
            'latestScans' => $latestScans,
            'computer_logs' => $computer_logs,
            'message' => 'Computer logs retrieved successfully'
        ]);
    }

     // returns all records without pagination
    public function export(Request $request) {
        $query = ComputerLog::with('student', 'computer.laboratory');

        // Date filters
        if ($request->has('from') && !empty($request->from)) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->has('to') && !empty($request->to)) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        // Default to today if no filters
        if (!$request->has('from') && !$request->has('to')) {
            $today = Carbon::now();
            $query->whereBetween('created_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()]);
        }

        $logs = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'logs' => $logs,
            'message' => 'Logs for export retrieved successfully'
        ]);
    }
}
