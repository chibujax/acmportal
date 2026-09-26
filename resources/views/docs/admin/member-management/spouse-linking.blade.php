<p class="text-muted">
    Members can link their own spouse from their profile, but sometimes a link is missing, wrong, or needs to be
    set up on someone's behalf. Admins with the <strong>Family & Spouse Linking</strong> permission can manage this
    directly.
</p>

<x-docs.step :number="1">
    Go to <strong>Spouse Linking</strong> under Family Records in the sidebar. You'll see every currently linked
    pair, searchable by name.
</x-docs.step>

<x-docs.step :number="2">
    To link two members, search for and select the first member, then the second, and select <strong>Link</strong>.
    Neither member can already have a spouse linked — if one does, unlink that pairing first.
</x-docs.step>

<x-docs.step :number="3">
    To correct a wrong pairing, select <strong>Unlink</strong> on the incorrect pair first, then link the correct
    pair as above. This two-step approach keeps the history clear rather than silently overwriting a link.
</x-docs.step>

<div class="alert alert-info small mt-3">
    <i class="bi bi-info-circle me-1"></i>
    Every link and unlink made here is recorded in the <strong>Audit Trail</strong> with both members' names —
    unlike a member linking their own spouse, which isn't logged.
</div>

<div class="alert alert-warning small mt-3">
    <i class="bi bi-exclamation-triangle me-1"></i>
    Linking or unlinking a spouse changes how dues are calculated for both members from that point on — see
    <em>Linking or Unlinking a Spouse</em> in the Member Guide for what changes on couple-shared vs. regular cycles.
</div>
