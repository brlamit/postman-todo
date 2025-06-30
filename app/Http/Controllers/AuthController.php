<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            // Generate and send OTP for email verification
            $otp = rand(100000, 999999);
            $expiresAt = Carbon::now()->addMinutes(10);
            $user->update([
                'otp' => $otp,
                'otp_expires_at' => $expiresAt,
            ]);

            Mail::raw("Your OTP for email verification is: $otp", function ($message) use ($user) {
                $message->to($user->email)->subject('Email Verification OTP');
            });

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'User registered successfully. Please verify your email with the OTP sent.',
                'user' => $user,
                'token' => $token,
                'otp' => $otp, // Included for testing purposes, remove in production
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Registration failed', 'error' => $e->getMessage()], 500);
        }
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!auth()->attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = auth()->user();

        // Check if email is verified
        if (!$user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email not verified. Please verify your email first.'], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User logged in successfully',
            'user' => $user,
            'token' => $token,
        ], 200);
    }

    public function sendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $user = User::where('email', $request->email)->first();
        $otp = rand(100000, 999999);
        $expiresAt = Carbon::now()->addMinutes(10);

        try {
            $user->update([
                'otp' => $otp,
                'otp_expires_at' => $expiresAt,
            ]);

            Mail::raw("Your OTP is: $otp", function ($message) use ($user) {
                $message->to($user->email)->subject('Password Reset OTP');
            });

            return response()->json(['message' => 'OTP sent to email', 'otp' => $otp], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to send OTP', 'error' => $e->getMessage()], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|numeric|digits:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if (!$user->otp || $user->otp !== (string)$request->otp) { // Ensure string comparison
            return response()->json(['message' => 'Invalid OTP'], 400);
        }

        if (Carbon::parse($user->otp_expires_at)->isPast()) {
            return response()->json(['message' => 'OTP has expired'], 400);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'otp' => null,
            'otp_expires_at' => null,
        ]);

        return response()->json([
            'message' => 'Password reset successfully',
        ], 200);
    }

    public function verifyEmail(Request $request)
    {
        $request->validate([
            'otp' => ['required', 'numeric', 'digits:6'],
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Debug output to inspect values
        \Log::info('Verify Email Debug:', [
            'user_otp' => $user->otp,
            'request_otp' => $request->otp,
            'otp_expires_at' => $user->otp_expires_at,
            'is_past' => Carbon::parse($user->otp_expires_at)->isPast(),
        ]);

        if (!$user->otp || $user->otp !== (string)$request->otp) { // Ensure string comparison
            return response()->json(['message' => 'Invalid OTP'], 400);
        }

        if (Carbon::parse($user->otp_expires_at)->isPast()) {
            return response()->json(['message' => 'OTP has expired'], 400);
        }

        // Verify email and reset OTP fields
        $user->forceFill([
            'email_verified_at' => now(),
            'otp' => null,
            'otp_expires_at' => null,
        ])->save();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Email verified successfully',
            'user' => $user,
            'token' => $token,
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out'], 200);
    }

    public function user(Request $request)
    {
        return response()->json([
            'message' => 'User data retrieved successfully',
            'user' => $request->user(),
        ], 200);
    }


    public function deleteAccount(Request $request){
        $request->validate([
             'email' => 'required|email|exists:users,email',
            'password' => 'required|string',
        ]);

        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully'], 200);
    }
}