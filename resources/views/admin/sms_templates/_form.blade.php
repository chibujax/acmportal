@php $tpl = $template ?? null; @endphp

{{-- Channel selector --}}
<div class="mb-3">
    <label class="form-label fw-medium">Channel</label>
    <div class="btn-group w-100" role="group">
        <input type="radio" class="btn-check" name="channel" id="ch-sms" value="sms"
               {{ old('channel', $tpl?->channel ?? 'sms') === 'sms' ? 'checked' : '' }}>
        <label class="btn btn-outline-primary" for="ch-sms">
            <i class="bi bi-phone me-1"></i>SMS
        </label>
        <input type="radio" class="btn-check" name="channel" id="ch-email" value="email"
               {{ old('channel', $tpl?->channel ?? 'sms') === 'email' ? 'checked' : '' }}>
        <label class="btn btn-outline-primary" for="ch-email">
            <i class="bi bi-envelope me-1"></i>Email
        </label>
    </div>
</div>

<div class="mb-3">
    <label class="form-label fw-medium">Template Name</label>
    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
           value="{{ old('name', $tpl?->name) }}" placeholder="e.g. Dues Reminder" required maxlength="100">
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <div class="form-text">A short, unique label to identify this template.</div>
</div>

{{-- Subject (email only) --}}
<div class="mb-3" id="subject-wrap" style="{{ old('channel', $tpl?->channel ?? 'sms') === 'sms' ? 'display:none' : '' }}">
    <label class="form-label fw-medium">Subject</label>
    <input type="text" name="subject" id="tpl-subject"
           class="form-control @error('subject') is-invalid @enderror"
           value="{{ old('subject', $tpl?->subject) }}" maxlength="255"
           placeholder="e.g. ACM Portal – Payment Reminder">
    @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-1">
    <label class="form-label fw-medium">Message Body</label>
    <textarea name="body" id="sms-body" rows="4"
              class="form-control @error('body') is-invalid @enderror"
              required
              placeholder="Hi {name}, you have an outstanding balance of £{amount} for {cycle}. Please pay at your earliest convenience.">{{ old('body', $tpl?->body) }}</textarea>
    @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <div class="d-flex justify-content-between mt-1">
        <div class="form-text" id="sms-hint">Max 160 characters for SMS. Use placeholders below.</div>
        <div class="form-text" id="char-wrap"><span id="char-count">0</span> / 160</div>
    </div>
</div>

<div class="alert alert-light border small mt-3 mb-0 p-2">
    <strong>Placeholders:</strong><br>
    <code>{name}</code> member name &nbsp;·&nbsp;
    <code>{amount}</code> outstanding balance &nbsp;·&nbsp;
    <code>{cycle}</code> dues cycle name &nbsp;·&nbsp;
    <code>{donations}</code> items donated (dues cycles)<br>
    <code>{meeting}</code> meeting title &nbsp;·&nbsp;
    <code>{date}</code> meeting date (attendance messages)
</div>

@push('scripts')
<script>
(function () {
    const body      = document.getElementById('sms-body');
    const counter   = document.getElementById('char-count');
    const charWrap  = document.getElementById('char-wrap');
    const smsHint   = document.getElementById('sms-hint');
    const subjectW  = document.getElementById('subject-wrap');
    const chSms     = document.getElementById('ch-sms');
    const chEmail   = document.getElementById('ch-email');

    function updateCount() { counter.textContent = body.value.length; }

    function applyChannel(ch) {
        if (ch === 'sms') {
            body.setAttribute('maxlength', '160');
            charWrap.classList.remove('d-none');
            smsHint.textContent = 'Max 160 characters for SMS. Use placeholders below.';
            subjectW.style.display = 'none';
            document.getElementById('tpl-subject').removeAttribute('required');
        } else {
            body.removeAttribute('maxlength');
            charWrap.classList.add('d-none');
            smsHint.textContent = 'Use placeholders below.';
            subjectW.style.display = '';
            document.getElementById('tpl-subject').setAttribute('required', '');
        }
        updateCount();
    }

    body.addEventListener('input', updateCount);
    chSms.addEventListener('change',   () => applyChannel('sms'));
    chEmail.addEventListener('change', () => applyChannel('email'));

    applyChannel(chEmail.checked ? 'email' : 'sms');
})();
</script>
@endpush
