<?php

namespace App\Domains\Auth\Controllers;

use App\Domains\Users\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

class AuthController extends Controller
{
    protected function guard(): JWTGuard
    {
        /** @var JWTGuard */
        return auth('api');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ]);

        $user = User::create($request->only(['name', 'email', 'password']));
        $token = $this->guard()->login($user);

        return $this->respondWithToken($token);
    }

    public function login(Request $request)
    {
        $credentials = $request->only(['email', 'password']);

        if (! $token = $this->guard()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function me()
    {
        return response()->json($this->guard()->user());
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,'.auth()->id(),
            'advance_alerts_time' => 'sometimes|integer|min:0|max:365',
            'advance_alerts_mileage' => 'sometimes|integer|min:0|max:100000',
            'preferred_distance_unit' => 'sometimes|string|in:km,mi',
        ]);

        $user = $this->guard()->user();
        $user->update($data);

        return response()->json($user);
    }

    public function refresh(Request $request)
    {
        try {
            $token = $request->bearerToken();
            if (! $token) {
                return response()->json(['error' => 'Token not provided'], 401);
            }
            $newToken = $this->guard()->setToken($token)->refresh();

            return $this->respondWithToken($newToken);
        } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'Token not refreshable'], 401);
        }
    }

    public function logout()
    {
        $this->guard()->logout();

        return response()->json(['message' => 'Logged out']);
    }

    protected function respondWithToken(string $token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->factory()->getTTL() * 60,
        ]);
    }
}
