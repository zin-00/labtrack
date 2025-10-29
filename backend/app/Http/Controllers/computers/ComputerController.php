<?php

namespace App\Http\Controllers\computers;

use App\Events\Audit\AuditEvent;
use App\Events\ComputerCameOnline;
use App\Events\ComputerEvent;
use App\Events\ComputerStatusUpdated;
use App\Events\ComputerUnlockRequested;
use App\Events\ComputerWentOffline;
use App\Events\HeartbeatAck;
use App\Events\SetOnlineEvent;
use App\Events\Student\ScanEvent;
use App\Http\Controllers\Controller;
use App\Models\Activity\AuditLogs;
use App\Models\Computer;
use App\Models\ComputerActivityLog;
use App\Models\ComputerLog;
use App\Models\ComputerStudent;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


class ComputerController extends Controller
{
    public function index(Request $request){
        $computers = Computer::with('laboratory');

        // Search filter - search across computer_number, ip_address, mac_address
        if($request->has('search') && $request->search){
            $search = $request->search;
            $computers->where(function($query) use ($search) {
                $query->where('computer_number', 'like', '%' . $search . '%')
                      ->orWhere('ip_address', 'like', '%' . $search . '%')
                      ->orWhere('mac_address', 'like', '%' . $search . '%');
            });
        }

        // Laboratory filter
        if($request->has('laboratory_id') && $request->laboratory_id !== 'all'){
            $computers->where('laboratory_id', $request->laboratory_id);
        }

        // Status filter
        if($request->has('status') && $request->status !== 'all'){
            $computers->where('status', $request->status);
        }

        $computers = $computers->get();

        return response()->json([
            'computers' => $computers,
            'message' => 'Computers retrieved successfully'
        ]);
    }

    public function showAllComputerWithNullLabId(Request $request){
        $computers = Computer::whereNull('laboratory_id')->get();
        return response()->json([
            'computers' => $computers,
            'message' => 'Computers with null laboratory ID retrieved successfully'
        ]);
    }

    public function store(Request $request){
        $data = $request->validate([
            'computer_number'   => ['required','string', 'max:255'],
            'ip_address'        => ['required', 'string', 'max:255'],
            'status'            => ['required', 'in:active,inactive,maintenance'],
            'laboratory_id'     => ['required','integer'],
            'mac_address'       => ['required', 'string', 'max:255'],
            'is_lock'           => ['required' , 'boolean'],
            'is_online'         => ['required', 'boolean'],
        ]);

        $computer = Computer::create($data);

        $audit_logs = AuditLogs::create([
            'user_id'    => $request->user()->id,
            'action'     => 'create',
            'entity_type'=> 'Computer',
            'entity_id'  => $computer->id,
            'ip_address' => $request->ip(),
            'description'=> 'Inserting new computer' .$computer->computer_number,
        ]);

        ComputerStatusUpdated::dispatch($computer);
        AuditEvent::dispatch($audit_logs);

        return response()->json([
            'message' => 'Computer registered successfully',
            'computer' => $computer
        ], 201);
    }

   public function update(Request $request, $id)
{
    $data = $request->validate([
        'computer_number'   => ['required', 'string', 'max:255', 'unique:computers,computer_number,' . $id],
        'ip_address'        => ['required', 'string','max:255', 'unique:computers,ip_address,' . $id],
        'mac_address'       => ['required', 'string', 'max:255', 'unique:computers,mac_address,' . $id],
        'status'            => ['required', 'in:active,inactive,maintenance'],
        'laboratory_id'     => ['required', 'integer', 'exists:laboratories,id'],
        'is_lock'           => ['required','boolean'],
        'is_online'         => ['required','boolean']
    ]);

    $computer = Computer::find($id);
    if (!$computer) {
        return response()->json(['message' => 'Computer not found'], 404);
    }

    // Save original values before update
    $oldData = $computer->only(array_keys($data));

    // Apply the update
    $computer->update($data);

    // Get only the fields that were changed
    $changes = $computer->getChanges();

    // Build old/new arrays for changed fields only
    $logOldData = [];
    $logNewData = [];

    foreach ($changes as $field => $newValue) {
        if (array_key_exists($field, $oldData)) {
            $logOldData[$field] = $oldData[$field];
            $logNewData[$field] = $newValue;
        }
    }

    // Create audit log only if something changed
    if (!empty($logNewData)) {
        $audit_logs = AuditLogs::create([
            'user_id'    => $request->user()->id,
            'action'     => 'update',
            'entity_type'=> 'Computer',
            'entity_id'  => $computer->id,
            'ip_address' => $request->ip(),
            'old_data'   => $logOldData,
            'new_data'   => $logNewData,
            'description'=> 'Updated computer record',
        ]);

        AuditEvent::dispatch($audit_logs);
    }

    broadcast(new ComputerEvent('update', $computer));

    return response()->json([
        'message' => 'Computer updated successfully',
        'computer' => $computer
    ]);
}


    public function assignLaboratory(Request $request)
    {
        $data = $request->validate([
            'computer_ids'   => 'required|array',
            'computer_ids.*' => 'integer|exists:computers,id',
            'laboratory_id'  => 'required|integer|exists:laboratories,id',
        ]);

        // Fetch affected computers before update
        $computers = Computer::whereIn('id', $data['computer_ids'])->get();

        $oldData = [];
        $newData = [];

        foreach ($computers as $computer) {
            $oldData[$computer->id] = ['laboratory_id' => $computer->laboratory_id];
            $newData[$computer->id] = ['laboratory_id' => $data['laboratory_id']];
        }

        // Perform update
        Computer::whereIn('id', $data['computer_ids'])
            ->update(['laboratory_id' => $data['laboratory_id']]);

        // Store audit log
        $audit_logs = AuditLogs::create([
            'user_id'     => $request->user()->id,
            'action'      => 'update',
            'entity_type' => 'Computer',
            'entity_id'   => $data['computer_ids'],  // array, will be JSON encoded
            'ip_address'  => $request->ip(),
            'old_data'    => $oldData,
            'new_data'    => $newData,
            'description' => 'Assigned computers to laboratory',
        ]);

        AuditEvent::dispatch($audit_logs);

        // Broadcast update per computer
        foreach ($computers as $computer) {
            broadcast(new ComputerEvent('update', $computer->id));
        }

        return response()->json([
            'message' => 'Laboratories assigned successfully',
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $computer = Computer::find($id);
        if (!$computer) {
            return response()->json(['message' => 'Computer not found'], 404);
        }

        $oldData = $computer->toArray();

        $computer->delete();

        // Audit log
        $audit_logs = AuditLogs::create([
            'user_id'    => $request->user()->id,
            'action'     => 'delete',
            'entity_type'=> 'Computer',
            'entity_id'  => $id,
            'ip_address' => $request->ip(),
            'old_data'   => $oldData,
            'new_data'   => null,
            'description'=> 'Deleted computer record',
        ]);

        AuditEvent::dispatch($audit_logs);
        broadcast(new ComputerEvent('delete', $id));

        return response()->json([
            'message' => 'Computer deleted successfully'
        ]);
    }

    // For Unlocking Computers
    public function unlock(Request $request, $id){
        $request->validate([
            'rfid_uid' => 'required|string|max:255',
        ]);

        $student = Student::where('rfid_uid', $request->input('rfid_uid'))->first();
        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        $computer = Computer::findOrFail($id);
        $computer->is_lock = false;
        $computer->save();

        $computer_log =  ComputerLog::create([
                'student_id'   => $student->id,
                'computer_id'  => $computer->id,
                'ip_address'   => $computer->ip_address,
                'mac_address'  => $computer->mac_address,
                'program'      => $student->program?->program_name ?? 'N/A',
                // 'year_level'   => $student->year_level,
                'start_time'   => Carbon::now(),
                'end_time'     => null,
            ]);

        if (!$computer_log) {
            return response()->json(['message' => 'Failed to create computer log'], 500);
        }

        // event(new ComputerUnlockRequested($computer->id, $student->id));
        ComputerStatusUpdated::dispatch($computer->id, $student->id);
        ComputerUnlockRequested::dispatch($computer);

        return response()->json([
            'message' => 'Computer state updated successfully',
            'computer' => $computer,
            'computer_log' => $computer_log,

        ]);

    }
public function isOffline(Request $request, $ip)
{
    $computer = Computer::where("ip_address", $ip)->first();

    if (!$computer) {
        return response()->json([
            "message" => "Computer not found"
        ], 404);
    }

    try {
        DB::beginTransaction();

        $wasOnline = $computer->is_online;

        $computer->update([
            "is_online" => false,
            "is_lock" => true,
        ]);

        // Create activity log
        ComputerActivityLog::create([
            'computer_id' => $computer->id,
            'activity_type' => 'offline',
            'reason' => 'manual_offline',
            'details' => 'Manually set to offline status',
            'ip_address' => $computer->ip_address,
            'logged_at' => now()
        ]);

        // Update the latest active log for this computer
        ComputerLog::where("ip_address", $ip)
            ->whereNull("end_time")
            ->update([
                "end_time" => Carbon::now()
            ]);

        DB::commit();

        if ($wasOnline) {
            broadcast(new ComputerWentOffline($computer, 'manual_offline'));
        }

        broadcast(new ComputerEvent($computer, 'update'));

        return response()->json([
            "message" => "Computer is now offline",
            "computer" => $computer,
            "activity_logged" => true
        ]);

    } catch (\Exception $e) {
        DB::rollBack();

        Log::error("Failed to set computer offline: " . $e->getMessage());

        return response()->json([
            "message" => "Failed to set computer offline",
            "error" => $e->getMessage()
        ], 500);
    }
}

   public function isOnline(Request $request, $ip)
    {
        $computer = Computer::where("ip_address", $ip)->first();

        if (!$computer) {
            return response()->json([
                "message" => "Computer not found"
            ], 404);
        }

        try {
            DB::beginTransaction();

            $wasOffline = !$computer->is_online;

            $computer->update([
                'is_online' => true,
                'is_lock' => true,
                'last_seen' => now(), // Update last_seen when manually set online
            ]);

            // Create activity log
            ComputerActivityLog::create([
                'computer_id' => $computer->id,
                'activity_type' => 'online',
                'reason' => 'manual_online',
                'details' => 'Manually set to online status',
                'ip_address' => $computer->ip_address,
                'logged_at' => now()
            ]);

            // Also create a session start log if needed
            ComputerActivityLog::create([
                'computer_id' => $computer->id,
                'activity_type' => 'session_start',
                'reason' => 'manual_online',
                'details' => 'Computer session started manually',
                'ip_address' => $computer->ip_address,
                'logged_at' => now()
            ]);

            DB::commit();

            // Broadcast using the new specific event
            if ($wasOffline) {
                broadcast(new ComputerCameOnline($computer, 'manual_online'));
            }

            // Also broadcast the generic update event if needed
            broadcast(new ComputerEvent($computer, 'update'));

            return response()->json([
                "message" => "Computer is now online",
                "computer" => $computer,
                "activity_logged" => true,
                "status_changed" => $wasOffline
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Failed to set computer online: " . $e->getMessage());

            return response()->json([
                "message" => "Failed to set computer online",
                "error" => $e->getMessage()
            ], 500);
        }
}

    public function getStatus($ip)
    {
        $computer = Computer::with('laboratory')->where('ip_address', $ip)->first();

        if (!$computer) {
            return response()->json([
                'message' => 'Computer not found'
            ], 404);
        }

        return response()->json([
            'is_online' => $computer->is_online,
            'is_lock' => $computer->is_lock,
            'name' => $computer->laboratory?->name,
            'pc_number' => $computer->computer_number,
        ]);
    }

    public function register_computer(Request $request)
    {
        $data = $request->validate([
            'computer_number' => ['required', 'string', 'max:255'],
            'ip_address' => ['required', 'string', 'max:255', 'unique:computers,ip_address'],
            'mac_address' => ['required', 'string', 'max:255', 'unique:computers,mac_address'],
            'status' => ['required', 'in:active,inactive,maintenance'],
            'is_lock' => ['required', 'boolean'],
            'is_online' => ['required', 'boolean'],
        ]);

        // Check if computer already exists by IP or MAC
        $existing = Computer::where('ip_address', $data['ip_address'])
            ->orWhere('mac_address', $data['mac_address'])
            ->first();

        if ($existing) {
            broadcast(new ComputerEvent($existing, 'update'));

            return response()->json([
                'message' => 'Computer already registered',
                'computer' => $existing,
            ], 200);
        }

        // Create new computer
        $computer = Computer::create($data);

        // ComputerStatusUpdated::dispatch($computer);

        broadcast(new ComputerEvent($computer, 'add'));

        return response()->json([
            'message' => 'Computer registered successfully',
            'computer' => $computer,
        ], 201);
    }

public function unlockAssignedComputer(Request $request){
    $request->validate([
        'rfid_uid' => 'required|string|max:255',
    ]);

    $student = Student::where('rfid_uid', $request->input('rfid_uid'))->first();

    if(!$student){
        return response()->json(['message' => 'Student not found'], 404);
    }

    // Use count() instead of empty() check for collections
    $computers = $student->computers()->get();

    if($computers->count() === 0){
        return response()->json(['message' => 'No computers assigned to this student'], 404);
    }

    $unlockedComputers = [];

    foreach ($computers as $computer) {
        $computer->update(['is_lock' => false]);

        // Create computer log - make sure to include year_level
        $computerLog = ComputerLog::create([
            'student_id' => $student->id,
            'computer_id' => $computer->id,
            'ip_address' => $computer->ip_address,
            'mac_address' => $computer->mac_address,
            'program' => $student->program?->program_name ?? 'N/A',
            'year_level' => $student->year_level ?? 'N/A',
            'start_time' => Carbon::now(),
            'end_time' => null,
        ]);

        $unlockedComputers[] = [
            'id' => $computer->id,
            'computer_number' => $computer->computer_number,
            'ip_address' => $computer->ip_address,
            'log_id' => $computerLog->id
        ];
    }

    ScanEvent::dispatch($student);
    ComputerUnlockRequested::dispatch($computer);

    return response()->json([
        'message' => 'Computers unlocked successfully',
        'computers' => $unlockedComputers, // Return meaningful data
        'student' => [
            'id' => $student->id,
            'name' => $student->first_name . ' ' . $student->last_name,
            'student_id' => $student->student_id
        ]
    ]);
}

public function unlockComputersByLab(Request $request, $labId, $rfid_uid)
{
    // Find the student by RFID
    $student = Student::where('rfid_uid', $rfid_uid)->first();

    if (!$student) {
        return response()->json(['message' => 'Student not found'], 404);
    }

    // Get only the computers assigned to this student in the selected lab
    $computers = Computer::where('laboratory_id', $labId)
        ->whereHas('studentAssignments', function ($q) use ($student) {
            $q->where('student_id', $student->id);
        })
        ->get();

    if ($computers->isEmpty()) {
        return response()->json([
            'message' => 'No computers assigned to this student in the selected laboratory'
        ], 404);
    }

    $unlockedComputers = [];

    foreach ($computers as $computer) {
        // Unlock the computer
        $computer->update(['is_lock' => false]);

        // Create log entry
        $computerLog = ComputerLog::create([
            'student_id'   => $student->id,
            'computer_id'  => $computer->id,
            'ip_address'   => $computer->ip_address,
            'mac_address'  => $computer->mac_address,
            'program'      => $student->program?->program_name ?? 'N/A',
            'year_level'   => $student->year_level ?? 'N/A',
            'start_time'   => now(),
            'end_time'     => null,
        ]);

        $unlockedComputers[] = [
            'id'              => $computer->id,
            'computer_number' => $computer->computer_number,
            'ip_address'      => $computer->ip_address,
            'log_id'          => $computerLog->id,
        ];
    }

    // Dispatch events ONCE per batch
    ScanEvent::dispatch($student);
    ComputerUnlockRequested::dispatch($unlockedComputers);

    return response()->json([
        'message'   => 'Computers unlocked successfully',
        'computers' => $unlockedComputers,
        'student'   => [
            'id'         => $student->id,
            'name'       => $student->first_name . ' ' . $student->last_name,
            'student_id' => $student->student_id,
        ],
    ]);
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

  public function heartbeat($ip)
    {
        $computer = Computer::where('ip_address', $ip)->first();

        if ($computer) {
            $wasOffline = !$computer->is_online;

                $computer->update([
                    'last_seen' => now(),
                    'is_online' => true
                ]);

            if ($wasOffline) {
                ComputerActivityLog::create([
                    'computer_id' => $computer->id,
                    'activity_type' => 'online',
                    'reason' => 'heartbeat_received',
                    'details' => 'Came back online after receiving heartbeat',
                    'ip_address' => $computer->ip_address,
                    'logged_at' => now()
                ]);

                // Broadcast online event
                // broadcast(new ComputerCameOnline($computer));
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Heartbeat received',
                'last_heartbeat' => $computer->last_seen
            ]);
        }

        return response()->json(['error' => 'Computer not found'], 404);
    }

}
