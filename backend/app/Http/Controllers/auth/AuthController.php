<?php

namespace App\Http\Controllers\Auth;

use App\Events\Audit\AuditEvent;
use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\Activity\AuditLogs;
use App\Models\PasswordResetOtp;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

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

    /**
     * Send OTP to email for password reset
     */
    public function sendResetOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'No account found with this email address.'
            ], 404);
        }

        // Delete any existing OTPs for this email
        PasswordResetOtp::where('email', $request->email)->delete();

        // Generate 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Create new OTP record with 10-minute expiry
        PasswordResetOtp::create([
            'email' => $request->email,
            'otp' => Hash::make($otp),
            'expires_at' => now()->addMinutes(10),
            'verified' => false,
        ]);

        // Send OTP via email
        try {
            Mail::to($request->email)->send(new OtpMail($otp, $user->name));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send OTP email. Please try again later.',
                'error' => $e->getMessage()
            ], 500);
        }

        return response()->json([
            'message' => 'OTP has been sent to your email address.',
            'email' => $request->email
        ]);
    }

    /**
     * Verify OTP code
     */
    public function verifyResetOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);

        $passwordReset = PasswordResetOtp::where('email', $request->email)
            ->where('verified', false)
            ->latest()
            ->first();

        if (!$passwordReset) {
            return response()->json([
                'message' => 'No OTP request found for this email.'
            ], 404);
        }

        if ($passwordReset->isExpired()) {
            $passwordReset->delete();
            return response()->json([
                'message' => 'OTP has expired. Please request a new one.'
            ], 400);
        }

        if (!Hash::check($request->otp, $passwordReset->otp)) {
            return response()->json([
                'message' => 'Invalid OTP code.'
            ], 400);
        }

        // Mark OTP as verified
        $passwordReset->update(['verified' => true]);

        return response()->json([
            'message' => 'OTP verified successfully. You can now reset your password.',
            'verified' => true
        ]);
    }

    /**
     * Reset password after OTP verification
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Check if OTP was verified
        $passwordReset = PasswordResetOtp::where('email', $request->email)
            ->where('verified', true)
            ->latest()
            ->first();

        if (!$passwordReset) {
            return response()->json([
                'message' => 'Please verify your OTP first.'
            ], 400);
        }

        // Check if the verification hasn't expired (give extra 5 minutes after verification)
        if ($passwordReset->updated_at->addMinutes(5)->isPast()) {
            $passwordReset->delete();
            return response()->json([
                'message' => 'Session expired. Please request a new OTP.'
            ], 400);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found.'
            ], 404);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->password)
        ]);

        // Delete the OTP record
        $passwordReset->delete();

        // Log the action
        AuditLogs::create([
            'user_id' => $user->id,
            'action' => 'password_reset',
            'entity_type' => 'User',
            'entity_id' => $user->id,
            'ip_address' => $request->ip(),
            'description' => 'User reset their password',
        ]);

        return response()->json([
            'message' => 'Password has been reset successfully. You can now login with your new password.'
        ]);
    }

    /**
     * Resend OTP code
     */
    public function resendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // Reuse the sendResetOtp method
        return $this->sendResetOtp($request);
    }
}
