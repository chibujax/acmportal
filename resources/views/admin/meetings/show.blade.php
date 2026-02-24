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

        {{-- Member search for JS --}}
        @if($absentees->isNotEmpty())
        <script>
        const members = {!! json_encode($absentees->map(fn($m) => ['id' => $m->id, 'name' => $m->name, 'phone' => $m->phone ?? ''])->values()) !!};

        function setupMemberSearch(inputId, hiddenId, dropdownId) {
            const input    = document.getElementById(inputId);
            const hidden   = document.getElementById(hiddenId);
            const dropdown = document.getElementById(dropdownId);

            input.addEventListener('input', function () {
                const q = this.value.trim().toLowerCase();
                hidden.value = '';

                if (!q) { dropdown.classList.add('d-none'); return; }

                const matches = members.filter(m =>
                    m.name.toLowerCase().includes(q) ||
                    (m.phone && m.phone.toLowerCase().includes(q))
                ).slice(0, 8);

                if (!matches.length) { dropdown.classList.add('d-none'); return; }

                dropdown.innerHTML = matches.map(m =>
                    `<button type="button" class="dropdown-item py-2" data-id="${m.id}" data-label="${m.name.replace(/"/g, '&quot;')}">
                        <span class="fw-medium">${m.name}</span>
                        <span class="text-muted small ms-2">${m.phone ?? ''}</span>
                    </button>`
                ).join('');
                dropdown.classList.remove('d-none');

                dropdown.querySelectorAll('.dropdown-item').forEach(btn => {
                    btn.addEventListener('click', function () {
                        input.value  = this.dataset.label;
                        hidden.value = this.dataset.id;
                        dropdown.classList.add('d-none');
                    });
                });
            });

            document.addEventListener('click', function (e) {
                if (!input.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.add('d-none');
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            setupMemberSearch('checkinSearch', 'checkinUserId', 'checkinDropdown');
            setupMemberSearch('excusedSearch', 'excusedUserId', 'excusedDropdown');
        });
        </script>
        @endif

        {{-- Manual Check-In --}}
        @if($meeting->status !== 'scheduled')
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-0 pt-3 pb-0">
                <h6 class="fw-semibold mb-3">
                    <i class="bi bi-person-check text-primary me-2"></i>Manual Check-In
                </h6>
            </div>
            <div class="card-body">
                @error('error')<div class="alert alert-danger py-2 small">{{ $message }}</div>@enderror
                <form method="POST" action="{{ route('admin.meetings.manual-checkin', $meeting) }}"
                      class="row g-2">
                    @csrf
                    <div class="col-12 col-sm-6 position-relative">
                        <input type="text" id="checkinSearch"
                               class="form-control" placeholder="Search by name or phone…"
                               autocomplete="off">
                        <div id="checkinDropdown"
                             class="d-none w-100 shadow-sm border rounded bg-white"
                             style="max-height:220px; overflow-y:auto; position:absolute; z-index:1050"></div>
                        <input type="hidden" name="user_id" id="checkinUserId" required>
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

        {{-- Mark Excused / Apologies --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 pt-3 pb-0">
                <h6 class="fw-semibold mb-3">
                    <i class="bi bi-patch-check text-info me-2"></i>Mark as Excused / Apologies
                </h6>
            </div>
            <div class="card-body">
                @error('excused_error')<div class="alert alert-danger py-2 small">{{ $message }}</div>@enderror
                <form method="POST" action="{{ route('admin.meetings.mark-excused', $meeting) }}"
                      class="row g-2">
                    @csrf
                    <div class="col-12 col-sm-6 position-relative">
                        <input type="text" id="excusedSearch"
                               class="form-control" placeholder="Search by name or phone…"
                               autocomplete="off">
                        <div id="excusedDropdown"
                             class="d-none w-100 shadow-sm border rounded bg-white"
                             style="max-height:220px; overflow-y:auto; position:absolute; z-index:1050"></div>
                        <input type="hidden" name="user_id" id="excusedUserId" required>
                    </div>
                    <div class="col-12 col-sm-4">
                        <input type="text" name="notes" class="form-control"
                               placeholder="Reason (e.g. travelling, illness)">
                    </div>
                    <div class="col-12 col-sm-2">
                        <button type="submit" class="btn btn-info text-white w-100">
                            <i class="bi bi-patch-check"></i> Excuse
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        {{-- Attendees list --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-semibold mb-2">
                    <i class="bi bi-people text-success me-2"></i>
                    Who Attended ({{ $meeting->attendanceRecords->count() }})
                </h6>
                <a href="{{ route('admin.meetings.export', $meeting) }}"
                   class="btn btn-sm btn-outline-success mb-2">
                    <i class="bi bi-download me-1"></i>Export CSV
                </a>
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
                                    @elseif($record->check_in_method === 'excused')
                                        <span class="badge bg-info text-dark"><i class="bi bi-patch-check me-1"></i>Excused</span>
                                    @else
                                        <span class="badge bg-secondary"><i class="bi bi-hand-index me-1"></i>Manual</span>
                                    @endif
                                </td>
                                <td>
                                    @if($record->status === 'excused')
                                        <span class="badge bg-info text-dark">Excused</span>
                                    @elseif($record->status === 'late')
                                        <span class="badge bg-warning text-dark">Late</span>
                                    @else
                                        <span class="badge bg-success">Present</span>
                                    @endif
                                    @if($record->location_mismatch)
                                        <span class="badge bg-orange text-dark ms-1"
                                              style="background-color:#fd7e14"
                                              title="Member was outside GPS radius when checked in">
                                            <i class="bi bi-geo"></i> GPS Flagged
                                        </span>
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
