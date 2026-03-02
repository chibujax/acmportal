<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private string $provider;

    public function __construct()
    {
        $this->provider = config('services.sms.provider', 'vonage');
    }

    /**
     * Send an SMS. Returns true on API acceptance, false on any failure.
     */
    public function send(string $to, string $message): bool
    {
        $to = preg_replace('/\D/', '', $to);

        // Normalise to UK E.164 digits (without leading +)
        if (str_starts_with($to, '44')) {
            // already correct: 447xxxxxxxxx
        } elseif (str_starts_with($to, '0')) {
            $to = '44' . substr($to, 1);   // 07xxx → 447xxx
        } elseif (strlen($to) === 10) {
            $to = '44' . $to;              // 7xxxxxxxxx → 447xxxxxxxxx
        }

        return match ($this->provider) {
            'twilio' => $this->sendViaTwilio($to, $message),
            default  => $this->sendViaVonage($to, $message),
        };
    }

    private function sendViaVonage(string $to, string $message): bool
    {
        $key    = config('services.vonage.key');
        $secret = config('services.vonage.secret');
        $from   = config('services.vonage.sms_from', 'ACMPortal');

        if (! $key || ! $secret) {
            Log::warning("SMS not sent via Vonage (credentials missing). To: {$to}");
            return false;
        }

        try {
            $response = Http::asForm()->post('https://rest.nexmo.com/sms/json', [
                'api_key'    => $key,
                'api_secret' => $secret,
                'to'         => $to,
                'from'       => $from,
                'text'       => $message,
            ]);

            $body    = $response->json();
            $msgData = $body['messages'][0] ?? [];
            $status  = (string) ($msgData['status'] ?? '1');

            if ($status !== '0') {
                $error = $msgData['error-text'] ?? 'unknown error';
                Log::error("Vonage SMS delivery failed. To: {$to}, status: {$status}, error: {$error}");
                return false;
            }

            $messageId = $msgData['message-id'] ?? 'n/a';
            Log::info("Vonage SMS sent. To: {$to}, message-id: {$messageId}");
            return true;

        } catch (\Exception $e) {
            Log::error("Vonage SMS exception. To: {$to}, error: " . $e->getMessage());
            return false;
        }
    }

    private function sendViaTwilio(string $to, string $message): bool
    {
        $sid   = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from  = config('services.twilio.from');

        if (! $sid || ! $token || ! $from) {
            Log::warning("SMS not sent via Twilio (credentials missing). To: {$to}");
            return false;
        }

        try {
            $response = Http::withBasicAuth($sid, $token)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                    'To'   => '+' . $to,
                    'From' => $from,
                    'Body' => $message,
                ]);

            if (! $response->successful()) {
                $body  = $response->json();
                $error = $body['message'] ?? $response->body();
                $code  = $body['code'] ?? $response->status();
                Log::error("Twilio SMS delivery failed. To: {$to}, HTTP: {$response->status()}, code: {$code}, error: {$error}");
                return false;
            }

            $sid = $response->json('sid') ?? 'n/a';
            Log::info("Twilio SMS sent. To: {$to}, sid: {$sid}");
            return true;

        } catch (\Exception $e) {
            Log::error("Twilio SMS exception. To: {$to}, error: " . $e->getMessage());
            return false;
        }
    }
}
