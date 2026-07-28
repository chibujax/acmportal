@extends('layouts.app')
@section('title', 'Contributions – ' . $duesCycle->title)
@section('page-title', 'Contributions: ' . $duesCycle->title)

@section('content')
<div class="d-flex gap-2 mb-3">
    <a href="{{ route('admin.dues-cycles.show', $duesCycle) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Cycle
    </a>
</div>


<div class="row g-4">
    {{-- Record Contribution Form --}}
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="fw-semibold mb-0"><i class="bi bi-hand-thumbs-up text-info me-2"></i>Record Contribution</h6>
                <small class="text-muted">Enter pledge amount and/or items for a member.</small>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.pledges.store', $duesCycle) }}">
                    @csrf

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="anonymousToggle" {{ old('donor_name') ? 'checked' : '' }}>
                        <label class="form-check-label small fw-medium" for="anonymousToggle">
                            Anonymous / non-member donor
                        </label>
                    </div>

                    {{-- Member Search --}}
                    <div class="mb-3 position-relative" id="memberSearchWrap">
                        <label class="form-label fw-medium">Member <span class="text-danger">*</span></label>
                        <input type="text" id="memberSearch"
                               class="form-control @error('user_id') is-invalid @enderror"
                               placeholder="Type name or phone to search…"
                               autocomplete="off">
                        <input type="hidden" name="user_id" id="userId" value="{{ old('user_id') }}">
                        <div id="memberDropdown"
                             class="position-absolute w-100 bg-white border rounded shadow-sm"
                             style="display:none; z-index:1050; max-height:220px; overflow-y:auto; top:100%; left:0">
                        </div>
                        <div id="memberPhone" class="mt-1 small text-muted d-none"></div>
                        @error('user_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    {{-- Anonymous / non-member donor name --}}
                    <div class="mb-3 d-none" id="donorNameWrap">
                        <label class="form-label fw-medium">Donor Name <span class="text-danger">*</span></label>
                        <input type="text" name="donor_name" id="donorName"
                               class="form-control @error('donor_name') is-invalid @enderror"
                               value="{{ old('donor_name') }}"
                               placeholder="e.g. Anonymous, or a visitor's name">
                        @error('donor_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Not linked to any member account — recorded as a standalone entry.</div>
                    </div>

                    {{-- Money Pledge --}}
                    <div class="mb-3">
                        <label class="form-label fw-medium">Pledge Amount <span class="text-muted small">(optional)</span></label>
                        <div class="input-group">
                            <span class="input-group-text">{{ $duesCycle->currency }}</span>
                            <input type="number" name="pledged_amount" step="0.01" min="0.01"
                                   class="form-control @error('pledged_amount') is-invalid @enderror"
                                   value="{{ old('pledged_amount') }}"
                                   placeholder="Leave blank if no money pledge">
                            @error('pledged_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-text" id="pledgeAmountHint">Updates existing pledge if already recorded for this member.</div>
                    </div>

                    {{-- Received amount — anonymous donors only, since there's no member account to log a payment against --}}
                    <div class="mb-3 d-none" id="receivedAmountWrap">
                        <label class="form-label fw-medium">Amount Already Received <span class="text-muted small">(optional)</span></label>
                        <div class="input-group">
                            <span class="input-group-text">{{ $duesCycle->currency }}</span>
                            <input type="number" name="received_amount" step="0.01" min="0"
                                   class="form-control @error('received_amount') is-invalid @enderror"
                                   value="{{ old('received_amount') }}"
                                   placeholder="0.00">
                            @error('received_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-text">Anonymous donors have no member account to record a payment against — enter what's already been received here directly.</div>
                    </div>

                    <div class="form-check mb-3" id="sharedWithSpouseWrap">
                        <input class="form-check-input" type="checkbox" name="shared_with_spouse" value="1"
                               id="sharedWithSpouse" {{ old('shared_with_spouse') ? 'checked' : '' }}>
                        <label class="form-check-label small fw-medium" for="sharedWithSpouse">
                            Shared with spouse — counts as their pledge too if they haven't pledged separately
                        </label>
                        <div class="form-text">Leave unchecked if this member's spouse wants to give separately.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Notes <span class="text-muted small">(optional)</span></label>
                        <textarea name="pledge_notes" rows="2" class="form-control"
                                  placeholder="e.g. Pledged at Feb meeting">{{ old('pledge_notes') }}</textarea>
                    </div>

                    @if($duesCycle->accepts_items)
                    <div id="itemContributionsWrap">
                        <hr class="my-3">

                        {{-- Item Contributions --}}
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="fw-medium mb-0 small">Item Contributions <span class="text-muted small">(optional)</span></label>
                            <button type="button" onclick="addItemRow()" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-plus me-1"></i>Add Item
                            </button>
                        </div>
                        <div id="itemRows" class="mb-2"></div>
                    </div>
                    @endif

                    <button type="submit" class="btn btn-info text-white w-100 mt-1">
                        <i class="bi bi-check-circle me-1"></i>Save Contribution
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Right: Summary + Detailed Tables --}}
    <div class="col-12 col-lg-8">

        {{-- Per-Member Combined Summary --}}
        @php
            $pledgeMap      = $pledges->keyBy('user_id');
            $itemsMap       = $donationItems->groupBy('user_id');
            $contributorIds = $pledges->pluck('user_id')
                                ->merge($donationItems->pluck('user_id'))
                                ->unique();
            $userMap = $pledges->pluck('user')
                        ->merge($donationItems->pluck('user'))
                        ->unique('id')
                        ->keyBy('id');
        @endphp
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="fw-semibold mb-0">
                    <i class="bi bi-person-lines-fill text-primary me-2"></i>Member Contributions
                </h6>
            </div>
            @if($contributorIds->isEmpty())
            <div class="card-body text-muted small">No contributions recorded yet.</div>
            @else
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Member</th>
                            <th>Pledged</th>
                            <th>Paid</th>
                            <th>Balance</th>
                            @if($duesCycle->accepts_items)
                            <th>Items</th>
                            @endif
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($contributorIds as $uid)
                        @php
                            $u        = $userMap[$uid] ?? null;
                            $pledge   = $pledgeMap[$uid] ?? null;
                            $items    = $itemsMap[$uid] ?? collect();
                            $paid     = $paymentsMap[$uid] ?? 0;
                            $pledged  = $pledge ? $pledge->pledged_amount : 0;
                            $balance  = max(0, $pledged - $paid);
                        @endphp
                        @if($u)
                        <tr>
                            <td>
                                <div class="fw-medium small">{{ $u->name }}</div>
                                <div class="text-muted" style="font-size:.72rem">{{ $u->phone }}</div>
                            </td>
                            <td class="small">
                                @if($pledge)
                                    <span class="text-success fw-semibold">{{ $duesCycle->currency }} {{ number_format($pledged, 2) }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="small">
                                @if($paid > 0)
                                    <span class="text-primary fw-semibold">{{ $duesCycle->currency }} {{ number_format($paid, 2) }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="small">
                                @if($pledge)
                                    @if($balance <= 0)
                                        <span class="badge bg-success">Settled</span>
                                    @else
                                        <span class="text-danger fw-semibold">{{ $duesCycle->currency }} {{ number_format($balance, 2) }}</span>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            @if($duesCycle->accepts_items)
                            <td class="small">
                                @if($items->isNotEmpty())
                                    @foreach($items as $item)
                                    <div class="d-flex align-items-center gap-1">
                                        @if($item->is_fulfilled)
                                            <i class="bi bi-check-circle-fill text-success" title="Received"></i>
                                        @else
                                            <i class="bi bi-circle text-muted" title="Pending"></i>
                                        @endif
                                        <span>{{ $item->quantity ? $item->quantity . ' ' : '' }}{{ $item->description }}</span>
                                    </div>
                                    @endforeach
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            @endif
                            <td>
                                <a href="{{ route('admin.payments.create', ['user_id' => $uid, 'dues_cycle_id' => $duesCycle->id]) }}"
                                   class="btn btn-sm btn-primary"
                                   title="Record payment for this member">
                                    <i class="bi bi-cash me-1"></i>Record Payment
                                </a>
                            </td>
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        {{-- Money Pledges --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-semibold mb-0">
                    <i class="bi bi-cash-coin text-success me-2"></i>Pledged Amounts
                </h6>
                <span class="badge bg-info text-dark">{{ $pledges->count() }}</span>
            </div>
            @if($pledges->isEmpty())
            <div class="card-body text-muted small">No money pledges recorded yet.</div>
            @else
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Member</th>
                            <th>Pledged</th>
                            <th>Redeemed</th>
                            <th>Balance</th>
                            <th>Notes</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pledges as $pledge)
                        @php
                            $redemption = $pledge->user_id ? ($paymentsMap[$pledge->user_id] ?? 0) : $pledge->received_amount;
                            $bal        = max(0, $pledge->pledged_amount - $redemption);
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-medium small">{{ $pledge->displayName() }}</div>
                                @if($pledge->user)
                                    <div class="text-muted" style="font-size:.72rem">{{ $pledge->user->phone }}</div>
                                @else
                                    <span class="badge bg-light text-dark border" style="font-size:.62rem">Anonymous / non-member</span>
                                @endif
                                @if($pledge->shared_with_spouse)
                                    <span class="badge bg-secondary" style="font-size:.62rem"><i class="bi bi-people-fill me-1"></i>Shared with spouse</span>
                                @endif
                            </td>
                            <td class="fw-semibold text-success">
                                {{ $duesCycle->currency }} {{ number_format($pledge->pledged_amount, 2) }}
                            </td>
                            <td class="small">
                                @if($redemption > 0)
                                    <span class="text-primary fw-semibold">{{ $duesCycle->currency }} {{ number_format($redemption, 2) }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="small">
                                @if($bal <= 0)
                                    <span class="badge bg-success">Settled</span>
                                @else
                                    <span class="text-danger fw-semibold">{{ $duesCycle->currency }} {{ number_format($bal, 2) }}</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $pledge->notes ?? '—' }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.pledges.destroy', $pledge) }}"
                                      onsubmit="return confirm('Remove this pledge?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        @php
                            $totalPledged   = $pledges->sum('pledged_amount');
                            $totalRedeemed  = $pledges->sum(fn($p) => $p->user_id ? ($paymentsMap[$p->user_id] ?? 0) : $p->received_amount);
                            $totalBalance   = max(0, $totalPledged - $totalRedeemed);
                        @endphp
                        <tr>
                            <th>Totals</th>
                            <th class="text-success">{{ $duesCycle->currency }} {{ number_format($totalPledged, 2) }}</th>
                            <th class="text-primary">{{ $duesCycle->currency }} {{ number_format($totalRedeemed, 2) }}</th>
                            <th class="{{ $totalBalance > 0 ? 'text-danger' : 'text-success' }}">
                                {{ $duesCycle->currency }} {{ number_format($totalBalance, 2) }}
                            </th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endif
        </div>

        @if($duesCycle->accepts_items)
        {{-- Item Contributions --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-semibold mb-0">
                    <i class="bi bi-box-seam text-warning me-2"></i>Item Contributions
                </h6>
                <span class="badge bg-warning text-dark">{{ $donationItems->count() }}</span>
            </div>
            @if($donationItems->isEmpty())
            <div class="card-body text-muted small">No item contributions recorded yet.</div>
            @else
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Member</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Qty</th>
                            <th>Est. Value</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($donationItems as $item)
                        <tr>
                            <td>
                                <div class="fw-medium small">{{ $item->user->name }}</div>
                                <div class="text-muted" style="font-size:.72rem">{{ $item->user->phone }}</div>
                            </td>
                            <td>
                                <span class="badge {{ $item->item_type === 'money' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ ucfirst($item->item_type) }}
                                </span>
                            </td>
                            <td class="small">{{ $item->description }}</td>
                            <td class="small text-muted">{{ $item->quantity ?? '—' }}</td>
                            <td class="small fw-medium">{{ $item->formattedValue() }}</td>
                            <td>
                                @if($item->is_fulfilled)
                                    <span class="badge bg-success">Fully Received</span>
                                    <div class="text-muted" style="font-size:.68rem">{{ $item->fulfilled_at?->format('d M Y') }}</div>
                                @elseif($item->fulfilled_quantity)
                                    <span class="badge bg-info text-dark">Partial</span>
                                    <div class="text-muted" style="font-size:.68rem">{{ $item->fulfilled_quantity }}</div>
                                @else
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    @if($item->is_fulfilled)
                                    {{-- Un-fulfill toggle --}}
                                    <form method="POST" action="{{ route('admin.donation-items.fulfill', $item) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-success" title="Mark as pending">
                                            <i class="bi bi-check-circle-fill"></i>
                                        </button>
                                    </form>
                                    @else
                                    {{-- Open partial redemption modal --}}
                                    <button class="btn btn-sm btn-outline-success"
                                            title="Record receipt"
                                            data-bs-toggle="modal"
                                            data-bs-target="#fulfillModal{{ $item->id }}">
                                        <i class="bi bi-box-arrow-in-down me-1"></i>Redeem
                                    </button>
                                    @endif
                                    <form method="POST" action="{{ route('admin.donation-items.destroy', $item) }}"
                                          onsubmit="return confirm('Remove this item?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>

                                {{-- Partial redemption modal --}}
                                @if(!$item->is_fulfilled)
                                <div class="modal fade" id="fulfillModal{{ $item->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-sm">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h6 class="modal-title">Record Receipt</h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST" action="{{ route('admin.donation-items.fulfill', $item) }}">
                                                @csrf
                                                <div class="modal-body">
                                                    <p class="small text-muted mb-2">
                                                        Pledged: <strong>{{ $item->quantity ? $item->quantity . ' — ' : '' }}{{ $item->description }}</strong>
                                                    </p>
                                                    @if($item->fulfilled_quantity)
                                                    <p class="small text-info mb-2">
                                                        Previously recorded: <strong>{{ $item->fulfilled_quantity }}</strong>
                                                    </p>
                                                    @endif
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-medium">Quantity Received</label>
                                                        <input type="text" name="fulfilled_quantity"
                                                               class="form-control form-control-sm"
                                                               value="{{ $item->fulfilled_quantity }}"
                                                               placeholder="e.g. 1 carton">
                                                        <div class="form-text">Leave blank if recording full receipt below.</div>
                                                    </div>
                                                    <div class="form-check">
                                                        <input type="hidden" name="is_fully_fulfilled" value="0">
                                                        <input type="checkbox" name="is_fully_fulfilled" value="1"
                                                               id="fullyFulfilled{{ $item->id }}" class="form-check-input">
                                                        <label class="form-check-label small" for="fullyFulfilled{{ $item->id }}">
                                                            Mark as <strong>fully received</strong>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="modal-footer py-2">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-sm btn-success">Save</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
        @endif

    </div>
</div>

<script>
const members = @json($members->map(fn($m) => ['id' => $m->id, 'name' => $m->name, 'phone' => $m->phone ?? '']));

const searchInput = document.getElementById('memberSearch');
const userIdInput = document.getElementById('userId');
const dropdown    = document.getElementById('memberDropdown');
const phoneEl     = document.getElementById('memberPhone');

// Anonymous / non-member donor toggle
const anonToggle       = document.getElementById('anonymousToggle');
const memberSearchWrap = document.getElementById('memberSearchWrap');
const donorNameWrap    = document.getElementById('donorNameWrap');
const donorNameInput   = document.getElementById('donorName');
const receivedWrap     = document.getElementById('receivedAmountWrap');
const sharedWrap       = document.getElementById('sharedWithSpouseWrap');
const itemsWrap        = document.getElementById('itemContributionsWrap');
const pledgeHint       = document.getElementById('pledgeAmountHint');

function applyAnonymousMode(isAnon) {
    memberSearchWrap.classList.toggle('d-none', isAnon);
    donorNameWrap.classList.toggle('d-none', !isAnon);
    receivedWrap.classList.toggle('d-none', !isAnon);
    sharedWrap.classList.toggle('d-none', isAnon);
    if (itemsWrap) itemsWrap.classList.toggle('d-none', isAnon);
    pledgeHint.textContent = isAnon
        ? 'Recorded as a standalone entry, not linked to any account.'
        : 'Updates existing pledge if already recorded for this member.';
    if (isAnon) {
        userIdInput.value = '';
        searchInput.value = '';
        phoneEl.classList.add('d-none');
    } else {
        donorNameInput.value = '';
    }
}

anonToggle.addEventListener('change', function () {
    applyAnonymousMode(this.checked);
});
applyAnonymousMode(anonToggle.checked);

// Pre-fill on validation failure
@if(old('user_id'))
const pre = members.find(m => m.id == {{ old('user_id') }});
if (pre) { searchInput.value = pre.name; showPhone(pre.phone); }
@endif

searchInput.addEventListener('input', function () {
    const q = this.value.toLowerCase().trim();
    userIdInput.value = '';
    phoneEl.classList.add('d-none');
    if (q.length < 2) { dropdown.style.display = 'none'; return; }

    const matches = members.filter(m =>
        m.name.toLowerCase().includes(q) || m.phone.includes(q)
    ).slice(0, 10);

    if (!matches.length) { dropdown.style.display = 'none'; return; }

    dropdown.innerHTML = matches.map(m =>
        `<div class="px-3 py-2" style="cursor:pointer"
              data-member-id="${m.id}"
              onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background=''">
            <div class="small fw-medium">${m.name}</div>
            <div class="text-muted" style="font-size:.72rem">${m.phone}</div>
        </div>`
    ).join('');
    dropdown.style.display = '';
});

function selectMember(id, name, phone) {
    searchInput.value = name;
    userIdInput.value = id;
    dropdown.style.display = 'none';
    showPhone(phone);
}

function showPhone(phone) {
    if (phone) {
        phoneEl.textContent = phone;
        phoneEl.classList.remove('d-none');
    } else {
        phoneEl.classList.add('d-none');
    }
}

dropdown.addEventListener('click', function (e) {
    const item = e.target.closest('[data-member-id]');
    if (item) {
        const m = members.find(m => m.id == item.dataset.memberId);
        if (m) selectMember(m.id, m.name, m.phone);
    }
});

document.addEventListener('click', function (e) {
    if (!dropdown.contains(e.target) && e.target !== searchInput) {
        dropdown.style.display = 'none';
    }
});

@if($duesCycle->accepts_items)
let itemCount = 0;

function addItemRow() {
    itemCount++;
    const div = document.createElement('div');
    div.className = 'border rounded p-2 mb-2 position-relative';
    div.id = 'item-' + itemCount;
    div.innerHTML = `
        <button type="button" onclick="removeItem(${itemCount})"
                class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 m-1 py-0 px-1" style="line-height:1.2">
            <i class="bi bi-x"></i>
        </button>
        <div class="row g-2 pe-4">
            <div class="col-6">
                <label class="form-label small mb-1">Type</label>
                <select name="items[${itemCount}][item_type]" class="form-select form-select-sm">
                    <option value="other">Physical item</option>
                    <option value="money">Cash</option>
                </select>
            </div>
            <div class="col-6">
                <label class="form-label small mb-1">Quantity <span class="text-muted">(opt)</span></label>
                <input type="text" name="items[${itemCount}][quantity]"
                       class="form-control form-control-sm" placeholder="e.g. 2 crates">
            </div>
            <div class="col-12">
                <label class="form-label small mb-1">Description <span class="text-danger">*</span></label>
                <input type="text" name="items[${itemCount}][description]"
                       class="form-control form-control-sm" required
                       placeholder="e.g. 2 crates of Guinness">
            </div>
            <div class="col-12">
                <label class="form-label small mb-1">Est. Value <span class="text-muted">(opt)</span></label>
                <input type="number" name="items[${itemCount}][estimated_value]"
                       class="form-control form-control-sm" step="0.01" min="0" placeholder="0.00">
            </div>
        </div>`;
    document.getElementById('itemRows').appendChild(div);
}

function removeItem(id) {
    document.getElementById('item-' + id).remove();
}
@endif
</script>
@endsection
