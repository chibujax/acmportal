@extends('layouts.app')
@section('title', 'My Attendance')
@section('page-title', 'My Attendance')

@section('content')

{{-- Year filter + summary --}}
<div class="row g-3 mb-4">
    <div class="col-12 d-flex align-items-center gap-3 flex-wrap">
        <form method="GET">
            <select name="year" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                @foreach($years->merge([now()->year])->unique()->sortDesc() as $y)
                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- Stats --}}
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <div class="fs-3 fw-bold text-success">{{ $attended }}</div>
                <div class="small text-muted">Meetings Attended</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <div class="fs-3 fw-bold text-danger">{{ $totalMeetings - $attended }}</div>
                <div class="small text-muted">Meetings Missed</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <div class="fs-3 fw-bold {{ $percentage >= 70 ? 'text-success' : 'text-danger' }}">
                    {{ $percentage }}%
                </div>
                <div class="small text-muted">Attendance Rate</div>
                @if($totalMeetings > 0)
                    <div class="mt-1">
                        @if($percentage >= 70)
                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Election Eligible</span>
                        @else
                            <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Below 70% threshold</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Chart --}}
@if($totalMeetings > 0)
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="fw-semibold"><i class="bi bi-bar-chart text-success me-2"></i>Monthly Attendance – {{ $year }}</h6>
    </div>
    <div class="card-body">
        <canvas id="attendanceChart" height="100"></canvas>
    </div>
</div>
@endif

{{-- Meeting list --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="fw-semibold mb-0">
            <i class="bi bi-calendar3 text-primary me-2"></i>All Meetings – {{ $year }}
        </h6>
    </div>
    <div class="card-body p-0">
        @if($meetings->isEmpty())
        <div class="text-center text-muted py-5">
            <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
            No meetings recorded for {{ $year }}.
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Meeting</th>
                        <th>Date</th>
                        <th>Your Status</th>
                        <th>Check-In Time</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($meetings as $m)
                    <tr>
                        <td>
                            <div class="fw-medium">{{ $m->title }}</div>
                            @if($m->venue)
                                <div class="text-muted small"><i class="bi bi-geo-alt me-1"></i>{{ $m->venue }}</div>
                            @endif
                        </td>
                        <td class="small text-muted">
                            {{ $m->meeting_date->format('d M Y') }}<br>
                            {{ \Carbon\Carbon::parse($m->meeting_time)->format('g:i A') }}
                        </td>
                        <td>
                            @if($m->user_record)
                                @if($m->user_record->status === 'late')
                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-clock me-1"></i>Late
                                    </span>
                                @else
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle me-1"></i>Present
                                    </span>
                                @endif
                            @else
                                <span class="badge bg-danger">
                                    <i class="bi bi-x-circle me-1"></i>Absent
                                </span>
                            @endif
                        </td>
                        <td class="small">
                            @if($m->user_record)
                                {{ $m->user_record->check_in_time->format('H:i') }}
                                @if($m->user_record->check_in_method === 'manual')
                                    <span class="badge bg-secondary ms-1">Admin</span>
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

@endsection

@push('scripts')
@if($totalMeetings > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('attendanceChart'), {
    type: 'bar',
    data: {
        labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
        datasets: [{
            label: 'Meetings Attended',
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
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
    },
});
</script>
@endif
@endpush
