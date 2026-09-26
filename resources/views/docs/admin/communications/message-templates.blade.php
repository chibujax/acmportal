<p class="text-muted">Save reusable message wording so you're not retyping the same reminder every time.</p>

<x-docs.step :number="1" image="admin/communications/templates-1.png" alt="The message template creation form">
    Go to <strong>Message Templates</strong> in the sidebar and select the new-template button. Choose the channel
    (<strong>SMS</strong> or <strong>Email</strong>), give it a name, and write the body. Email templates also get a
    <strong>Subject</strong> field.
</x-docs.step>

<x-docs.step :number="2">
    Use placeholders and they'll be filled in automatically when sent: <code>{name}</code>, <code>{amount}</code>,
    <code>{cycle}</code>, <code>{donations}</code> (for dues messages), or <code>{meeting}</code> and
    <code>{date}</code> (for attendance messages). SMS bodies are capped at 160 characters.
</x-docs.step>

<x-docs.step :number="3">
    Saved templates then appear as a dropdown wherever messages are sent — dues reminders, bulk messages, and
    absentee follow-ups — so you can load one instead of writing from scratch.
</x-docs.step>
