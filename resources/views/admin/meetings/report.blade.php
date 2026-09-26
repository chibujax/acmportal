@extends('layouts.app')
@section('title', 'Attendance Report')
@section('page-title', 'Attendance Report – ' . $year)

@section('content')

{{-- Filter bar --}}
<form method="GET" id="filterForm" class="mb-4">
    <input type="hidden" name="sort" value="{{ $sort }}">
    <input type="hidden" name="dir"  value="{{ $dir }}">

    <div class="card border-0 shadow-sm">
        <div class="card-body py-2">
            <div class="row g-2 align-items-center">

                <div class="col-auto">
                    <select name="year" class="form-select form-select-sm" style="width:auto"
                            onchange="this.form.submit()">
                        @foreach($years->merge([now()->year])->unique()->sortDesc() as $y)
                            <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-auto">
                    <select name="per_page" class="form-select form-select-sm" style="width:auto"
                            onchange="this.form.submit()">
                        @foreach([10, 20, 50, 100] as $n)
                            <option value="{{ $n }}" {{ request('per_page', 20) == $n ? 'selected' : '' }}>{{ $n }} / page</option>
                        @endforeach
                    </select>
                </div>

                <div class="col col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control"
                               placeholder="Search member name…"
                               value="{{ $search }}">
                        @if($search !== '')
                        <a href="{{ request()->fullUrlWithQuery(['search' => '', 'page' => 1]) }}"
                           class="btn btn-outline-secondary btn-sm" title="Clear">
                            <i class="bi bi-x"></i>
                        </a>
                        @endif
                    </div>
                </div>

                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-funnel me-1"></i>Filter
                    </button>
                </div>

                <div class="col-auto ms-auto d-flex gap-2">
                    <span class="text-muted small align-self-center">{{ $totalMeetings }} meeting(s)</span>

                    <a href="{{ route('admin.meetings.report.export', array_merge(
                            ['year' => $year],
                            $search !== '' ? ['search' => $search] : [],
                            $sort  !== 'name'  ? ['sort' => $sort]  : [],
                            $dir   !== 'asc'   ? ['dir'  => $dir]   : []
                        )) }}"
                       class="btn btn-sm btn-outline-success">
                        <i class="bi bi-download me-1"></i>Export CSV
                    </a>

                    <a href="{{ route('admin.meetings.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Meetings
                    </a>
                </div>

            </div>
        </div>
    </div>
</form>

@if($search !== '')
<div class="alert alert-info py-2 mb-3">
    <i class="bi bi-search me-1"></i>
    Showing results for <strong>{{ $search }}</strong> —
    <a href="{{ request()->fullUrlWithQuery(['search' => '', 'page' => 1]) }}" class="alert-link">clear filter</a>
</div>
@endif

{{-- Summary cards --}}
<div class="row g-3 mb-4">
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
                <div class="fs-3 fw-bold text-warning">{{ $eligibleCount }}</div>
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
@php
    // Build a sort URL for a given column, toggling direction if already sorted by it
    $sortUrl = function(string $field) use ($sort, $dir) {
        $newDir = ($sort === $field && $dir === 'asc') ? 'desc' : 'asc';
        return request()->fullUrlWithQuery(['sort' => $field, 'dir' => $newDir, 'page' => 1]);
    };
    $sortIcon = function(string $field) use ($sort, $dir) {
        if ($sort !== $field) return 'bi-arrow-down-up text-muted';
        return $dir === 'asc' ? 'bi-sort-down-alt text-primary' : 'bi-sort-up text-primary';
    };
@endphp

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-semibold mb-0">
            <i class="bi bi-people text-primary me-2"></i>Member Attendance ({{ $year }})
            @if($search !== '')
                <span class="badge bg-info ms-2">Filtered: {{ $search }}</span>
            @endif
        </h6>
        <span class="small text-muted">70% required for election eligibility</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th style="width:40px">#</th>
                        <th>
                            <a href="{{ $sortUrl('name') }}" class="text-decoration-none text-dark d-flex align-items-center gap-1">
                                Member <i class="bi {{ $sortIcon('name') }}"></i>
                            </a>
                        </th>
                        <th>
                            <a href="{{ $sortUrl('attended') }}" class="text-decoration-none text-dark d-flex align-items-center gap-1">
                                Attended <i class="bi {{ $sortIcon('attended') }}"></i>
                            </a>
                        </th>
                        <th>Missed</th>
                        <th>
                            <a href="{{ $sortUrl('rate') }}" class="text-decoration-none text-dark d-flex align-items-center gap-1">
                                Rate <i class="bi {{ $sortIcon('rate') }}"></i>
                            </a>
                        </th>
                        <th>Eligible</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($memberStats as $i => $m)
                    <tr class="{{ $m->eligible ? '' : 'table-warning' }}">
                        <td class="text-muted">{{ ($memberStats->currentPage() - 1) * $memberStats->perPage() + $i + 1 }}</td>
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
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            @if($search !== '')
                                No members found matching <strong>{{ $search }}</strong>.
                            @else
                                No active members found.
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($memberStats->hasPages())
    <div class="card-footer bg-white">{{ $memberStats->links() }}</div>
    @endif
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
