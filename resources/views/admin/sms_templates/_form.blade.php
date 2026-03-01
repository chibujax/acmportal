@php $tpl = $template ?? null; @endphp

<div class="mb-3">
    <label class="form-label fw-medium">Template Name</label>
    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
           value="{{ old('name', $tpl?->name) }}" placeholder="e.g. Dues Reminder" required maxlength="100">
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <div class="form-text">A short, unique label to identify this template.</div>
</div>

<div class="mb-1">
    <label class="form-label fw-medium">Message Body</label>
    <textarea name="body" id="sms-body" rows="4"
              class="form-control @error('body') is-invalid @enderror"
              maxlength="160" required
              placeholder="Hi {name}, you have an outstanding balance of £{amount} for {cycle}. Please pay at your earliest convenience.">{{ old('body', $tpl?->body) }}</textarea>
    @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <div class="d-flex justify-content-between mt-1">
        <div class="form-text">Max 160 characters. Use placeholders below.</div>
        <div class="form-text"><span id="char-count">0</span> / 160</div>
    </div>
</div>

<div class="alert alert-light border small mt-3 mb-0 p-2">
    <strong>Placeholders:</strong><br>
    <code>{name}</code> member name &nbsp;·&nbsp;
    <code>{amount}</code> outstanding balance &nbsp;·&nbsp;
    <code>{cycle}</code> dues cycle name &nbsp;·&nbsp;
    <code>{donations}</code> items donated (dues cycles)<br>
    <code>{meeting}</code> meeting title &nbsp;·&nbsp;
    <code>{date}</code> meeting date (attendance SMS)
</div>

@push('scripts')
<script>
    const body = document.getElementById('sms-body');
    const counter = document.getElementById('char-count');
    function updateCount() { counter.textContent = body.value.length; }
    body.addEventListener('input', updateCount);
    updateCount();
</script>
@endpush
