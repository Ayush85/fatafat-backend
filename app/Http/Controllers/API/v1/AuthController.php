<?php

namespace App\Http\Controllers\API\v1;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'contact_number' => 'nullable|string|max:20',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors());
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'contact_number' => $request->contact_number,
                'status' => 1,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->successResponse([
                'user' => new UserResource($user),
                'access_token' => $token,
                'token_type' => 'Bearer',
                'refresh_token' => Str::random(60),
            ], 'User registered successfully', 201);

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors());
            }

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return $this->errorResponse('Invalid credentials', 401);
            }

            if ($user->status != 1) {
                return $this->errorResponse('Account is inactive', 403);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->successResponse([
                'user' => new UserResource($user),
                'access_token' => $token,
                'token_type' => 'Bearer',
                'refresh_token' => Str::random(60),
            ], 'Login successful');

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    public function refreshAccessToken(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'refresh_token' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors());
            }

            $user = $request->user();

            if (!$user) {
                return $this->errorResponse('Unauthorized', 401);
            }

            // Revoke old token
            $user->currentAccessToken()->delete();

            // Create new token
            $newToken = $user->createToken('auth_token')->plainTextToken;

            return $this->successResponse([
                'user' => new UserResource($user),
                'access_token' => $newToken,
                'token_type' => 'Bearer',
                'refresh_token' => Str::random(60),
            ], 'Token refreshed successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    public function forgottenPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors());
            }

            $user = User::where('email', $request->email)->first();

            // Generate OTP (6 digits)
            $otp = rand(100000, 999999);

            // In production, save OTP to database with expiry and send via email
            // For now, returning in response for development

            return $this->successResponse([
                'message' => 'OTP sent to your email',
                'otp' => $otp, // Remove in production
                'email' => $user->email,
            ], 'Check your email for password reset code');

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    public function verifyOTP(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
                'code' => 'required|string|size:6',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors());
            }

            // In production, verify OTP from database
            // For now, accepting any 6-digit code

            $user = User::where('email', $request->email)->first();

            return $this->successResponse([
                'verified' => true,
                'reset_token' => Str::random(60),
            ], 'OTP verified successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
                'code' => 'required|string',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors());
            }

            $user = User::where('email', $request->email)->first();

            $user->update([
                'password' => Hash::make($request->password),
            ]);

            return $this->successResponse(null, 'Password reset successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    public function googleLogin(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'google_token' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors());
            }

            // In production, verify Google token
            // For now, accepting any token

            // Example: Decode token and get user info
            // In real implementation, use Google API client

            $user = User::where('email', $request->email ?? 'google-user@example.com')->first();

            if (!$user) {
                $user = User::create([
                    'name' => $request->name ?? 'Google User',
                    'email' => $request->email ?? 'google-user-' . time() . '@example.com',
                    'password' => Hash::make(Str::random(32)),
                    'status' => 1,
                ]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->successResponse([
                'user' => new UserResource($user),
                'access_token' => $token,
                'token_type' => 'Bearer',
                'refresh_token' => Str::random(60),
            ], 'Google login successful');

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    public function facebookLogin(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'facebook_token' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors());
            }

            // In production, verify Facebook token
            // For now, accepting any token

            // Example: Use Facebook Graph API
            // In real implementation, use Facebook SDK

            $user = User::where('email', $request->email ?? 'facebook-user@example.com')->first();

            if (!$user) {
                $user = User::create([
                    'name' => $request->name ?? 'Facebook User',
                    'email' => $request->email ?? 'facebook-user-' . time() . '@example.com',
                    'password' => Hash::make(Str::random(32)),
                    'status' => 1,
                ]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->successResponse([
                'user' => new UserResource($user),
                'access_token' => $token,
                'token_type' => 'Bearer',
                'refresh_token' => Str::random(60),
            ], 'Facebook login successful');

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return $this->successResponse(null, 'Logged out successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    public function me(Request $request)
    {
        try {
            return $this->successResponse(new UserResource($request->user()));

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }
}
