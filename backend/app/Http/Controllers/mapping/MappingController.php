<?php

namespace App\Http\Controllers\mapping;

use App\Http\Controllers\Controller;
use App\Models\Computer;
use App\Models\ComputerStudent;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MappingController extends Controller
{
     public function getUnassignedStudents(Request $request)
        {
            try {
                $computerId = $request->computer_id;
                $yearLevel  = $request->year_level;
                $program    = $request->program;
                $search     = $request->search;

                Log::debug('getUnassignedStudents called with:', [
                    'computer_id' => $computerId,
                    'year_level' => $yearLevel,
                    'program' => $program,
                    'search' => $search
                ]);

                if (!$computerId) {
                    return response()->json(['error' => 'Computer ID is required'], 400);
                }

                // Get the computer and its lab
                $computer = Computer::find($computerId);
                if (!$computer) {
                    return response()->json(['error' => 'Computer not found'], 404);
                }

                $labId = $computer->laboratory_id;
                Log::debug('Computer found:', ['computer' => $computer->toArray(), 'lab_id' => $labId]);

                // Get student_ids already assigned in THIS lab only
                $assignedInLab = ComputerStudent::where('laboratory_id', $labId)
                    ->pluck('student_id')
                    ->toArray();

                Log::debug('Students already assigned in lab ' . $labId . ':', $assignedInLab);

                // Only exclude students in THIS lab
                $query = Student::query()
                    ->when(!empty($assignedInLab), function ($q) use ($assignedInLab) {
                        $q->whereNotIn('id', $assignedInLab);
                    });

                // Apply filters
                if (!empty($yearLevel) && $yearLevel !== 'all') {
                    $query->where('year_level_id', $yearLevel);
                }

                if (!empty($program) && $program !== 'all') {
                    $query->where('program_id', $program);
                }

                if (!empty($search)) {
                    $query->where(function ($q) use ($search) {
                        $q->where('first_name', 'like', "%$search%")
                        ->orWhere('last_name', 'like', "%$search%")
                        ->orWhere('student_id', 'like', "%$search%");
                    });
                }

                $students = $query->orderBy('last_name')->orderBy('first_name')->get();

                Log::debug('Unassigned students found:', [
                    'count' => $students->count(),
                    'students' => $students->pluck('id')->toArray()
                ]);

                return response()->json([
                    'students' => $students,
                    'total'    => $students->count(),
                ]);

            } catch (\Exception $e) {
                Log::error('Error in getUnassignedStudents: ' . $e->getMessage());
                return response()->json(['error' => 'Internal server error'], 500);
            }
        }

        public function bulkAssign(Request $request)
{
    $request->validate([
        'computer_id'   => 'required|exists:computers,id',
        'student_ids'   => 'required|array',
        'student_ids.*' => 'exists:students,id',
    ]);

    $computer = Computer::findOrFail($request->computer_id);
    $labId    = $computer->laboratory_id;

    $conflicts = [];
    $successfulAssignments = [];

    foreach ($request->student_ids as $studentId) {
        // Check if student already assigned in THIS laboratory
        $existsInSameLab = DB::table('computer_students as cs')
            ->join('computers as c', 'c.id', '=', 'cs.computer_id')
            ->where('cs.student_id', $studentId)
            ->where('c.laboratory_id', $labId)
            ->exists();

        if ($existsInSameLab) {
            $student = Student::find($studentId);
            $conflicts[] = $student ? $student->first_name . ' ' . $student->last_name : "Student ID $studentId";
            continue;
        }

        // Otherwise assign student to the selected computer
        ComputerStudent::create([
            'computer_id' => $computer->id,
            'student_id'  => $studentId,
            'laboratory_id' => $computer->laboratory_id,
        ]);

        $successfulAssignments[] = $studentId;
    }

    if (!empty($conflicts)) {
        return response()->json([
            'message'                 => 'Some students could not be assigned due to conflicts in this laboratory.',
            'conflicts'               => $conflicts,
            'successful_assignments'  => $successfulAssignments
        ], 422);
    }

    return response()->json([
        'message'        => 'All students assigned successfully',
        'assigned_count' => count($successfulAssignments)
    ]);
}

    // Unassign students from a computer
    public function bulkUnassignStudents(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'computer_id' => 'required|exists:computers,id',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $computer = Computer::findOrFail($request->computer_id);
        $studentIds = $request->student_ids;

        // Unassign students by setting unassign_at timestamp
        $now = now();
        $unassignedCount = ComputerStudent::where('computer_id', $computer->id)
            ->whereIn('student_id', $studentIds)
            ->whereNull('unassign_at')
            ->update(['unassign_at' => $now]);

        return response()->json([
            'message' => $unassignedCount . ' student(s) unassigned successfully',
            'unassigned_count' => $unassignedCount
        ]);
    }

    // Get available students for bulk assignment
    public function getAvailableStudents(Request $request)
    {
        try {
            $query = Student::with(['program', 'year_level', 'section']);

            // Filter by status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            } else {
                $query->where('status', 'active');
            }

            // Filter by program
            if ($request->filled('program_id')) {
                $query->where('program_id', $request->program_id);
            }

            // Filter by year level
            if ($request->filled('year_level_id')) {
                $query->where('year_level_id', $request->year_level_id);
            }

            // Filter by section
            if ($request->filled('section_id')) {
                $query->where('section_id', $request->section_id);
            }

            // Search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('student_id', 'like', "%$search%")
                      ->orWhere('first_name', 'like', "%$search%")
                      ->orWhere('last_name', 'like', "%$search%");
                });
            }

            // Filter unassigned students for a specific laboratory
            if ($request->filled('unassigned_only') && $request->unassigned_only) {
                if ($request->filled('laboratory_id')) {
                    $labId = $request->laboratory_id;
                    $assignedStudentIds = ComputerStudent::whereHas('computer', function ($q) use ($labId) {
                        $q->where('laboratory_id', $labId);
                    })->pluck('student_id')->toArray();

                    $query->whereNotIn('id', $assignedStudentIds);
                } else {
                    // Get students not assigned to any computer
                    $assignedStudentIds = ComputerStudent::pluck('student_id')
                        ->toArray();
                    $query->whereNotIn('id', $assignedStudentIds);
                }
            }

            $students = $query->orderBy('last_name')->orderBy('first_name')->get();

            return response()->json([
                'students' => $students,
                'total' => $students->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getAvailableStudents: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json([
                'error' => 'Failed to fetch students',
                'message' => $e->getMessage()
            ], 500);
        }
    }    // Get available computers for bulk assignment
    public function getAvailableComputers(Request $request)
    {
        try {
            $query = Computer::with(['laboratory']);

            // Filter by laboratory
            if ($request->filled('laboratory_id')) {
                $query->where('laboratory_id', $request->laboratory_id);
            }

            // Search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('computer_number', 'like', "%$search%")
                      ->orWhere('ip_address', 'like', "%$search%");
                });
            }

            // Filter available computers (not fully assigned)
            if ($request->filled('available_only') && $request->available_only) {
                // Get computer IDs that have active assignments
                $assignedComputerIds = ComputerStudent::pluck('computer_id')
                    ->unique()
                    ->toArray();

                // Exclude computers that are already assigned
                $query->whereNotIn('id', $assignedComputerIds);
            }

            $computers = $query->orderBy('computer_number')->get();

            return response()->json([
                'computers' => $computers,
                'total' => $computers->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getAvailableComputers: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json([
                'error' => 'Failed to fetch computers',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Bulk assign students to computers automatically
    public function bulkAssignAuto(Request $request)
    {
        try {
            $request->validate([
                'student_ids' => 'required|array|min:1',
                'student_ids.*' => 'exists:students,id',
                'computer_ids' => 'required|array|min:1',
                'computer_ids.*' => 'exists:computers,id',
                'mode' => 'in:sequential,random',
                'laboratory_id' => 'nullable|exists:laboratories,id'
            ]);

            $studentIds = $request->student_ids;
            $computerIds = $request->computer_ids;
            $mode = $request->mode ?? 'sequential';

            // Shuffle for random mode
            if ($mode === 'random') {
                shuffle($computerIds);
            }

            $assignments = [];
            $assignedCount = 0;
            $skippedCount = 0;

            // Assign each student to each computer (many-to-many)
            foreach ($studentIds as $studentId) {
                foreach ($computerIds as $computerId) {
                    $computer = Computer::find($computerId);

                    if (!$computer) {
                        continue;
                    }

                    // Check if this exact student-computer pair already exists
                    $exists = ComputerStudent::where('student_id', $studentId)
                        ->where('computer_id', $computerId)
                        ->exists();

                    if ($exists) {
                        $skippedCount++;
                        continue; // Skip if already assigned to this specific computer
                    }

                    $assignments[] = [
                        'student_id' => $studentId,
                        'computer_id' => $computerId,
                        'laboratory_id' => $computer->laboratory_id ?? $request->laboratory_id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];

                    $assignedCount++;
                }
            }

            if (count($assignments) > 0) {
                ComputerStudent::insert($assignments);
            }

            $message = "$assignedCount assignment(s) created successfully";
            if ($skippedCount > 0) {
                $message .= " ($skippedCount skipped - already assigned)";
            }

            return response()->json([
                'message' => $message,
                'count' => $assignedCount,
                'skipped' => $skippedCount,
                'total_students' => count($studentIds),
                'total_computers' => count($computerIds)
            ]);
        } catch (\Exception $e) {
            Log::error('Error in bulkAssignAuto: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json([
                'error' => 'Failed to assign students',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
