<?php

namespace App\Http\Controllers\Auth;

use App\Events\Audit\AuditEvent;
use App\Http\Controllers\Controller;
use App\Models\Activity\AuditLogs;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!auth()->attempt($data)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = auth()->user();
        $token = $user->createToken('auth_token')->plainTextToken;
        $currentIp = $request->ip();

        // Check if IP address has changed from last login
        $lastLogin = AuditLogs::where('user_id', $user->id)
            ->where('action', 'login')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastLogin && $lastLogin->ip_address !== $currentIp) {
            // IP address changed - send notification to the user
            NotificationService::notifyUser(
                $user->id,
                'warning',
                'New Login Location Detected',
                "Your account was accessed from a new IP address: {$currentIp}. Previous IP: {$lastLogin->ip_address}. If this wasn't you, please secure your account immediately.",
                [
                    'icon' => 'shield',
                    'data' => [
                        'old_ip' => $lastLogin->ip_address,
                        'new_ip' => $currentIp,
                        'login_time' => now()->toDateTimeString()
                    ]
                ]
            );
        }

         $audit_logs = AuditLogs::create([
                    'user_id'    => $user->id,
                    'action'     => 'login',
                    'entity_type'=> 'User',
                    'entity_id'  => $user->id,
                    'ip_address' => $request->ip(),
                    'description'=> 'User logged in successfully',
                ]);
        broadcast(new AuditEvent($audit_logs));

        return response()->json([
            'message' => 'User logged in successfully',
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function isEmailExist(Request $request)
    {
        $exists = User::where('email', $request->email)->exists();
        return response()->json(['exists' => $exists]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        $user->currentAccessToken()->delete();

        // Log the action
        $audit_logs = AuditLogs::create([
            'user_id'    => $user->id,
            'action'     => 'logout',
            'entity_type'=> 'User',
            'entity_id'  => $user->id,
            'ip_address' => $request->ip(),
            'description'=> 'User logged out successfully',
        ]);

        broadcast(new AuditEvent($audit_logs));

        return response()->json([
            'message' => 'User logged out successfully'
        ]);
    }


    public function user(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
            'message' => 'User retrieved successfully'
        ]);
    }

    public function getUserLoginHistory(Request $request)
    {
        $user = $request->user();

        // Get login audit logs for the current user from the last few weeks
        $loginHistory = AuditLogs::where('user_id', $user->id)
            ->where('action', 'login')
            ->where('created_at', '>=', now()->subWeeks(4))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get the last login (second most recent, as the current session is the most recent)
        $lastLogin = AuditLogs::where('user_id', $user->id)
            ->where('action', 'login')
            ->orderBy('created_at', 'desc')
            ->skip(1)
            ->first();

        // Count total logins
        $totalLogins = AuditLogs::where('user_id', $user->id)
            ->where('action', 'login')
            ->count();

        return response()->json([
            'login_history' => $loginHistory,
            'last_login' => $lastLogin,
            'total_logins' => $totalLogins,
            'message' => 'Login history retrieved successfully'
        ]);
    }
}
