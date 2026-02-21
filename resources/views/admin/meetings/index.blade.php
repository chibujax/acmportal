@extends('layouts.app')
@section('title', 'Meetings')
@section('page-title', 'Meetings & Attendance')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-2">
        <form method="GET" class="d-flex gap-2">
            <select name="year" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                @foreach($years->merge([now()->year])->unique()->sortDesc() as $y)
                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </form>
        <span class="text-muted small">{{ $meetings->total() }} meeting(s)</span>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.meetings.report') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-bar-chart me-1"></i>Attendance Report
        </a>
        <a href="{{ route('admin.meetings.create') }}" class="btn btn-success btn-sm">
            <i class="bi bi-plus-circle me-1"></i>New Meeting
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Meeting</th>
                        <th>Date</th>
                        <th>Venue</th>
                        <th>Status</th>
                        <th>Attendance</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($meetings as $m)
                    <tr>
                        <td>
                            <div class="fw-medium">{{ $m->title }}</div>
                            @if($m->description)
                                <div class="text-muted small">{{ Str::limit($m->description, 60) }}</div>
                            @endif
                        </td>
                        <td class="small">
                            {{ $m->meeting_date->format('d M Y') }}<br>
                            <span class="text-muted">{{ \Carbon\Carbon::parse($m->meeting_time)->format('g:i A') }}</span>
                        </td>
                        <td class="small text-muted">{{ $m->venue ?? '—' }}</td>
                        <td>
                            @php
                                $badgeMap = [
                                    'scheduled' => 'bg-secondary',
                                    'active'    => 'bg-success',
                                    'closed'    => 'bg-dark',
                                ];
                            @endphp
                            <span class="badge {{ $badgeMap[$m->status] ?? 'bg-secondary' }}">
                                {{ ucfirst($m->status) }}
                                @if($m->isActive()) <i class="bi bi-broadcast ms-1"></i> @endif
                            </span>
                        </td>
                        <td>
                            <span class="fw-semibold">{{ $m->attendance_records_count }}</span>
                            <span class="text-muted small"> member(s)</span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.meetings.show', $m) }}"
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye me-1"></i>View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
                            No meetings for {{ $year }}.
                            <a href="{{ route('admin.meetings.create') }}" class="d-block mt-2">Create one</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($meetings->hasPages())
    <div class="card-footer bg-white">{{ $meetings->links() }}</div>
    @endif
</div>
@endsection
