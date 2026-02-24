<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    // ── Show forgot-password form ─────────────────────────────

    public function showForgotForm()
    {
        return view('auth.forgot-password');
    }

    // ── Accept phone/email, route to email link or SMS OTP ───

    public function sendReset(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
        ]);

        $identifier = trim($request->identifier);

        // Find user by phone or email (case-insensitive email)
        $user = User::where('phone', $identifier)
            ->orWhere('email', strtolower($identifier))
            ->first();

        // Generic error — never reveal whether account exists
        $notFoundMsg = 'If an account with that phone/email exists, you will receive reset instructions.';

        if (! $user) {
            return back()->with('status', $notFoundMsg);
        }

        if ($user->email) {
            // ── Path A: email reset link ──────────────────────
            $token = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                ['token' => Hash::make($token), 'created_at' => now()]
            );

            $resetUrl = route('password.reset.form', [
                'token' => $token,
                'email' => $user->email,
            ]);

            Mail::send('emails.password-reset', [
                'user'     => $user,
                'resetUrl' => $resetUrl,
            ], function ($m) use ($user) {
                $m->to($user->email)
                  ->subject('Reset Your ACM Portal Password');
            });

            return back()->with('status', $notFoundMsg);
        }

        // ── Path B: SMS OTP (no email on account) ────────────
        // Rate-limit: max 1 OTP per 2 minutes per phone
        $recent = DB::table('password_reset_otps')
            ->where('phone', $user->phone)
            ->where('created_at', '>=', now()->subMinutes(2))
            ->exists();

        if ($recent) {
            return back()->withErrors([
                'identifier' => 'An OTP was recently sent to this number. Please wait 2 minutes before requesting another.',
            ]);
        }

        // Delete old OTPs for this phone
        DB::table('password_reset_otps')->where('phone', $user->phone)->delete();

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('password_reset_otps')->insert([
            'phone'      => $user->phone,
            'otp'        => Hash::make($otp),
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
        ]);

        $this->sendSmsOtp($user->phone, $otp);

        // Store phone in session so OTP form knows who to verify
        session(['reset_phone' => $user->phone]);

        return redirect()->route('password.otp.form')
            ->with('status', 'A 6-digit code has been sent to your registered phone number.');
    }

    // ── Show OTP entry form ───────────────────────────────────

    public function showOtpForm(Request $request)
    {
        if (! session('reset_phone')) {
            return redirect()->route('password.forgot');
        }

        return view('auth.verify-otp');
    }

    // ── Verify OTP ────────────────────────────────────────────

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $phone = session('reset_phone');

        if (! $phone) {
            return redirect()->route('password.forgot')
                ->withErrors(['otp' => 'Session expired. Please start again.']);
        }

        $record = DB::table('password_reset_otps')
            ->where('phone', $phone)
            ->whereNull('used_at')
            ->where('expires_at', '>=', now())
            ->orderByDesc('created_at')
            ->first();

        if (! $record || ! Hash::check($request->otp, $record->otp)) {
            return back()->withErrors(['otp' => 'Invalid or expired code. Please try again.']);
        }

        // Mark OTP as used
        DB::table('password_reset_otps')
            ->where('id', $record->id)
            ->update(['used_at' => now()]);

        // Store verified status in session for the reset form
        session(['reset_verified_phone' => $phone]);
        session()->forget('reset_phone');

        return redirect()->route('password.reset.form');
    }

    // ── Show new-password form ────────────────────────────────

    public function showResetForm(Request $request)
    {
        // SMS path: verified via OTP
        if (session('reset_verified_phone')) {
            return view('auth.reset-password', ['via' => 'sms']);
        }

        // Email path: token + email in query string
        if ($request->filled('token') && $request->filled('email')) {
            return view('auth.reset-password', [
                'via'   => 'email',
                'token' => $request->token,
                'email' => $request->email,
            ]);
        }

        return redirect()->route('password.forgot');
    }

    // ── Save new password ─────────────────────────────────────

    public function updatePassword(Request $request)
    {
        $request->validate([
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        $via = $request->input('via');

        if ($via === 'sms') {
            // Verify session
            $phone = session('reset_verified_phone');
            if (! $phone) {
                return redirect()->route('password.forgot')
                    ->withErrors(['password' => 'Session expired. Please start again.']);
            }

            $user = User::where('phone', $phone)->first();
            if (! $user) {
                return redirect()->route('password.forgot');
            }

            $user->update(['password' => Hash::make($request->password)]);
            session()->forget('reset_verified_phone');

        } elseif ($via === 'email') {
            $email = $request->input('email');
            $token = $request->input('token');

            $record = DB::table('password_reset_tokens')
                ->where('email', $email)
                ->first();

            if (! $record || ! Hash::check($token, $record->token)) {
                return back()->withErrors(['password' => 'Invalid or expired reset link.']);
            }

            // Token expires in 60 minutes
            if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
                DB::table('password_reset_tokens')->where('email', $email)->delete();
                return back()->withErrors(['password' => 'This reset link has expired. Please request a new one.']);
            }

            $user = User::where('email', $email)->first();
            if (! $user) {
                return redirect()->route('password.forgot');
            }

            $user->update(['password' => Hash::make($request->password)]);
            DB::table('password_reset_tokens')->where('email', $email)->delete();

        } else {
            return redirect()->route('password.forgot');
        }

        return redirect()->route('login')
            ->with('success', 'Password updated successfully. Please sign in.');
    }

    // ── Send SMS via Vonage REST API ──────────────────────────

    private function sendSmsOtp(string $phone, string $otp): void
    {
        $key    = config('services.vonage.key');
        $secret = config('services.vonage.secret');
        $from   = config('services.vonage.sms_from', 'ACMPortal');

        if (! $key || ! $secret) {
            // Vonage not configured — log for admin awareness
            \Log::warning("SMS OTP not sent (Vonage not configured). Phone: {$phone}, OTP: {$otp}");
            return;
        }

        try {
            Http::asForm()->post('https://rest.nexmo.com/sms/json', [
                'api_key'    => $key,
                'api_secret' => $secret,
                'to'         => preg_replace('/\D/', '', $phone),
                'from'       => $from,
                'text'       => "Your ACM Portal password reset code is: {$otp}\nExpires in 10 minutes.",
            ]);
        } catch (\Exception $e) {
            \Log::error("Failed to send SMS OTP: " . $e->getMessage());
        }
    }
}
