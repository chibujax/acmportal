@extends('layouts.app')
@section('title','Member Engagement Report')
@section('page-title','Member Engagement Report')

@push('styles')
<style>
@media print {
    #sidebar, #sidebar-overlay, .topbar, .no-print { display: none !important; }
    #main-content { margin-left: 0 !important; }
    .page-content { padding: 0 !important; }
    .print-header { display: block !important; }
    .card { border: 1px solid #dee2e6 !important; box-shadow: none !important; }
    body { background: #fff !important; }
    a { color: inherit !important; text-decoration: none !important; }
}
.print-header { display: none; }
</style>
@endpush

@section('content')

{{-- Print-only header --}}
<div class="print-header mb-4">
    <div class="d-flex align-items-center gap-3 mb-1">
        <img src="{{ asset('logo.jpg') }}" alt="ACM" style="height:48px; object-fit:contain">
        <div>
            <div class="fw-bold fs-5">Abia Community Manchester</div>
            <div class="text-muted small">Member Engagement Report</div>
        </div>
    </div>
    <div class="text-muted small">
        Generated: {{ now()->format('d M Y, H:i') }}
        @if($search !== '') &nbsp;|&nbsp; Filter: {{ $search }} @endif
        &nbsp;|&nbsp; {{ $totalMembers }} active members
    </div>
    <hr>
</div>

{{-- Filter form --}}
<div class="card border-0 shadow-sm mb-4 no-print">
    <div class="card-body">
        <form method="GET" id="engagementForm">
            <input type="hidden" name="sort" value="{{ $sort }}">
            <input type="hidden" name="dir"  value="{{ $dir }}">

            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label fw-medium small mb-1">Search name</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control"
                               placeholder="Member name…" value="{{ $search }}">
                        @if($search !== '')
                        <a href="{{ request()->fullUrlWithQuery(['search' => '', 'page' => 1]) }}"
                           class="btn btn-outline-secondary" title="Clear">
                            <i class="bi bi-x"></i>
                        </a>
                        @endif
                    </div>
                </div>

                <div class="col-auto">
                    <label class="form-label fw-medium small mb-1">Per page</label>
                    <select name="per_page" class="form-select form-select-sm" style="width:auto"
                            onchange="this.form.submit()">
                        @foreach([10, 25, 50, 100] as $n)
                        <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-funnel me-1"></i>Filter
                    </button>
                </div>

                <div class="col-auto ms-auto d-flex gap-2">
                    {{-- CSV export --}}
                    <a href="{{ route('admin.reports.engagement.export', array_filter([
                            'search' => $search !== '' ? $search : null,
                            'sort'   => $sort,
                            'dir'    => $dir,
                        ])) }}"
                       class="btn btn-sm btn-outline-success">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV
                    </a>

                    {{-- Print / PDF --}}
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                            onclick="window.print()">
                        <i class="bi bi-printer me-1"></i>Print / PDF
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Summary KPIs --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center h-100">
            <div class="card-body py-3">
                <div class="fs-3 fw-bold">{{ $totalMembers }}</div>
                <div class="small text-muted">Active Members</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center h-100">
            <div class="card-body py-3">
                <div class="fs-3 fw-bold text-danger">{{ $neverAttended }}</div>
                <div class="small text-muted">Never Attended a Meeting</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center h-100">
            <div class="card-body py-3">
                <div class="fs-3 fw-bold text-danger">{{ $neverPaid }}</div>
                <div class="small text-muted">Never Paid Dues</div>
            </div>
        </div>
    </div>
</div>

@if($search !== '')
<div class="alert alert-info py-2 mb-3 no-print">
    <i class="bi bi-search me-1"></i>
    Showing results for <strong>{{ $search }}</strong>
    ({{ $members->total() }} of {{ $totalMembers }} members) —
    <a href="{{ request()->fullUrlWithQuery(['search' => '', 'page' => 1]) }}" class="alert-link">clear</a>
</div>
@endif

{{-- Sort helpers --}}
@php
    $sortUrl = fn(string $field) => request()->fullUrlWithQuery([
        'sort' => $field,
        'dir'  => ($sort === $field && $dir === 'asc') ? 'desc' : 'asc',
        'page' => 1,
    ]);
    $sortIcon = fn(string $field) => $sort !== $field
        ? 'bi-arrow-down-up text-muted'
        : ($dir === 'asc' ? 'bi-sort-down-alt text-primary' : 'bi-sort-up text-primary');
    $staleDate = fn($date) => !$date || \Carbon\Carbon::parse($date)->lt(now()->subDays(90));
@endphp

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-semibold mb-0">
            <i class="bi bi-person-dash me-2"></i>Member Engagement
            @if($search !== '')
                <span class="badge bg-info ms-1 fw-normal">{{ $members->total() }} found</span>
            @else
                <span class="text-muted fw-normal">({{ $totalMembers }})</span>
            @endif
        </h6>
        <span class="small text-muted no-print">
            Showing {{ $members->firstItem() }}–{{ $members->lastItem() }}
            of {{ $members->total() }}
        </span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:36px">#</th>
                    <th>
                        <a href="{{ $sortUrl('name') }}"
                           class="text-decoration-none text-dark d-flex align-items-center gap-1 no-print">
                            Name <i class="bi {{ $sortIcon('name') }}"></i>
                        </a>
                        <span class="print-header">Name</span>
                    </th>
                    <th>Phone</th>
                    <th>
                        <a href="{{ $sortUrl('last_meeting_date') }}"
                           class="text-decoration-none text-dark d-flex align-items-center gap-1 no-print">
                            Last Meeting Attended <i class="bi {{ $sortIcon('last_meeting_date') }}"></i>
                        </a>
                        <span class="print-header">Last Meeting Attended</span>
                    </th>
                    <th>
                        <a href="{{ $sortUrl('last_payment_date') }}"
                           class="text-decoration-none text-dark d-flex align-items-center gap-1 no-print">
                            Last Dues Payment <i class="bi {{ $sortIcon('last_payment_date') }}"></i>
                        </a>
                        <span class="print-header">Last Dues Payment</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse($members as $i => $m)
                <tr>
                    <td class="text-muted small">
                        {{ ($members->currentPage() - 1) * $members->perPage() + $i + 1 }}
                    </td>
                    <td>
                        <a href="{{ route('admin.members.show', $m['id']) }}"
                           class="fw-medium text-decoration-none no-print">{{ $m['name'] }}</a>
                        <span class="fw-medium print-header">{{ $m['name'] }}</span>
                    </td>
                    <td class="small">{{ $m['phone'] ?? '—' }}</td>
                    <td class="{{ $staleDate($m['last_meeting_date']) ? 'text-danger fw-medium' : '' }}">
                        {{ $m['last_meeting_date'] ? \Carbon\Carbon::parse($m['last_meeting_date'])->format('d M Y') : 'Never' }}
                    </td>
                    <td class="{{ $staleDate($m['last_payment_date']) ? 'text-danger fw-medium' : '' }}">
                        {{ $m['last_payment_date'] ? \Carbon\Carbon::parse($m['last_payment_date'])->format('d M Y') : 'Never' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">No members found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($members->hasPages())
    <div class="card-footer bg-white no-print">
        {{ $members->links() }}
    </div>
    @endif
</div>

<p class="text-muted small mt-2 no-print">
    <i class="bi bi-info-circle me-1"></i>Dates in red are more than 90 days ago (or never).
</p>

@endsection
