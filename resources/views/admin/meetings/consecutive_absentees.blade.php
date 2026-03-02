@extends('layouts.app')
@section('title', 'Consecutive Absentees')
@section('page-title', 'Members Absent from Last 3 Meetings')

@section('content')
<div class="d-flex gap-2 mb-3">
    <a href="{{ route('admin.meetings.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Meetings
    </a>
    @if($enough && $members->isNotEmpty())
    <button type="button" class="btn btn-sm btn-outline-danger ms-auto"
            data-bs-toggle="modal" data-bs-target="#smsAbsentModal">
        <i class="bi bi-send me-1"></i>Contact ({{ $members->count() }})
    </button>
    @endif
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show py-2">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('warning'))
<div class="alert alert-warning alert-dismissible fade show py-2">{{ session('warning') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

@if(!$enough)
<div class="card border-0 shadow-sm">
    <div class="card-body text-center text-muted py-5">
        <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
        Not enough meetings yet — at least 3 closed/active meetings are needed.<br>
        <small>{{ $lastMeetings->count() }} meeting(s) recorded so far.</small>
    </div>
</div>
@else

{{-- Last 3 meetings referenced --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-3 pb-1">
        <h6 class="fw-semibold mb-0"><i class="bi bi-calendar3 text-primary me-2"></i>Based on last 3 meetings</h6>
    </div>
    <div class="card-body py-2">
        <div class="d-flex flex-wrap gap-2">
            @foreach($lastMeetings as $m)
            <span class="badge bg-light text-dark border fs-6 fw-normal">
                {{ $m->meeting_date->format('d M Y') }} — {{ $m->title }}
            </span>
            @endforeach
        </div>
    </div>
</div>

@if($members->isEmpty())
<div class="card border-0 shadow-sm">
    <div class="card-body text-center text-success py-5">
        <i class="bi bi-check-circle fs-2 d-block mb-2"></i>
        No members were absent from all 3 consecutive meetings.
    </div>
</div>
@else
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
        <h6 class="fw-semibold mb-2">
            <i class="bi bi-person-x text-danger me-2"></i>
            {{ $members->count() }} member(s) missed all 3 meetings
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                </tr>
            </thead>
            <tbody>
                @foreach($members as $i => $m)
                <tr>
                    <td class="text-muted">{{ $i + 1 }}</td>
                    <td class="fw-medium">
                        <a href="{{ route('admin.members.show', $m) }}" class="text-decoration-none">{{ $m->name }}</a>
                    </td>
                    <td class="text-muted">{{ $m->phone ?? '—' }}</td>
                    <td class="text-muted">{{ $m->email ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endif

@if($enough && $members->isNotEmpty())
{{-- Contact Modal --}}
<div class="modal fade" id="smsAbsentModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.meetings.send-consecutive-sms') }}">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-semibold"><i class="bi bi-send me-2"></i>Contact Consecutive Absentees</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    {{-- Channel --}}
                    <div class="mb-3">
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <input type="radio" class="btn-check" name="channel" id="ca-ch-sms" value="sms" checked>
                            <label class="btn btn-outline-primary" for="ca-ch-sms"><i class="bi bi-phone me-1"></i>SMS</label>
                            <input type="radio" class="btn-check" name="channel" id="ca-ch-email" value="email">
                            <label class="btn btn-outline-primary" for="ca-ch-email"><i class="bi bi-envelope me-1"></i>Email</label>
                        </div>
                    </div>

                    {{-- Template picker --}}
                    @php $smsTpls = \App\Models\SmsTemplate::orderBy('name')->get(); @endphp
                    @if($smsTpls->isNotEmpty())
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Load a template</label>
                        <select id="ca-template" class="form-select form-select-sm">
                            <option value="">— Custom message —</option>
                            @foreach($smsTpls as $tpl)
                            <option value="{{ $tpl->body }}"
                                    data-channel="{{ $tpl->channel }}"
                                    data-subject="{{ $tpl->subject ?? '' }}">{{ $tpl->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    {{-- Subject (email) --}}
                    <div class="mb-2 d-none" id="ca-subject-wrap">
                        <label class="form-label small fw-medium">Subject</label>
                        <input type="text" name="subject" id="ca-subject"
                               class="form-control form-control-sm" maxlength="255">
                    </div>

                    {{-- Message --}}
                    <div class="mb-2">
                        <label class="form-label small fw-medium">Message</label>
                        <textarea name="message" id="ca-message" rows="3"
                                  class="form-control @error('message') is-invalid @enderror"
                                  maxlength="160" required
                                  placeholder="Hi {name}, we noticed you missed our last 3 meetings. Please get in touch.">{{ old('message') }}</textarea>
                        @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="d-flex justify-content-between mt-1">
                            <div class="form-text"><code>{name}</code></div>
                            <div class="form-text" id="ca-char-wrap"><span id="ca-char-count">0</span> / 160</div>
                        </div>
                    </div>

                    {{-- Recipients --}}
                    <div class="mb-1">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-medium mb-0">Recipients</label>
                            <button type="button" id="ca-toggle-all" class="btn btn-link btn-sm p-0 text-decoration-none small">Select all</button>
                        </div>
                        <div class="border rounded p-2" style="max-height:200px; overflow-y:auto">
                            @foreach($members as $ab)
                            <div class="form-check py-1 border-bottom">
                                <input class="form-check-input ca-recipient"
                                       type="checkbox" name="user_ids[]"
                                       value="{{ $ab->id }}" id="ca-{{ $ab->id }}"
                                       data-phone="{{ $ab->phone ? '1' : '0' }}"
                                       data-email="{{ $ab->email ? '1' : '0' }}"
                                       {{ $ab->phone ? '' : 'disabled' }}>
                                <label class="form-check-label small d-flex justify-content-between w-100" for="ca-{{ $ab->id }}">
                                    <span>{{ $ab->name }}</span>
                                    <span class="ca-contact-info text-muted ms-2">{{ $ab->phone ?? 'no phone' }}</span>
                                    <span class="ca-email-info text-muted ms-2 d-none">{{ $ab->email ?? 'no email' }}</span>
                                </label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="ca-send-btn" class="btn btn-sm btn-danger">
                        <i class="bi bi-send me-1"></i>Send
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const tplSel    = document.getElementById('ca-template');
    const msgArea   = document.getElementById('ca-message');
    const charCount = document.getElementById('ca-char-count');
    const charWrap  = document.getElementById('ca-char-wrap');
    const toggle    = document.getElementById('ca-toggle-all');
    const sendBtn   = document.getElementById('ca-send-btn');
    const form      = document.querySelector('#smsAbsentModal form');
    const subjectW  = document.getElementById('ca-subject-wrap');
    const subjectIn = document.getElementById('ca-subject');
    const chSms     = document.getElementById('ca-ch-sms');
    const chEmail   = document.getElementById('ca-ch-email');

    function updateCount() { charCount.textContent = msgArea.value.length; }
    msgArea.addEventListener('input', updateCount);
    updateCount();

    function applyChannel(ch) {
        if (ch === 'sms') {
            msgArea.setAttribute('maxlength', '160');
            charWrap.classList.remove('d-none');
            subjectW.classList.add('d-none');
            subjectIn.removeAttribute('required');
        } else {
            msgArea.removeAttribute('maxlength');
            charWrap.classList.add('d-none');
            subjectW.classList.remove('d-none');
            subjectIn.setAttribute('required', '');
        }
        document.querySelectorAll('.ca-recipient').forEach(cb => {
            const has = ch === 'email' ? cb.dataset.email === '1' : cb.dataset.phone === '1';
            cb.disabled = !has;
            if (!has) cb.checked = false;
        });
        document.querySelectorAll('.ca-contact-info').forEach(el => el.classList.toggle('d-none', ch === 'email'));
        document.querySelectorAll('.ca-email-info').forEach(el => el.classList.toggle('d-none', ch === 'sms'));
        updateCount();
    }

    chSms.addEventListener('change',   () => applyChannel('sms'));
    chEmail.addEventListener('change', () => applyChannel('email'));

    if (tplSel) {
        tplSel.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (this.value) {
                msgArea.value = this.value;
                if (opt.dataset.subject) subjectIn.value = opt.dataset.subject;
                updateCount();
            }
        });
    }

    let allSelected = false;
    toggle.textContent = 'Select all';
    toggle.addEventListener('click', function () {
        allSelected = !allSelected;
        document.querySelectorAll('.ca-recipient:not(:disabled)').forEach(cb => cb.checked = allSelected);
        this.textContent = allSelected ? 'Deselect all' : 'Select all';
    });

    form.addEventListener('submit', function () {
        sendBtn.disabled = true;
        sendBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Sending…';
    });
})();
</script>
@endpush
@endif

@endsection
