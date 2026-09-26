<p class="text-muted">
    If you gave an email address when you registered, the portal asks you to confirm it belongs to you. This isn't
    required to use the portal, but unverified accounts see a reminder banner until it's done.
</p>

<x-docs.step :number="1">
    After registering with an email address, check your inbox for a verification email from the portal and select
    the link inside it.
</x-docs.step>

<x-docs.step :number="2" image="member/getting-started/verify-email-banner.png" alt="The dashboard banner prompting email verification">
    You'll be taken back to your dashboard with a confirmation. If you haven't verified yet, you'll instead see a
    banner at the top of your dashboard with a <strong>Resend</strong> option in case the original email didn't
    arrive.
</x-docs.step>

<x-docs.step :number="3" image="member/getting-started/verify-email-failed.png" alt="The email verification failed page">
    If the link has expired or was already used, you'll see a notice explaining that — use the Resend option from
    your dashboard to get a fresh one.
</x-docs.step>

<div class="alert alert-info small mt-3">
    <i class="bi bi-info-circle me-1"></i>
    No email on file at all? Your dashboard will instead show a prompt to add one from
    <a href="{{ route('member.profile') }}">My Profile</a>.
</div>
