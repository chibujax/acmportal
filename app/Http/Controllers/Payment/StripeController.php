<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\DuesCycle;
use App\Models\MemberPledge;
use App\Models\Payment;
use App\Models\StripeEvent;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        if (! config('services.stripe.enabled')) {
            return redirect()->route('member.dashboard')
                ->with('warning', 'Online card payment is not available yet.');
        }

        $user      = auth()->user();
        $remaining = $this->remainingBalance($user, $cycle);

        if ($remaining <= 0) {
            return redirect()->route('member.dashboard')
                ->with('warning', 'This dues cycle is already fully paid.');
        }

        // Suggest paying it off in full by default, but the member can lower
        // this on the form — partial payments are allowed, same as when an
        // admin records a manual payment.
        $amount = min($cycle->installmentAmount(), $remaining) ?: $remaining;

        return view('payment.stripe.checkout', [
            'cycle'     => $cycle,
            'amount'    => $amount,
            'remaining' => $remaining,
            'stripeKey' => config('services.stripe.key'),
        ]);
    }

    /**
     * Create a PaymentIntent and return clientSecret to the front end.
     *
     * Called only once the member submits the form (after elements.submit()
     * has validated the Payment Element client-side) — the Payment Element
     * itself is mounted in "deferred intent" mode using just amount/currency,
     * so no PaymentIntent (or pending Payment row) exists until an actual
     * payment attempt is made.
     */
    public function createIntent(Request $request)
    {
        if (! config('services.stripe.enabled')) {
            return response()->json(['error' => 'Online card payment is not available yet.'], 503);
        }

        $request->validate([
            'dues_cycle_id' => 'required|exists:dues_cycles,id',
            'amount'        => 'required|numeric|min:0.50',
        ]);

        $cycle     = DuesCycle::findOrFail($request->dues_cycle_id);
        $user      = auth()->user();
        $remaining = $this->remainingBalance($user, $cycle);

        // Enforce the cap server-side — the amount on the form is only a suggestion.
        if ($request->amount > $remaining + 0.01) {
            return response()->json([
                'error' => 'That amount is more than the remaining balance of £' . number_format($remaining, 2) . '.',
            ], 422);
        }

        try {
            $intent = PaymentIntent::create([
                'amount'   => (int) round($request->amount * 100), // pence/cents
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
     * How much a member (plus spouse, where pledges/dues are shared) still
     * owes on a cycle — mirrors the calculation in Member\DashboardController
     * so the cap shown here matches what's shown on the dashboard.
     */
    private function remainingBalance(User $user, DuesCycle $cycle): float
    {
        if ($cycle->is_pledge_based) {
            $pledge = MemberPledge::where('dues_cycle_id', $cycle->id)
                ->where('user_id', $user->id)
                ->first();

            if (! $pledge && $user->spouse()) {
                $pledge = MemberPledge::where('dues_cycle_id', $cycle->id)
                    ->where('user_id', $user->spouse()->id)
                    ->where('shared_with_spouse', true)
                    ->first();
            }

            $obligation   = $pledge->pledged_amount ?? 0;
            $mergeSpouse  = $pledge->shared_with_spouse ?? false;
        } else {
            $obligation  = $user->obligationFor($cycle);
            $mergeSpouse = $cycle->couple_shared;
        }

        $paid = $user->totalPaidWithSpouse($cycle->id, $mergeSpouse);

        return max(0, round($obligation - $paid, 2));
    }

    /**
     * Confirm payment success on front end callback.
     *
     * The client cannot be trusted to say a payment succeeded — it only
     * triggered confirmCardPayment(), it never captured the funds itself.
     * We re-fetch the PaymentIntent from Stripe and check its real status
     * before marking anything as completed; the webhook is the other,
     * server-to-server path that arrives independently of this request.
     */
    public function success(Request $request)
    {
        $payment = Payment::where('gateway_reference', $request->query('payment_intent'))
            ->where('user_id', auth()->id())
            ->first();

        if (! $payment) {
            return redirect()->route('member.dashboard')
                ->with('warning', 'We could not find that payment.');
        }

        if ($payment->status !== 'completed') {
            try {
                $intent = PaymentIntent::retrieve($payment->gateway_reference);
            } catch (ApiErrorException $e) {
                Log::error('Stripe success callback: failed to retrieve PaymentIntent', [
                    'payment_id' => $payment->id,
                    'error'      => $e->getMessage(),
                ]);

                return redirect()->route('member.dashboard')
                    ->with('warning', 'We could not confirm your payment status. Please contact us if you were charged.');
            }

            if ($intent->status === 'succeeded') {
                $this->markCompleted($payment, $intent);
            } else {
                return redirect()->route('member.dashboard')
                    ->with('warning', 'Payment was not completed.');
            }
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
            // Silent before this fix — a stale STRIPE_WEBHOOK_SECRET (e.g. from
            // an old `stripe listen` session) would fail here with no trace at all.
            Log::warning('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Stripe retries webhook delivery until it gets a 2xx, so the same
        // event id can arrive more than once — skip if we've already logged it.
        if (StripeEvent::where('stripe_event_id', $event->id)->exists()) {
            return response()->json(['status' => 'ok']);
        }

        $object = $event->data->object;

        $paymentIntentId = match (true) {
            str_starts_with($event->type, 'payment_intent.') => $object->id,
            $event->type === 'charge.dispute.created'        => $object->payment_intent,
            $event->type === 'charge.refunded'                => $object->payment_intent,
            default                                          => null,
        };

        StripeEvent::create([
            'stripe_event_id' => $event->id,
            'type'            => $event->type,
            'payment_id'      => $paymentIntentId
                ? Payment::where('gateway_reference', $paymentIntentId)->value('id')
                : null,
            // Keep everything — amount, currency, card brand/last4, decline
            // reason, dispute/refund detail, risk assessment — this is our
            // own reconciliation record, not just a copy of Stripe's dashboard.
            // Only genuine PII (billing name/address/email/phone, dispute
            // evidence uploads) gets blanked out, wherever it appears.
            'payload'         => $this->redactSensitiveKeys($object->toArray()),
            'received_at'     => now(),
        ]);

        match ($event->type) {
            'payment_intent.succeeded'             => $this->handleIntentSucceeded($object),
            'payment_intent.payment_failed'        => $this->handleIntentFailed($object),
            'charge.dispute.created'               => $this->handleDispute($object), // event object is a Dispute
            'charge.refunded'                       => $this->handleRefund($object),  // event object is a Charge
            default                                => null,
        };

        return response()->json(['status' => 'ok']);
    }

    /**
     * Recursively blanks out genuine PII (billing/shipping name, email,
     * phone, address, dispute evidence uploads) and live credentials
     * (client_secret — usable client-side to act on the PaymentIntent,
     * not just descriptive data) wherever they appear in a Stripe object,
     * regardless of nesting depth. Deliberately does NOT redact card
     * brand/last4/exp, customer/charge IDs, or risk data — that's exactly
     * the reconciliation detail this log exists to keep, and none of it
     * is more sensitive than what Stripe's own dashboard already displays.
     */
    private function redactSensitiveKeys(array $data): array
    {
        static $sensitiveKeys = [
            'billing_details', 'shipping', 'receipt_email', 'evidence',
            'name', 'email', 'phone', 'address', 'line1', 'line2', 'postal_code',
            'client_secret',
        ];

        foreach ($data as $key => $value) {
            if (in_array(is_string($key) ? strtolower($key) : $key, $sensitiveKeys, true)) {
                $data[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $data[$key] = $this->redactSensitiveKeys($value);
            }
        }

        return $data;
    }

    private function handleIntentSucceeded(object $intent): void
    {
        $payment = Payment::where('gateway_reference', $intent->id)->first();

        if ($payment && $payment->status !== 'completed') {
            $this->markCompleted($payment, $intent);
        }
    }

    private function handleIntentFailed(object $intent): void
    {
        $payment = Payment::where('gateway_reference', $intent->id)->first();

        if (! $payment) {
            return;
        }

        $payment->update([
            'status'           => 'failed',
            'gateway_response' => $intent->last_payment_error?->message ?? 'failed',
        ]);

        $this->flagIfSuspicious($payment);
    }

    /**
     * Email admins the moment a member crosses 3 failed card attempts within
     * an hour — a repeated burst of declines on one account is the classic
     * card-testing / stolen-card signal. Fires once per crossing (exactly at
     * count 3), not on every failure after, to avoid flooding admin inboxes.
     */
    private function flagIfSuspicious(Payment $payment): void
    {
        $recentFailures = Payment::where('user_id', $payment->user_id)
            ->where('method', 'stripe')
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recentFailures !== 3) {
            return;
        }

        $member = $payment->user;
        $email  = app(EmailService::class);

        User::whereIn('role', ['admin', 'super_admin'])
            ->whereNotNull('email')
            ->get()
            ->each(fn ($admin) => $email->send(
                $admin->email,
                'Suspicious payment activity: ' . $member->name,
                "{$member->name} ({$member->phone}) has had {$recentFailures} failed card payment attempts in the last hour. "
                    . "This can indicate someone testing stolen card numbers. Review their payment history: "
                    . route('admin.payments.index', ['search' => $member->name])
            ));
    }

    private function handleDispute(object $dispute): void
    {
        Log::warning('Stripe dispute created — chargeback needs review', [
            'dispute_id'     => $dispute->id,
            'payment_intent' => $dispute->payment_intent,
            'amount'         => $dispute->amount,
            'reason'         => $dispute->reason ?? null,
        ]);

        Payment::where('gateway_reference', $dispute->payment_intent)
            ->update(['gateway_response' => 'disputed: ' . ($dispute->reason ?? 'unknown')]);
    }

    /**
     * Keep our record in sync when a refund happens on Stripe's side (e.g. an
     * admin refunds directly from the Stripe dashboard). Without this, a
     * refunded payment would silently stay 'completed' in our own records
     * forever — exactly the kind of drift a reconciliation log is meant to catch.
     */
    private function handleRefund(object $charge): void
    {
        $payment = Payment::where('gateway_reference', $charge->payment_intent)->first();

        if (! $payment) {
            return;
        }

        $fullyRefunded = $charge->amount_refunded >= $charge->amount;

        $payment->update([
            'status'           => $fullyRefunded ? 'refunded' : $payment->status,
            'gateway_response' => $fullyRefunded
                ? 'refunded'
                : 'partially refunded: £' . number_format($charge->amount_refunded / 100, 2),
        ]);
    }

    /**
     * Mark a payment completed from a verified Stripe PaymentIntent.
     * Shared by both the success callback and the webhook so a payment
     * only gets its payment_date/receipt_number stamped once.
     */
    private function markCompleted(Payment $payment, object $intent): void
    {
        $payment->update([
            'status'           => 'completed',
            'gateway_response' => 'succeeded',
            'gateway_payload'  => $this->redactSensitiveKeys($intent->toArray()),
            'payment_date'     => $payment->payment_date ?? now(),
            'receipt_number'   => $payment->receipt_number ?? Payment::generateReceiptNumber(),
        ]);
    }
}
