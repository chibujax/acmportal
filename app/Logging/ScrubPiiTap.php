<?php

namespace App\Logging;

use Illuminate\Log\Logger;

/**
 * Registered via the 'tap' array on a logging channel in config/logging.php.
 */
class ScrubPiiTap
{
    public function __invoke(Logger $logger): void
    {
        foreach ($logger->getLogger()->getHandlers() as $handler) {
            $handler->pushProcessor(new ScrubPiiProcessor());
        }
    }
}
