<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }
        
        // Check if this is a Filament admin request
        if ($request->is('admin') || $request->is('admin/*')) {
            return route('filament.admin.auth.login');
        }
        
        // For other requests, redirect to Filament login as well since it's your main auth
        return route('filament.admin.auth.login');
    }
}