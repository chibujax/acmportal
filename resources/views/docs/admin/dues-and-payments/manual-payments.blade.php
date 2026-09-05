<p class="text-muted">Record a cash or bank transfer payment on a member's behalf — for anyone not paying online by card.</p>

<x-docs.step :number="1" image="admin/dues-and-payments/payments-1.png" alt="The payments list with Record New Payment button">
    Go to <strong>Payments</strong> in the sidebar and select <strong>Record New Payment</strong>.
</x-docs.step>

<x-docs.step :number="2" image="admin/dues-and-payments/payments-2.png" alt="The manual payment recording form">
    Search for the member, then choose the dues cycle. The amount field auto-fills with what they owe — the couple
    or single rate for shared cycles, or their recorded pledge for pledge-based ones — but you can adjust it for a
    partial payment.
</x-docs.step>

<x-docs.step :number="3">
    If the member has already settled this cycle, a warning appears so you can double-check you've picked the right
    cycle before continuing.
</x-docs.step>

<x-docs.step :number="4">
    Set the payment date, add any notes, and optionally attach a photo or PDF as proof of payment. Select
    <strong>Record Payment</strong>.
</x-docs.step>

<div class="alert alert-info small mt-3">
    <i class="bi bi-info-circle me-1"></i>
    A married member's spouse can't be paid for in the same form unless the cycle itself is couple-shared — record
    their payment separately if needed.
</div>
