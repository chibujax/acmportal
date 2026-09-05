<p class="text-muted">Every meeting needs a location before members can check in by GPS, plus an activation step to go live.</p>

<x-docs.step :number="1" image="admin/meetings-and-attendance/create-1.png" alt="The new meeting form">
    Go to <strong>Meetings</strong> in the sidebar and select <strong>New Meeting</strong> (or the equivalent
    button on the list page). Set the title, date, start time, the time after which check-ins count as
    <strong>Late</strong>, and the end time (when the QR code expires).
</x-docs.step>

<x-docs.step :number="2">
    Enter the venue <strong>Postcode</strong> and select <strong>Look Up</strong> — this finds candidate addresses
    and their coordinates automatically. Choose the correct one from the dropdown. If the lookup doesn't work for
    your venue, tick <strong>Enter address & coordinates manually</strong> instead.
</x-docs.step>

<x-docs.step :number="3">
    Set the <strong>GPS Radius</strong> (how close a member's phone must be to count as "at the venue") and choose
    what happens if someone checks in from outside it: <strong>Block & contact admin</strong>, or
    <strong>Allow but flag for review</strong>.
</x-docs.step>

<x-docs.step :number="4">
    Select <strong>Create Meeting</strong>. The meeting starts in <strong>Scheduled</strong> status — nobody can
    check in yet.
</x-docs.step>

<x-docs.step :number="5" image="admin/meetings-and-attendance/create-2.png" alt="A meeting's detail page with the live QR code">
    When it's time, open the meeting and select <strong>Start Meeting (Activate QR)</strong>. A QR code appears —
    display it on a screen for members to scan. The page also shows live attendance stats and an
    <strong>Open Check-In URL</strong> link you can share directly.
</x-docs.step>

<x-docs.step :number="6">
    Below the QR code you can manually check someone in (for members without the app), mark someone
    <strong>excused</strong>, or remove an erroneous check-in. Select <strong>Close Meeting</strong> when it ends to
    stop accepting new check-ins.
</x-docs.step>
