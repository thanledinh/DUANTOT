<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTokenIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Kiểm tra xem người dùng có quyền truy cập API admin không
        if (!$request->user() || !$request->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 411);
        }

        return $next($request);
    }
}
