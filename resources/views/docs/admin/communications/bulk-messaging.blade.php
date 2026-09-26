<p class="text-muted">Send an SMS or email to a group of members at once — an announcement, a general reminder, anything not tied to a specific dues cycle or meeting.</p>

<x-docs.step :number="1" image="admin/communications/bulk-1.png" alt="The bulk message compose form and recipient list">
    Go to <strong>Bulk Message</strong> in the sidebar. Choose the channel, optionally load a saved template, and
    write (or edit) your message.
</x-docs.step>

<x-docs.step :number="2">
    Select recipients from the member list on the right — filter by role or status first if you only want a
    subset.
</x-docs.step>

<x-docs.step :number="3">
    Select <strong>Send to selected members</strong>. Every send is recorded in the
    <a href="{{ route('docs.show', ['admin','communications','contact-log']) }}">Contact Log</a> under a shared
    batch ID, so you can always see what went out and to whom.
</x-docs.step>
