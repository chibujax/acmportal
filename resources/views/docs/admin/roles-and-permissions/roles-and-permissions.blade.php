<p class="text-muted">
    <strong>Super Admin only.</strong> Roles let you give an admin access to just the parts of the portal they
    actually need — a Treasurer who handles payments doesn't need to manage meetings or send bulk messages, for
    example.
</p>

<x-docs.step :number="1" image="admin/roles-and-permissions/roles-1.png" alt="The role creation form with page access checkboxes">
    Go to <strong>Role Management</strong> in the sidebar and select <strong>New Role</strong>. Give it a name (e.g.
    "Treasurer") and tick every admin section it should be able to see — each one matches a specific feature, such
    as Payments & Dues Cycles, Stripe Reconciliation, or Meeting Management.
</x-docs.step>

<x-docs.step :number="2">
    Select <strong>Create Role</strong>. Roles can be edited or deleted later, though a role still assigned to
    someone can't be deleted until it's unassigned.
</x-docs.step>

<x-docs.step :number="3" image="admin/roles-and-permissions/roles-2.png" alt="The role assignment page listing admins and their current roles">
    To give an admin their access, go to <strong>Assign</strong>. Every user with the Admin role appears here — tick
    whichever roles they should hold (an admin can have more than one).
</x-docs.step>

<div class="alert alert-light border small mt-3">
    <strong>To make someone an admin in the first place:</strong> open their profile in Member Management and
    change their Role to <em>Admin</em> — they'll then appear on this Assign page ready to be given specific
    permissions.
</div>
