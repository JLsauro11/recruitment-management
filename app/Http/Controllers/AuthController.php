<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) return redirect()->route(Auth::user()->role . '.dashboard');

        // Keep staff authentication visually inside the public careers landing page.
        return redirect()->route('careers.index', ['staff_login' => 1]);
    }

    public function login(Request $r)
    {
        $v = $r->validate(['email' => 'required|email', 'password' => 'required']);
        if (!Auth::attempt(['email' => $v['email'], 'password' => $v['password']], $r->boolean('remember'))) throw ValidationException::withMessages(['email' => ['Invalid email or password.']]);
        $r->session()->regenerate();
        if (Auth::user()->status !== 'active') {
            Auth::logout();
            throw ValidationException::withMessages(['email' => ['Your account is inactive.']]);
        }
        return response()->json(['message' => 'Login successful.', 'redirect' => route(Auth::user()->role . '.dashboard')]);
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();
        return redirect()->route('login');
    }
}
