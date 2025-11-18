<?php

namespace App\Http\Controllers\students\attendance;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $query = Attendance::with([
            'student.program',
            'student.year_level',
            'student.section'
        ]);

        // Date filters
        if ($request->has('from') && !empty($request->from)) {
            $query->whereDate('attendance_date', '>=', $request->from);
        }

        if ($request->has('to') && !empty($request->to)) {
            $query->whereDate('attendance_date', '<=', $request->to);
        }

        // Default to today if no filters
        if (!$request->has('from') && !$request->has('to')) {
            $today = Carbon::now();
            $query->whereDate('attendance_date', $today->toDateString());
        }

        // Filter by program
        if ($request->has('program_id') && $request->program_id !== 'all') {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('program_id', $request->program_id);
            });
        }

        // Filter by year level
        if ($request->has('year_level_id') && $request->year_level_id !== 'all') {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('year_level_id', $request->year_level_id);
            });
        }

        // Filter by section
        if ($request->has('section_id') && $request->section_id !== 'all') {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('section_id', $request->section_id);
            });
        }

        // Search by student name or ID
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('student_id', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $attendances = $query->orderBy('attendance_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 10));

        return response()->json([
            'attendances' => $attendances,
            'message' => 'Attendance records retrieved successfully'
        ]);
    }

    public function export(Request $request)
    {
        $query = Attendance::with([
            'student.program',
            'student.year_level',
            'student.section'
        ]);

        // Apply the same filters as index
        if ($request->has('from') && !empty($request->from)) {
            $query->whereDate('attendance_date', '>=', $request->from);
        }

        if ($request->has('to') && !empty($request->to)) {
            $query->whereDate('attendance_date', '<=', $request->to);
        }

        if (!$request->has('from') && !$request->has('to')) {
            $today = Carbon::now();
            $query->whereDate('attendance_date', $today->toDateString());
        }

        if ($request->has('program_id') && $request->program_id !== 'all') {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('program_id', $request->program_id);
            });
        }

        if ($request->has('year_level_id') && $request->year_level_id !== 'all') {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('year_level_id', $request->year_level_id);
            });
        }

        if ($request->has('section_id') && $request->section_id !== 'all') {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('section_id', $request->section_id);
            });
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('student_id', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $attendances = $query->orderBy('attendance_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'attendances' => $attendances,
            'message' => 'Attendance records for export retrieved successfully'
        ]);
    }

    public function store(Request $request)
    {

    }

    public function update(Request $request)
    {

    }

    public function destroy(Request $request)
    {

    }
}
