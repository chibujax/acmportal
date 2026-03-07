<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    protected int $maxAttempts = 3;
    protected int $decaySeconds = 300; // 5 minutes

    public function login(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        $key = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($key, $this->maxAttempts)) {
            throw ValidationException::withMessages([
                'login' => 'Too many login attempts. Please try again later.',
            ]);
        }

        // Accept phone or email
        $field    = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $credentials = [$field => $request->login, 'password' => $request->password];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, $this->decaySeconds);

            throw ValidationException::withMessages([
                'login' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        $user = Auth::user();

        if ($user->status === 'suspended') {
            Auth::logout();
            return back()->withErrors(['login' => 'Your account has been suspended. Please contact an administrator.']);
        }

        return redirect()->intended($user->isSuperAdmin()
            ? route('admin.dashboard')
            : route('member.dashboard'));
    }

    private function throttleKey(Request $request): string
    {
        return strtolower($request->input('login')) . '|' . $request->ip();
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
