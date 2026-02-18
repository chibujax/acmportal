<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\DuesCycle;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaystackController extends Controller
{
    private string $secretKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret_key');
        $this->baseUrl   = config('services.paystack.payment_url', 'https://api.paystack.co');
    }

    /**
     * Initiate a Paystack payment – redirect to Paystack hosted page.
     */
    public function initiate(Request $request)
    {
        $request->validate([
            'dues_cycle_id' => 'required|exists:dues_cycles,id',
        ]);

        $cycle     = DuesCycle::findOrFail($request->dues_cycle_id);
        $user      = auth()->user();
        $amount    = $cycle->installmentAmount();
        $reference = 'ACM-PS-' . Str::upper(Str::random(10));

        // Create pending payment record
        $payment = Payment::create([
            'user_id'           => $user->id,
            'dues_cycle_id'     => $cycle->id,
            'amount'            => $amount,
            'currency'          => $cycle->currency,
            'method'            => 'paystack',
            'status'            => 'pending',
            'gateway_reference' => $reference,
        ]);

        // Amount in kobo/pence (Paystack uses smallest unit × 100)
        $amountInSmallestUnit = (int) ($amount * 100);

        $response = Http::withToken($this->secretKey)
            ->post("{$this->baseUrl}/transaction/initialize", [
                'email'     => $user->email ?? config('services.paystack.merchant_email'),
                'amount'    => $amountInSmallestUnit,
                'currency'  => strtoupper($cycle->currency),
                'reference' => $reference,
                'callback_url' => route('payment.paystack.callback'),
                'metadata'  => [
                    'user_id'    => $user->id,
                    'cycle_id'   => $cycle->id,
                    'payment_id' => $payment->id,
                    'custom_fields' => [
                        ['display_name' => 'Member', 'variable_name' => 'member', 'value' => $user->name],
                        ['display_name' => 'Dues Cycle', 'variable_name' => 'cycle', 'value' => $cycle->title],
                    ],
                ],
            ]);

        if (! $response->successful() || ! $response->json('status')) {
            return back()->withErrors(['payment' => 'Could not initiate Paystack payment. Please try again.']);
        }

        return redirect($response->json('data.authorization_url'));
    }

    /**
     * Paystack callback – verify and update.
     */
    public function callback(Request $request)
    {
        $reference = $request->get('reference') ?? $request->get('trxref');

        if (! $reference) {
            return redirect()->route('member.dashboard')
                ->withErrors(['payment' => 'No payment reference received.']);
        }

        $verified = $this->verifyTransaction($reference);

        $payment = Payment::where('gateway_reference', $reference)->first();

        if (! $payment) {
            return redirect()->route('member.dashboard')
                ->withErrors(['payment' => 'Payment record not found.']);
        }

        if ($verified && $verified['status'] === 'success') {
            $payment->update([
                'status'           => 'completed',
                'gateway_response' => 'success',
                'gateway_payload'  => json_encode($verified),
            ]);
            return redirect()->route('member.dashboard')
                ->with('success', 'Payment successful! Thank you.');
        }

        $payment->update(['status' => 'failed', 'gateway_response' => $verified['status'] ?? 'unknown']);

        return redirect()->route('member.dashboard')
            ->withErrors(['payment' => 'Payment was not completed.']);
    }

    /**
     * Paystack webhook – HMAC-SHA512 signature verification.
     */
    public function webhook(Request $request)
    {
        $payload   = $request->getContent();
        $signature = $request->header('x-paystack-signature');

        // Verify HMAC-SHA512
        $computedHash = hash_hmac('sha512', $payload, $this->secretKey);

        if (! hash_equals($computedHash, $signature ?? '')) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $event = json_decode($payload, true);

        match ($event['event'] ?? '') {
            'charge.success'   => $this->handleChargeSuccess($event['data']),
            'refund.processed' => $this->handleRefund($event['data']),
            default            => null,
        };

        return response()->json(['status' => 'ok']);
    }

    // ── Private helpers ────────────────────────────────────────

    private function verifyTransaction(string $reference): ?array
    {
        $response = Http::withToken($this->secretKey)
            ->get("{$this->baseUrl}/transaction/verify/{$reference}");

        if ($response->successful() && $response->json('status')) {
            return $response->json('data');
        }
        return null;
    }

    private function handleChargeSuccess(array $data): void
    {
        Payment::where('gateway_reference', $data['reference'])
            ->update([
                'status'           => 'completed',
                'gateway_response' => 'success',
                'gateway_payload'  => json_encode($data),
            ]);
    }

    private function handleRefund(array $data): void
    {
        Payment::where('gateway_reference', $data['transaction_reference'])
            ->update(['status' => 'refunded']);
    }
}
