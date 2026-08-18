<?php

namespace App\Logging;

use App\Support\PiiScrubber;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Masks phone numbers/emails out of every log line before it's written to disk,
 * regardless of which Log:: call site produced it - see App\Support\PiiScrubber.
 */
class ScrubPiiProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(
            message: PiiScrubber::mask($record->message),
            context: $this->maskContext($record->context),
        );
    }

    private function maskContext(array $context): array
    {
        foreach ($context as $key => $value) {
            if (is_string($value)) {
                $context[$key] = PiiScrubber::mask($value);
            } elseif (is_array($value)) {
                $context[$key] = $this->maskContext($value);
            }
        }

        return $context;
    }
}
