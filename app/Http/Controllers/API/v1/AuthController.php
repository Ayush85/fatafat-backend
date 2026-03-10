<?php

namespace App\Http\Controllers\API\v1;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * @group Users
 *
 * User authentication and profile management endpoints.
 */
class AuthController extends Controller
{
    /**
     * Register User
     *
     * @name Register User
     */
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

    /**
     * Login User
     *
     * @name Login User
     */
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

    /**
     * Refresh Access Token
     *
     * @name Refresh Access Token
     */
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

    /**
     * Request Password Reset OTP
     *
     * @name Request Password Reset OTP
     */
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

            // Store in password_resets table
            DB::table('password_resets')->where('email', $request->email)->delete();
            DB::table('password_resets')->insert([
                'email' => $request->email,
                'token' => $otp,
                'created_at' => now(),
            ]);

            return $this->successResponse([
                'message' => 'OTP sent to your email',
                'otp' => $otp, // Keep for dev, ensure frontend handles debug mode or remove for prod
                'email' => $user->email,
            ], 'Check your email for password reset code');

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Verify Password Reset OTP
     *
     * @name Verify Password Reset OTP
     */
    public function verifyOTP(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
                'code' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors());
            }

            $record = DB::table('password_resets')
                ->where('email', $request->email)
                ->where('token', $request->code)
                ->first();

            if (!$record) {
                return $this->errorResponse('Invalid OTP', 400);
            }

            // Check expiry (e.g. 15 minutes)
            if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
                return $this->errorResponse('OTP expired', 400);
            }

            return $this->successResponse([
                'verified' => true,
                'email' => $request->email,
                'code' => $request->code,
            ], 'OTP verified successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Reset Password
     *
     * @name Reset Password
     */
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

            // Verify OTP again before resetting
            $record = DB::table('password_resets')
                ->where('email', $request->email)
                ->where('token', $request->code)
                ->first();

            if (!$record) {
                return $this->errorResponse('Invalid or expired OTP', 400);
            }

            if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
                return $this->errorResponse('OTP expired', 400);
            }

            $user = User::where('email', $request->email)->first();

            $user->update([
                'password' => Hash::make($request->password),
            ]);

            // Clear the token
            DB::table('password_resets')->where('email', $request->email)->delete();

            return $this->successResponse(null, 'Password reset successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Login With Google
     *
     * @name Login With Google
     */
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

    /**
     * Login With Facebook
     *
     * @name Login With Facebook
     */
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

    /**
     * Logout User
     *
     * @name Logout User
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return $this->successResponse(null, 'Logged out successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get Current User
     *
     * @name Get Current User
     */
    public function me(Request $request)
    {
        try {
            return $this->successResponse(new UserResource($request->user()));

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update User Profile
     *
     * @name Update User Profile
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email,' . $user->id,
                'contact_number' => 'nullable|string|max:20',
                'date_of_birth' => 'nullable|date',
                'address' => 'nullable|string|max:500',
                'institute_name' => 'nullable|string|max:255',
                'photo' => 'nullable|image|max:2048', // 2MB Max
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors());
            }

            $data = $request->only([
                'name',
                'email',
                'contact_number',
                'date_of_birth',
                'address',
                'institute_name',
            ]);

            // Handle Photo Upload
            if ($request->hasFile('photo')) {
                // Delete old avatar if exists?
                // For now, just upload new one.
                // Path: uploads/avatars/users/{id}/
                $path = $request->file('photo')->store('uploads/avatars/users/' . $user->id, 'public');
                $data['avatar'] = basename($path);
            }

            $user->update($data);

            return $this->successResponse(new UserResource($user), 'Profile updated successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }
}
