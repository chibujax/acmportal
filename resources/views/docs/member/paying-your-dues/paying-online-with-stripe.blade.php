<p class="text-muted">Pay any open dues cycle or levy online by card, Apple Pay, or Google Pay — in full or in part.</p>

<x-docs.step :number="1">
    From your dashboard, select <strong>Pay by Card</strong> next to the cycle you want to pay. If you have a
    linked spouse and the cycle isn't already couple-shared, you'll instead see a <strong>Pay</strong> button that
    opens a choice between <strong>Pay as Individual</strong> and <strong>Pay as Family</strong>.
</x-docs.step>

<x-docs.step :number="2" image="member/paying-your-dues/stripe-1.png" alt="The Stripe payment form">
    The amount defaults to your full remaining balance, but you can lower it to make a partial payment. Choose a
    payment method — card, Apple Pay, or Google Pay, where available — and enter its details.
</x-docs.step>

<x-docs.step :number="3">
    Select <strong>Pay Securely</strong>. Your card is charged immediately — you'll see a brief "Processing…"
    state, then a confirmation.
</x-docs.step>

<div class="alert alert-light border small mt-3">
    <strong>If your card is declined</strong> or the amount is more than your remaining balance, you'll see an
    error right on the form and no charge is made — just correct it and try again.
</div>

<h6 class="fw-semibold mt-4">Paying as a Family</h6>
<p class="text-muted">
    When you and your spouse are both linked and owe on the same non-shared cycle, choosing
    <strong>Pay as Family</strong> opens a single checkout that covers both of you in one card charge:
</p>

<x-docs.step :number="1">
    You'll see two amounts — your share and your spouse's share — each defaulting to what that person still owes.
    Adjust either one; the combined total updates automatically.
</x-docs.step>

<x-docs.step :number="2">
    Pay as usual. One charge is made on your card, but it's recorded as two separate payments — one against your
    balance, one against your spouse's — so both of your records update correctly.
</x-docs.step>

<div class="alert alert-info small mt-3">
    <i class="bi bi-info-circle me-1"></i>
    If your own dues on that cycle are already settled but your spouse still owes, you'll see a button labeled
    <strong>Pay for</strong> their name instead — it takes you straight to the same family checkout with your own
    share fixed at £0, so you can cover just their balance.
</div>
