<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Exercises the whole pipeline (config/logging.php's tap -> ScrubPiiTap ->
 * ScrubPiiProcessor -> PiiScrubber), not just the PiiScrubber function in
 * isolation - guards against the tap silently getting dropped from config.
 */
class LogScrubbingTest extends TestCase
{
    public function test_written_log_lines_have_phone_and_email_masked(): void
    {
        $path = storage_path('logs/laravel.log');
        if (file_exists($path)) {
            unlink($path);
        }

        Log::channel('single')->error('Vonage SMS delivery failed. To: 447445127890, status: 9, error: Quota Exceeded - rejected');
        Log::channel('single')->info('Email sent. To: abcdfgh@gmail.com, Subject: Test');

        $contents = file_get_contents($path);

        $this->assertStringContainsString('xxxxxxxx7890', $contents);
        $this->assertStringContainsString('xxxxfgh@gmail.com', $contents);
        $this->assertStringNotContainsString('447445127890', $contents);
        $this->assertStringNotContainsString('abcdfgh@gmail.com', $contents);

        unlink($path);
    }
}
