@extends('layouts.app')
@section('title', 'Record Donation Item – ' . $duesCycle->title)
@section('page-title', 'Record Donation Item')

@section('content')
<div class="d-flex gap-2 mb-3">
    <a href="{{ route('admin.donation-items.index', $duesCycle) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="fw-semibold mb-0">
                    <i class="bi bi-box-seam text-warning me-2"></i>Record Item Donation
                </h6>
                <small class="text-muted">For: {{ $duesCycle->title }}</small>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.donation-items.store', $duesCycle) }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-medium">Member <span class="text-danger">*</span></label>
                        <select name="user_id" class="form-select @error('user_id') is-invalid @enderror" required>
                            <option value="">— Select member —</option>
                            @foreach($members as $m)
                            <option value="{{ $m->id }}" {{ old('user_id') == $m->id ? 'selected' : '' }}>
                                {{ $m->name }} ({{ $m->phone }})
                            </option>
                            @endforeach
                        </select>
                        @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Item Type <span class="text-danger">*</span></label>
                        <select name="item_type" id="itemType" class="form-select @error('item_type') is-invalid @enderror" required>
                            <option value="other" {{ old('item_type', 'other') === 'other' ? 'selected' : '' }}>Other (food, drinks, etc.)</option>
                            <option value="money" {{ old('item_type') === 'money' ? 'selected' : '' }}>Money (cash)</option>
                        </select>
                        @error('item_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Description <span class="text-danger">*</span></label>
                        <input type="text" name="description"
                               class="form-control @error('description') is-invalid @enderror"
                               value="{{ old('description') }}"
                               placeholder="e.g. 2 crates of Guinness, Bag of rice, Cash donation"
                               required>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-medium">Quantity <span class="text-muted small">(optional)</span></label>
                            <input type="text" name="quantity"
                                   class="form-control @error('quantity') is-invalid @enderror"
                                   value="{{ old('quantity') }}"
                                   placeholder="e.g. 2 crates, 5 kg">
                            @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-medium">Est. Value ({{ $duesCycle->currency }}) <span class="text-muted small">(optional)</span></label>
                            <div class="input-group">
                                <span class="input-group-text">£</span>
                                <input type="number" name="estimated_value" step="0.01" min="0"
                                       class="form-control @error('estimated_value') is-invalid @enderror"
                                       value="{{ old('estimated_value') }}">
                                @error('estimated_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Donation Date <span class="text-danger">*</span></label>
                        <input type="date" name="donation_date"
                               class="form-control @error('donation_date') is-invalid @enderror"
                               value="{{ old('donation_date', today()->toDateString()) }}" required>
                        @error('donation_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">Notes <span class="text-muted small">(optional)</span></label>
                        <textarea name="notes" rows="2" class="form-control"
                                  placeholder="Any additional details">{{ old('notes') }}</textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-warning text-white">
                            <i class="bi bi-check-circle me-1"></i>Save Donation
                        </button>
                        <a href="{{ route('admin.donation-items.index', $duesCycle) }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
