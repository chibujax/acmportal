@extends('layouts.app')
@section('title','Financial Report')
@section('page-title','Financial Report')

@section('content')

<!-- Year Filter -->
<form method="GET" class="d-flex align-items-center gap-2 mb-4">
    <label class="fw-medium">Year:</label>
    <select name="year" class="form-select" style="width:100px" onchange="this.form.submit()">
        @foreach(range(date('Y'), date('Y')-4) as $y)
        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
        @endforeach
    </select>
</form>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="fs-4 fw-bold text-success">£{{ number_format($totalCollected,2) }}</div>
                <div class="text-muted small">Total Collected {{ $year }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="fs-4 fw-bold text-primary">{{ $totalMembers }}</div>
                <div class="text-muted small">Active Members</div>
            </div>
        </div>
    </div>
</div>

<!-- Monthly Chart -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="fw-semibold"><i class="bi bi-bar-chart text-success me-2"></i>Monthly Collections {{ $year }}</h6>
    </div>
    <div class="card-body">
        <canvas id="monthlyChart" height="100"></canvas>
    </div>
</div>

<!-- Cycle Stats -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="fw-semibold"><i class="bi bi-list-check text-primary me-2"></i>Dues Cycle Breakdown</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Cycle</th>
                    <th>Target / Member</th>
                    <th>Total Collected</th>
                    <th>Payers</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cycleStats as $c)
                <tr>
                    <td class="fw-medium">{{ $c->title }}</td>
                    <td>£{{ number_format($c->amount, 2) }}</td>
                    <td class="text-success fw-semibold">£{{ number_format($c->collected ?? 0, 2) }}</td>
                    <td>{{ $c->payers ?? 0 }} members</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center text-muted py-3">No data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('monthlyChart');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
        datasets: [{
            label: 'Collections (£)',
            data: @json($chartData),
            backgroundColor: 'rgba(26,107,60,0.7)',
            borderColor: 'rgba(26,107,60,1)',
            borderWidth: 2,
            borderRadius: 4,
        }],
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { callback: v => '£' + v } },
        },
    },
});
</script>
@endpush
