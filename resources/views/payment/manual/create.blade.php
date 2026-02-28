@extends('layouts.app')
@section('title','Record Payment')
@section('page-title','Record Manual Payment')


@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="fw-semibold mb-0">
                    <i class="bi bi-cash-coin text-success me-2"></i>
                    Record Cash / Bank Transfer Payment
                </h6>
                <small class="text-muted">Financial Secretary use only</small>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.payments.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-medium">Member <span class="text-danger">*</span></label>
                        <input type="hidden" name="user_id" id="memberIdInput" value="{{ old('user_id') }}" required>
                        <input type="text" id="memberSearch"
                               class="form-control @error('user_id') is-invalid @enderror"
                               placeholder="Type name or phone to search…"
                               autocomplete="off"
                               value="{{ old('user_id') ? $members->firstWhere('id', old('user_id'))?->name : '' }}">
                        <div id="memberSuggestions" class="list-group shadow-sm mt-1" style="display:none; max-height:220px; overflow-y:auto; position:absolute; z-index:999; width:100%"></div>
                        <div id="memberSelected" class="mt-1 small text-success {{ old('user_id') ? '' : 'd-none' }}">
                            <i class="bi bi-check-circle me-1"></i><span id="memberSelectedName">
                                {{ old('user_id') ? $members->firstWhere('id', old('user_id'))?->name : '' }}
                            </span>
                            <a href="#" id="memberClear" class="ms-2 text-danger small">Change</a>
                        </div>
                        @error('user_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Dues Cycle</label>
                        <select name="dues_cycle_id" id="cycleSelect" class="form-select">
                            <option value="">— General / Unspecified —</option>
                            @foreach($cycles as $c)
                            <option value="{{ $c->id }}" {{ old('dues_cycle_id') == $c->id ? 'selected' : '' }}>
                                {{ $c->title }} (£{{ number_format($c->amount,2) }})
                            </option>
                            @endforeach
                        </select>
                        <div id="obligationHint" class="form-text text-info d-none"></div>
                        <div id="pledgeHint" class="form-text text-warning d-none"></div>
                    </div>

                    {{-- Pay for Spouse checkbox (shown dynamically via JS) --}}
                    <div id="couplePayWrap" class="mb-3 d-none">
                        <div class="form-check p-3 rounded" style="border:1px solid #e5e7eb; background:#f0fdf4">
                            <input type="hidden" name="pay_for_spouse" value="0">
                            <input type="checkbox" name="pay_for_spouse" value="1" id="payForSpouse" class="form-check-input">
                            <label class="form-check-label" for="payForSpouse">
                                <strong>Pay for spouse too</strong>
                                <span id="spouseNameHint" class="text-muted small d-block"></span>
                            </label>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-medium">Amount (GBP) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">£</span>
                                <input type="number" name="amount" step="0.01" min="0.01"
                                       class="form-control @error('amount') is-invalid @enderror"
                                       value="{{ old('amount') }}" required>
                                @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-medium">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date"
                                   class="form-control @error('payment_date') is-invalid @enderror"
                                   value="{{ old('payment_date', today()->toDateString()) }}" required>
                            @error('payment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Notes</label>
                        <textarea name="notes" rows="2" class="form-control"
                                  placeholder="e.g. Cash received at monthly meeting">{{ old('notes') }}</textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">Proof of Payment <span class="text-muted small">(optional)</span></label>
                        <input type="file" name="proof" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                        <small class="text-muted">Max 4MB. JPG, PNG or PDF.</small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle me-1"></i>Record Payment
                        </button>
                        <a href="{{ route('admin.payments.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="application/json" id="memberData">{!! json_encode($members->map(fn($m) => ['id' => $m->id, 'name' => $m->name, 'phone' => $m->phone, 'has_spouse' => isset($membersWithSpouse[$m->id])])) !!}</script>
<script type="application/json" id="cycleData">{!! json_encode($cycles->map(fn($c) => ['id' => $c->id, 'couple_shared' => (bool) $c->couple_shared, 'is_pledge_based' => (bool) $c->is_pledge_based, 'amount' => $c->amount])) !!}</script>
<script type="application/json" id="spouseMapData">{!! json_encode($spouseMap) !!}</script>
<script type="application/json" id="pledgeMapData">{!! json_encode($pledgeMap) !!}</script>
<script>
(function () {
    const members        = JSON.parse(document.getElementById('memberData').textContent);
    const cycles         = JSON.parse(document.getElementById('cycleData').textContent);
    const spouseMap      = JSON.parse(document.getElementById('spouseMapData').textContent);
    const pledgeMap      = JSON.parse(document.getElementById('pledgeMapData').textContent);

    const searchInput    = document.getElementById('memberSearch');
    const idInput        = document.getElementById('memberIdInput');
    const suggestions    = document.getElementById('memberSuggestions');
    const selectedDiv    = document.getElementById('memberSelected');
    const selectedName   = document.getElementById('memberSelectedName');
    const clearBtn       = document.getElementById('memberClear');
    const cycleSelect    = document.getElementById('cycleSelect');
    const amountInput    = document.querySelector('input[name="amount"]');
    const obligationHint = document.getElementById('obligationHint');
    const pledgeHint     = document.getElementById('pledgeHint');
    const couplePayWrap  = document.getElementById('couplePayWrap');
    const spouseNameHint = document.getElementById('spouseNameHint');
    const payForSpouse   = document.getElementById('payForSpouse');

    let selectedMember = null;

    function updateObligation() {
        const cycleId = parseInt(cycleSelect.value);

        // Reset UI
        obligationHint.classList.add('d-none');
        pledgeHint.classList.add('d-none');
        couplePayWrap.classList.add('d-none');
        if (payForSpouse) payForSpouse.checked = false;

        if (!selectedMember || !cycleId) return;

        const cycle = cycles.find(c => c.id === cycleId);
        if (!cycle) return;

        const memberId = selectedMember.id;

        // Pledge-based cycle: use pledge amount if available
        if (cycle.is_pledge_based) {
            const memberPledges = pledgeMap[memberId];
            const pledgeAmt = memberPledges ? memberPledges[cycleId] : null;
            if (pledgeAmt) {
                amountInput.value = parseFloat(pledgeAmt).toFixed(2);
                pledgeHint.textContent = `Pledge on record: £${parseFloat(pledgeAmt).toFixed(2)}`;
                pledgeHint.classList.remove('d-none');
            } else {
                pledgeHint.textContent = 'No pledge recorded for this member — enter amount manually.';
                pledgeHint.classList.remove('d-none');
            }
            return;
        }

        // Flat-rate cycle
        let obligation = cycle.amount;
        if (cycle.couple_shared) {
            obligation = selectedMember.has_spouse ? cycle.amount : cycle.amount / 2;
            const label = selectedMember.has_spouse ? 'Couple rate' : 'Single rate';
            obligationHint.textContent = `${label}: £${obligation.toFixed(2)}`;
            obligationHint.classList.remove('d-none');
        } else {
            obligationHint.textContent = `Obligation: £${obligation.toFixed(2)}`;
            obligationHint.classList.remove('d-none');
        }
        amountInput.value = obligation.toFixed(2);

        // Show "pay for spouse" checkbox only if member has spouse AND cycle is NOT couple_shared
        if (selectedMember.has_spouse && !cycle.couple_shared) {
            const spouse = spouseMap[memberId];
            if (spouse) {
                spouseNameHint.textContent = `This will also create a £${obligation.toFixed(2)} record for ${spouse.name}.`;
                couplePayWrap.classList.remove('d-none');
            }
        }
    }

    cycleSelect.addEventListener('change', updateObligation);

    searchInput.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        suggestions.innerHTML = '';

        if (q.length < 2) {
            suggestions.style.display = 'none';
            return;
        }

        const matches = members.filter(m =>
            m.name.toLowerCase().includes(q) || m.phone.toLowerCase().includes(q)
        ).slice(0, 50);

        if (matches.length === 0) {
            suggestions.innerHTML = '<div class="list-group-item text-muted small">No members found.</div>';
        } else {
            matches.forEach(m => {
                const a = document.createElement('a');
                a.href = '#';
                a.className = 'list-group-item list-group-item-action small';
                a.innerHTML = `<strong>${m.name}</strong> <span class="text-muted">${m.phone}</span>`;
                a.addEventListener('click', function (e) {
                    e.preventDefault();
                    idInput.value            = m.id;
                    selectedMember           = m;
                    selectedName.textContent = m.name + ' (' + m.phone + ')';
                    selectedDiv.classList.remove('d-none');
                    searchInput.style.display = 'none';
                    suggestions.style.display = 'none';
                    updateObligation();
                });
                suggestions.appendChild(a);
            });
        }

        suggestions.style.display = 'block';
    });

    clearBtn.addEventListener('click', function (e) {
        e.preventDefault();
        idInput.value             = '';
        selectedMember            = null;
        searchInput.value         = '';
        searchInput.style.display = '';
        selectedDiv.classList.add('d-none');
        obligationHint.classList.add('d-none');
        pledgeHint.classList.add('d-none');
        couplePayWrap.classList.add('d-none');
        searchInput.focus();
    });

    document.addEventListener('click', function (e) {
        if (!suggestions.contains(e.target) && e.target !== searchInput) {
            suggestions.style.display = 'none';
        }
    });
})();
</script>
@endpush
