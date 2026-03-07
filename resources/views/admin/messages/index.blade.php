@extends('layouts.app')
@section('title', 'Bulk Message')
@section('page-title', 'Bulk Message')

@section('content')
<div class="row g-4">

    {{-- Left: Controls --}}
    <div class="col-lg-5">
        <form method="POST" action="{{ route('admin.messages.send') }}" id="bulk-form">
            @csrf

            {{-- Channel --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white border-0 pt-3 pb-2">
                    <h6 class="fw-semibold mb-0"><i class="bi bi-send me-2 text-primary"></i>Compose Message</h6>
                </div>
                <div class="card-body pt-0">

                    <div class="mb-3">
                        <label class="form-label small fw-medium">Channel</label>
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <input type="radio" class="btn-check" name="channel" id="ch-sms" value="sms" checked>
                            <label class="btn btn-outline-primary" for="ch-sms"><i class="bi bi-phone me-1"></i>SMS</label>
                            <input type="radio" class="btn-check" name="channel" id="ch-email" value="email">
                            <label class="btn btn-outline-primary" for="ch-email"><i class="bi bi-envelope me-1"></i>Email</label>
                        </div>
                    </div>

                    {{-- Template picker --}}
                    @if($templates->isNotEmpty())
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Load a template</label>
                        <select id="bm-template" class="form-select form-select-sm">
                            <option value="">— Custom message —</option>
                            @foreach($templates as $tpl)
                            <option value="{{ $tpl->body }}"
                                    data-channel="{{ $tpl->channel }}"
                                    data-subject="{{ $tpl->subject ?? '' }}">
                                {{ $tpl->name }}
                                <span class="text-muted">({{ strtoupper($tpl->channel) }})</span>
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    {{-- Subject --}}
                    <div class="mb-3 d-none" id="bm-subject-wrap">
                        <label class="form-label small fw-medium">Subject</label>
                        <input type="text" name="subject" id="bm-subject"
                               class="form-control form-control-sm @error('subject') is-invalid @enderror"
                               maxlength="255" value="{{ old('subject') }}">
                        @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Message --}}
                    <div class="mb-2">
                        <label class="form-label small fw-medium">Message</label>
                        <textarea name="message" id="bm-message" rows="4"
                                  class="form-control @error('message') is-invalid @enderror"
                                  maxlength="160" required
                                  placeholder="Hi {name}, …">{{ old('message') }}</textarea>
                        @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="d-flex justify-content-between mt-1">
                            <div class="form-text">Placeholder: <code>{name}</code></div>
                            <div class="form-text" id="bm-char-wrap"><span id="bm-char-count">0</span> / 160</div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Send button --}}
            <div class="d-grid">
                <button type="submit" id="bm-send-btn" class="btn btn-primary">
                    <i class="bi bi-send me-1"></i>Send to selected members
                </button>
            </div>
        </form>
    </div>

    {{-- Right: Member list --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3 pb-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h6 class="fw-semibold mb-0"><i class="bi bi-people me-2 text-primary"></i>Recipients</h6>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    {{-- Role + Status filter --}}
                    <form method="GET" action="{{ route('admin.messages.index') }}" class="d-inline-flex gap-1">
                        <select name="role" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="members" {{ $role === 'members' ? 'selected' : '' }}>Members</option>
                            <option value="admins"  {{ $role === 'admins'  ? 'selected' : '' }}>Admins</option>
                            <option value="all"     {{ $role === 'all'     ? 'selected' : '' }}>Everyone</option>
                        </select>
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="all"       {{ $status === 'all'       ? 'selected' : '' }}>Any status</option>
                            <option value="active"    {{ $status === 'active'    ? 'selected' : '' }}>Active</option>
                            <option value="inactive"  {{ $status === 'inactive'  ? 'selected' : '' }}>Inactive</option>
                            <option value="suspended" {{ $status === 'suspended' ? 'selected' : '' }}>Suspended</option>
                        </select>
                    </form>
                    <button type="button" id="bm-toggle-all" class="btn btn-link btn-sm p-0 text-decoration-none small text-nowrap">Select all</button>
                </div>
            </div>

            @if($members->isEmpty())
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-people fs-2 d-block mb-2"></i>No members found.
            </div>
            @else
            <div class="card-body p-0">
                <div style="max-height:500px; overflow-y:auto">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="width:36px"></th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($members as $member)
                            <tr>
                                <td>
                                    <input class="form-check-input bm-recipient" type="checkbox"
                                           name="user_ids[]" value="{{ $member->id }}"
                                           form="bulk-form"
                                           id="bm-{{ $member->id }}"
                                           data-phone="{{ $member->phone ? '1' : '0' }}"
                                           data-email="{{ $member->email ? '1' : '0' }}"
                                           {{ $member->phone ? '' : 'disabled' }}>
                                </td>
                                <td class="fw-medium">
                                    <label for="bm-{{ $member->id }}" class="mb-0 cursor-pointer">
                                        <a href="{{ route('admin.members.show', $member) }}" class="text-decoration-none">{{ $member->name }}</a>
                                    </label>
                                </td>
                                <td class="text-muted bm-phone-col">{{ $member->phone ?? '—' }}</td>
                                <td class="text-muted bm-email-col">{{ $member->email ?? '—' }}</td>
                                <td>
                                    <span class="badge badge-{{ $member->status }}">{{ ucfirst($member->status) }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-top-0 text-muted small py-2">
                {{ $members->count() }} member(s) shown &mdash; <span id="bm-selected-count">0</span> selected
            </div>
            @endif
        </div>
    </div>

</div>

@push('scripts')
<script>
(function () {
    const chSms     = document.getElementById('ch-sms');
    const chEmail   = document.getElementById('ch-email');
    const tplSel    = document.getElementById('bm-template');
    const msgArea   = document.getElementById('bm-message');
    const charCount = document.getElementById('bm-char-count');
    const charWrap  = document.getElementById('bm-char-wrap');
    const subjectW  = document.getElementById('bm-subject-wrap');
    const subjectIn = document.getElementById('bm-subject');
    const toggle    = document.getElementById('bm-toggle-all');
    const sendBtn   = document.getElementById('bm-send-btn');
    const form      = document.getElementById('bulk-form');
    const selCount  = document.getElementById('bm-selected-count');

    function updateCount() {
        if (charCount) charCount.textContent = msgArea.value.length;
        updateSelectedCount();
    }

    function updateSelectedCount() {
        if (!selCount) return;
        const checked = document.querySelectorAll('.bm-recipient:checked').length;
        selCount.textContent = checked;
    }

    msgArea.addEventListener('input', updateCount);
    updateCount();

    function applyChannel(ch) {
        if (ch === 'sms') {
            msgArea.setAttribute('maxlength', '160');
            if (charWrap) charWrap.classList.remove('d-none');
            if (subjectW) subjectW.classList.add('d-none');
            if (subjectIn) subjectIn.removeAttribute('required');
        } else {
            msgArea.removeAttribute('maxlength');
            if (charWrap) charWrap.classList.add('d-none');
            if (subjectW) subjectW.classList.remove('d-none');
            if (subjectIn) subjectIn.setAttribute('required', '');
        }
        document.querySelectorAll('.bm-recipient').forEach(cb => {
            const has = ch === 'email' ? cb.dataset.email === '1' : cb.dataset.phone === '1';
            cb.disabled = !has;
            if (!has) cb.checked = false;
        });
        updateSelectedCount();
        if (toggle) toggle.textContent = 'Select all';
        allSelected = false;
    }

    chSms.addEventListener('change',   () => applyChannel('sms'));
    chEmail.addEventListener('change', () => applyChannel('email'));

    if (tplSel) {
        tplSel.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (this.value) {
                msgArea.value = this.value;
                if (opt.dataset.subject && subjectIn) subjectIn.value = opt.dataset.subject;
                // Auto-switch channel to match template
                const tplCh = opt.dataset.channel;
                if (tplCh === 'email') {
                    chEmail.checked = true;
                    applyChannel('email');
                } else {
                    chSms.checked = true;
                    applyChannel('sms');
                }
                updateCount();
            }
        });
    }

    document.querySelectorAll('.bm-recipient').forEach(cb => {
        cb.addEventListener('change', updateSelectedCount);
    });

    let allSelected = false;
    if (toggle) {
        toggle.addEventListener('click', function () {
            allSelected = !allSelected;
            document.querySelectorAll('.bm-recipient:not(:disabled)').forEach(cb => cb.checked = allSelected);
            this.textContent = allSelected ? 'Deselect all' : 'Select all';
            updateSelectedCount();
        });
    }

    form.addEventListener('submit', function () {
        sendBtn.disabled = true;
        sendBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Sending…';
    });
})();
</script>
@endpush
@endsection
