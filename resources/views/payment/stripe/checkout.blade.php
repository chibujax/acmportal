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
                    <div class="fs-4 fw-bold text-success mt-2">
                        £{{ number_format($amount, 2) }}
                        @if($cycle->payment_options !== 'once')
                            <small class="fs-6 text-muted">/ {{ $cycle->payment_options === 'monthly' ? 'month' : 'instalment' }}</small>
                        @endif
                    </div>
                </div>

                <!-- Stripe Payment Form -->
                <div id="payment-message" class="alert alert-danger d-none mb-3"></div>

                <form id="stripe-form">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Cardholder Name</label>
                        <input type="text" id="card-name" class="form-control" placeholder="Name on card" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-medium">Card Details</label>
                        <div id="card-element" class="form-control" style="height:42px; padding-top:10px">
                            <!-- Stripe Element will mount here -->
                        </div>
                        <div id="card-errors" class="text-danger small mt-1"></div>
                    </div>

                    <button id="pay-btn" type="submit"
                            class="btn w-100 text-white fw-semibold"
                            style="background:#635bff; border-radius:8px; padding:.7rem">
                        <span id="btn-text">
                            <i class="bi bi-lock me-2"></i>Pay £{{ number_format($amount, 2) }} Securely
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

@push('styles')
<style>
    #card-element { border: 1px solid #ced4da; border-radius: 6px; }
    #card-element.StripeElement--focus { border-color: #86b7fe; box-shadow: 0 0 0 0.25rem rgba(13,110,253,.25); }
    #card-element.StripeElement--invalid { border-color: #dc3545; }
</style>
@endpush

@push('scripts')
<!-- Stripe.js -->
<script src="https://js.stripe.com/v3/"></script>
<script>
const stripe = Stripe('{{ config('services.stripe.key') }}');
const elements = stripe.elements();
const cardElement = elements.create('card', {
    style: {
        base: { fontSize: '15px', color: '#212529', '::placeholder': { color: '#6c757d' } },
        invalid: { color: '#dc3545' },
    }
});
cardElement.mount('#card-element');

cardElement.addEventListener('change', ({ error }) => {
    document.getElementById('card-errors').textContent = error ? error.message : '';
});

document.getElementById('stripe-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    const btn = document.getElementById('pay-btn');
    btn.disabled = true;
    document.getElementById('btn-text').classList.add('d-none');
    document.getElementById('btn-spinner').classList.remove('d-none');

    // 1. Create PaymentIntent via our backend
    const res = await fetch('{{ route('payment.stripe.intent') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
        },
        body: JSON.stringify({
            dues_cycle_id: {{ $cycle->id }},
            amount: {{ $amount }},
        }),
    });

    const data = await res.json();

    if (data.error) {
        showError(data.error);
        return;
    }

    // 2. Confirm payment with Stripe
    const { error, paymentIntent } = await stripe.confirmCardPayment(data.clientSecret, {
        payment_method: {
            card: cardElement,
            billing_details: { name: document.getElementById('card-name').value },
        },
    });

    if (error) {
        showError(error.message);
    } else if (paymentIntent.status === 'succeeded') {
        window.location.href = '{{ route('payment.stripe.success') }}?payment_intent=' + paymentIntent.id;
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
