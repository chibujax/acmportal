<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivationController extends Controller
{
    public function showForm(string $token)
    {
        $user = $this->resolveToken($token);

        if (! $user) {
            return view('auth.activate', ['expired' => true]);
        }

        return view('auth.activate', [
            'expired' => false,
            'token'   => $token,
            'name'    => $user->name,
        ]);
    }

    public function activate(Request $request, string $token)
    {
        $user = $this->resolveToken($token);

        if (! $user) {
            return view('auth.activate', ['expired' => true]);
        }

        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'password'                    => $request->password,
            'portal_activated_at'         => now(),
            'activation_token'            => null,
            'activation_token_expires_at' => null,
        ]);

        Auth::login($user);

        return redirect()->route('member.dashboard')
            ->with('success', "Welcome, {$user->name}! Your account has been activated.");
    }

    private function resolveToken(string $token): ?User
    {
        $user = User::where('activation_token', $token)
            ->whereNull('portal_activated_at')
            ->first();

        if (! $user) {
            return null;
        }

        if (! $user->activation_token_expires_at || $user->activation_token_expires_at->isPast()) {
            return null;
        }

        return $user;
    }
}
