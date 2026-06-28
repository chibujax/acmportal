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
            <div class="text-muted small">Financial Report — {{ $year }}
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
                    <select name="year" class="form-select form-select-sm" style="width:90px"
                            onchange="document.getElementById('cycleSelect').value='all'; this.form.submit()">
                        @foreach(range(date('Y'), 2026) as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>

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
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-semibold mb-0"><i class="bi bi-people text-primary me-2"></i>Member Detail</h6>
        <span class="badge bg-secondary no-print">{{ $memberDetail->count() }} members</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Member</th>
                    <th>Obligation</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Status</th>
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
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-semibold mb-0"><i class="bi bi-people text-primary me-2"></i>Member Detail</h6>
        <span class="badge bg-secondary no-print">{{ $memberDetail->count() }} members</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Member</th>
                    <th>Pledged</th>
                    <th>Paid</th>
                    <th>Balance on Pledge</th>
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

    const chartData  = @json($chartData);
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
