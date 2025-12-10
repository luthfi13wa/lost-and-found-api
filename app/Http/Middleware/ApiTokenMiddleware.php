<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class ApiTokenMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $authHeader = $request->header('Authorization');

        if (! $authHeader || ! str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $token = substr($authHeader, 7);

        $user = User::where('api_token', $token)->first();

        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Make user available via $request->user() and auth()
        auth()->setUser($user);

        return $next($request);
    }
}
