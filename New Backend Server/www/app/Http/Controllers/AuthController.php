<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Admin;
use App\Services\License;
use App\Services\DatabaseGuard;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('admin_login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::guard('admin')->attempt($credentials)) {
            $user = Auth::guard('admin')->user();

            // License Check: Block non-Super Admins if license is invalid
            if ($user->role !== 'super_admin') {
                if (!License::valid() || !DatabaseGuard::valid()) {
                    Auth::guard('admin')->logout();
                    $request->session()->invalidate();
                    return back()->withErrors([
                        'email' => 'System not activated or Database tampering detected. Contact developer.'
                    ])->onlyInput('email');
                }
            }

            $request->session()->regenerate();
            
            // Redirect based on role
            if ($user->role === 'editor') {
                return redirect()->route('admin.users.index');
            }
            if ($user->role === 'super_admin') {
                return redirect()->route('admin.settings.index');
            }
            if ($user->role === 'viewer') {
                return redirect()->route('admin.reports.index');
            }

            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
