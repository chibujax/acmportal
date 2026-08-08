@extends('layouts.app')

@section('title', 'Contact Log Report')
@section('page-title', 'Contact Log Report')

@push('styles')
<style>
@media print {
    #sidebar, #sidebar-overlay, .topbar, .no-print { display: none !important; }
    #main-content { margin-left: 0 !important; }
    .page-content { padding: 0 !important; }
    .print-header { display: block !important; }
    .card { border: 1px solid #dee2e6 !important; box-shadow: none !important; }
    body { background: #fff !important; }
}
.print-header { display: none; }
</style>
@endpush

@section('content')

<div class="print-header mb-4">
    <div class="d-flex align-items-center gap-3 mb-1">
        <img src="{{ asset('logo.jpg') }}" alt="ACM" style="height:48px; object-fit:contain">
        <div>
            <div class="fw-bold fs-5">Abia Community Manchester</div>
            <div class="text-muted small">Contact Log Report</div>
        </div>
    </div>
    <div class="text-muted small">Date range: {{ $dateRangeLabel }} &nbsp;|&nbsp; Generated: {{ now()->format('d M Y, H:i') }}</div>
    <hr>
</div>

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <div>
        <h5 class="fw-semibold mb-0">Contact Log Report</h5>
        <p class="text-muted small mb-0">Date range: {{ $dateRangeLabel }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.contact-log.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
            <i class="bi bi-printer me-1"></i>Print / PDF
        </button>
    </div>
</div>

@if($emailSample)
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-0 pt-3 pb-1">
        <h6 class="fw-semibold mb-0"><i class="bi bi-envelope text-primary me-2"></i>Email Content</h6>
    </div>
    <div class="card-body py-2">
        @if($emailSample->subject)
            <div class="fw-medium">{{ $emailSample->subject }}</div>
        @endif
        <div class="text-muted small" style="white-space:pre-wrap">{{ $emailSample->message }}</div>
    </div>
</div>
@endif

@if($smsSample)
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-0 pt-3 pb-1">
        <h6 class="fw-semibold mb-0"><i class="bi bi-phone text-primary me-2"></i>SMS Content</h6>
    </div>
    <div class="card-body py-2">
        <div class="text-muted small" style="white-space:pre-wrap">{{ $smsSample->message }}</div>
    </div>
</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>Datetime</th>
                    <th>Name</th>
                    <th>Channel</th>
                    <th>Reason</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td class="text-muted">{{ $log->created_at->format('d M Y H:i') }}</td>
                    <td>{{ $log->user?->name ?? 'Unknown' }}</td>
                    <td><span class="badge bg-light text-dark border">{{ strtoupper($log->channel) }}</span></td>
                    <td class="small text-muted">{{ ucfirst(str_replace('_', ' ', $log->context)) }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center text-muted py-4">No records match this filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
window.addEventListener('load', function () { window.print(); });
</script>
@endpush
