<p class="text-muted">
    Use this to add a batch of members at once from a spreadsheet, and to send them their registration links.
</p>

<x-docs.step :number="1" image="admin/member-management/import-1.png" alt="The CSV import upload form">
    Go to <strong>Import (CSV)</strong> in the sidebar. Your file needs a header row with at least
    <code>name</code> and <code>phone</code> columns — <code>email</code> is optional. Select
    <strong>Download Template</strong> if you want a ready-made example file to fill in.
</x-docs.step>

<x-docs.step :number="2">
    Choose your CSV file and select <strong>Import Members</strong>. Rows that duplicate an existing member are
    skipped automatically, and any row-level problems are listed as warnings after the upload.
</x-docs.step>

<x-docs.step :number="3" image="admin/member-management/import-2.png" alt="The list of import batches with Send Invites">
    Your upload appears as a new batch in the table on the right, showing how many were imported and how many have
    since registered or been invited. Select <strong>Send Invites</strong> on a batch to email/text everyone in it
    their registration link at once.
</x-docs.step>

<x-docs.step :number="4">
    Need to add just one person without a full spreadsheet? Select <strong>Invite Individual</strong> at the top of
    the batches panel instead.
</x-docs.step>

<x-docs.step :number="5" image="admin/member-management/import-3.png" alt="The pending invites list with copyable registration links">
    Select <strong>View Pending</strong> (or <strong>Pending Invites</strong> in the sidebar) to see everyone who's
    been imported but hasn't registered yet, resend an individual invite, or copy their registration link to share
    manually — handy if an SMS or email didn't arrive.
</x-docs.step>
