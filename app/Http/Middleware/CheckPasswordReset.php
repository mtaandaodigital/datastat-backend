<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPasswordReset
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Check if user needs to reset password
            if ($user->needsPasswordReset()) {
                // Allow access to password reset routes
                $allowedRoutes = [
                    'password.request',
                    'password.email',
                    'password.reset',
                    'password.update',
                    'logout',
                    'filament.admin.auth.password-reset.request',
                    'filament.admin.auth.password-reset.reset',
                ];
                
                if (!in_array($request->route()->getName(), $allowedRoutes)) {
                    // For API requests, return JSON response
                    if ($request->expectsJson()) {
                        return response()->json([
                            'message' => 'Password reset required',
                            'requires_password_reset' => true
                        ], 403);
                    }
                    
                    // For web requests, redirect to password reset
                    return redirect()->route('password.request')
                        ->with('warning', 'You must reset your password before continuing.');
                }
            }
        }
        
        return $next($request);
    }
}