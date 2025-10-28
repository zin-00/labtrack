<?php

namespace App\Http\Controllers\Auth;

use App\Events\Audit\AuditEvent;
use App\Http\Controllers\Controller;
use App\Models\Activity\AuditLogs;
use App\Models\User;
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
}
