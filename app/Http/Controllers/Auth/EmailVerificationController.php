<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EmailVerificationController extends Controller
{
    public function verify(string $token)
    {
        $record = DB::table('email_verifications')
            ->where('token', $token)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $record) {
            return view('auth.email-verify-failed');
        }

        User::where('id', $record->user_id)
            ->update(['email_verified_at' => now()]);

        DB::table('email_verifications')
            ->where('token', $token)
            ->update(['verified_at' => now()]);

        return redirect()->route('member.dashboard')
            ->with('success', 'Email address verified successfully!');
    }

    public function resend(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return back()->with('info', 'Your email is already verified.');
        }

        $token = Str::random(64);

        DB::table('email_verifications')->where('user_id', $user->id)->delete();
        DB::table('email_verifications')->insert([
            'user_id'    => $user->id,
            'token'      => $token,
            'expires_at' => now()->addHours(48),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $verifyUrl = route('email.verify', $token);

        try {
            Mail::send('emails.verify-email', [
                'user'      => $user,
                'verifyUrl' => $verifyUrl,
            ], function ($m) use ($user) {
                $m->to($user->email)
                  ->subject('Verify Your Email – ACM Portal');
            });
        } catch (\Exception $e) {
            Log::error('Email verification resend failed', [
                'user_id' => $user->id,
                'email'   => $user->email,
                'error'   => $e->getMessage(),
            ]);

            return back()->withErrors(['email' => 'Failed to send verification email. Please try again later.']);
        }

        return back()->with('success', 'Verification email resent. Please check your inbox.');
    }
}
