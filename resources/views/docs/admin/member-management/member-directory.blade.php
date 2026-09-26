<p class="text-muted">
    The Member Directory is where you find, filter, and open any member's record.
</p>

<x-docs.step :number="1" image="admin/member-management/directory-1.png" alt="The member directory list with filters">
    Go to <strong>All Members</strong> in the sidebar. Use the search box to look up a member by name, phone, or
    email, and the dropdowns to narrow the list by <strong>Status</strong> (active/inactive/suspended),
    <strong>Role</strong>, or <strong>Portal Access</strong> (whether they've registered yet). Select
    <strong>Filter</strong> to apply, or <strong>Reset</strong> to clear.
</x-docs.step>

<x-docs.step :number="2">
    Each row shows the member's contact details, whether their email is verified, whether they've registered for
    portal access, their status, and role. Select the eye icon (or the member's name) to open their full record.
</x-docs.step>

<x-docs.step :number="3" image="admin/member-management/directory-2.png" alt="An individual member's detail page">
    On a member's detail page you'll see their payment history, what they currently owe across active dues cycles,
    any linked spouse and children, and outstanding balances carried over from previous years.
</x-docs.step>

<x-docs.step :number="4">
    From here you can change a member's <strong>status</strong> or <strong>role</strong>, and send them a one-off
    SMS — if your admin role has been granted the "Manage Member Status & Role" permission. Editing a member's
    phone or email directly is restricted to Super Admins.
</x-docs.step>
