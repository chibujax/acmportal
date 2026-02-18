<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PendingMember;
use App\Models\RegistrationToken;
use App\Models\User;
use App\Notifications\EmailVerificationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    /**
     * Show the registration form for a token-based invite.
     */
    public function showForm(string $token)
    {
        $regToken = RegistrationToken::with('pendingMember')
            ->where('token', $token)
            ->first();

        if (! $regToken || ! $regToken->isValid()) {
            return view('auth.register-invalid');
        }

        return view('auth.register', [
            'token'         => $token,
            'pendingMember' => $regToken->pendingMember,
        ]);
    }

    /**
     * Handle registration form submission.
     */
    public function register(Request $request)
    {
        $regToken = RegistrationToken::with('pendingMember')
            ->where('token', $request->token)
            ->first();

        if (! $regToken || ! $regToken->isValid()) {
            return redirect()->route('login')
                ->withErrors(['token' => 'This registration link is invalid or has expired.']);
        }

        $pending = $regToken->pendingMember;

        $request->validate([
            'token'                 => 'required|string',
            'phone'                 => "required|string|unique:users,phone",
            'password'              => 'required|string|min:8|confirmed',
            'email'                 => 'nullable|email|unique:users,email',
        ]);

        DB::transaction(function () use ($request, $pending, $regToken) {
            $user = User::create([
                'name'     => $pending->name,
                'phone'    => $request->phone,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'role'     => 'member',
                'status'   => 'active',
            ]);

            // Mark registration token used
            $regToken->markUsed();

            // Update pending member record
            $pending->update([
                'status'          => 'registered',
                'registered_at'   => now(),
            ]);

            // Send email verification if email provided
            if ($user->email) {
                $user->notify(new EmailVerificationNotification());
            }

            Auth::login($user);
        });

        return redirect()->route('member.dashboard')
            ->with('success', 'Welcome to ACM Portal!' .
                ($request->email ? ' Please check your email to verify your address.' : ''));
    }
}
