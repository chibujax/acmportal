<?php

namespace App\Support;

use Sentry\Event;
use Sentry\EventHint;

class SentryEventScrubber
{
    /**
     * Members' phone numbers and emails are embedded directly in some log messages
     * (SmsService, EmailService) and 'breadcrumbs.logs' attaches recent log lines to
     * every event, so redact both the event itself and its breadcrumbs before anything
     * leaves the server.
     *
     * A static method callable (rather than a Closure) so config/sentry.php stays
     * serializable by `php artisan config:cache` - Closures can't be var_export'd.
     */
    public static function beforeSend(Event $event, ?EventHint $hint): ?Event
    {
        if ($event->getMessage() !== null) {
            $event->setMessage(PiiScrubber::scrub($event->getMessage()));
        }

        foreach ($event->getExceptions() as $exception) {
            $exception->setValue(PiiScrubber::scrub($exception->getValue()));
        }

        $event->setBreadcrumb(array_map(
            fn (\Sentry\Breadcrumb $breadcrumb) => $breadcrumb->getMessage() !== null
                ? $breadcrumb->withMessage(PiiScrubber::scrub($breadcrumb->getMessage()))
                : $breadcrumb,
            $event->getBreadcrumbs()
        ));

        return $event;
    }
}
