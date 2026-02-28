@extends('layouts.app')
@section('title', 'Payment ' . $payment->receipt_number)
@section('page-title', 'Payment Receipt')

@section('content')
<div class="d-flex gap-2 mb-3">
    <a href="{{ route('admin.payments.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Payments
    </a>
</div>

<div class="row g-3">
    {{-- Receipt Card --}}
    <div class="col-md-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom pt-3 pb-2 d-flex justify-content-between align-items-center">
                <h6 class="fw-semibold mb-0"><i class="bi bi-receipt me-2 text-success"></i>Receipt</h6>
                <code class="fs-6">{{ $payment->receipt_number }}</code>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted fw-normal">Member</dt>
                    <dd class="col-7 fw-semibold">
                        <a href="{{ route('admin.members.show', $payment->user) }}" class="text-decoration-none">
                            {{ $payment->user->name }}
                        </a>
                        <div class="small text-muted">{{ $payment->user->phone }}</div>
                    </dd>

                    <dt class="col-5 text-muted fw-normal">Dues Cycle</dt>
                    <dd class="col-7">{{ $payment->duesCycle?->title ?? '—' }}</dd>

                    <dt class="col-5 text-muted fw-normal">Amount</dt>
                    <dd class="col-7 fw-bold text-success fs-5">£{{ number_format($payment->amount, 2) }}</dd>

                    <dt class="col-5 text-muted fw-normal">Payment Date</dt>
                    <dd class="col-7">{{ $payment->payment_date?->format('d M Y') ?? '—' }}</dd>

                    <dt class="col-5 text-muted fw-normal">Method</dt>
                    <dd class="col-7">{{ ucfirst($payment->method) }}</dd>

                    <dt class="col-5 text-muted fw-normal">Status</dt>
                    <dd class="col-7">
                        <span class="badge {{ $payment->status === 'completed' ? 'bg-success' : ($payment->status === 'failed' ? 'bg-danger' : 'bg-warning text-dark') }}">
                            {{ ucfirst($payment->status) }}
                        </span>
                    </dd>

                    <dt class="col-5 text-muted fw-normal">Recorded By</dt>
                    <dd class="col-7">{{ $payment->recordedBy?->name ?? '—' }}</dd>

                    @if($payment->notes)
                    <dt class="col-5 text-muted fw-normal">Notes</dt>
                    <dd class="col-7">{{ $payment->notes }}</dd>
                    @endif
                </dl>
            </div>
        </div>

        @if($payment->proof_of_payment)
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-white border-bottom pt-3 pb-2">
                <h6 class="fw-semibold mb-0"><i class="bi bi-paperclip me-2"></i>Proof of Payment</h6>
            </div>
            <div class="card-body text-center">
                @if(str_ends_with(strtolower($payment->proof_of_payment), '.pdf'))
                    <a href="{{ Storage::url($payment->proof_of_payment) }}" target="_blank" class="btn btn-outline-primary">
                        <i class="bi bi-file-pdf me-1"></i>View PDF
                    </a>
                @else
                    <img src="{{ Storage::url($payment->proof_of_payment) }}"
                         class="img-fluid rounded shadow-sm" style="max-height:400px" alt="Proof">
                @endif
            </div>
        </div>
        @endif
    </div>

    {{-- Update Status --}}
    <div class="col-md-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom pt-3 pb-2">
                <h6 class="fw-semibold mb-0"><i class="bi bi-pencil-square me-2 text-primary"></i>Update Payment</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.payments.update', $payment) }}">
                    @csrf @method('PATCH')
                    <div class="mb-3">
                        <label class="form-label fw-medium">Status</label>
                        <select name="status" class="form-select">
                            <option value="completed" {{ $payment->status === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="failed"    {{ $payment->status === 'failed'    ? 'selected' : '' }}>Failed</option>
                            <option value="refunded"  {{ $payment->status === 'refunded'  ? 'selected' : '' }}>Refunded</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Notes</label>
                        <textarea name="notes" class="form-control" rows="3">{{ $payment->notes }}</textarea>
                    </div>
                    <button class="btn btn-primary w-100">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success mt-3">{{ session('success') }}</div>
@endif
@endsection
