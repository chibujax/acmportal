<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\DuesCycle;
use App\Models\Payment;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Exception\ApiErrorException;

class StripeController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Show Stripe checkout for a dues cycle.
     */
    public function checkout(DuesCycle $cycle)
    {
        $user    = auth()->user();
        $amount  = $cycle->installmentAmount();

        return view('payment.stripe.checkout', compact('cycle', 'amount'));
    }

    /**
     * Create a PaymentIntent and return clientSecret to the front end.
     */
    public function createIntent(Request $request)
    {
        $request->validate([
            'dues_cycle_id' => 'required|exists:dues_cycles,id',
            'amount'        => 'required|numeric|min:0.50',
        ]);

        $cycle = DuesCycle::findOrFail($request->dues_cycle_id);

        try {
            $intent = PaymentIntent::create([
                'amount'   => (int) ($request->amount * 100), // pence/cents
                'currency' => strtolower($cycle->currency),
                'metadata' => [
                    'user_id'       => auth()->id(),
                    'cycle_id'      => $cycle->id,
                    'cycle_title'   => $cycle->title,
                ],
                'automatic_payment_methods' => ['enabled' => true],
            ]);

            // Create a pending payment record
            $payment = Payment::create([
                'user_id'           => auth()->id(),
                'dues_cycle_id'     => $cycle->id,
                'amount'            => $request->amount,
                'currency'          => $cycle->currency,
                'method'            => 'stripe',
                'status'            => 'pending',
                'gateway_reference' => $intent->id,
            ]);

            return response()->json([
                'clientSecret' => $intent->client_secret,
                'payment_id'   => $payment->id,
            ]);

        } catch (ApiErrorException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Confirm payment success on front end callback.
     */
    public function success(Request $request)
    {
        $payment = Payment::where('gateway_reference', $request->payment_intent)
            ->where('user_id', auth()->id())
            ->first();

        if ($payment) {
            $payment->update(['status' => 'completed']);
        }

        return redirect()->route('member.dashboard')
            ->with('success', 'Payment successful! Thank you.');
    }

    /**
     * Stripe webhook endpoint – verify and process events.
     * Route must be excluded from CSRF middleware (done in routes/web.php).
     */
    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret  = config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        match ($event->type) {
            'payment_intent.succeeded'             => $this->handleIntentSucceeded($event->data->object),
            'payment_intent.payment_failed'        => $this->handleIntentFailed($event->data->object),
            'charge.dispute.created'               => $this->handleDispute($event->data->object),
            default                                => null,
        };

        return response()->json(['status' => 'ok']);
    }

    private function handleIntentSucceeded(object $intent): void
    {
        Payment::where('gateway_reference', $intent->id)
            ->update([
                'status'           => 'completed',
                'gateway_response' => 'succeeded',
                'gateway_payload'  => json_encode((array) $intent),
            ]);
    }

    private function handleIntentFailed(object $intent): void
    {
        Payment::where('gateway_reference', $intent->id)
            ->update([
                'status'           => 'failed',
                'gateway_response' => $intent->last_payment_error?->message ?? 'failed',
            ]);
    }

    private function handleDispute(object $charge): void
    {
        // TODO: notify admin of chargeback
    }
}
