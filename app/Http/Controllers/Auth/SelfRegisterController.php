<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PendingMember;
use App\Models\PendingMemberOtp;
use App\Models\RegistrationToken;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

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
     * Step 2 – look up pending member by phone or email, send OTP.
     */
    public function lookup(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string|max:255',
        ]);

        $identifier = trim($request->identifier);

        $pending = PendingMember::where('phone', $identifier)
            ->orWhere('email', strtolower($identifier))
            ->whereIn('status', ['pending', 'invited'])
            ->first();

        if (! $pending) {
            return back()
                ->withInput()
                ->withErrors(['identifier' => 'We could not find a pending registration for that phone number or email. Please contact the administrator.']);
        }

        // Rate-limit: 1 OTP per 2 minutes per pending member
        $recent = PendingMemberOtp::where('pending_member_id', $pending->id)
            ->where('created_at', '>=', now()->subMinutes(2))
            ->exists();

        if ($recent) {
            return back()
                ->withInput()
                ->withErrors(['identifier' => 'A code was recently sent. Please wait 2 minutes before requesting another.']);
        }

        // Delete old OTPs for this member
        PendingMemberOtp::where('pending_member_id', $pending->id)->delete();

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        PendingMemberOtp::create([
            'pending_member_id' => $pending->id,
            'otp'               => Hash::make($otp),
            'expires_at'        => now()->addMinutes(10),
        ]);

        $sent = $this->sendOtp($pending, $otp);

        if (! $sent) {
            return back()
                ->withInput()
                ->withErrors(['identifier' => 'We found your record but could not send the code. Please try again or contact the administrator.']);
        }

        session(['join_pending_id' => $pending->id]);

        $hint = $pending->email
            ? 'A 6-digit code has been sent to your registered email address.'
            : 'A 6-digit code has been sent to your registered phone number.';

        return redirect()->route('join.otp.form')->with('status', $hint);
    }

    /**
     * Step 3 – show OTP entry form.
     */
    public function showOtp()
    {
        if (! session('join_pending_id')) {
            return redirect()->route('join');
        }

        return view('auth.join-otp');
    }

    /**
     * Step 4 – verify OTP and redirect to registration form.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $pendingId = session('join_pending_id');

        if (! $pendingId) {
            return redirect()->route('join')
                ->withErrors(['otp' => 'Session expired. Please start again.']);
        }

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

        // Generate (or reuse) a registration token and send the user straight to the form
        $regToken = RegistrationToken::generate($pending);

        return redirect()->route('register.form', ['token' => $regToken->token]);
    }

    // ─────────────────────────────────────────────────────────────

    private function sendOtp(PendingMember $pending, string $otp): bool
    {
        $message = "Your ACM Portal registration code is: {$otp}\nExpires in 10 minutes.";

        // Prefer email if available, fall back to SMS
        if ($pending->email) {
            try {
                Mail::raw($message, function ($m) use ($pending) {
                    $m->to($pending->email)
                      ->subject('Your ACM Portal Registration Code');
                });
                return true;
            } catch (\Throwable $e) {
                // fall through to SMS
            }
        }

        if ($pending->phone) {
            return app(SmsService::class)->send($pending->phone, $message);
        }

        return false;
    }
}
