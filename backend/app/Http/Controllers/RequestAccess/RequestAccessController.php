<?php

namespace App\Http\Controllers\RequestAccess;

use App\Events\Audit\AuditEvent;
use App\Http\Controllers\Controller;
use App\Models\Activity\AuditLogs;
use App\Models\RequestAccess;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RequestAccessController extends Controller
{
    public function index (Request $request){
        $requestAccess = RequestAccess::orderBy('created_at', 'desc')->paginate(7);
        return response()->json([
            'requestAccess' => $requestAccess,
            'message' => 'RequestAccess retrieved successfully'
        ]);
    }

    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'id_number' => 'required|string|max:255',
            'fullname' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:request_accesses',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:admin,faculty,staff,student',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $validator->validated();
            unset($data['password_confirmation']);
            $data['password'] = bcrypt($data['password']);
            $data['status'] = 'pending';

            $requestAccess = RequestAccess::create($data);

            // Send notification to all admins about new account request
            NotificationService::broadcast(
                'access_request',
                'New Account Request',
                "{$requestAccess->fullname} ({$requestAccess->email}) has requested a {$requestAccess->role} account.",
                [
                    'link' => '/request-access',
                    'data' => ['request_id' => $requestAccess->id, 'role' => $requestAccess->role]
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Request submitted successfully!',
                'data' => $requestAccess
            ], 201);
        } catch (\Exception $e) {
            Log::error('Request Access Error: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

     public function approve(Request $request, $id)
    {
        $requestAccess = RequestAccess::findOrFail($id);

        // Check if user already exists with this email
        $existingUser = User::where('email', $requestAccess->email)->first();
        if ($existingUser) {
            return response()->json([
                'message' => 'User with this email already exists',
            ], 400);
        }

        $user = User::create([
            'name'     => $requestAccess->fullname,
            'email'    => $requestAccess->email,
            'password' => $requestAccess->password, // already hashed
            'status'   => 'active',
            'roles'    => $requestAccess->role,
        ]);

        $oldData = $requestAccess->toArray();

        $requestAccess->update([
            'status' => 'approved'
        ]);

        $newData = $requestAccess->toArray();

        $audit_log = AuditLogs::create([
            'user_id'     => Auth::user()->id,
            'action'      => 'approve',
            'entity_type' => 'RequestAccess',
            'entity_id'   => $requestAccess->id,
            'ip_address'  => $request->ip(),
            'old_data'    => $oldData,
            'new_data'    => $newData,
            'description' => "RequestAccess #{$requestAccess->id} approved and User #{$user->id} created",
        ]);
        AuditEvent::dispatch($audit_log);

        // Notify the new user that their account has been approved
        NotificationService::notifyUser(
            $user->id,
            'success',
            'Account Approved!',
            'Your account request has been approved. Welcome to LabTrack!',
            ['link' => '/dashboard']
        );

        // Notify admins about the approval
        NotificationService::broadcast(
            'access_request',
            'Account Request Approved',
            "The account request from {$requestAccess->fullname} has been approved by " . Auth::user()->name,
            [
                'link' => '/request-access',
                'data' => ['request_id' => $requestAccess->id, 'user_id' => $user->id]
            ]
        );

        return response()->json([
            'message' => 'Request approved successfully',
            'user'    => $user,
        ]);
    }

    public function reject(Request $request, $id)
    {
        $requestAccess = RequestAccess::findOrFail($id);

        $oldData = $requestAccess->toArray();

        $requestAccess->update([
            'status' => 'rejected'
        ]);

        $newData = $requestAccess->toArray();

        $audit_log = AuditLogs::create([
            'user_id'     => Auth::user()->id,
            'action'      => 'reject',
            'entity_type' => 'RequestAccess',
            'entity_id'   => $requestAccess->id,
            'ip_address'  => $request->ip(),
            'old_data'    => $oldData,
            'new_data'    => $newData,
            'description' => "RequestAccess #{$requestAccess->id} rejected by admin",
        ]);
        AuditEvent::dispatch($audit_log);

        // Notify admins about the rejection
        NotificationService::broadcast(
            'access_request',
            'Account Request Rejected',
            "The account request from {$requestAccess->fullname} has been rejected by " . Auth::user()->name,
            [
                'link' => '/request-access',
                'data' => ['request_id' => $requestAccess->id]
            ]
        );

        return response()->json([
            'message' => 'RequestAccess rejected successfully',
        ]);
    }
}
