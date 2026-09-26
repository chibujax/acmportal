<?php

namespace App\Console\Commands;

use App\Models\NotificationRecipient;
use App\Models\User;
use App\Services\EmailService;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonthlyAbsenteeAlert extends Command
{
    protected $signature   = 'acm:monthly-absentee-alert';
    protected $description = 'Notify configured recipients that the consecutive absentees report is ready to review.';

    public function handle(EmailService $email, SmsService $sms): int
    {
        $recipients = NotificationRecipient::active()->get();
        $url        = url(route('admin.meetings.consecutive-absentees', [], false));

        // ── No recipients configured ────────────────────────────
        if ($recipients->isEmpty()) {
            $this->warn('No active recipients configured.');
            Log::warning('MonthlyAbsenteeAlert: no active recipients configured.');
            $this->alertSuperAdmins(
                $email, $sms,
                'ACM – Cron Alert: No Recipients Configured',
                "The monthly consecutive absentees cron ran but found no active recipients to notify.\n\nPlease add recipients at:\n" . url('/admin/notification-recipients')
            );
            return Command::SUCCESS;
        }

        // ── Send to each recipient ──────────────────────────────
        $sent         = 0;
        $failed       = 0;
        $failedNames  = [];

        foreach ($recipients as $r) {
            if ($r->email) {
                $subject = 'ACM – Monthly Absentee Report Ready';
                $body    = "Hi {$r->name},\n\nThe monthly consecutive absentees report is ready for your review.\n\nPlease log in to the ACM Portal and action it:\n{$url}\n\nACM Portal";
                if ($email->send($r->email, $subject, $body)) {
                    $sent++;
                } else {
                    $failed++;
                    $failedNames[] = "{$r->name} (email: {$r->email})";
                    Log::warning("MonthlyAbsenteeAlert: failed to email {$r->email}");
                }
            }

            if ($r->phone) {
                $message = "ACM: Hi {$r->name}, the monthly consecutive absentees report is ready. Log in to review it.";
                if ($sms->send($r->phone, $message)) {
                    $sent++;
                } else {
                    $failed++;
                    $failedNames[] = "{$r->name} (SMS: {$r->phone})";
                    Log::warning("MonthlyAbsenteeAlert: failed to SMS {$r->phone}");
                }
            }
        }

        $this->info("Monthly absentee alert sent. Sent: {$sent}, Failed: {$failed}.");
        Log::info("MonthlyAbsenteeAlert completed. Sent: {$sent}, Failed: {$failed}.");

        // ── Alert super admins if any sends failed ──────────────
        if ($failed > 0) {
            $failedList = implode("\n  - ", $failedNames);
            $this->alertSuperAdmins(
                $email, $sms,
                'ACM – Cron Alert: Delivery Failures',
                "The monthly absentee alert cron ran but {$failed} notification(s) failed to send:\n\n  - {$failedList}\n\n{$sent} notification(s) delivered successfully.\n\nCheck the application logs for details."
            );
        }

        return Command::SUCCESS;
    }

    /**
     * Notify all super_admin users of a cron issue via email (and SMS if available).
     */
    private function alertSuperAdmins(EmailService $email, SmsService $sms, string $subject, string $body): void
    {
        $superAdmins = User::where('role', 'super_admin')->get();

        if ($superAdmins->isEmpty()) {
            Log::error('MonthlyAbsenteeAlert: no super_admin users found to send fallback alert to.');
            return;
        }

        foreach ($superAdmins as $admin) {
            if ($admin->email) {
                $email->send($admin->email, $subject, $body);
            }
            if ($admin->phone) {
                // SMS is short — just a heads-up to check email/logs
                $sms->send($admin->phone, "ACM Portal: {$subject}. Please check your email or the application logs.");
            }
        }

        Log::info("MonthlyAbsenteeAlert: fallback alert sent to " . $superAdmins->count() . " super admin(s).");
    }
}
