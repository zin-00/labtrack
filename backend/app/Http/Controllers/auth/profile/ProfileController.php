<?php

namespace App\Http\Controllers\auth\profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
            'message' => 'Profile retrieved successfully'
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $request->user()->id,
            'password' => 'sometimes|string|min:8|confirmed',
            'role' => 'sometimes|in:admin,faculty,staff,student',
            'status' => 'sometimes|in:active,inactive',

        ]);

        $user = $request->user();
        $user->update($data);

        return response()->json([
            'user' => $user,
            'message' => 'Profile updated successfully'
        ]);
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed:new_password_confirmation',
            'new_password_confirmation' => 'required|string|min:8',
        ]);

        $user = $request->user();

        // Verify current password
        if (!password_verify($data['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect'
            ], 422);
        }

        // Update password
        $user->password = bcrypt($data['new_password']);
        $user->save();

        return response()->json([
            'message' => 'Password changed successfully'
        ]);
    }
}
