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

<x-docs.step :number="5">
    If the member has a linked spouse and the cycle isn't couple-shared (each spouse owes their own amount), a
    <strong>Split this payment with spouse</strong> checkbox appears. Tick it to record one combined payment as two:
    <ol class="mb-0">
        <li>Enter the <strong>Total Amount Received</strong> — the full amount you actually collected from the family.</li>
        <li>Adjust the breakdown below it — this member's share and the spouse's share — so the two add up to that
            total. They default to what each of them owes, but you can change either one.</li>
    </ol>
</x-docs.step>

<div class="alert alert-warning small mt-3">
    <i class="bi bi-exclamation-triangle me-1"></i>
    The form won't let you submit until the breakdown adds up to the total — this is what prevents a family payment
    from accidentally being recorded twice (once at each person's full balance).
</div>

<div class="alert alert-info small mt-3">
    <i class="bi bi-info-circle me-1"></i>
    Splitting only applies to cycles where each spouse owes independently. For a couple-shared cycle, a single
    payment already counts for both spouses — nothing extra to do there.
</div>
