<p class="text-muted">
    If you can't remember your password, you can reset it yourself. How you verify your identity depends on
    whether your account has an email address on file.
</p>

<x-docs.step :number="1" image="member/getting-started/forgot-1.png" alt="The Forgot Password page">
    From the sign-in page, select <strong>Forgot password?</strong>, then enter your phone number or email.
</x-docs.step>

<x-docs.step :number="2">
    Select <strong>Send Reset Instructions</strong>.
</x-docs.step>

<div class="alert alert-light border small mt-3 mb-4">
    <strong>Two possible paths from here:</strong>
    <ul class="mb-0 mt-2">
        <li><strong>You have an email on file:</strong> you'll receive a reset link by email. The link is valid for
            60 minutes.</li>
        <li><strong>Phone number only:</strong> you'll receive a 6-digit code by SMS and be taken straight to a
            code-entry screen.</li>
    </ul>
</div>

<x-docs.step :number="3" image="member/getting-started/forgot-2.png" alt="Setting a new password">
    Whichever path you took, you'll end up on the same <strong>Set New Password</strong> screen. Enter and confirm
    your new password, then select <strong>Save New Password</strong>.
</x-docs.step>

<x-docs.step :number="4">
    You'll be returned to the sign-in page with a confirmation message — sign in with your new password.
</x-docs.step>
