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
}
