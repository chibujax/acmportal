<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SendActivationInvites extends Command
{
    protected $signature = 'members:send-activation-invites
                            {--resend : Re-send to members who were already invited but have not yet activated}';

    protected $description = 'Send account activation SMS links to members who have not yet set their portal password.';

    public function handle(SmsService $sms): int
    {
        $query = User::where('role', '!=', 'super_admin')->where('status', 'active')->whereNull('portal_activated_at');

        if (! $this->option('resend')) {
            $query->whereNull('activation_invited_at');
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            $this->info('No members without portal access to invite.');
            return Command::SUCCESS;
        }

        $this->info("Found {$users->count()} member(s) to invite.");

        $sent    = 0;
        $failed  = 0;
        $skipped = 0;

        foreach ($users as $user) {
            if (! $user->phone) {
                $this->warn("  ⚠ No phone for #{$user->id} {$user->name} — skipped.");
                Log::warning("SendActivationInvites: user #{$user->id} ({$user->name}) has no phone — skipped.");
                $skipped++;
                continue;
            }

            $token = Str::random(64);

            $user->update([
                'activation_token'             => $token,
                'activation_token_expires_at'  => now()->addDays(7),
                'activation_invited_at'        => now(),
            ]);

            $url     = route('activate.form', ['token' => $token]);
            $message = "ACM Portal: Hi {$user->name}, activate your member account and set your password here: {$url}";

            if ($sms->send($user->phone, $message)) {
                $this->line("  ✓ Sent to {$user->name} ({$user->phone})");
                Log::info("SendActivationInvites: SMS sent to user #{$user->id} ({$user->name})");
                $sent++;
            } else {
                $this->warn("  ✗ SMS failed for {$user->name} ({$user->phone})");
                Log::error("SendActivationInvites: SMS failed for user #{$user->id} ({$user->name})");
                $failed++;
            }
        }

        $this->info("Done. Sent: {$sent}, Failed: {$failed}, Skipped (no phone): {$skipped}.");
        Log::info("SendActivationInvites completed. Sent: {$sent}, Failed: {$failed}, Skipped: {$skipped}.");

        return Command::SUCCESS;
    }
}
