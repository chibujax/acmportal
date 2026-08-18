<?php

namespace App\Support;

class PiiScrubber
{
    /**
     * Redact things that look like phone numbers or email addresses from a
     * free-text message before it leaves the server (error reporting, etc.).
     * Several services (SmsService, EmailService) embed the recipient's raw
     * contact info directly in their log messages, so this is the one place
     * that needs to catch it rather than relying on every call site.
     */
    public static function scrub(string $text): string
    {
        $text = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '[redacted-email]', $text);
        $text = preg_replace('/(?:\+|00)?\d{9,15}/', '[redacted-phone]', $text);

        return $text;
    }
}
