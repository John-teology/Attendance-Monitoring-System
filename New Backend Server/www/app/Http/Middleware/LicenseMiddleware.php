<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Services\License;
use App\Services\DatabaseGuard;

class LicenseMiddleware
{
    public function handle($request, Closure $next)
    {
        // Bypass for Super Admin to allow system recovery/setup
        $user = Auth::guard('admin')->user();
        if ($user && $user->role === 'super_admin') {
            return $next($request);
        }

        if (!License::valid()) {
            abort(403, 'License invalid.');
        }

        if (!DatabaseGuard::valid()) {
            abort(403, 'Database tampering detected.');
        }

        return $next($request);
    }
}
