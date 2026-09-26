<p class="text-muted">A record of who did what across the portal — every create, update, and delete by an admin.</p>

<x-docs.step :number="1" image="admin/audit-trail/audit-1.png" alt="The audit trail list with filters">
    Go to <strong>Audit Trail</strong> in the sidebar. Filter by action (<strong>Login</strong>,
    <strong>Created</strong>, <strong>Updated</strong>, <strong>Deleted</strong>, etc.), resource type, or a date
    range.
</x-docs.step>

<x-docs.step :number="2">
    Each entry shows who performed the action, what it was, and when — useful for tracing back a change (e.g. "who
    edited this member's status") without having to ask around.
</x-docs.step>
