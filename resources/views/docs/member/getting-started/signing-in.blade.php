<p class="text-muted">Once you have a portal password — either from <a href="{{ route('docs.show', ['member','getting-started','joining-the-portal']) }}">joining the portal</a> or <a href="{{ route('docs.show', ['member','getting-started','activating-your-account']) }}">activating your account</a> — use these steps to sign in any time.</p>

<x-docs.step :number="1" image="member/getting-started/signing-in-1.png" alt="The ACM Portal sign in page">
    Go to the portal's sign-in page. Enter the <strong>phone number or email</strong> your account was registered with,
    and your <strong>password</strong>.
</x-docs.step>

<x-docs.step :number="2">
    Optionally tick <strong>Remember me</strong> to stay signed in on this device for longer. Then select
    <strong>Sign In</strong>.
</x-docs.step>

<x-docs.step :number="3" image="member/getting-started/signing-in-2.png" alt="The member dashboard after signing in">
    You'll land on your dashboard. If you see a red error message instead, double-check your phone/email and
    password — after several incorrect attempts the portal will briefly lock further attempts for security, so wait
    a few minutes before trying again.
</x-docs.step>

<div class="alert alert-info small mt-3">
    <i class="bi bi-info-circle me-1"></i>
    Forgotten your password? See <a href="{{ route('docs.show', ['member','getting-started','forgot-password']) }}">Forgot Password</a>.
</div>
