<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PendingMember;
use App\Models\PendingMemberOtp;
use App\Models\RegistrationToken;
use App\Models\User;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SelfRegisterController extends Controller
{
    /**
     * Step 1 – show lookup form.
     */
    public function showLookup()
    {
        return view('auth.join');
    }

    /**
     * Step 2 – look up by phone or email.
     * Checks pending_members first; falls back to inactive users.
     */
    public function lookup(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string|max:255',
        ]);

        $identifier = trim($request->identifier);

        // ── Path A: pending member ────────────────────────────
        $pending = PendingMember::where('phone', $identifier)
            ->orWhere('email', strtolower($identifier))
            ->whereIn('status', ['pending', 'invited'])
            ->first();

        if ($pending) {
            // Rate-limit: 1 OTP per 2 minutes
            $recent = PendingMemberOtp::where('pending_member_id', $pending->id)
                ->where('created_at', '>=', now()->subMinutes(2))
                ->exists();

            if ($recent) {
                return back()->withInput()->withErrors([
                    'identifier' => 'A code was recently sent. Please wait 2 minutes before requesting another.',
                ]);
            }

            PendingMemberOtp::where('pending_member_id', $pending->id)->delete();

            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            PendingMemberOtp::create([
                'pending_member_id' => $pending->id,
                'otp'               => Hash::make($otp),
                'expires_at'        => now()->addMinutes(10),
            ]);

            $sent = $this->sendOtpToPending($pending, $otp);

            if (! $sent) {
                return back()->withInput()->withErrors([
                    'identifier' => 'We found your record but could not send the code. Please try again or contact the administrator.',
                ]);
            }

            session(['join_pending_id' => $pending->id]);

            $hint = $pending->email
                ? 'A 6-digit code has been sent to your registered email address.'
                : 'A 6-digit code has been sent to your registered phone number.';

            return redirect()->route('join.otp.form')->with('status', $hint);
        }

        // ── Path B: pre-created member who hasn't set a portal password yet ─
        $user = User::where(function ($q) use ($identifier) {
            $q->where('phone', $identifier)
              ->orWhere('email', strtolower($identifier));
        })->whereNull('portal_activated_at')->first();

        if (! $user) {
            // Check if they already have a portal account so we give a helpful message
            $alreadyActive = User::where(function ($q) use ($identifier) {
                $q->where('phone', $identifier)
                  ->orWhere('email', strtolower($identifier));
            })->whereNotNull('portal_activated_at')->exists();

            if ($alreadyActive) {
                return redirect()->route('login')
                    ->with('status', 'You already have an active account. Please log in.');
            }

            return back()->withInput()->withErrors([
                'identifier' => 'We could not find a pending registration for that phone number or email. Please contact the administrator.',
            ]);
        }

        // Rate-limit: 1 OTP per 2 minutes (session-based)
        $sentAt = session('join_user_otp_sent_at');
        if ($sentAt && Carbon::parse($sentAt)->diffInMinutes(now()) < 2) {
            return back()->withInput()->withErrors([
                'identifier' => 'A code was recently sent. Please wait 2 minutes before requesting another.',
            ]);
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        session([
            'join_user_id'          => $user->id,
            'join_user_otp'         => Hash::make($otp),
            'join_user_otp_expires' => now()->addMinutes(10)->toIso8601String(),
            'join_user_otp_sent_at' => now()->toIso8601String(),
        ]);

        $sent = $this->sendOtpToUser($user, $otp);

        if (! $sent) {
            session()->forget(['join_user_id', 'join_user_otp', 'join_user_otp_expires', 'join_user_otp_sent_at']);
            return back()->withInput()->withErrors([
                'identifier' => 'We found your record but could not send the code. Please try again or contact the administrator.',
            ]);
        }

        $hint = $user->email
            ? 'A 6-digit code has been sent to your registered email address.'
            : 'A 6-digit code has been sent to your registered phone number.';

        return redirect()->route('join.otp.form')->with('status', $hint);
    }

    /**
     * Step 3 – show OTP entry form.
     */
    public function showOtp()
    {
        if (! session('join_pending_id') && ! session('join_user_id')) {
            return redirect()->route('join');
        }

        return view('auth.join-otp');
    }

    /**
     * Step 4 – verify OTP.
     * Pending member path → registration form.
     * Inactive user path  → activation form (set password).
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        // ── Path A: pending member ────────────────────────────
        if ($pendingId = session('join_pending_id')) {
            $record = PendingMemberOtp::where('pending_member_id', $pendingId)
                ->whereNull('used_at')
                ->where('expires_at', '>=', now())
                ->orderByDesc('created_at')
                ->first();

            if (! $record || ! Hash::check($request->otp, $record->otp)) {
                return back()->withErrors(['otp' => 'Invalid or expired code. Please try again.']);
            }

            $record->markUsed();

            $pending = PendingMember::find($pendingId);
            session()->forget('join_pending_id');

            $regToken = RegistrationToken::generate($pending);

            return redirect()->route('register.form', ['token' => $regToken->token]);
        }

        // ── Path B: inactive user ─────────────────────────────
        if ($userId = session('join_user_id')) {
            $otpHash = session('join_user_otp');
            $expires = session('join_user_otp_expires');

            if (! $otpHash || Carbon::parse($expires)->isPast()) {
                session()->forget(['join_user_id', 'join_user_otp', 'join_user_otp_expires', 'join_user_otp_sent_at']);
                return redirect()->route('join')
                    ->withErrors(['otp' => 'Your code has expired. Please start again.']);
            }

            if (! Hash::check($request->otp, $otpHash)) {
                return back()->withErrors(['otp' => 'Invalid or expired code. Please try again.']);
            }

            session()->forget(['join_user_id', 'join_user_otp', 'join_user_otp_expires', 'join_user_otp_sent_at']);

            $user  = User::findOrFail($userId);
            $token = Str::random(64);

            $user->update([
                'activation_token'             => $token,
                'activation_token_expires_at'  => now()->addHours(1),
                'activation_invited_at'        => now(),
            ]);

            return redirect()->route('activate.form', ['token' => $token]);
        }

        return redirect()->route('join');
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function sendOtpToPending(PendingMember $pending, string $otp): bool
    {
        $message = "Your ACM Portal registration code is: {$otp}\nExpires in 10 minutes.";

        if ($pending->email) {
            try {
                Mail::raw($message, fn($m) => $m->to($pending->email)->subject('Your ACM Portal Registration Code'));
                return true;
            } catch (\Throwable $e) {
                \Log::error('OTP email failed, falling back to SMS', ['pending_member_id' => $pending->id, 'error' => $e->getMessage()]);
            }
        }

        if ($pending->phone) {
            return app(SmsService::class)->send($pending->phone, $message);
        }

        return false;
    }

    private function sendOtpToUser(User $user, string $otp): bool
    {
        $message = "Your ACM Portal verification code is: {$otp}\nExpires in 10 minutes.";

        if ($user->email) {
            try {
                Mail::raw($message, fn($m) => $m->to($user->email)->subject('Your ACM Portal Verification Code'));
                return true;
            } catch (\Throwable $e) {
                \Log::error('OTP email failed for inactive user, falling back to SMS', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            }
        }

        if ($user->phone) {
            return app(SmsService::class)->send($user->phone, $message);
        }

        return false;
    }
}
