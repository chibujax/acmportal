@extends('layouts.app')
@section('title', $duesCycle->title)
@section('page-title', $duesCycle->title)

@push('styles')
<style>
@media print {
    #sidebar, #sidebar-overlay, .topbar, .no-print { display: none !important; }
    #main-content { margin-left: 0 !important; }
    .page-content { padding: 0 !important; }
    .print-header { display: block !important; }
    .card { border: 1px solid #dee2e6 !important; box-shadow: none !important; }
    .badge { border: 1px solid #ccc; }
    body { background: #fff !important; }
}
.print-header { display: none; }
</style>
@endpush

@section('content')

{{-- Print header (hidden on screen, shown when printing) --}}
<div class="print-header mb-4">
    <div class="d-flex align-items-center gap-3 mb-1">
        <img src="{{ asset('logo.jpg') }}" alt="ACM" style="height:48px; object-fit:contain">
        <div>
            <div class="fw-bold fs-5">Abia Community Manchester</div>
            <div class="text-muted small">{{ $duesCycle->title }}</div>
        </div>
    </div>
    <div class="text-muted small">Generated: {{ now()->format('d M Y, H:i') }}</div>
    <hr>
</div>

<div class="d-flex gap-2 mb-3 flex-wrap no-print">
    <a href="{{ route('admin.dues-cycles.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
    <a href="{{ route('admin.dues-cycles.edit', $duesCycle) }}" class="btn btn-sm btn-outline-primary">
        <i class="bi bi-pencil me-1"></i>Edit
    </a>
    <a href="{{ route('admin.dues-cycles.export', $duesCycle) }}?sort={{ $sort }}&dir={{ $dir }}" class="btn btn-sm btn-outline-success">
        <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV
    </a>
    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
        <i class="bi bi-printer me-1"></i>Print / PDF
    </button>
    @if($duesCycle->is_pledge_based)
    <a href="{{ route('admin.pledges.index', $duesCycle) }}" class="btn btn-sm btn-outline-info">
        <i class="bi bi-hand-thumbs-up me-1"></i>Manage Pledges
    </a>
    <a href="{{ route('admin.payments.create', ['dues_cycle_id' => $duesCycle->id]) }}" class="btn btn-sm btn-primary">
        <i class="bi bi-cash me-1"></i>Record Payment
    </a>
    @endif
    @if($duesCycle->accepts_items && !$duesCycle->is_pledge_based)
    <a href="{{ route('admin.donation-items.index', $duesCycle) }}" class="btn btn-sm btn-outline-warning">
        <i class="bi bi-box-seam me-1"></i>Donation Items
    </a>
    @endif
    @php $remindableMembers = $allMembers->filter(fn($m) => $m->remaining > 0 && !($m->is_anonymous ?? false)); @endphp
    @if($duesCycle->send_reminders && auth()->user()->hasAccess('communications') && $remindableMembers->isNotEmpty())
    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#smsRemindersModal">
        <i class="bi bi-phone me-1"></i>SMS Reminders ({{ $remindableMembers->count() }})
    </button>
    @endif
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-4 fw-bold text-primary">£{{ number_format($totalObligation, 2) }}</div>
            <div class="small text-muted">Total Expected</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-4 fw-bold text-success">£{{ number_format($totalCollected, 2) }}</div>
            <div class="small text-muted">Money at Hand</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-4 fw-bold text-danger">£{{ number_format($totalOutstanding, 2) }}</div>
            <div class="small text-muted">Outstanding</div>
        </div>
    </div>
    @if($duesCycle->is_pledge_based)
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-4 fw-bold text-warning">{{ $pledgerCount }}</div>
            <div class="small text-muted">Members Pledged</div>
        </div>
    </div>
    @else
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-4 fw-bold text-success">{{ $allMembers->where('settled', true)->count() }}</div>
            <div class="small text-muted">Settled Members</div>
        </div>
    </div>
    @endif
</div>

{{-- Cycle Details --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-2 small">
            <div class="col-6 col-md-3"><span class="text-muted">Type:</span> {{ str_replace('_', ' ', $duesCycle->type) }}</div>
            <div class="col-6 col-md-3"><span class="text-muted">Period:</span> {{ $duesCycle->start_date->format('d M Y') }} – {{ $duesCycle->end_date->format('d M Y') }}</div>
            <div class="col-6 col-md-3"><span class="text-muted">Payment:</span> {{ $duesCycle->payment_options }}</div>
            <div class="col-6 col-md-3">
                <span class="text-muted">Status:</span>
                @if($duesCycle->status === 'active')
                    <span class="badge bg-success">Active</span>
                @elseif($duesCycle->status === 'closed')
                    <span class="badge bg-secondary">Closed</span>
                @else
                    <span class="badge bg-warning text-dark">Draft</span>
                @endif
            </div>
        </div>
        @if($duesCycle->description)
            <p class="text-muted small mt-2 mb-0">{{ $duesCycle->description }}</p>
        @endif
        <div class="mt-2 small d-flex flex-wrap gap-2">
            @if($duesCycle->couple_shared)
            <span class="badge bg-info text-dark"><i class="bi bi-people me-1"></i>Couple shared</span>
            @endif
            @if($duesCycle->is_pledge_based)
            <span class="badge bg-warning text-dark"><i class="bi bi-hand-thumbs-up me-1"></i>Pledge-based</span>
            @endif
            @if($duesCycle->accepts_items)
            <span class="badge bg-secondary"><i class="bi bi-box-seam me-1"></i>Accepts item donations</span>
            @endif
        </div>
    </div>
</div>

{{-- Per-member payment matrix --}}
@php
    $dSortUrl  = fn(string $f) => request()->fullUrlWithQuery(['sort' => $f, 'dir' => ($sort === $f && $dir === 'asc') ? 'desc' : 'asc', 'page' => null]);
    $dSortIcon = fn(string $f) => $sort !== $f ? 'bi-arrow-down-up text-muted' : ($dir === 'asc' ? 'bi-sort-down-alt text-primary' : 'bi-sort-up text-primary');
@endphp
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="fw-semibold mb-0"><i class="bi bi-people me-2 text-primary"></i>Member Payment Status</h6>
        <span class="badge bg-secondary no-print">{{ $members->total() }} member(s)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>
                        <a href="{{ $dSortUrl('name') }}" class="text-decoration-none text-dark d-flex align-items-center gap-1 no-print">
                            Member <i class="bi {{ $dSortIcon('name') }}"></i>
                        </a>
                        <span class="print-header">Member</span>
                    </th>
                    <th>Spouse</th>
                    <th>Obligation</th>
                    <th>
                        <a href="{{ $dSortUrl('paid') }}" class="text-decoration-none text-dark d-flex align-items-center gap-1 no-print">
                            Paid <i class="bi {{ $dSortIcon('paid') }}"></i>
                        </a>
                        <span class="print-header">Paid</span>
                    </th>
                    <th>
                        <a href="{{ $dSortUrl('remaining') }}" class="text-decoration-none text-dark d-flex align-items-center gap-1 no-print">
                            Remaining <i class="bi {{ $dSortIcon('remaining') }}"></i>
                        </a>
                        <span class="print-header">Remaining</span>
                    </th>
                    <th class="no-print">Progress</th>
                    <th>
                        <a href="{{ $dSortUrl('status') }}" class="text-decoration-none text-dark d-flex align-items-center gap-1 no-print">
                            Status <i class="bi {{ $dSortIcon('status') }}"></i>
                        </a>
                        <span class="print-header">Status</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse($members as $member)
                    <tr>
                        <td>
                            @if($member->is_anonymous ?? false)
                                <span class="fw-medium">{{ $member->name }}</span>
                                <span class="badge bg-light text-dark border ms-1" style="font-size:.62rem">Anonymous / non-member</span>
                            @else
                                <a href="{{ route('admin.members.show', $member) }}" class="text-decoration-none fw-medium">
                                    {{ $member->name }}
                                </a>
                                <div class="small text-muted">{{ $member->phone }}</div>
                            @endif
                        </td>
                        <td class="small text-muted">{{ $member->spouseName ?? '—' }}</td>
                        <td>£{{ number_format($member->obligation, 2) }}</td>
                        <td>£{{ number_format($member->paid, 2) }}</td>
                        <td>
                            @if($member->remaining > 0)
                                <span class="text-danger">£{{ number_format($member->remaining, 2) }}</span>
                            @else
                                <span class="text-success">—</span>
                            @endif
                        </td>
                        <td style="width:120px">
                            <div class="progress" style="height:6px">
                                <div class="progress-bar {{ $member->settled ? 'bg-success' : 'bg-warning' }}"
                                     style="width:{{ $member->percent }}%"></div>
                            </div>
                            <div class="small text-muted mt-1">{{ $member->percent }}%</div>
                        </td>
                        <td>
                            @if($member->settled)
                                <span class="badge bg-success">Settled</span>
                            @else
                                <span class="badge bg-danger">Outstanding</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No members to show.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($members->hasPages())
    <div class="card-footer bg-white no-print">{{ $members->links() }}</div>
    @endif
</div>

@if($duesCycle->accepts_items && $donationItems->isNotEmpty())
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
        <h6 class="fw-semibold mb-0"><i class="bi bi-box-seam text-warning me-2"></i>Donation Items</h6>
        <a href="{{ route('admin.donation-items.create', $duesCycle) }}" class="btn btn-sm btn-outline-warning">
            <i class="bi bi-plus me-1"></i>Add Item
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Member</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Quantity</th>
                    <th>Est. Value</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($donationItems as $item)
                <tr>
                    <td class="fw-medium small">{{ $item->user->name }}</td>
                    <td>
                        <span class="badge {{ $item->item_type === 'money' ? 'bg-success' : 'bg-secondary' }}">
                            {{ ucfirst($item->item_type) }}
                        </span>
                    </td>
                    <td class="small">{{ $item->description }}</td>
                    <td class="small text-muted">{{ $item->quantity ?? '—' }}</td>
                    <td class="small">{{ $item->formattedValue() }}</td>
                    <td class="small text-muted">{{ $item->donation_date->format('d M Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@elseif($duesCycle->accepts_items)
<div class="card border-0 shadow-sm mt-4">
    <div class="card-body d-flex justify-content-between align-items-center">
        <span class="text-muted small">No donation items recorded yet.</span>
        <a href="{{ route('admin.donation-items.create', $duesCycle) }}" class="btn btn-sm btn-outline-warning">
            <i class="bi bi-plus me-1"></i>Record Item
        </a>
    </div>
</div>
@endif

@if($duesCycle->send_reminders && auth()->user()->hasAccess('communications'))
@php $outstandingMembers = $allMembers->filter(fn($m) => $m->remaining > 0 && !($m->is_anonymous ?? false)); @endphp
@if($outstandingMembers->isNotEmpty())
{{-- SMS Reminders Modal --}}
<div class="modal fade" id="smsRemindersModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.dues-cycles.send-reminders', $duesCycle) }}">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-semibold"><i class="bi bi-phone me-2"></i>Send SMS Reminders</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    {{-- Channel selector --}}
                    <div class="mb-3">
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <input type="radio" class="btn-check" name="channel" id="rm-ch-sms" value="sms" checked>
                            <label class="btn btn-outline-primary" for="rm-ch-sms"><i class="bi bi-phone me-1"></i>SMS</label>
                            <input type="radio" class="btn-check" name="channel" id="rm-ch-email" value="email">
                            <label class="btn btn-outline-primary" for="rm-ch-email"><i class="bi bi-envelope me-1"></i>Email</label>
                        </div>
                    </div>

                    {{-- Template picker --}}
                    @php $smsTemplates = \App\Models\SmsTemplate::orderBy('name')->get(); @endphp
                    @if($smsTemplates->isNotEmpty())
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Load a template</label>
                        <select id="reminder-template" class="form-select form-select-sm">
                            <option value="">— Custom message —</option>
                            @foreach($smsTemplates as $tpl)
                            <option value="{{ $tpl->body }}"
                                    data-channel="{{ $tpl->channel }}"
                                    data-subject="{{ $tpl->subject ?? '' }}">{{ $tpl->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    {{-- Subject (email only) --}}
                    <div class="mb-2 d-none" id="reminder-subject-wrap">
                        <label class="form-label small fw-medium">Subject</label>
                        <input type="text" name="subject" id="reminder-subject"
                               class="form-control form-control-sm" maxlength="255"
                               placeholder="e.g. ACM Portal – Outstanding Balance">
                    </div>

                    {{-- Message --}}
                    <div class="mb-2">
                        <label class="form-label small fw-medium">Message</label>
                        <textarea name="message" id="reminder-message" rows="3" class="form-control @error('message') is-invalid @enderror"
                                  maxlength="160" required
                                  placeholder="Hi {name}, you have an outstanding balance of £{amount} for {cycle}.">{{ old('message') }}</textarea>
                        @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="d-flex justify-content-between mt-1">
                            <div class="form-text">
                                <code>{name}</code> &nbsp;·&nbsp;
                                <code>{amount}</code> &nbsp;·&nbsp;
                                <code>{cycle}</code> &nbsp;·&nbsp;
                                <code>{donations}</code> items donated
                            </div>
                            <div class="form-text" id="reminder-char-wrap"><span id="reminder-char-count">0</span> / 160</div>
                        </div>
                    </div>

                    {{-- Recipient list --}}
                    <div class="mb-1">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-medium mb-0">
                                Recipients — {{ $outstandingMembers->count() }} with outstanding balance
                            </label>
                            <button type="button" id="reminder-toggle-all" class="btn btn-link btn-sm p-0 text-decoration-none small">
                                Deselect all
                            </button>
                        </div>
                        <div class="border rounded p-2" style="max-height:200px; overflow-y:auto">
                            @foreach($outstandingMembers as $om)
                            <div class="form-check py-1 border-bottom">
                                <input class="form-check-input reminder-recipient"
                                       type="checkbox" name="user_ids[]"
                                       value="{{ $om->id }}" id="rm-{{ $om->id }}"
                                       data-phone="{{ $om->phone ? '1' : '0' }}"
                                       data-email="{{ $om->email ? '1' : '0' }}">
                                <label class="form-check-label small d-flex justify-content-between w-100" for="rm-{{ $om->id }}">
                                    <span>{{ $om->name }}</span>
                                    <span class="rm-phone-info text-muted ms-1 small">{{ $om->phone ?? 'no phone' }}</span>
                                    <span class="rm-email-info text-muted ms-1 small d-none">{{ $om->email ?? 'no email' }}</span>
                                    <span class="text-danger ms-auto">£{{ number_format($om->remaining, 2) }}</span>
                                </label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="reminder-send-btn" class="btn btn-sm btn-danger">
                        <i class="bi bi-send me-1"></i>Send Reminders
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const tplSelect   = document.getElementById('reminder-template');
    const msgArea     = document.getElementById('reminder-message');
    const counter     = document.getElementById('reminder-char-count');
    const charWrap    = document.getElementById('reminder-char-wrap');
    const toggleBtn   = document.getElementById('reminder-toggle-all');
    const reminderForm= document.querySelector('#smsRemindersModal form');
    const sendBtn     = document.getElementById('reminder-send-btn');
    const subjectWrap = document.getElementById('reminder-subject-wrap');
    const subjectIn   = document.getElementById('reminder-subject');
    const chSms       = document.getElementById('rm-ch-sms');
    const chEmail     = document.getElementById('rm-ch-email');

    function updateCount() { counter.textContent = msgArea.value.length; }
    msgArea.addEventListener('input', updateCount);
    updateCount();

    function applyChannel(ch) {
        if (ch === 'sms') {
            msgArea.setAttribute('maxlength', '160');
            charWrap.classList.remove('d-none');
            subjectWrap.classList.add('d-none');
            subjectIn.removeAttribute('required');
        } else {
            msgArea.removeAttribute('maxlength');
            charWrap.classList.add('d-none');
            subjectWrap.classList.remove('d-none');
            subjectIn.setAttribute('required', '');
        }
        document.querySelectorAll('.reminder-recipient').forEach(cb => {
            const has = ch === 'email' ? cb.dataset.email === '1' : cb.dataset.phone === '1';
            cb.disabled = !has;
            if (!has) cb.checked = false;
        });
        document.querySelectorAll('.rm-phone-info').forEach(el => el.classList.toggle('d-none', ch === 'email'));
        document.querySelectorAll('.rm-email-info').forEach(el => el.classList.toggle('d-none', ch === 'sms'));
    }

    chSms.addEventListener('change',   () => applyChannel('sms'));
    chEmail.addEventListener('change', () => applyChannel('email'));

    if (tplSelect) {
        tplSelect.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (this.value) {
                msgArea.value = this.value;
                if (opt.dataset.subject) subjectIn.value = opt.dataset.subject;
                updateCount();
            }
        });
    }

    if (reminderForm && sendBtn) {
        reminderForm.addEventListener('submit', function () {
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Sending…';
        });
    }

    if (toggleBtn) {
        let allSelected = false;
        toggleBtn.textContent = 'Select all';
        toggleBtn.addEventListener('click', function () {
            allSelected = !allSelected;
            document.querySelectorAll('.reminder-recipient:not(:disabled)').forEach(cb => cb.checked = allSelected);
            this.textContent = allSelected ? 'Deselect all' : 'Select all';
        });
    }
})();
</script>
@endpush
@endif
@endif

@endsection
