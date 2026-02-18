<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\EmailVerificationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmailVerificationController extends Controller
{
    public function verify(Request $request, string $token)
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

        $user->notify(new EmailVerificationNotification());

        return back()->with('success', 'Verification email resent. Please check your inbox.');
    }
}
