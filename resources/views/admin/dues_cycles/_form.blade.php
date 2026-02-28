{{-- Shared form partial for create & edit --}}

<div class="mb-3">
    <label class="form-label fw-medium">Title <span class="text-danger">*</span></label>
    <input type="text" name="title"
           class="form-control @error('title') is-invalid @enderror"
           value="{{ old('title', $duesCycle->title ?? '') }}"
           placeholder="e.g. Annual Dues 2026"
           required>
    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="row g-3 mb-3">
    <div class="col-6">
        <label class="form-label fw-medium">Type <span class="text-danger">*</span></label>
        <select name="type" class="form-select @error('type') is-invalid @enderror" required>
            @foreach(['yearly_dues' => 'Yearly Dues', 'donation' => 'Donation', 'event_levy' => 'Event Levy'] as $val => $label)
                <option value="{{ $val }}" {{ old('type', $duesCycle->type ?? 'yearly_dues') === $val ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-6">
        <label class="form-label fw-medium">Currency <span class="text-danger">*</span></label>
        <select name="currency" class="form-select @error('currency') is-invalid @enderror" required>
            @foreach(['GBP' => 'GBP (£)', 'USD' => 'USD ($)', 'EUR' => 'EUR (€)', 'NGN' => 'NGN (₦)'] as $val => $label)
                <option value="{{ $val }}" {{ old('currency', $duesCycle->currency ?? 'GBP') === $val ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="mb-3">
    <label class="form-label fw-medium">Full Amount <span class="text-danger">*</span></label>
    <div class="input-group">
        <span class="input-group-text">£</span>
        <input type="number" name="amount" step="0.01" min="0.01"
               class="form-control @error('amount') is-invalid @enderror"
               value="{{ old('amount', $duesCycle->amount ?? '120.00') }}"
               required>
    </div>
    @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="row g-3 mb-3">
    <div class="col-6">
        <label class="form-label fw-medium">Start Date <span class="text-danger">*</span></label>
        <input type="date" name="start_date"
               class="form-control @error('start_date') is-invalid @enderror"
               value="{{ old('start_date', isset($duesCycle) ? $duesCycle->start_date->format('Y-m-d') : '') }}"
               required>
        @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-6">
        <label class="form-label fw-medium">End Date <span class="text-danger">*</span></label>
        <input type="date" name="end_date"
               class="form-control @error('end_date') is-invalid @enderror"
               value="{{ old('end_date', isset($duesCycle) ? $duesCycle->end_date->format('Y-m-d') : '') }}"
               required>
        @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6">
        <label class="form-label fw-medium">Payment Options <span class="text-danger">*</span></label>
        <select name="payment_options" id="paymentOptions"
                class="form-select @error('payment_options') is-invalid @enderror" required>
            @foreach(['once' => 'One-off', 'monthly' => 'Monthly', 'installments' => 'Installments'] as $val => $label)
                <option value="{{ $val }}" {{ old('payment_options', $duesCycle->payment_options ?? 'once') === $val ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('payment_options')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-6" id="installmentCountWrap"
         style="{{ old('payment_options', $duesCycle->payment_options ?? 'once') !== 'installments' ? 'display:none' : '' }}">
        <label class="form-label fw-medium">Number of Installments</label>
        <input type="number" name="installment_count" min="2" max="24"
               class="form-control @error('installment_count') is-invalid @enderror"
               value="{{ old('installment_count', $duesCycle->installment_count ?? '') }}">
        @error('installment_count')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="mb-3">
    <label class="form-label fw-medium">Status <span class="text-danger">*</span></label>
    <select name="status" class="form-select @error('status') is-invalid @enderror" required>
        @foreach(['draft' => 'Draft', 'active' => 'Active', 'closed' => 'Closed'] as $val => $label)
            <option value="{{ $val }}" {{ old('status', $duesCycle->status ?? 'draft') === $val ? 'selected' : '' }}>
                {{ $label }}
            </option>
        @endforeach
    </select>
    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label class="form-label fw-medium">Description</label>
    <textarea name="description" rows="3"
              class="form-control @error('description') is-invalid @enderror"
              placeholder="Optional details about this dues cycle">{{ old('description', $duesCycle->description ?? '') }}</textarea>
    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="form-check mb-2">
    <input type="hidden" name="couple_shared" value="0">
    <input type="checkbox" name="couple_shared" value="1" id="coupleShared" class="form-check-input"
           {{ old('couple_shared', $duesCycle->couple_shared ?? false) ? 'checked' : '' }}>
    <label class="form-check-label" for="coupleShared">
        <strong>Couple shared</strong> — married members share this obligation
        <span class="text-muted small d-block">When ticked: couples pay the full amount together; single members pay half.</span>
    </label>
</div>

<div class="form-check mb-2">
    <input type="hidden" name="send_reminders" value="0">
    <input type="checkbox" name="send_reminders" value="1" id="sendReminders" class="form-check-input"
           {{ old('send_reminders', $duesCycle->send_reminders ?? true) ? 'checked' : '' }}>
    <label class="form-check-label" for="sendReminders">
        Send payment reminders to members
    </label>
</div>

<script>
    document.getElementById('paymentOptions').addEventListener('change', function () {
        document.getElementById('installmentCountWrap').style.display =
            this.value === 'installments' ? '' : 'none';
    });
</script>
