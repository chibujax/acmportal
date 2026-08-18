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

    /**
     * Same targets as scrub(), but partially mask instead of fully redacting -
     * e.g. "07445123456" -> "xxxxxxx3456", "abcdfgh@gmail.com" -> "xxxxfgh@gmail.com".
     * Used for local log files (storage/logs/laravel.log), where keeping a trailing
     * few characters visible is useful for cross-referencing without exposing the
     * full contact detail.
     */
    public static function mask(string $text): string
    {
        $text = preg_replace_callback(
            '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/',
            function (array $m): string {
                [$local, $domain] = explode('@', $m[0], 2);
                $visible = min(3, strlen($local));
                return str_repeat('x', strlen($local) - $visible) . substr($local, -$visible) . '@' . $domain;
            },
            $text
        );

        $text = preg_replace_callback(
            '/(?:\+|00)?\d{9,15}/',
            function (array $m): string {
                $digits  = $m[0];
                $visible = min(4, strlen($digits));
                return str_repeat('x', strlen($digits) - $visible) . substr($digits, -$visible);
            },
            $text
        );

        return $text;
    }
}
