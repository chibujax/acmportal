<p class="text-muted">Every meeting needs a location before members can check in by GPS, plus an activation step to go live.</p>

<x-docs.step :number="1" image="admin/meetings-and-attendance/create-1.png" alt="The new meeting form">
    Go to <strong>Meetings</strong> in the sidebar and select <strong>New Meeting</strong> (or the equivalent
    button on the list page). Set the title, date, start time, the time after which check-ins count as
    <strong>Late</strong>, and the end time (when the QR code expires).
</x-docs.step>

<x-docs.step :number="2" image="admin/meetings-and-attendance/create-2.png" alt="Venue searched and pinned on the satellite map, with GPS radius and Confirm Location">
    Start typing the venue <strong>Address</strong> and pick it from the suggestions. It appears as a pin on the
    satellite map — drag the pin to the exact building if it's slightly off, set the <strong>GPS Radius</strong>
    (how close a member's phone must be to count as "at the venue"), then select <strong>Confirm Location</strong>.
    The meeting can't be saved until the location is confirmed.
</x-docs.step>

<x-docs.step :number="3">
    Choose what happens if someone checks in from outside the radius: <strong>Block & contact admin</strong>, or
    <strong>Allow but flag for review</strong>.
</x-docs.step>

<x-docs.step :number="4">
    Select <strong>Create Meeting</strong>. The meeting starts in <strong>Scheduled</strong> status — nobody can
    check in yet.
</x-docs.step>

<x-docs.step :number="5" image="admin/meetings-and-attendance/create-3.png" alt="A meeting's detail page with the live QR code">
    When it's time, open the meeting and select <strong>Start Meeting (Activate QR)</strong>. A QR code appears —
    display it on a screen for members to scan. The page also shows live attendance stats and an
    <strong>Open Check-In URL</strong> link you can share directly.
</x-docs.step>

<x-docs.step :number="6">
    Below the QR code you can manually check someone in (for members without the app), mark someone
    <strong>excused</strong>, or remove an erroneous check-in. Select <strong>Close Meeting</strong> when it ends to
    stop accepting new check-ins.
</x-docs.step>
