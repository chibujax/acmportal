<p class="text-muted">A dues cycle covers anything members owe or contribute towards — annual dues, an event levy, or a donation drive.</p>

<x-docs.step :number="1" image="admin/dues-and-payments/create-1.png" alt="The dues cycle list with New Dues Cycle button">
    Go to <strong>Dues Cycles</strong> in the sidebar, then select <strong>New Dues Cycle</strong>.
</x-docs.step>

<x-docs.step :number="2" image="admin/dues-and-payments/create-2.png" alt="The dues cycle creation form">
    Fill in the title, type (<strong>Yearly Dues</strong>, <strong>Donation</strong>, or <strong>Event Levy</strong>),
    currency, amount, and date range. Choose how it's paid: <strong>One-off</strong>, <strong>Monthly</strong>, or
    <strong>Installments</strong> (with a number of installments).
</x-docs.step>

<x-docs.step :number="3">
    Four toggles control how the cycle behaves:
    <ul class="mt-2">
        <li><strong>Couple shared</strong> — married members pay the full amount together; singles pay half.</li>
        <li><strong>Pledge-based</strong> — members set their own amount instead of a fixed one (see
            <a href="{{ route('docs.show', ['admin','dues-and-payments','pledges-and-donation-items']) }}">Pledges & Donation Items</a>).</li>
        <li><strong>Accepts item donations</strong> — allows recording physical items (food, drinks, etc.) alongside money.</li>
        <li><strong>Send payment reminders</strong> — enables the SMS/email reminder tool on this cycle.</li>
    </ul>
</x-docs.step>

<x-docs.step :number="4">
    Set <strong>Status</strong> to <strong>Active</strong> when ready for members to see and pay it, or leave as
    <strong>Draft</strong> to prepare it first.
</x-docs.step>

<x-docs.step :number="5" image="admin/dues-and-payments/create-3.png" alt="The dues cycle detail page showing per-member payment status">
    Once created, the cycle's detail page shows totals (expected, collected, outstanding) and a per-member payment
    matrix — sortable by name, paid, remaining, or status. From here you can export to CSV, print, or send reminders
    to everyone still outstanding.
</x-docs.step>
