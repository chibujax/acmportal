<p class="text-muted">
    Use this if an admin has already added you as a member and you need to set up your own portal login for the
    first time. Depending on how your record was added, you'll end up on one of two different final screens — both
    start the same way.
</p>

<x-docs.step :number="1" image="member/getting-started/joining-1.png" alt="The Join page asking for phone or email">
    Go to the <strong>Join</strong> page and enter the phone number or email address the admin used when they
    registered you.
</x-docs.step>

<x-docs.step :number="2">
    Select <strong>Send Verification Code</strong>. A 6-digit code is sent to your phone or email — it expires in
    10 minutes.
</x-docs.step>

<x-docs.step :number="3" image="member/getting-started/joining-2.png" alt="The OTP verification code entry screen">
    Enter the 6-digit code on the next screen and select <strong>Verify Code</strong>.
</x-docs.step>

<div class="alert alert-light border small mt-3 mb-4">
    <strong>What happens next depends on how you were added:</strong>
    <ul class="mb-0 mt-2">
        <li>If you were added through a bulk import, you'll be taken to a short <strong>registration form</strong>
            to choose your password and confirm your details — see the form fields in
            <a href="{{ route('docs.show', ['member','getting-started','activating-your-account']) }}">Activating Your Account</a>.</li>
        <li>If your account already existed but had never been activated, you'll go straight to
            <strong>set your password</strong> and be signed in immediately.</li>
    </ul>
</div>

<x-docs.step :number="4">
    If the phone/email you enter isn't recognised, or your account is already active, you'll see a message
    explaining what to do instead (e.g. "this account is already active — sign in").
</x-docs.step>
