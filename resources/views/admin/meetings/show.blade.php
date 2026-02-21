@extends('layouts.app')
@section('title', $meeting->title)
@section('page-title', $meeting->title)

@section('content')

{{-- Status bar --}}
<div class="d-flex align-items-center gap-2 mb-4 flex-wrap">
    @php
        $badgeMap = ['scheduled' => 'bg-secondary', 'active' => 'bg-success', 'closed' => 'bg-dark'];
    @endphp
    <span class="badge fs-6 {{ $badgeMap[$meeting->status] ?? 'bg-secondary' }}">
        {{ ucfirst($meeting->status) }}
        @if($meeting->isActive()) <i class="bi bi-broadcast ms-1"></i> @endif
    </span>
    <span class="text-muted small">
        <i class="bi bi-calendar me-1"></i>{{ $meeting->meeting_date->format('l, d F Y') }}
        &middot; {{ \Carbon\Carbon::parse($meeting->meeting_time)->format('g:i A') }}
        @if($meeting->venue) &middot; <i class="bi bi-geo-alt me-1"></i>{{ $meeting->venue }} @endif
    </span>

    <div class="ms-auto d-flex gap-2 flex-wrap">
        @if($meeting->status === 'scheduled')
            <form method="POST" action="{{ route('admin.meetings.activate', $meeting) }}">
                @csrf @method('PATCH')
                <button class="btn btn-success btn-sm">
                    <i class="bi bi-play-circle me-1"></i>Start Meeting (Activate QR)
                </button>
            </form>
        @elseif($meeting->status === 'active')
            <form method="POST" action="{{ route('admin.meetings.close', $meeting) }}"
                  onsubmit="return confirm('Close this meeting? No more check-ins will be accepted.')">
                @csrf @method('PATCH')
                <button class="btn btn-danger btn-sm">
                    <i class="bi bi-stop-circle me-1"></i>Close Meeting
                </button>
            </form>
        @endif
        <a href="{{ route('admin.meetings.edit', $meeting) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
    </div>
</div>

<div class="row g-4">

    {{-- Left: QR Code + Stats --}}
    <div class="col-12 col-lg-4">

        {{-- QR Code Card --}}
        @if($meeting->isActive() && $qrUrl)
        <div class="card border-0 shadow-sm mb-4 text-center">
            <div class="card-header bg-success text-white border-0">
                <i class="bi bi-qr-code me-2"></i>
                <strong>LIVE – Scan to Check In</strong>
            </div>
            <div class="card-body p-3">
                <div class="bg-white p-2 d-inline-block rounded border">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data={{ urlencode($qrUrl) }}"
                         alt="QR Code" width="220" height="220" class="d-block">
                </div>
                <p class="small text-muted mt-2 mb-1">
                    Display this on a screen for members to scan.
                </p>
                @if($meeting->qr_expires_at)
                <span class="badge bg-warning text-dark">
                    <i class="bi bi-clock me-1"></i>Expires {{ $meeting->qr_expires_at->diffForHumans() }}
                </span>
                @endif
                <div class="mt-2">
                    <a href="{{ $qrUrl }}" target="_blank" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-box-arrow-up-right me-1"></i>Open Check-In URL
                    </a>
                </div>
            </div>
        </div>
        @elseif($meeting->status === 'closed')
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body text-center py-4 text-muted">
                <i class="bi bi-lock-fill fs-2 d-block mb-2"></i>
                Meeting Closed
            </div>
        </div>
        @else
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body text-center py-4 text-muted">
                <i class="bi bi-clock fs-2 d-block mb-2"></i>
                QR not active yet.<br>
                <small>Click "Start Meeting" to activate check-in.</small>
            </div>
        </div>
        @endif

        {{-- Stats --}}
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="row g-2 text-center">
                    <div class="col-6">
                        <div class="fs-3 fw-bold text-success">{{ $meeting->attendanceRecords->count() }}</div>
                        <div class="small text-muted">Attended</div>
                    </div>
                    <div class="col-6">
                        <div class="fs-3 fw-bold text-danger">{{ $absentees->count() }}</div>
                        <div class="small text-muted">Absent</div>
                    </div>
                    <div class="col-12 mt-2">
                        @php $rate = $meeting->attendanceRate(); @endphp
                        <div class="progress" style="height:10px; border-radius:5px">
                            <div class="progress-bar bg-success" style="width:{{ $rate }}%"></div>
                        </div>
                        <div class="small text-muted mt-1">{{ $rate }}% attendance rate</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Right: Attendees + Manual Check-In --}}
    <div class="col-12 col-lg-8">

        {{-- Manual Check-In --}}
        @if($meeting->status !== 'scheduled')
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 pt-3 pb-0">
                <h6 class="fw-semibold mb-3">
                    <i class="bi bi-person-check text-primary me-2"></i>Manual Check-In
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.meetings.manual-checkin', $meeting) }}"
                      class="row g-2">
                    @csrf
                    <div class="col-12 col-sm-6">
                        <select name="user_id" class="form-select @error('error') is-invalid @enderror" required>
                            <option value="">— Select Member —</option>
                            @foreach($absentees as $m)
                            <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->phone }})</option>
                            @endforeach
                        </select>
                        @error('error')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-sm-4">
                        <input type="text" name="notes" class="form-control"
                               placeholder="Note (optional, e.g. phone dead)">
                    </div>
                    <div class="col-12 col-sm-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-plus-circle"></i> Check In
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        {{-- Attendees list --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between">
                <h6 class="fw-semibold mb-2">
                    <i class="bi bi-people text-success me-2"></i>
                    Who Attended ({{ $meeting->attendanceRecords->count() }})
                </h6>
            </div>
            <div class="card-body p-0">
                @if($meeting->attendanceRecords->isEmpty())
                <div class="text-center text-muted py-4">No check-ins yet.</div>
                @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Member</th>
                                <th>Check-In Time</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Notes</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($meeting->attendanceRecords->sortBy('check_in_time') as $i => $record)
                            <tr>
                                <td class="text-muted">{{ $i + 1 }}</td>
                                <td class="fw-medium">{{ $record->user->name }}</td>
                                <td>{{ $record->check_in_time->format('H:i:s') }}</td>
                                <td>
                                    @if($record->check_in_method === 'qr_scan')
                                        <span class="badge bg-primary"><i class="bi bi-qr-code me-1"></i>QR</span>
                                    @else
                                        <span class="badge bg-secondary"><i class="bi bi-hand-index me-1"></i>Manual</span>
                                    @endif
                                </td>
                                <td>
                                    @if($record->status === 'late')
                                        <span class="badge bg-warning text-dark">Late</span>
                                    @else
                                        <span class="badge bg-success">Present</span>
                                    @endif
                                </td>
                                <td class="text-muted">{{ $record->notes ?? '—' }}</td>
                                <td>
                                    <form method="POST"
                                          action="{{ route('admin.meetings.remove-checkin', [$meeting, $record]) }}"
                                          onsubmit="return confirm('Remove this check-in?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger py-0 px-1">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>

        {{-- Absent members (collapsible) --}}
        @if($absentees->isNotEmpty())
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-white border-0 pt-2 pb-2">
                <button class="btn btn-sm btn-link text-decoration-none text-danger p-0"
                        data-bs-toggle="collapse" data-bs-target="#absentList">
                    <i class="bi bi-person-x me-1"></i>
                    Absent ({{ $absentees->count() }}) — click to expand
                </button>
            </div>
            <div id="absentList" class="collapse">
                <div class="card-body p-0">
                    <div class="row g-0">
                        @foreach($absentees as $m)
                        <div class="col-6 col-md-4 px-3 py-2 border-bottom border-end small text-muted">
                            {{ $m->name }}
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif

    </div>
</div>
@endsection
