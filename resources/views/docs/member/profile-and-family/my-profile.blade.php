<p class="text-muted">Keep your contact details up to date so admins can reach you and payment receipts arrive correctly.</p>

<x-docs.step :number="1" image="member/profile-and-family/profile-1.png" alt="The My Profile page in view mode">
    Go to <strong>My Profile</strong> in the sidebar. You'll see your current email, address, occupation, and
    gender.
</x-docs.step>

<x-docs.step :number="2" image="member/profile-and-family/profile-2.png" alt="The My Profile page in edit mode">
    Select <strong>Edit</strong> to make changes. Your name and phone number aren't editable here — contact an
    admin if those need to change.
</x-docs.step>

<x-docs.step :number="3">
    Select <strong>Save Changes</strong>. If your email address changes, you'll need to verify it again — see
    <a href="{{ route('docs.show', ['member','getting-started','verifying-your-email']) }}">Verifying Your Email</a>.
</x-docs.step>

<div class="alert alert-info small mt-3">
    <i class="bi bi-info-circle me-1"></i>
    To change your password, use <strong>Reset via Forgot Password</strong> at the bottom of this page rather than
    a dedicated change-password form.
</div>
