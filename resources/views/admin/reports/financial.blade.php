@extends('layouts.app')
@section('title','Financial Report')
@section('page-title','Financial Report')

@push('styles')
<style>
/* ── Print styles ── */
@media print {
    #sidebar, #sidebar-overlay, .topbar, .no-print { display: none !important; }
    #main-content { margin-left: 0 !important; }
    .page-content { padding: 0 !important; }
    .print-header { display: block !important; }
    .card { border: 1px solid #dee2e6 !important; box-shadow: none !important; }
    .badge { border: 1px solid #ccc; }
    body { background: #fff !important; }
}
.print-header { display: none; }
</style>
@endpush

@section('content')

{{-- Print header (hidden on screen, shown when printing) --}}
<div class="print-header mb-4">
    <div class="d-flex align-items-center gap-3 mb-1">
        <img src="{{ asset('logo.jpg') }}" alt="ACM" style="height:48px; object-fit:contain">
        <div>
            <div class="fw-bold fs-5">Abia Community Manchester</div>
            <div class="text-muted small">Financial Report — {{ $mode === 'legacy' ? 'Historical Debt (Pre-2026)' : $year }}
                @if($selectedCycle) · {{ $selectedCycle->title }} @endif
            </div>
        </div>
    </div>
    <div class="text-muted small">Generated: {{ now()->format('d M Y, H:i') }} &nbsp;|&nbsp; Active members: {{ $totalMembers }}</div>
    <hr>
</div>

{{-- ── Filter Form ── --}}
<form method="GET" class="no-print mb-4" id="filterForm">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-3 align-items-end">

                <div class="col-auto">
                    <label class="form-label fw-medium small mb-1">Year</label>
                    <select name="year" class="form-select form-select-sm" style="width:150px"
                            onchange="var cs=document.getElementById('cycleSelect'); if(cs) cs.value='all'; this.form.submit()">
                        @foreach(range(date('Y'), 2026) as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                        <option value="legacy" {{ $year === 'legacy' ? 'selected' : '' }}>Historical Debt (Pre-2026)</option>
                    </select>
                </div>

                @if($mode !== 'legacy')
                <div class="col-auto">
                    <label class="form-label fw-medium small mb-1">Dues Cycle</label>
                    <select name="cycle_id" id="cycleSelect" class="form-select form-select-sm" style="min-width:220px">
                        <option value="all" {{ $cycleId === 'all' ? 'selected' : '' }}>All cycles ({{ $year }})</option>
                        @foreach($cycles as $c)
                        <option value="{{ $c->id }}" {{ $cycleId == $c->id ? 'selected' : '' }}>
                            {{ $c->title }}
                            @if($c->is_pledge_based) (pledge) @endif
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-auto d-flex align-items-end gap-2 pb-1">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" name="detail" value="1"
                               id="detailCheck" {{ $showDetail ? 'checked' : '' }}>
                        <label class="form-check-label small" for="detailCheck">Member detail</label>
                    </div>
                </div>
                @endif

                <div class="col-auto d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-bar-chart me-1"></i>Generate
                    </button>

                    {{-- CSV export --}}
                    <a href="{{ route('admin.reports.financial.export') }}?year={{ $year }}&cycle_id={{ $cycleId }}"
                       class="btn btn-sm btn-outline-success">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV
                    </a>

                    {{-- Print / PDF --}}
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i>Print / PDF
                    </button>
                </div>

            </div>
        </div>
    </div>
</form>

{{-- ══════════════════════════════════════════════════════════════
     MODE: ALL CYCLES (year overview)
═══════════════════════════════════════════════════════════════ --}}
@if($mode === 'all')

{{-- Summary KPIs --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="fs-4 fw-bold text-success">£{{ number_format($totalCollected, 2) }}</div>
                <div class="text-muted small">Total Collected {{ $year }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="fs-4 fw-bold text-primary">{{ $totalMembers }}</div>
                <div class="text-muted small">Active Members</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="fs-4 fw-bold text-info">{{ $fixedCycles->count() }}</div>
                <div class="text-muted small">Fixed Dues Cycles</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="fs-4 fw-bold text-warning">{{ $pledgeCycles->count() }}</div>
                <div class="text-muted small">Pledge / Donation Cycles</div>
            </div>
        </div>
    </div>
</div>

{{-- Monthly chart --}}
<div class="card border-0 shadow-sm mb-4 no-print">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="fw-semibold mb-0"><i class="bi bi-bar-chart text-success me-2"></i>Monthly Collections {{ $year }}</h6>
    </div>
    <div class="card-body">
        <canvas id="monthlyChart" height="90"></canvas>
    </div>
</div>

{{-- Fixed dues cycles --}}
@if($fixedCycles->count())
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="fw-semibold mb-0"><i class="bi bi-list-check text-primary me-2"></i>Fixed Dues Cycles</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Cycle</th>
                    <th>Rate / Member</th>
                    <th>Expected</th>
                    <th>Collected</th>
                    <th>Collection Rate</th>
                    <th>Members Paid</th>
                </tr>
            </thead>
            <tbody>
                @foreach($fixedCycles as $fc)
                <tr>
                    <td class="fw-medium">
                        <a href="{{ route('admin.reports.financial') }}?year={{ $year }}&cycle_id={{ $fc['cycle']->id }}"
                           class="text-decoration-none">{{ $fc['cycle']->title }}</a>
                    </td>
                    <td>£{{ number_format($fc['cycle']->amount, 2) }}</td>
                    <td>£{{ number_format($fc['expected'], 2) }}</td>
                    <td class="text-success fw-semibold">£{{ number_format($fc['collected'], 2) }}</td>
                    <td>
                        @php $rate = $fc['rate'] @endphp
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1 no-print" style="height:6px">
                                <div class="progress-bar bg-success" style="width:{{ $rate }}%"></div>
                            </div>
                            <span class="badge {{ $rate >= 80 ? 'bg-success' : ($rate >= 50 ? 'bg-warning text-dark' : 'bg-danger') }}">
                                {{ $rate }}%
                            </span>
                        </div>
                    </td>
                    <td>{{ $fc['paid_count'] }} / {{ $totalMembers }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Pledge / donation cycles --}}
@if($pledgeCycles->count())
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="fw-semibold mb-0"><i class="bi bi-heart text-danger me-2"></i>Pledge / Donation Cycles</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Cycle</th>
                    <th>Total Pledged</th>
                    <th>Collected</th>
                    <th>Fulfillment Rate</th>
                    <th>Pledgers</th>
                    <th>Not Pledged</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pledgeCycles as $pc)
                <tr>
                    <td class="fw-medium">
                        <a href="{{ route('admin.reports.financial') }}?year={{ $year }}&cycle_id={{ $pc['cycle']->id }}"
                           class="text-decoration-none">{{ $pc['cycle']->title }}</a>
                    </td>
                    <td>£{{ number_format($pc['pledged'], 2) }}</td>
                    <td class="text-success fw-semibold">£{{ number_format($pc['collected'], 2) }}</td>
                    <td>
                        @php $rate = $pc['fulfillment'] @endphp
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1 no-print" style="height:6px">
                                <div class="progress-bar bg-danger" style="width:{{ $rate }}%"></div>
                            </div>
                            <span class="badge {{ $rate >= 80 ? 'bg-success' : ($rate >= 50 ? 'bg-warning text-dark' : 'bg-danger') }}">
                                {{ $rate }}%
                            </span>
                        </div>
                    </td>
                    <td>{{ $pc['pledger_count'] }}</td>
                    <td class="text-muted">{{ $totalMembers - $pc['pledger_count'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if($fixedCycles->isEmpty() && $pledgeCycles->isEmpty())
<div class="alert alert-info">No dues cycles found for {{ $year }}.</div>
@endif

{{-- Payment method breakdown --}}
@if($methodBreakdown->count())
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="fw-semibold mb-0"><i class="bi bi-credit-card text-secondary me-2"></i>Payment Method Breakdown</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr><th>Method</th><th>Transactions</th><th>Total Collected</th></tr>
            </thead>
            <tbody>
                @foreach($methodBreakdown as $mb)
                <tr>
                    <td class="fw-medium">{{ ucfirst(str_replace('_',' ', $mb->method ?? 'unknown')) }}</td>
                    <td>{{ $mb->count }}</td>
                    <td class="text-success">£{{ number_format($mb->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- All dues payment transactions for this year --}}
@if(isset($annualDuesPayments) && $annualDuesPayments->total() > 0 || $txnSearch !== '')
@php
    $tSortUrl  = fn(string $f) => request()->fullUrlWithQuery(['txn_sort' => $f, 'txn_dir' => ($txnSort === $f && $txnDir === 'asc') ? 'desc' : 'asc', 'page' => null]);
    $tSortIcon = fn(string $f) => $txnSort !== $f ? 'bi-arrow-down-up text-muted' : ($txnDir === 'asc' ? 'bi-sort-down-alt text-primary' : 'bi-sort-up text-primary');
@endphp
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <h6 class="fw-semibold mb-0">
                <i class="bi bi-receipt text-success me-2"></i>Dues Payment Transactions {{ $year }}
            </h6>
            <span class="badge bg-secondary no-print">{{ $annualDuesPayments->total() }} payments</span>
        </div>
        <form method="GET" class="no-print">
            <input type="hidden" name="year" value="{{ $year }}">
            <input type="hidden" name="cycle_id" value="{{ $cycleId }}">
            @if($showDetail)<input type="hidden" name="detail" value="1">@endif
            <input type="hidden" name="txn_sort" value="{{ $txnSort }}">
            <input type="hidden" name="txn_dir" value="{{ $txnDir }}">
            <div class="input-group input-group-sm" style="max-width:320px">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" name="txn_search" class="form-control" placeholder="Search member name…" value="{{ $txnSearch }}">
                <button class="btn btn-outline-secondary" type="submit">Filter</button>
                @if($txnSearch !== '')
                    <a href="{{ request()->fullUrlWithQuery(['txn_search' => null, 'page' => null]) }}" class="btn btn-outline-danger">Clear</a>
                @endif
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Member</th>
                    <th>Cycle</th>
                    <th>
                        <a href="{{ $tSortUrl('amount') }}" class="text-decoration-none text-dark d-flex align-items-center gap-1 no-print">
                            Amount <i class="bi {{ $tSortIcon('amount') }}"></i>
                        </a>
                        <span class="print-header">Amount</span>
                    </th>
                    <th>
                        <a href="{{ $tSortUrl('date') }}" class="text-decoration-none text-dark d-flex align-items-center gap-1 no-print">
                            Date <i class="bi {{ $tSortIcon('date') }}"></i>
                        </a>
                        <span class="print-header">Date</span>
                    </th>
                    <th>Method</th>
                </tr>
            </thead>
            <tbody>
                @forelse($annualDuesPayments as $i => $p)
                <tr>
                    <td class="text-muted small">{{ $annualDuesPayments->firstItem() + $i }}</td>
                    <td>
                        <a href="{{ route('admin.members.show', $p->user) }}"
                           class="fw-medium small text-decoration-none no-print">{{ $p->user->name }}</a>
                        <span class="fw-medium small print-header">{{ $p->user->name }}</span>
                    </td>
                    <td class="small text-muted">{{ $p->duesCycle?->title ?? '—' }}</td>
                    <td class="text-success fw-semibold">£{{ number_format($p->amount, 2) }}</td>
                    <td class="small text-muted">{{ $p->payment_date?->format('d M Y') ?? '—' }}</td>
                    <td class="small">{{ ucfirst(str_replace('_', ' ', $p->method ?? '—')) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">No transactions match this filter.</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot class="table-light fw-semibold">
                <tr>
                    <td colspan="3" class="text-end">Total</td>
                    <td class="text-success">£{{ number_format($txnTotal, 2) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @if($annualDuesPayments->hasPages())
    <div class="card-footer bg-white no-print">{{ $annualDuesPayments->links() }}</div>
    @endif
</div>
@endif

{{-- ══════════════════════════════════════════════════════════════
     MODE: HISTORICAL DEBT (pre-2026 legacy carryover, no calculation)
═══════════════════════════════════════════════════════════════ --}}
@elseif($mode === 'legacy')

<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="fs-4 fw-bold text-danger">£{{ number_format($legacyGrandTotal, 2) }}</div>
                <div class="text-muted small">Total Historical Debt</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="fs-4 fw-bold text-primary">{{ $legacyMemberCount }}</div>
                <div class="text-muted small">Members Affected</div>
            </div>
        </div>
    </div>
</div>

@php
    $lSortUrl  = fn(string $f) => request()->fullUrlWithQuery(['legacy_sort' => $f, 'legacy_dir' => ($legacySort === $f && $legacyDir === 'asc') ? 'desc' : 'asc', 'legacy_page' => null]);
    $lSortIcon = fn(string $f) => $legacySort !== $f ? 'bi-arrow-down-up text-muted' : ($legacyDir === 'asc' ? 'bi-sort-down-alt text-primary' : 'bi-sort-up text-primary');
@endphp
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <h6 class="fw-semibold mb-0">
                <i class="bi bi-clock-history text-danger me-2"></i>Historical Debt — Pre-2026 Carryover
            </h6>
        </div>
        <form method="GET" class="no-print">
            <input type="hidden" name="year" value="legacy">
            <input type="hidden" name="legacy_sort" value="{{ $legacySort }}">
            <input type="hidden" name="legacy_dir" value="{{ $legacyDir }}">
            <div class="input-group input-group-sm" style="max-width:320px">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" name="legacy_search" class="form-control" placeholder="Search member name…" value="{{ $legacySearch }}">
                <button class="btn btn-outline-secondary" type="submit">Filter</button>
                @if($legacySearch !== '')
                    <a href="{{ request()->fullUrlWithQuery(['legacy_search' => null, 'legacy_page' => null]) }}" class="btn btn-outline-danger">Clear</a>
                @endif
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>
                        <a href="{{ $lSortUrl('name') }}" class="text-decoration-none text-dark d-flex align-items-center gap-1 no-print">
                            Member <i class="bi {{ $lSortIcon('name') }}"></i>
                        </a>
                        <span class="print-header">Member</span>
                    </th>
                    <th>Description</th>
                    <th>
                        <a href="{{ $lSortUrl('year') }}" class="text-decoration-none text-dark d-flex align-items-center gap-1 no-print">
                            Year <i class="bi {{ $lSortIcon('year') }}"></i>
                        </a>
                        <span class="print-header">Year</span>
                    </th>
                    <th>
                        <a href="{{ $lSortUrl('amount') }}" class="text-decoration-none text-dark d-flex align-items-center gap-1 no-print">
                            Amount <i class="bi {{ $lSortIcon('amount') }}"></i>
                        </a>
                        <span class="print-header">Amount</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse($legacyBalances as $i => $lb)
                <tr>
                    <td class="text-muted small">{{ $legacyBalances->firstItem() + $i }}</td>
                    <td>
                        <a href="{{ route('admin.members.show', $lb->user_id) }}"
                           class="fw-medium small text-decoration-none no-print">{{ $lb->member_name }}</a>
                        <span class="fw-medium small print-header">{{ $lb->member_name }}</span>
                    </td>
                    <td class="small text-muted">{{ $lb->label }}</td>
                    <td class="small text-muted">{{ $lb->year }}</td>
                    <td class="{{ $lb->amount < 0 ? 'text-success' : 'text-danger' }} fw-semibold">
                        £{{ number_format(abs($lb->amount), 2) }}{{ $lb->amount < 0 ? ' (credit)' : '' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">No historical debt records match this filter.</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot class="table-light fw-semibold">
                <tr>
                    <td colspan="4" class="text-end">Total</td>
                    <td class="text-danger">£{{ number_format($legacyGrandTotal, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @if($legacyBalances->hasPages())
    <div class="card-footer bg-white no-print">{{ $legacyBalances->links() }}</div>
    @endif
</div>

{{-- ══════════════════════════════════════════════════════════════
     MODE: FIXED DUES CYCLE
═══════════════════════════════════════════════════════════════ --}}
@elseif($mode === 'fixed')

{{-- KPI cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="fs-5 fw-bold text-secondary">£{{ number_format($totalExpected, 2) }}</div>
                <div class="text-muted small">Total Expected</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="fs-5 fw-bold text-success">£{{ number_format($totalCollected, 2) }}</div>
                <div class="text-muted small">Collected</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="fs-5 fw-bold text-danger">£{{ number_format($totalExpected - $totalCollected, 2) }}</div>
                <div class="text-muted small">Outstanding</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="fs-5 fw-bold {{ $collectionRate >= 80 ? 'text-success' : ($collectionRate >= 50 ? 'text-warning' : 'text-danger') }}">
                    {{ $collectionRate }}%
                </div>
                <div class="text-muted small">Collection Rate</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="fs-5 fw-bold text-primary">{{ $paidCount }}</div>
                <div class="text-muted small">Members Paid</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="fs-5 fw-bold text-danger">{{ $unpaidCount }}</div>
                <div class="text-muted small">Unpaid / Partial</div>
            </div>
        </div>
    </div>
</div>

{{-- Collection rate progress bar --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-2">
        <div class="d-flex justify-content-between small mb-1">
            <span class="fw-medium">Collection Progress</span>
            <span>£{{ number_format($totalCollected, 2) }} of £{{ number_format($totalExpected, 2) }}</span>
        </div>
        <div class="progress" style="height:12px; border-radius:6px">
            <div class="progress-bar bg-success" role="progressbar"
                 style="width:{{ $collectionRate }}%"
                 aria-valuenow="{{ $collectionRate }}" aria-valuemin="0" aria-valuemax="100">
                {{ $collectionRate }}%
            </div>
        </div>
    </div>
</div>

{{-- Monthly chart --}}
<div class="card border-0 shadow-sm mb-4 no-print">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="fw-semibold mb-0"><i class="bi bi-bar-chart text-success me-2"></i>Monthly Collections — {{ $selectedCycle->title }}</h6>
    </div>
    <div class="card-body">
        <canvas id="monthlyChart" height="90"></canvas>
    </div>
</div>

{{-- Payment method breakdown --}}
@if($methodBreakdown->count())
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="fw-semibold mb-0"><i class="bi bi-credit-card text-secondary me-2"></i>Payment Method Breakdown</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr><th>Method</th><th>Transactions</th><th>Total Collected</th></tr>
            </thead>
            <tbody>
                @foreach($methodBreakdown as $mb)
                <tr>
                    <td class="fw-medium">{{ ucfirst(str_replace('_',' ', $mb->method ?? 'unknown')) }}</td>
                    <td>{{ $mb->count }}</td>
                    <td class="text-success">£{{ number_format($mb->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Member detail (optional) --}}
@if($showDetail && $memberDetail)
@php
    $dSortUrl  = fn(string $f) => request()->fullUrlWithQuery(['sort' => $f, 'dir' => ($sort === $f && $dir === 'asc') ? 'desc' : 'asc']);
    $dSortIcon = fn(string $f) => $sort !== $f ? 'bi-arrow-down-up text-muted' : ($dir === 'asc' ? 'bi-sort-down-alt text-primary' : 'bi-sort-up text-primary');
@endphp
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-semibold mb-0"><i class="bi bi-people text-primary me-2"></i>Member Detail</h6>
        <span class="badge bg-secondary no-print">{{ $memberDetail->count() }} members</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>
                        <a href="{{ $dSortUrl('name') }}" class="text-decoration-none text-dark d-flex align-items-center gap-1 no-print">
                            Member <i class="bi {{ $dSortIcon('name') }}"></i>
                        </a>
                        <span class="print-header">Member</span>
                    </th>
                    <th>Obligation</th>
                    <th>
                        <a href="{{ $dSortUrl('paid') }}" class="text-decoration-none text-dark d-flex align-items-center gap-1 no-print">
                            Paid <i class="bi {{ $dSortIcon('paid') }}"></i>
                        </a>
                        <span class="print-header">Paid</span>
                    </th>
                    <th>
                        <a href="{{ $dSortUrl('balance') }}" class="text-decoration-none text-dark d-flex align-items-center gap-1 no-print">
                            Balance <i class="bi {{ $dSortIcon('balance') }}"></i>
                        </a>
                        <span class="print-header">Balance</span>
                    </th>
                    <th>
                        <a href="{{ $dSortUrl('status') }}" class="text-decoration-none text-dark d-flex align-items-center gap-1 no-print">
                            Status <i class="bi {{ $dSortIcon('status') }}"></i>
                        </a>
                        <span class="print-header">Status</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach($memberDetail as $m)
                <tr>
                    <td class="fw-medium">{{ $m['name'] }}</td>
                    <td>£{{ number_format($m['obligation'], 2) }}</td>
                    <td class="text-success">£{{ number_format($m['paid'], 2) }}</td>
                    <td class="{{ $m['balance'] > 0 ? 'text-danger' : 'text-success' }}">
                        £{{ number_format($m['balance'], 2) }}
                    </td>
                    <td>
                        @if($m['status'] === 'paid')
                            <span class="badge bg-success">Paid</span>
                        @elseif($m['status'] === 'partial')
                            <span class="badge bg-warning text-dark">Partial</span>
                        @else
                            <span class="badge bg-danger">Unpaid</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ══════════════════════════════════════════════════════════════
     MODE: PLEDGE / DONATION CYCLE
═══════════════════════════════════════════════════════════════ --}}
@elseif($mode === 'pledge')

{{-- KPI cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="fs-5 fw-bold text-primary">{{ $pledgerCount }}</div>
                <div class="text-muted small">Members Pledged</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="fs-5 fw-bold text-secondary">{{ $nonPledgerCount }}</div>
                <div class="text-muted small">Not Yet Pledged</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="fs-5 fw-bold text-warning">£{{ number_format($totalPledged, 2) }}</div>
                <div class="text-muted small">Total Pledged</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="fs-5 fw-bold text-success">£{{ number_format($totalCollected, 2) }}</div>
                <div class="text-muted small">Collected</div>
            </div>
        </div>
    </div>
</div>

{{-- Pledge participation banner --}}
<div class="alert {{ $pledgerCount >= $totalMembers * 0.7 ? 'alert-success' : 'alert-info' }} d-flex justify-content-between align-items-center mb-4">
    <span>
        <strong>{{ $pledgerCount }}</strong> out of <strong>{{ $totalMembers }}</strong> active members have pledged.
        @if($nonPledgerCount > 0)
            <span class="text-muted">({{ $nonPledgerCount }} have not yet pledged.)</span>
        @endif
    </span>
    @if($totalPledged > 0)
    <span class="badge bg-primary fs-6">{{ $pledgeFulfillmentRate }}% fulfilled</span>
    @endif
</div>

{{-- Fulfillment progress --}}
@if($totalPledged > 0)
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-2">
        <div class="d-flex justify-content-between small mb-1">
            <span class="fw-medium">Pledge Fulfillment</span>
            <span>£{{ number_format($totalCollected, 2) }} collected of £{{ number_format($totalPledged, 2) }} pledged</span>
        </div>
        <div class="progress" style="height:12px; border-radius:6px">
            <div class="progress-bar bg-warning" role="progressbar"
                 style="width:{{ $pledgeFulfillmentRate }}%">
                {{ $pledgeFulfillmentRate }}%
            </div>
        </div>
    </div>
</div>
@endif

{{-- Monthly chart --}}
<div class="card border-0 shadow-sm mb-4 no-print">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="fw-semibold mb-0"><i class="bi bi-bar-chart text-warning me-2"></i>Monthly Collections — {{ $selectedCycle->title }}</h6>
    </div>
    <div class="card-body">
        <canvas id="monthlyChart" height="90"></canvas>
    </div>
</div>

{{-- Payment method breakdown --}}
@if($methodBreakdown->count())
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="fw-semibold mb-0"><i class="bi bi-credit-card text-secondary me-2"></i>Payment Method Breakdown</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr><th>Method</th><th>Transactions</th><th>Total Collected</th></tr>
            </thead>
            <tbody>
                @foreach($methodBreakdown as $mb)
                <tr>
                    <td class="fw-medium">{{ ucfirst(str_replace('_',' ', $mb->method ?? 'unknown')) }}</td>
                    <td>{{ $mb->count }}</td>
                    <td class="text-success">£{{ number_format($mb->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Member detail (optional) --}}
@if($showDetail && $memberDetail)
@php
    $dSortUrl  = fn(string $f) => request()->fullUrlWithQuery(['sort' => $f, 'dir' => ($sort === $f && $dir === 'asc') ? 'desc' : 'asc']);
    $dSortIcon = fn(string $f) => $sort !== $f ? 'bi-arrow-down-up text-muted' : ($dir === 'asc' ? 'bi-sort-down-alt text-primary' : 'bi-sort-up text-primary');
@endphp
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-semibold mb-0"><i class="bi bi-people text-primary me-2"></i>Member Detail</h6>
        <span class="badge bg-secondary no-print">{{ $memberDetail->count() }} members</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>
                        <a href="{{ $dSortUrl('name') }}" class="text-decoration-none text-dark d-flex align-items-center gap-1 no-print">
                            Member <i class="bi {{ $dSortIcon('name') }}"></i>
                        </a>
                        <span class="print-header">Member</span>
                    </th>
                    <th>
                        <a href="{{ $dSortUrl('pledged') }}" class="text-decoration-none text-dark d-flex align-items-center gap-1 no-print">
                            Pledged <i class="bi {{ $dSortIcon('pledged') }}"></i>
                        </a>
                        <span class="print-header">Pledged</span>
                    </th>
                    <th>
                        <a href="{{ $dSortUrl('paid') }}" class="text-decoration-none text-dark d-flex align-items-center gap-1 no-print">
                            Paid <i class="bi {{ $dSortIcon('paid') }}"></i>
                        </a>
                        <span class="print-header">Paid</span>
                    </th>
                    <th>
                        <a href="{{ $dSortUrl('balance') }}" class="text-decoration-none text-dark d-flex align-items-center gap-1 no-print">
                            Balance on Pledge <i class="bi {{ $dSortIcon('balance') }}"></i>
                        </a>
                        <span class="print-header">Balance on Pledge</span>
                    </th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($memberDetail as $m)
                <tr>
                    <td class="fw-medium">{{ $m['name'] }}</td>
                    <td>
                        @if($m['has_pledged'])
                            £{{ number_format($m['pledged'], 2) }}
                        @else
                            <span class="text-muted fst-italic">No pledge</span>
                        @endif
                    </td>
                    <td class="{{ $m['paid'] > 0 ? 'text-success' : 'text-muted' }}">
                        £{{ number_format($m['paid'], 2) }}
                    </td>
                    <td class="{{ $m['balance'] > 0 ? 'text-danger' : ($m['has_pledged'] ? 'text-success' : 'text-muted') }}">
                        @if($m['has_pledged'])
                            £{{ number_format($m['balance'], 2) }}
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        @if(!$m['has_pledged'])
                            <span class="badge bg-secondary">Not Pledged</span>
                        @elseif($m['balance'] <= 0)
                            <span class="badge bg-success">Fulfilled</span>
                        @elseif($m['paid'] > 0)
                            <span class="badge bg-warning text-dark">Partial</span>
                        @else
                            <span class="badge bg-danger">Pledged / Unpaid</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endif {{-- end mode --}}

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    const canvas = document.getElementById('monthlyChart');
    if (!canvas) return;

    const chartData  = @json($chartData ?? []);
    const isPledge   = @json($mode === 'pledge');
    const color      = isPledge ? 'rgba(200,168,75,' : 'rgba(26,107,60,';

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
            datasets: [{
                label: 'Collections (£)',
                data: chartData,
                backgroundColor: color + '0.7)',
                borderColor:     color + '1)',
                borderWidth: 2,
                borderRadius: 4,
            }],
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { callback: v => '£' + v.toLocaleString() } },
            },
        },
    });
})();
</script>
@endpush
