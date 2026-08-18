<?php

namespace Tests\Unit;

use App\Support\PiiScrubber;
use Tests\TestCase;

class PiiScrubberTest extends TestCase
{
    public function test_redacts_email_addresses(): void
    {
        $this->assertSame(
            'Email failed. To: [redacted-email], error: Connection timed out',
            PiiScrubber::scrub('Email failed. To: member@example.com, error: Connection timed out')
        );
    }

    public function test_redacts_normalized_uk_phone_numbers(): void
    {
        // SmsService normalizes numbers to 44xxxxxxxxxx before logging
        $this->assertSame(
            'Vonage SMS delivery failed. To: [redacted-phone], status: 9, error: Quota Exceeded - rejected',
            PiiScrubber::scrub('Vonage SMS delivery failed. To: 447445123456, status: 9, error: Quota Exceeded - rejected')
        );
    }

    public function test_redacts_raw_and_plus_prefixed_phone_numbers(): void
    {
        $this->assertSame('Call [redacted-phone] now', PiiScrubber::scrub('Call 07445123456 now'));
        $this->assertSame('Call [redacted-phone] now', PiiScrubber::scrub('Call +447445123456 now'));
    }

    public function test_leaves_short_numbers_and_normal_text_untouched(): void
    {
        $this->assertSame(
            'Vonage SMS delivery failed. To: [redacted-phone], status: 9, error: Quota Exceeded - rejected',
            PiiScrubber::scrub('Vonage SMS delivery failed. To: 447445123456, status: 9, error: Quota Exceeded - rejected')
        );
        $this->assertSame('status: 9', PiiScrubber::scrub('status: 9'));
        $this->assertSame('Payment of £60.00 failed', PiiScrubber::scrub('Payment of £60.00 failed'));
    }
}
