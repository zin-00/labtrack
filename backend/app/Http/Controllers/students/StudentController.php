<?php

namespace App\Http\Controllers\students;

use App\Events\Audit\AuditEvent;
use App\Http\Controllers\Controller;
use App\Models\Activity\AuditLogs;
use App\Models\Computer;
use App\Models\ComputerStudent;
use App\Models\Program;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
    public function index(Request $request){
        $students = Student::with(['program', 'year_level','section']);

        // Search filter - search across student_id, first_name, last_name, email
        if($request->has('search') && $request->search){
            $search = $request->search;
            $students->where(function($query) use ($search) {
                $query->where('student_id', 'like', '%' . $search . '%')
                      ->orWhere('first_name', 'like', '%' . $search . '%')
                      ->orWhere('last_name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        // Program filter
        if($request->has('program_id') && $request->program_id !== 'all'){
            $students->where('program_id', $request->program_id);
        }

        // Year level filter
        if($request->has('year_level_id') && $request->year_level_id !== 'all'){
            $students->where('year_level_id', $request->year_level_id);
        }

        // Section filter
        if($request->has('section_id') && $request->section_id !== 'all'){
            $students->where('section_id', $request->section_id);
        }

        // Status filter
        if($request->has('status') && $request->status !== 'all'){
            $students->where('status', $request->status);
        }

        $students = $students->orderBy('created_at', 'desc')->paginate(7);

        return response()->json([
            'students' => $students,
            'message' => 'Students retrieved successfully'
        ]);
    }
    public function store(Request $request){
        $data = $request->validate([
            'student_id'    => ['required', 'string', 'max:255'],
            'rfid_uid'      => ['required', 'string', 'max:255'],
            'first_name'    => ['required', 'string', 'max:255'],
            'middle_name'   => ['required', 'string', 'max:255'],
            'last_name'     => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'max:255'],
            'program_id'    => ['required', 'exists:programs,id'],
            'year_level_id' => ['required', 'exists:year_levels,id'],
            'section_id'    => ['required', 'exists:sections,id'],
            'status'        => ['required', 'in:active,inactive,restricted'],
        ]);

        $student = Student::create($data);

        $audit_logs = AuditLogs::create([
            'user_id'    => $request->user()->id,
            'action'     => 'create',
            'entity_type'=> 'Student',
            'entity_id'  => $student->id,
            'ip_address' => $request->ip(),
            'description'=> 'Added new student' .$student->student_id,
        ]);

        AuditEvent::dispatch($audit_logs);

        return response()->json([
            'message' => 'Student registered successfully',
            'student' => $student
        ], 201);
    }
public function importStudents(Request $request)
{
    $validator = Validator::make($request->all(), [
        'students' => ['required', 'array', 'min:1'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation error',
            'errors' => $validator->errors()
        ], 422);
    }

    $importedCount = 0;
    $skippedCount = 0;
    $errors = [];
    $importedStudents = [];

    DB::beginTransaction();
    try {
        foreach ($request->students as $studentData) {
            // ❌ Skip row if key fields are missing
            if (
                empty($studentData['student_id']) ||
                empty($studentData['rfid_uid']) ||
                empty($studentData['first_name']) ||
                empty($studentData['last_name']) ||
                empty($studentData['email']) ||
                empty($studentData['year_level_id'])
            ) {
                $skippedCount++;
                $errors[] = [
                    'student' => $studentData,
                    'errors' => ['Row skipped because of missing required fields']
                ];
                continue;
            }

            try {
                // ✅ Validate only this student
                $studentValidator = Validator::make($studentData, [
                    'student_id' => ['required', 'string', 'max:255', 'unique:students,student_id'],
                    'rfid_uid'   => ['required', 'string', 'max:255', 'unique:students,rfid_uid'],
                    'first_name' => ['required', 'string', 'max:255'],
                    'middle_name'=> ['nullable', 'string', 'max:255'],
                    'last_name'  => ['required', 'string', 'max:255'],
                    'email'      => ['required', 'email', 'max:255', 'unique:students,email'],
                    'year_level_id' => ['required', 'exists:year_levels,id'],
                ]);

                if ($studentValidator->fails()) {
                    $skippedCount++;
                    $errors[] = [
                        'student' => $studentData,
                        'errors' => $studentValidator->errors()->toArray()
                    ];
                    continue;
                }

                $student = Student::create($studentData);
                $importedStudents[] = $student->only(['id', 'student_id', 'first_name', 'last_name', 'email']);
                $importedCount++;

            } catch (\Exception $e) {
                $skippedCount++;
                $errors[] = [
                    'student' => $studentData,
                    'error' => $e->getMessage()
                ];
            }
        }

        DB::commit();

        // 🔥 Create ONE audit log summarizing the bulk import
        if ($importedCount > 0 || $skippedCount > 0) {
            $auditLog = AuditLogs::create([
                'user_id'     => $request->user()->id,
                'action'      => 'import',
                'entity_type' => 'Student',
                'entity_id'   => null, // bulk, so no single entity
                'ip_address'  => $request->ip(),
                'old_data'    => null,
                'new_data'    => [
                    'imported_count' => $importedCount,
                    'skipped_count'  => $skippedCount,
                    'imported_students' => $importedStudents,
                ],
                'description' => "Bulk student import completed by user {$request->user()->id}",
            ]);

            AuditEvent::dispatch($auditLog);
        }

        return response()->json([
            'message' => 'Bulk import completed',
            'imported_count' => $importedCount,
            'skipped_count' => $skippedCount,
            'errors' => $errors
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'Error during bulk import',
            'error' => $e->getMessage()
        ], 500);
    }
}


   public function update(Request $request, $id)
    {
        $data = $request->validate([
            'student_id'    => ['required', 'string', 'max:255'],
            'rfid_uid'      => ['required', 'string', 'max:255'],
            'first_name'    => ['required', 'string', 'max:255'],
            'middle_name'   => ['required', 'string', 'max:255'],
            'last_name'     => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'max:255'],
            'program_id'    => ['required', 'exists:programs,id'],
            'year_level_id' => ['required', 'exists:year_levels,id'],
            'section_id'    => ['required', 'exists:sections,id'],
            'status'        => ['required', 'in:active,inactive,restricted']
        ]);

        $student = Student::findOrFail($id);
        $oldData = $student->toArray();

        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        $student->update($data);
        $newData = $student->toArray();

        // Only log the fields that actually changed
        $changes = [];
        foreach ($newData as $key => $value) {
            if (array_key_exists($key, $oldData) && $oldData[$key] != $value) {
                $changes[$key] = [
                    'old' => $oldData[$key],
                    'new' => $value
                ];
            }
        }

        if (!empty($changes)) {
            $audit_logs = AuditLogs::create([
                'user_id'     => $request->user()->id,
                'action'      => 'update',
                'entity_type' => 'Student',
                'entity_id'   => $student->id,
                'ip_address'  => $request->ip(),
                'old_data'    => array_column($changes, 'old', 'field'),
                'new_data'    => array_column($changes, 'new', 'field'),
                'description' => 'Updated student record with specific field changes',
            ]);

            AuditEvent::dispatch($audit_logs);
        }

        return response()->json([
            'message' => 'Student updated successfully',
            'student' => $student,
        ]);
    }


    public function destroy(Request $request, $id){
        $student = Student::findOrFail($id);

        if(!$student){
            return response()->json(['message' => 'Student not found'], 404);
        }
        $student->delete();
        $audit_logs = AuditLogs::create([
            'user_id'    => $request->user()->id,
            'action'     => 'delete',
            'entity_type'=> 'Student',
            'entity_id'  => $id,
            'ip_address' => $request->ip(),
            'description'=> 'Deleted student record',
        ]);

        AuditEvent::dispatch($audit_logs);

        return response()->json([
            'message' => 'Student deleted successfully',
            'student' => $student
        ]);
    }

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
}
