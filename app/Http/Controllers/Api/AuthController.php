<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RegisterUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use App\Traits\ApiResponse;
use App\Http\Resources\UserResource;
use App\Http\Requests\Api\LoginUserRequest;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(RegisterUserRequest $request): JsonResponse
    {

        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'push_token' => $validated['push_token'] ?? null,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse(
            new UserResource($user),
            'User registered successfully',
            201,
            [
                'token' => $token,
            ]
        );
    }

    public function login(LoginUserRequest $request)
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return $this->errorResponse('Invalid credentials', 401);
        }

        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse(
            new UserResource($user),
            'User logged in successfully',
            200,
            [
                'token' => $token,
            ]
        );
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return $this->errorResponse('User not authenticated', 401);
        }

        $user->tokens()->delete();

        return $this->successResponse(
            null,
            'User logged out successfully',
            200
        );
    }

    public function me(Request $request)
    {
        return $this->successResponse(
            new UserResource($request->user()),
            'User information retrieved successfully',
            200
        );
    }

    /**
     * Update user's push notification token
     *
     * Used by mobile/web clients to register their device for push notifications.
     * The push token is typically obtained from:
     * - Firebase Cloud Messaging (FCM)
     * - OneSignal
     * - Web Push API
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updatePushToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'push_token' => ['required', 'string', 'max:500'],
        ], [
            'push_token.required' => 'Push token is required.',
            'push_token.string' => 'Push token must be a string.',
            'push_token.max' => 'Push token may not be greater than 500 characters.',
        ]);

        $request->user()->update(['push_token' => $validated['push_token']]);

        return $this->successResponse(
            new UserResource($request->user()),
            'Push token updated successfully',
            200
        );
    }

    /**
     * Clear user's push notification token
     *
     * Used by clients to unsubscribe from push notifications.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function clearPushToken(Request $request): JsonResponse
    {
        $request->user()->update(['push_token' => null]);

        return $this->successResponse(
            null,
            'Push token cleared successfully',
            200
        );
    }
}
