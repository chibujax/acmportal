@extends('layouts.app')
@section('title','Pay as Family')
@section('page-title','Family Payment – Stripe')

@php $spouseOnly = $selfRemaining <= 0; @endphp

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="fw-semibold">
                    <i class="bi bi-people-fill text-success me-2"></i>
                    {{ $spouseOnly ? "Pay for {$spouse->name} (Stripe)" : 'Pay as Family (Stripe)' }}
                </h6>
            </div>
            <div class="card-body">
                <!-- Summary -->
                <div class="p-3 rounded mb-4" style="background:#f8f9fa; border-left:4px solid #1a6b3c">
                    <div class="fw-semibold">{{ $cycle->title }}</div>
                    <div class="text-muted small">{{ $cycle->description }}</div>
                    <div class="text-muted small mt-1">
                        Your remaining: £{{ number_format($selfRemaining, 2) }}
                        &middot; {{ $spouse->name }}'s remaining: £{{ number_format($spouseRemaining, 2) }}
                    </div>
                </div>

                <div id="payment-message" class="alert alert-danger d-none mb-3"></div>

                <form id="stripe-form">
                    <div class="row g-3 mb-2">
                        <div class="col-6">
                            <label class="form-label fw-medium">Your Share (£)</label>
                            <input type="number" id="self-amount" class="form-control"
                                   min="0" max="{{ $selfRemaining }}" step="0.01"
                                   value="{{ number_format($selfRemaining, 2, '.', '') }}" required
                                   {{ $selfRemaining <= 0 ? 'readonly' : '' }}>
                            @if($selfRemaining <= 0)
                            <div class="form-text text-success">You have nothing outstanding on this cycle.</div>
                            @endif
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-medium">{{ $spouse->name }}'s Share (£)</label>
                            <input type="number" id="spouse-amount" class="form-control"
                                   min="0" max="{{ $spouseRemaining }}" step="0.01"
                                   value="{{ number_format($spouseRemaining, 2, '.', '') }}" required
                                   {{ $spouseRemaining <= 0 ? 'readonly' : '' }}>
                            @if($spouseRemaining <= 0)
                            <div class="form-text text-success">{{ $spouse->name }} has nothing outstanding on this cycle.</div>
                            @endif
                        </div>
                    </div>
                    <div class="form-text mb-3">
                        Defaults to what each of you still owes on this cycle. You can adjust either amount —
                        the total below updates automatically. Combined total: up to
                        £{{ number_format($selfRemaining + $spouseRemaining, 2) }}.
                    </div>

                    <div class="p-2 rounded mb-4 text-center bg-light">
                        <span class="text-muted small">Total to charge:</span>
                        <span id="total-display" class="fw-bold fs-5 ms-1">£{{ number_format($selfRemaining + $spouseRemaining, 2) }}</span>
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
                        Secured by Stripe. One charge on your card covers both shares — each is recorded
                        separately against you and {{ $spouse->name }}.
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

const selfMax   = {{ $selfRemaining }};
const spouseMax = {{ $spouseRemaining }};
const currency  = '{{ strtolower($cycle->currency) }}';

const selfInput   = document.getElementById('self-amount');
const spouseInput = document.getElementById('spouse-amount');
const totalDisplay = document.getElementById('total-display');

function currentTotal() {
    const s = parseFloat(selfInput.value)   || 0;
    const p = parseFloat(spouseInput.value) || 0;
    return Math.round((s + p) * 100) / 100;
}

function toMinorUnits(amount) {
    return Math.round(amount * 100);
}

// "Deferred intent" mode — see payment.stripe.checkout for the same pattern.
const elements = stripe.elements({
    mode: 'payment',
    amount: toMinorUnits(currentTotal()),
    currency: currency,
});
const paymentElement = elements.create('payment');
paymentElement.mount('#payment-element');

function refreshTotal() {
    const total = currentTotal();
    totalDisplay.textContent = '£' + total.toFixed(2);
    if (total >= 0.50) {
        elements.update({ amount: toMinorUnits(total) });
    }
}

selfInput.addEventListener('input', refreshTotal);
spouseInput.addEventListener('input', refreshTotal);

document.getElementById('stripe-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    const selfAmount   = parseFloat(selfInput.value);
    const spouseAmount = parseFloat(spouseInput.value);

    if (!(selfAmount >= 0) || selfAmount > selfMax + 0.01) {
        showError('Your share must be between £0 and £' + selfMax.toFixed(2) + '.');
        return;
    }
    if (!(spouseAmount >= 0) || spouseAmount > spouseMax + 0.01) {
        showError("{{ $spouse->name }}'s share must be between £0 and £" + spouseMax.toFixed(2) + '.');
        return;
    }
    if (selfAmount + spouseAmount < 0.50) {
        showError('The combined amount must be at least £0.50.');
        return;
    }

    const btn = document.getElementById('pay-btn');
    btn.disabled = true;
    document.getElementById('btn-text').classList.add('d-none');
    document.getElementById('btn-spinner').classList.remove('d-none');

    const { error: submitError } = await elements.submit();

    if (submitError) {
        showError(submitError.message);
        return;
    }

    const res = await fetch('{{ route('payment.stripe.family-intent') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
        },
        body: JSON.stringify({
            dues_cycle_id: {{ $cycle->id }},
            self_amount: selfAmount,
            spouse_amount: spouseAmount,
        }),
    });

    const data = await res.json();

    if (data.error) {
        showError(data.error);
        return;
    }

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
