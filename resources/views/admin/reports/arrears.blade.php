@extends('layouts.app')
@section('title','Arrears Report')
@section('page-title','Arrears Report')

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
            <div class="text-muted small">
                Arrears Report
                @if($cycleId && $cycles->find($cycleId))
                    — {{ $cycles->find($cycleId)->title }}
                @endif
            </div>
        </div>
    </div>
    <div class="text-muted small">
        Generated: {{ now()->format('d M Y, H:i') }}
        @if(isset($search) && $search !== '') &nbsp;|&nbsp; Filter: {{ $search }} @endif
        @if(isset($totalInArrears)) &nbsp;|&nbsp; {{ $totalInArrears }} members in arrears @endif
    </div>
    <hr>
</div>

{{-- Filter form --}}
<div class="card border-0 shadow-sm mb-4 no-print">
    <div class="card-body">
        <form method="GET" id="arrearsForm">
            <input type="hidden" name="sort" value="{{ $sort ?? 'outstanding' }}">
            <input type="hidden" name="dir"  value="{{ $dir ?? 'desc' }}">

            <div class="row g-2 align-items-end">

                <div class="col-12 col-md-4">
                    <label class="form-label fw-medium small mb-1">Dues Cycle</label>
                    <select name="cycle_id" class="form-select form-select-sm"
                            onchange="this.form.submit()">
                        <option value="">— Choose cycle —</option>
                        @foreach($cycles as $c)
                        <option value="{{ $c->id }}" {{ $cycleId == $c->id ? 'selected' : '' }}>
                            {{ $c->title }} ({{ ucfirst($c->status) }})
                        </option>
                        @endforeach
                    </select>
                </div>

                @if($cycleId)
                <div class="col-12 col-md-3">
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
                    {{-- CSV / Excel export --}}
                    <a href="{{ route('admin.reports.arrears.export', array_filter([
                            'cycle_id' => $cycleId,
                            'search'   => $search !== '' ? $search : null,
                            'sort'     => $sort,
                            'dir'      => $dir,
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

                @else
                <div class="col-auto">
                    <button class="btn btn-sm btn-success">Generate Report</button>
                </div>
                @endif

            </div>
        </form>
    </div>
</div>

@if($cycleId && $totalInArrears > 0)

{{-- Summary KPIs --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center h-100">
            <div class="card-body py-3">
                <div class="fs-3 fw-bold text-danger">{{ $totalInArrears }}</div>
                <div class="small text-muted">Members in Arrears</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center h-100">
            <div class="card-body py-3">
                <div class="fs-3 fw-bold text-danger">£{{ number_format($totalOutstanding, 2) }}</div>
                <div class="small text-muted">Total Outstanding</div>
            </div>
        </div>
    </div>
</div>

@if($search !== '')
<div class="alert alert-info py-2 mb-3 no-print">
    <i class="bi bi-search me-1"></i>
    Showing results for <strong>{{ $search }}</strong>
    ({{ $arrearsMembers->total() }} of {{ $totalInArrears }} members) —
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
@endphp

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-semibold mb-0 text-danger">
            <i class="bi bi-exclamation-triangle me-2"></i>Members in Arrears
            @if($search !== '')
                <span class="badge bg-info ms-1 fw-normal">{{ $arrearsMembers->total() }} found</span>
            @else
                <span class="text-muted fw-normal">({{ $totalInArrears }})</span>
            @endif
        </h6>
        <span class="small text-muted no-print">
            Showing {{ $arrearsMembers->firstItem() }}–{{ $arrearsMembers->lastItem() }}
            of {{ $arrearsMembers->total() }}
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
                    <th>Email</th>
                    <th>Spouse</th>
                    <th>
                        <a href="{{ $sortUrl('obligation') }}"
                           class="text-decoration-none text-dark d-flex align-items-center gap-1 no-print">
                            Obligation <i class="bi {{ $sortIcon('obligation') }}"></i>
                        </a>
                        <span class="print-header">Obligation</span>
                    </th>
                    <th>
                        <a href="{{ $sortUrl('paid') }}"
                           class="text-decoration-none text-dark d-flex align-items-center gap-1 no-print">
                            Paid <i class="bi {{ $sortIcon('paid') }}"></i>
                        </a>
                        <span class="print-header">Paid</span>
                    </th>
                    <th>
                        <a href="{{ $sortUrl('outstanding') }}"
                           class="text-decoration-none text-danger d-flex align-items-center gap-1 no-print">
                            Outstanding <i class="bi {{ $sortIcon('outstanding') }}"></i>
                        </a>
                        <span class="print-header">Outstanding</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach($arrearsMembers as $i => $m)
                <tr>
                    <td class="text-muted small">
                        {{ ($arrearsMembers->currentPage() - 1) * $arrearsMembers->perPage() + $i + 1 }}
                    </td>
                    <td>
                        <a href="{{ route('admin.members.show', $m) }}"
                           class="fw-medium text-decoration-none no-print">{{ $m->name }}</a>
                        <span class="fw-medium print-header">{{ $m->name }}</span>
                    </td>
                    <td class="small">{{ $m->phone ?? '—' }}</td>
                    <td class="small">{{ $m->email ?? '—' }}</td>
                    <td class="small text-muted">{{ $m->spouseName ?? '—' }}</td>
                    <td>£{{ number_format($m->obligation, 2) }}</td>
                    <td class="text-success">£{{ number_format($m->paid, 2) }}</td>
                    <td>
                        <span class="fw-bold text-danger">£{{ number_format($m->outstanding, 2) }}</span>
                        @if($m->paid > 0)
                        <div class="progress mt-1 no-print" style="height:4px; width:80px">
                            <div class="progress-bar bg-success"
                                 style="width:{{ round($m->paid / $m->obligation * 100) }}%"></div>
                        </div>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="table-light fw-semibold">
                <tr>
                    <td colspan="6" class="text-end">Page total outstanding:</td>
                    <td></td>
                    <td class="text-danger">
                        £{{ number_format($arrearsMembers->sum('outstanding'), 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if($arrearsMembers->hasPages())
    <div class="card-footer bg-white no-print">
        {{ $arrearsMembers->links() }}
    </div>
    @endif
</div>

@elseif($cycleId)
<div class="alert alert-success">
    <i class="bi bi-check-circle me-2"></i>All active members are up to date for this cycle.
</div>
@endif

@endsection
