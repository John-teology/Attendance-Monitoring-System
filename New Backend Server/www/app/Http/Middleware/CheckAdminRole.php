<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckAdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = Auth::guard('admin')->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // 'admin' role requires strict admin access
        if ($role === 'admin') {
            if (!in_array($user->role, ['admin', 'super_admin'])) {
                abort(403, 'Unauthorized action. You do not have permission to access this page.');
            }
        } 
        // 'editor' role allows admin and editor
        elseif ($role === 'editor') {
            if (!in_array($user->role, ['admin', 'editor', 'super_admin'])) {
                abort(403, 'Unauthorized action.');
            }
        }
        // 'viewer' role allows everyone (assuming viewer is the lowest)
        elseif ($role === 'viewer') {
            // All roles can access viewer level stuff
        }

        return $next($request);
    }
}
