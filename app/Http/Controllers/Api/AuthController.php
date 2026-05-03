<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserProfileResource;
use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $credentials['email'])->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $tokens = ApiToken::issuePair($user, 'login', $request->ip(), $request->userAgent());

        return response()->json([
            ...$tokens,
            'user' => new UserProfileResource($user),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'referrer_code' => ['nullable', 'string', 'max:64'],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        event(new Registered($user));

        $tokens = ApiToken::issuePair($user, 'register', $request->ip(), $request->userAgent());

        return response()->json([
            ...$tokens,
            'user' => new UserProfileResource($user),
        ], 201);
    }

    public function refresh(Request $request): JsonResponse
    {
        $data = $request->validate([
            'refresh_token' => ['required', 'string'],
        ]);

        $tokens = ApiToken::rotateRefreshToken($data['refresh_token'], $request->ip(), $request->userAgent());

        if ($tokens === null) {
            throw ValidationException::withMessages([
                'refresh_token' => ['The refresh token is invalid or expired.'],
            ]);
        }

        return response()->json([
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'token_type' => $tokens['token_type'],
            'expires_in' => $tokens['expires_in'],
            'user' => new UserProfileResource($tokens['user']),
        ]);
    }

    public function sendPasswordResetLink(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(['email' => $data['email']]);

        return response()->json([
            'message' => __($status),
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $data,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                ])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json([
            'message' => __($status),
        ]);
    }

    public function oauth(string $provider): JsonResponse
    {
        if (! in_array($provider, ['google', 'apple', 'facebook'], true)) {
            throw ValidationException::withMessages([
                'provider' => ['The selected provider is invalid.'],
            ]);
        }

        return response()->json([
            'message' => sprintf('OAuth login for %s is not connected yet.', $provider),
        ], 501);
    }

    public function logout(Request $request)
    {
        $token = $request->attributes->get('api_token');

        if ($token instanceof ApiToken) {
            $token->revoke();
        }

        if ($request->filled('refresh_token')) {
            $refreshToken = ApiToken::findByPlainText((string) $request->input('refresh_token'), 'refresh');

            if ($refreshToken !== null) {
                $refreshToken->revoke();
            }
        }

        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
        }

        return response()->noContent();
    }
}