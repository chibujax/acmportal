<p class="text-muted">
    A dedicated view of online card payments only — for matching what Stripe reports against what actually lands in
    the bank account. This can be granted to a Treasurer role without giving them full payment-recording access.
</p>

<x-docs.step :number="1" image="admin/dues-and-payments/reconciliation-1.png" alt="The Stripe reconciliation summary and filters">
    Go to <strong>Stripe Reconciliation</strong> in the sidebar. The totals at the top summarise
    <strong>Completed</strong>, <strong>Refunded</strong>, and <strong>Pending</strong> amounts, plus a count of
    failed attempts.
</x-docs.step>

<x-docs.step :number="2">
    Filter by member, status, or a date range to narrow down to a specific bank statement period.
</x-docs.step>

<x-docs.step :number="3">
    Each row shows the member, cycle, amount, status, receipt number, and the underlying Stripe reference — use
    <strong>Export</strong> to get a CSV for matching against the bank statement line by line.
</x-docs.step>
