<p class="text-muted">
    Linking your spouse lets you see each other's dues on your dashboard and, on most cycles, pay together in one
    transaction instead of two separate ones.
</p>

<x-docs.step :number="1" image="member/profile-and-family/spouse-1.png" alt="The spouse linking search form">
    Go to <strong>Family & Relationships</strong> in the sidebar. If you don't have a spouse linked yet, you'll see
    a search box — type their name or phone number (they must already be a registered member).
</x-docs.step>

<x-docs.step :number="2">
    Select the correct match from the suggestions, then select <strong>Link Spouse</strong>.
</x-docs.step>

<x-docs.step :number="3" image="member/profile-and-family/spouse-2.png" alt="A linked spouse shown with an Unlink option">
    Once linked, you'll see their details with a <strong>Linked</strong> badge. What changes depends on the cycle:
    <ul class="mb-0">
        <li>On a cycle the admin has set up as <strong>couple-shared</strong> (e.g. a couple's rate), your dues
            merge into one household obligation automatically.</li>
        <li>On a regular cycle like annual dues, you and your spouse still owe your own amounts, but your dashboard
            shows both side by side and offers a <strong>Pay as Family</strong> option to cover both in one card
            charge — see <em>Paying Online with Stripe</em>.</li>
    </ul>
</x-docs.step>

<x-docs.step :number="4">
    To remove the link, select <strong>Unlink</strong> and confirm — this affects dues obligations from the next
    cycle onward, not retroactively.
</x-docs.step>

<div class="alert alert-light border small mt-3">
    <i class="bi bi-info-circle me-1"></i>
    Linked the wrong person, or need it done on your behalf? An admin with access to spouse linking can link,
    unlink, or correct a pairing for you from the Member Management section.
</div>
