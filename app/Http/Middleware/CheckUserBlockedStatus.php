<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserBlockedStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // return $next($request);
        if (Auth::check() && Auth::user()->isBlocked()) {
            Auth::logout(); // Log them out
            // $request->session()->invalidate();
            // $request->session()->regenerateToken();

            return response()->json([
                'message' => 'Your account has been blocked. You have been logged out.'
            ], 403); // 403 Forbidden
        }

        return $next($request);
    }
}
