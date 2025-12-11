<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthStaff
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->isStaff()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Staff access required.'
            ], 403);
        }

        return $next($request);
    }
}
