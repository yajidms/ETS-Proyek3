<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $payload = $request->attributes->get('jwt_payload');

        if (! is_array($payload) || ! array_key_exists('role', $payload)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (! in_array($payload['role'], $roles, true)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
