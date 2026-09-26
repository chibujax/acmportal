<p class="text-muted">An admin-wide view of every child record added by members, plus the ability to add one directly.</p>

<x-docs.step :number="1" image="admin/family-records/children-1.png" alt="The admin children list">
    Go to <strong>Children</strong> in the sidebar. Search by child or parent name, and see each child's gender,
    date of birth, age, linked father/mother, who added the record, and any notes.
</x-docs.step>

<x-docs.step :number="2">
    Select <strong>Add Child</strong> to create a record directly (useful if a member asks you to add one on their
    behalf, e.g. over the phone) — link at least one parent by searching for their member record.
</x-docs.step>

<x-docs.step :number="3">
    Select a child to edit their details, or remove the record entirely.
</x-docs.step>

<div class="alert alert-info small mt-3">
    <i class="bi bi-info-circle me-1"></i>
    Members can also add their own children's records themselves — see the member-facing
    <a href="{{ route('docs.show', ['member','profile-and-family','managing-children']) }}">Managing Children</a>
    guide.
</div>
