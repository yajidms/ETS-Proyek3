<?php

namespace App\Http\Middleware;

use App\Models\Pengguna;
use App\Models\RevokedToken;
use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractToken($request);

        if (! $token) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $secret = config('services.jwt.secret');

        if (! $secret) {
            return response()->json(['message' => 'JWT secret is not configured.'], 500);
        }

        $tokenHash = hash('sha256', $token);

        $revoked = RevokedToken::where('token_hash', $tokenHash)
            ->where('expires_at', '>', now())
            ->exists();

        if ($revoked) {
            return response()->json(['message' => 'Token has been revoked.'], 401);
        }

        try {
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
        } catch (Throwable $exception) {
            return response()->json(['message' => 'Invalid token.', 'error' => $exception->getMessage()], 401);
        }

        $user = Pengguna::find($decoded->sub ?? null);

        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $request->attributes->set('jwt_payload', (array) $decoded);
        $request->attributes->set('auth_user', $user);
        $request->attributes->set('token', $token);
        $request->setUserResolver(static fn () => $user);

        return $next($request);
    }

    private function extractToken(Request $request): ?string
    {
        $auth = $request->header('Authorization');

        if (! $auth || ! str_starts_with($auth, 'Bearer ')) {
            return null;
        }

        return trim(substr($auth, 7));
    }
}
