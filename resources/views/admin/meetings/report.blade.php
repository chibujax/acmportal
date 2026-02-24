@extends('layouts.app')
@section('title', 'Attendance Report')
@section('page-title', 'Attendance Report – ' . $year)

@section('content')

{{-- Year filter --}}
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <form method="GET" class="d-flex gap-2">
        <select name="year" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
            @foreach($years->merge([now()->year])->unique()->sortDesc() as $y)
                <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
            @endforeach
        </select>
        <span class="align-self-center text-muted small">{{ $totalMeetings }} meeting(s) held</span>
    </form>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.meetings.report.export', ['year' => $year]) }}"
           class="btn btn-sm btn-outline-success">
            <i class="bi bi-download me-1"></i>Export CSV
        </a>
        <a href="{{ route('admin.meetings.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>All Meetings
        </a>
    </div>
</div>

{{-- Summary cards --}}
<div class="row g-3 mb-4">
    @php
        $avgRate = $totalMeetings > 0
            ? round($memberStats->avg('percentage'), 1)
            : 0;
        $eligible = $memberStats->where('eligible', true)->count();
    @endphp
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <div class="fs-3 fw-bold text-primary">{{ $totalMeetings }}</div>
                <div class="small text-muted">Meetings Held</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <div class="fs-3 fw-bold text-success">{{ $avgRate }}%</div>
                <div class="small text-muted">Avg Attendance</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <div class="fs-3 fw-bold text-info">{{ $totalMembers }}</div>
                <div class="small text-muted">Active Members</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <div class="fs-3 fw-bold text-warning">{{ $eligible }}</div>
                <div class="small text-muted">Eligible (≥70%)</div>
            </div>
        </div>
    </div>
</div>

{{-- Chart --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="fw-semibold"><i class="bi bi-bar-chart text-success me-2"></i>Monthly Attendance – {{ $year }}</h6>
    </div>
    <div class="card-body">
        <canvas id="attendanceChart" height="100"></canvas>
    </div>
</div>

{{-- Per-member table --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-semibold mb-0">
            <i class="bi bi-people text-primary me-2"></i>Member Attendance ({{ $year }})
        </h6>
        <span class="small text-muted">70% required for election eligibility</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Member</th>
                        <th>Attended</th>
                        <th>Missed</th>
                        <th>Rate</th>
                        <th>Eligible</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($memberStats as $i => $m)
                    <tr class="{{ $m->eligible ? '' : 'table-warning' }}">
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td class="fw-medium">{{ $m->name }}</td>
                        <td class="text-success fw-semibold">{{ $m->attended }}</td>
                        <td class="text-danger">{{ $totalMeetings - $m->attended }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height:6px; min-width:60px">
                                    <div class="progress-bar {{ $m->eligible ? 'bg-success' : 'bg-danger' }}"
                                         style="width:{{ $m->percentage }}%"></div>
                                </div>
                                <span>{{ $m->percentage }}%</span>
                            </div>
                        </td>
                        <td>
                            @if($m->eligible)
                                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Yes</span>
                            @else
                                <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>No</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No active members found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Meetings per-row detail --}}
@if($meetings->isNotEmpty())
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="fw-semibold mb-0"><i class="bi bi-list-check me-2 text-secondary"></i>Meetings Breakdown</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Meeting</th>
                        <th>Date</th>
                        <th>Attended</th>
                        <th>Rate</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($meetings as $m)
                    <tr>
                        <td><a href="{{ route('admin.meetings.show', $m) }}" class="text-decoration-none">{{ $m->title }}</a></td>
                        <td>{{ $m->meeting_date->format('d M Y') }}</td>
                        <td>{{ $m->attendance_records_count }} / {{ $totalMembers }}</td>
                        <td>
                            @php $r = $totalMembers > 0 ? round($m->attendance_records_count / $totalMembers * 100) : 0; @endphp
                            <div class="progress" style="height:6px; width:80px; display:inline-block; vertical-align:middle">
                                <div class="progress-bar bg-success" style="width:{{ $r }}%"></div>
                            </div>
                            <span class="ms-1">{{ $r }}%</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('attendanceChart'), {
    type: 'bar',
    data: {
        labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
        datasets: [{
            label: 'Check-ins',
            data: @json($monthlyChart),
            backgroundColor: 'rgba(26,107,60,0.7)',
            borderColor: 'rgba(26,107,60,1)',
            borderWidth: 2,
            borderRadius: 4,
        }],
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
    },
});
</script>
@endpush
