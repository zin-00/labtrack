<?php

namespace App\Http\Controllers\computers;

use App\Http\Controllers\Controller;
use App\Models\ComputerLog;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ComputerLogController extends Controller
{
       public function index(Request $request){
        $query = ComputerLog::with('student.program', 'student.year_level', 'student.section', 'computer.laboratory');

        // Date filters
        if ($request->has('from') && !empty($request->from)) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->has('to') && !empty($request->to)) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        // Student filter
        // if ($request->has('student_id') && !empty($request->student_id)) {
        //     $query->where('student_id', $request->student_id);
        // }

        // Program filter
        if ($request->has('program_id') && !empty($request->program_id)) {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('program_id', $request->program_id);
            });
        }

        // Year Level filter
        if ($request->has('year_level_id') && !empty($request->year_level_id)) {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('year_level_id', $request->year_level_id);
            });
        }

        // Section filter
        if ($request->has('section_id') && !empty($request->section_id)) {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('section_id', $request->section_id);
            });
        }

        // Default to today if no filters
        if (!$request->has('from') && !$request->has('to')) {
            $today = Carbon::now();
            $query->whereBetween('created_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()]);
        }

        $computer_logs = $query->orderBy('created_at', 'desc')->paginate(7);
        $latestScans = ComputerLog::with(['student', 'computer.laboratory'])
            ->whereDate('created_at', Carbon::today())
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
        $query = ComputerLog::with('student.program', 'student.year_level', 'student.section', 'computer.laboratory');

        // Date filters
        if ($request->has('from') && !empty($request->from)) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->has('to') && !empty($request->to)) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        // Student filter
        if ($request->has('student_id') && !empty($request->student_id)) {
            $query->where('student_id', $request->student_id);
        }

        // Program filter
        if ($request->has('program_id') && !empty($request->program_id)) {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('program_id', $request->program_id);
            });
        }

        // Year Level filter
        if ($request->has('year_level_id') && !empty($request->year_level_id)) {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('year_level_id', $request->year_level_id);
            });
        }

        // Section filter
        if ($request->has('section_id') && !empty($request->section_id)) {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('section_id', $request->section_id);
            });
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
