<p class="text-muted">
    Meetings are checked into by scanning a QR code (or tapping the live-meeting link on your dashboard), which
    confirms your identity and, where required, your location.
</p>

<x-docs.step :number="1">
    When a meeting is live, scan the QR code shown at the venue (or select <strong>Join Meeting</strong> from your
    dashboard banner if you're already there).
</x-docs.step>

<x-docs.step :number="2" image="member/meetings/checkin-1.png" alt="The identity confirmation screen, Is this you?">
    You'll land on an <strong>Is this you?</strong> screen showing your name and phone number. If it's correct,
    select <strong>Yes, that's me — Check Me In</strong>. If not, select <strong>Not me — sign in as someone
    else</strong> to switch accounts.
</x-docs.step>

<x-docs.step :number="3">
    Your browser will ask permission to use your location — tap <strong>Allow</strong>. This confirms you're
    actually at the venue.
</x-docs.step>

<div class="alert alert-light border small mt-3 mb-4">
    <strong>What you'll see next depends on the result:</strong>
    <ul class="mb-0 mt-2">
        <li>✅ <strong>Checked in</strong> — on time, recorded successfully.</li>
        <li>⏰ <strong>Checked in (Late)</strong> — you arrived after the meeting's start time.</li>
        <li>⚠️ <strong>Checked in (Location Flagged)</strong> — your attendance was still recorded, but your
            location didn't match the venue closely enough, so an admin may review it.</li>
        <li>📍 <strong>Location error</strong> — if you deny location access, you'll see step-by-step instructions
            for re-enabling it in your phone settings, plus a <strong>Try again</strong> button.</li>
    </ul>
</div>

<x-docs.step :number="4" image="member/meetings/checkin-2.png" alt="Already checked in confirmation screen">
    If you scan the code again after already checking in, you'll simply see a confirmation of your recorded time
    rather than being asked to check in twice.
</x-docs.step>
