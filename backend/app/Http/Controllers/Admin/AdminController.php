<?php

namespace App\Http\Controllers\Admin;

use App\Events\Audit\AuditEvent;
use App\Events\ComputerStatusUpdated;
use App\Events\ComputerUnlockRequested;
use App\Events\MainEvent;
use App\Http\Controllers\Controller;
use App\Models\Activity\AuditLogs;
use App\Models\Computer;
use App\Models\ComputerLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function index (Request $request){
        $query = User::query();

        // Search filter
        if($request->has('search') && $request->search){
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%");
            });
        }

        // Status filter
        if($request->has('status') && $request->status !== 'all'){
            $query->where('status', $request->status);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(7);

        return response()->json([
            'users' => $users,
            'message' => 'Users retrieved successfully'
        ]);
    }


   public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'password_confirmation' => 'required|same:password',
            'status' => 'required|in:active,inactive,restricted',
            'roles' => 'required|in:admin,faculty,superadmin',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'status' => $data['status'],
            'roles' => $data['roles'],
        ]);

        $audit_logs = AuditLogs::create([
            'user_id'    => $request->user()->id,
            'action'     => 'create',
            'entity_type'=> 'User',
            'entity_id'  => $user->id,
            'ip_address' => $request->ip(),
            'description'=> 'Added new user',
        ]);

        AuditEvent::dispatch($audit_logs);
        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'user' => $user,
        ], 201);
    }


public function update(Request $request, $id){
    $data = $request->validate([
        'name' => 'sometimes|required|string|max:255',
        'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
        'password' => 'sometimes|nullable|string|min:8|confirmed',
        'status' => ['sometimes','required', Rule::in(['active','inactive','restricted'])],
        'roles'  => ['sometimes','required', Rule::in(['admin','faculty'])],
    ]);

    if (!empty($data['password'])) {
        $data['password'] = bcrypt($data['password']);
    } else {
        unset($data['password']);
    }

    $user = User::findOrFail($id);
    $oldData = $user->toArray();

    $user->update($data);
    $newData = $user->toArray();

    $changes = [];
    foreach ($newData as $key => $value) {
        if (array_key_exists($key, $oldData) && $oldData[$key] != $value) {
            $changes[$key] = [
                'old' => $oldData[$key],
                'new' => $value
            ];
        }
    }

    if(!empty($changes)){
        $audit_logs = AuditLogs::create([
            'user_id'     => $request->user()->id,
            'action'      => 'update',
            'entity_type' => 'User',
            'entity_id'   => $user->id,
            'ip_address'  => $request->ip(),
            'old_data'    => array_column($changes, 'old', 'field'),
            'new_data'    => array_column($changes, 'new', 'field'),
            'description' => 'Updated user record with specific field changes',
        ]);

        AuditEvent::dispatch($audit_logs);
    }
    return response()->json([
        'message' => 'User updated successfully',
        'user' => $user,
    ]);
}


    public function edit (Request $request, $id){
        $user = User::findOrFail($id);
        return response()->json([
            'user' => $user,
        ]);
    }

    public function delete(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $oldData = $user->toArray();

        $user->delete();

        // 🔹 Log deletion in audit logs
        AuditLogs::create([
            'user_id'     => $request->user()->id,
            'action'      => 'delete',
            'entity_type' => 'User',
            'entity_id'   => $id,
            'ip_address'  => $request->ip(),
            'old_data'    => $oldData,
            'new_data'    => null,
            'description' => 'Deleted user account',
        ]);

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }


     public function unlockByAdmin(Request $request, $id)
    {
        $computer = Computer::findOrFail($id);
        $oldData = ['is_lock' => $computer->is_lock];

        // Unlock directly
        $computer->is_lock = false;
        $computer->save();
        $newData = ['is_lock' => $computer->is_lock];

        // Audit log
        $audit_log = AuditLogs::create([
            'user_id'     => $request->user()->id,
            'action'      => 'unlock_by_admin',
            'entity_type' => 'Computer',
            'entity_id'   => $computer->id,
            'ip_address'  => $request->ip(),
            'old_data'    => $oldData,
            'new_data'    => $newData,
            'description' => "Computer #{$computer->id} unlocked manually by Admin/Teacher ({$request->user()->name})",
        ]);

        //Dispatch events
        AuditEvent::dispatch($audit_log);
        broadcast(new MainEvent('computer', 'unlock', $computer));
        ComputerStatusUpdated::dispatch($computer);
        ComputerUnlockRequested::dispatch($computer, null);

        return response()->json([
            'message'      => 'Computer unlocked by admin successfully',
            'computer'     => $computer,
            'audit_log'    => $audit_log,
        ]);
    }
}
