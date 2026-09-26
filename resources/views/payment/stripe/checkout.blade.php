@extends('layouts.app')
@section('title','Pay with Stripe')
@section('page-title','Online Payment – Stripe')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="fw-semibold"><i class="bi bi-credit-card text-primary me-2"></i>Pay via Card (Stripe)</h6>
            </div>
            <div class="card-body">
                <!-- Summary -->
                <div class="p-3 rounded mb-4" style="background:#f8f9fa; border-left:4px solid #1a6b3c">
                    <div class="fw-semibold">{{ $cycle->title }}</div>
                    <div class="text-muted small">{{ $cycle->description }}</div>
                    <div class="text-muted small mt-1">Remaining balance: £{{ number_format($remaining, 2) }}</div>
                </div>

                <!-- Stripe Payment Form -->
                <div id="payment-message" class="alert alert-danger d-none mb-3"></div>

                <form id="stripe-form">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Amount to Pay (£)</label>
                        <input type="number" id="pay-amount" class="form-control"
                               min="0.50" max="{{ $remaining }}" step="0.01"
                               value="{{ number_format($amount, 2, '.', '') }}" required>
                        <div class="form-text">You can pay this off in full or make a partial payment, up to £{{ number_format($remaining, 2) }}.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">Payment Details</label>
                        <div id="payment-element">
                            <!-- Stripe Payment Element will mount here -->
                        </div>
                    </div>

                    <button id="pay-btn" type="submit"
                            class="btn w-100 text-white fw-semibold"
                            style="background:#635bff; border-radius:8px; padding:.7rem">
                        <span id="btn-text">
                            <i class="bi bi-lock me-2"></i>Pay Securely
                        </span>
                        <span id="btn-spinner" class="d-none">
                            <span class="spinner-border spinner-border-sm me-2"></span>Processing…
                        </span>
                    </button>
                </form>

                <div class="text-center mt-3">
                    <small class="text-muted">
                        <i class="bi bi-shield-lock me-1"></i>
                        Secured by Stripe. We never store your card details.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Stripe.js -->
<script src="https://js.stripe.com/v3/"></script>
<script>
const stripe = Stripe('{{ $stripeKey }}');

const remainingBalance = {{ $remaining }};
const currency = '{{ strtolower($cycle->currency) }}';

// "Deferred intent" mode: the Payment Element mounts from just an
// amount/currency, with no PaymentIntent (and no pending Payment row)
// created until the member actually submits. This also means the amount
// field can be edited freely client-side via elements.update() below,
// with no server round trip needed to keep wallet sheets (Apple Pay/Google
// Pay) in sync.
const elements = stripe.elements({
    mode: 'payment',
    amount: toMinorUnits(parseFloat(document.getElementById('pay-amount').value)),
    currency: currency,
});
const paymentElement = elements.create('payment');
paymentElement.mount('#payment-element');

function toMinorUnits(amount) {
    return Math.round(amount * 100);
}

document.getElementById('pay-amount').addEventListener('input', () => {
    const amount = parseFloat(document.getElementById('pay-amount').value);

    if (amount >= 0.50) {
        elements.update({ amount: toMinorUnits(amount) });
    }
});

document.getElementById('stripe-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    const amount = parseFloat(document.getElementById('pay-amount').value);

    if (!(amount >= 0.50) || amount > remainingBalance + 0.01) {
        showError('Enter an amount between £0.50 and £' + remainingBalance.toFixed(2) + '.');
        return;
    }

    const btn = document.getElementById('pay-btn');
    btn.disabled = true;
    document.getElementById('btn-text').classList.add('d-none');
    document.getElementById('btn-spinner').classList.remove('d-none');

    // 1. Validate and collect the payment details entered into the Element.
    const { error: submitError } = await elements.submit();

    if (submitError) {
        showError(submitError.message);
        return;
    }

    // 2. Create the PaymentIntent via our backend, now that we know the
    // member is actually ready to pay.
    const res = await fetch('{{ route('payment.stripe.intent') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
        },
        body: JSON.stringify({
            dues_cycle_id: {{ $cycle->id }},
            amount: amount,
        }),
    });

    const data = await res.json();

    if (data.error) {
        showError(data.error);
        return;
    }

    // 3. Confirm payment with Stripe.
    const { error, paymentIntent } = await stripe.confirmPayment({
        elements,
        clientSecret: data.clientSecret,
        confirmParams: {
            return_url: '{{ route('payment.stripe.success') }}',
        },
        redirect: 'if_required',
    });

    if (error) {
        showError(error.message);
    } else if (paymentIntent && paymentIntent.status === 'succeeded') {
        window.location.href = '{{ route('payment.stripe.success') }}?payment_intent=' + paymentIntent.id;
    } else {
        showError('Payment was not completed.');
    }
});

function showError(msg) {
    const el = document.getElementById('payment-message');
    el.textContent = msg;
    el.classList.remove('d-none');
    const btn = document.getElementById('pay-btn');
    btn.disabled = false;
    document.getElementById('btn-text').classList.remove('d-none');
    document.getElementById('btn-spinner').classList.add('d-none');
}
</script>
@endpush
