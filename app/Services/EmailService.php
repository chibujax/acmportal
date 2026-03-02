<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    /**
     * Send a plain-text email. Returns true on success, false on failure.
     */
    public function send(string $to, string $subject, string $body): bool
    {
        try {
            Mail::raw($body, function ($message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            });

            Log::info("Email sent. To: {$to}, Subject: {$subject}");
            return true;
        } catch (\Exception $e) {
            Log::error("Email failed. To: {$to}, error: " . $e->getMessage());
            return false;
        }
    }
}
