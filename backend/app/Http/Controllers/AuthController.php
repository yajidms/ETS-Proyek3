<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\Pengguna;
use App\Models\RevokedToken;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Throwable;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $user = Pengguna::where('username', $credentials['username'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $secret = config('services.jwt.secret');
        $ttl = (int) config('services.jwt.ttl');

        if (! $secret) {
            return response()->json([
                'message' => 'JWT secret is not configured.',
            ], 500);
        }

        $issuedAt = now();
        $expiresAt = $issuedAt->copy()->addSeconds($ttl);

        $payload = [
            'iss' => config('app.url'),
            'sub' => $user->id_pengguna,
            'username' => $user->username,
            'role' => $user->role,
            'iat' => $issuedAt->timestamp,
            'exp' => $expiresAt->timestamp,
            'jti' => (string) Str::uuid(),
        ];

        $token = JWT::encode($payload, $secret, 'HS256');

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $ttl,
            'user' => [
                'id' => $user->id_pengguna,
                'username' => $user->username,
                'role' => $user->role,
                'nama_depan' => $user->nama_depan,
                'nama_belakang' => $user->nama_belakang,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $this->extractToken($request);

        if (! $token) {
            return response()->json([
                'message' => 'Token not provided.',
            ], 400);
        }

        $secret = config('services.jwt.secret');

        if (! $secret) {
            return response()->json([
                'message' => 'JWT secret is not configured.',
            ], 500);
        }

        try {
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
        } catch (Throwable $exception) {
            return response()->json([
                'message' => 'Invalid token.',
                'error' => $exception->getMessage(),
            ], 400);
        }

        $tokenHash = hash('sha256', $token);

        $expiresAt = isset($decoded->exp) ? Carbon::createFromTimestamp($decoded->exp) : now();

        RevokedToken::updateOrCreate(
            ['token_hash' => $tokenHash],
            ['expires_at' => $expiresAt]
        );

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    private function extractToken(Request $request): ?string
    {
        $authorization = $request->header('Authorization');

        if (! $authorization) {
            return null;
        }

        if (str_starts_with($authorization, 'Bearer ')) {
            return trim(substr($authorization, 7));
        }

        return null;
    }
}
