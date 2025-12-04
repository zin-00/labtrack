<?php

namespace App\Http\Controllers\RequestAccess;

use App\Events\Audit\AuditEvent;
use App\Events\MainEvent;
use App\Http\Controllers\Controller;
use App\Mail\AccountApprovedMail;
use App\Mail\AccountRejectedMail;
use App\Models\Activity\AuditLogs;
use App\Models\RequestAccess;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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

    public function store(Request $request)
    {
        // Check previous access requests
        $existing = RequestAccess::where('email', $request->email)->first();

        if ($existing) {
            if ($existing->status === 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Your previous request is still pending.',
                ], 409);
            }

            if ($existing->status === 'approved') {
                // Check if user already exists in users table
                $userExists = User::where('email', $request->email)->exists();
                if ($userExists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Your account has already been approved and created.',
                    ], 409);
                }
                // User was approved but doesn't exist in users table → allow resubmit
                $existing->delete();
            }

            if ($existing->status === 'rejected') {
                // Allow reapply → remove old request
                $existing->delete();
            }
        }

    // Validate request
    $validator = Validator::make($request->all(), [
        'id_number' => 'required|string|max:255',
        'fullname' => 'required|string|max:255',
        'email' => 'required|email|max:255',
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

        broadcast(new MainEvent('request-access', 'created', $requestAccess));
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
            'password' => $requestAccess->password,
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

        // Send approval email to the user
        try {
            Mail::to($requestAccess->email)->send(new AccountApprovedMail(
                $requestAccess->fullname,
                $requestAccess->email,
                $requestAccess->role
            ));
        } catch (\Exception $e) {
            Log::error('Failed to send account approval email: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Request approved successfully',
            'user'    => $user,
        ]);
    }

    public function reject(Request $request, $id)
    {
        $requestAccess = RequestAccess::findOrFail($id);

        // Get optional rejection reason from request
        $reason = $request->input('reason', null);

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
            'description' => "RequestAccess #{$requestAccess->id} rejected by admin" . ($reason ? ": {$reason}" : ''),
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

        // Send rejection email to the user
        try {
            Mail::to($requestAccess->email)->send(new AccountRejectedMail(
                $requestAccess->fullname,
                $requestAccess->email,
                $reason
            ));
        } catch (\Exception $e) {
            Log::error('Failed to send account rejection email: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'RequestAccess rejected successfully',
        ]);
    }
}
