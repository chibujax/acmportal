@extends('layouts.app')
@section('title', 'Check In')

@section('content')
<div class="min-vh-100 d-flex align-items-center justify-content-center"
     style="background: linear-gradient(135deg, #0f3d22 0%, #1a6b3c 100%)">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-8 col-md-5 col-lg-4">

                <div class="text-center mb-4">
                    <div style="font-size:3rem">🦅</div>
                    <h4 class="text-white fw-bold mt-2">ACM Attendance</h4>
                </div>

                <div class="card border-0 shadow-lg" style="border-radius:16px">
                    <div class="card-body p-4 text-center">

                        {{-- ── ERROR ──────────────────────────────────── --}}
                        @if(isset($error))
                            <div style="font-size:3rem">⛔</div>
                            <h5 class="fw-bold mt-3 text-danger">Check-In Unavailable</h5>
                            <p class="text-muted mt-2">{{ $error }}</p>
                            @if(isset($meeting))
                                <div class="mt-3 p-3 rounded bg-light text-start small">
                                    <strong>{{ $meeting->title }}</strong><br>
                                    <span class="text-muted">{{ $meeting->meeting_date->format('d F Y') }}</span>
                                </div>
                            @endif
                            @auth
                                <a href="{{ route('member.dashboard') }}" class="btn btn-success mt-3 w-100">
                                    Go to Dashboard
                                </a>
                            @endauth

                        {{-- ── ALREADY CHECKED IN ──────────────────────── --}}
                        @elseif(isset($already) && $already)
                            <div style="font-size:3rem">✅</div>
                            <h5 class="fw-bold mt-3 text-success">Already Checked In!</h5>
                            <p class="text-muted">You already signed in for this meeting.</p>
                            <div class="p-3 rounded mb-3 text-start" style="background:#f0fdf4; border-left:4px solid #22c55e">
                                <div class="fw-semibold">{{ $meeting->title }}</div>
                                <div class="small text-muted">
                                    {{ $meeting->meeting_date->format('d F Y') }}
                                    &middot; Checked in at {{ $record->check_in_time->format('H:i') }}
                                </div>
                                @if($record->status === 'late')
                                    <span class="badge bg-warning text-dark mt-1">Late arrival</span>
                                @endif
                                @if($record->location_mismatch)
                                    <span class="badge bg-warning text-dark mt-1">Location flagged</span>
                                @endif
                            </div>
                            <a href="{{ route('member.attendance') }}" class="btn btn-outline-success w-100">
                                View My Attendance
                            </a>

                        {{-- ── IDENTITY CONFIRMATION + GPS ─────────────── --}}
                        @elseif(isset($confirm) && $confirm)
                            {{-- Meeting info --}}
                            <div class="p-3 rounded mb-4 text-start" style="background:#f8f9fa; border-left:4px solid #1a6b3c">
                                <div class="fw-semibold small text-uppercase text-muted mb-1">Meeting</div>
                                <div class="fw-bold">{{ $meeting->title }}</div>
                                <div class="small text-muted">
                                    {{ $meeting->meeting_date->format('d F Y') }}
                                    @if($meeting->venue) &middot; {{ $meeting->venue }} @endif
                                    @if($meeting->venue_postcode) ({{ strtoupper($meeting->venue_postcode) }}) @endif
                                </div>
                            </div>

                            {{-- Step: Confirm identity --}}
                            <div id="step-confirm">
                                <div style="font-size:2.5rem">👤</div>
                                <h5 class="fw-bold mt-2">Is this you?</h5>
                                <div class="p-3 rounded mb-3" style="background:#f0fdf4; border:1px solid #bbf7d0">
                                    <div class="fw-bold fs-5">{{ $user->name }}</div>
                                    <div class="text-muted small">{{ $user->phone }}</div>
                                </div>
                                {{-- Location permission notice --}}
                                <div class="alert alert-info py-2 px-3 mb-3 text-start small">
                                    <i class="bi bi-geo-alt-fill me-1"></i>
                                    <strong>Location required.</strong> After confirming, your browser will ask for permission to access your location. Please tap <strong>Allow</strong> to complete check-in.
                                </div>

                                <button id="btn-yes" class="btn btn-success w-100 mb-2">
                                    <i class="bi bi-check-circle me-1"></i> Yes, that's me — Check Me In
                                </button>
                                <form method="POST" action="{{ route('attendance.switch', $meeting->qr_token) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-secondary w-100 btn-sm">
                                        Not me — sign in as someone else
                                    </button>
                                </form>
                            </div>

                            {{-- Step: GPS checking --}}
                            <div id="step-gps" class="d-none">
                                <div style="font-size:2.5rem" class="mb-2">📍</div>
                                <h5 class="fw-bold">Requesting your location…</h5>
                                <p class="text-muted small">
                                    A prompt should appear at the top of your screen.<br>
                                    Please tap <strong>Allow</strong> to continue.
                                </p>
                                <div class="spinner-border text-success mt-2" role="status">
                                    <span class="visually-hidden">Loading…</span>
                                </div>
                            </div>

                            {{-- Step: GPS error --}}
                            <div id="step-gps-error" class="d-none">
                                <div style="font-size:2.5rem" class="mb-2">📍</div>
                                <h5 class="fw-bold text-danger" id="gps-error-title">Location Error</h5>
                                <p class="text-muted small" id="gps-error-msg"></p>
                                <a href="mailto:info@abiacommunitymanchester.org.uk"
                                   class="btn btn-outline-success w-100 mt-2">
                                    <i class="bi bi-envelope me-1"></i> Contact Admin
                                </a>
                                <button id="btn-retry" class="btn btn-link w-100 mt-1 small">Try again</button>
                            </div>

                            {{-- Step: Success (JS-rendered) --}}
                            <div id="step-success" class="d-none">
                                <div style="font-size:3rem" id="success-icon">✅</div>
                                <h5 class="fw-bold mt-3 text-success" id="success-title">Checked In!</h5>
                                <p class="text-muted small" id="success-msg"></p>
                                <div class="p-3 rounded mb-3 text-start" style="background:#f0fdf4; border-left:4px solid #22c55e">
                                    <div class="fw-semibold">{{ $meeting->title }}</div>
                                    <div class="small text-muted">{{ $meeting->meeting_date->format('d F Y') }}</div>
                                    <div class="small mt-1 text-muted" id="success-distance"></div>
                                </div>
                                <a href="{{ route('member.attendance') }}" class="btn btn-success w-100">
                                    <i class="bi bi-calendar-check me-1"></i> View My Attendance
                                </a>
                            </div>

                            {{-- Hidden CSRF token for JS fetch --}}
                            <meta name="checkin-url" content="{{ route('attendance.checkin.post', $meeting->qr_token) }}">

                        {{-- ── SUCCESS (server-side fallback) ─────────── --}}
                        @elseif(isset($success) && $success)
                            <div style="font-size:3rem">{{ isset($isLate) && $isLate ? '⏰' : '✅' }}</div>
                            <h5 class="fw-bold mt-3 {{ isset($isLate) && $isLate ? 'text-warning' : 'text-success' }}">
                                {{ isset($isLate) && $isLate ? 'Checked In (Late)' : 'Checked In!' }}
                            </h5>
                            <p class="text-muted">Your attendance has been recorded.</p>
                            <div class="p-3 rounded mb-3 text-start" style="background:#f0fdf4; border-left:4px solid #22c55e">
                                <div class="fw-semibold">{{ $meeting->title }}</div>
                                <div class="small text-muted">
                                    {{ $meeting->meeting_date->format('d F Y') }}
                                    &middot; {{ $meeting->venue ?? '' }}
                                </div>
                                <div class="small fw-medium mt-1">
                                    <i class="bi bi-clock me-1"></i>
                                    Signed in at {{ $record->check_in_time->format('H:i:s') }}
                                </div>
                            </div>
                            <a href="{{ route('member.attendance') }}" class="btn btn-success w-100">
                                <i class="bi bi-calendar-check me-1"></i>View My Attendance
                            </a>

                        @endif

                    </div>
                </div>

                @auth
                <p class="text-center text-white-50 small mt-3">
                    Signed in as <strong class="text-white">{{ auth()->user()->name }}</strong>
                    &middot;
                    <a href="{{ route('logout') }}"
                       onclick="event.preventDefault(); document.getElementById('qr-logout').submit();"
                       class="text-white-50">Sign out</a>
                    <form id="qr-logout" method="POST" action="{{ route('logout') }}" class="d-none">@csrf</form>
                </p>
                @endauth

            </div>
        </div>
    </div>
</div>

@if(isset($confirm) && $confirm)
<script>
(function () {
    const btnYes      = document.getElementById('btn-yes');
    const btnRetry    = document.getElementById('btn-retry');
    const stepConfirm = document.getElementById('step-confirm');
    const stepGps     = document.getElementById('step-gps');
    const stepError   = document.getElementById('step-gps-error');
    const stepSuccess = document.getElementById('step-success');
    const checkinUrl  = document.querySelector('meta[name="checkin-url"]').content;
    const csrfToken   = document.querySelector('meta[name="csrf-token"]').content;

    function showStep(step) {
        [stepConfirm, stepGps, stepError, stepSuccess].forEach(el => el.classList.add('d-none'));
        step.classList.remove('d-none');
    }

    function showGpsError(title, msg) {
        document.getElementById('gps-error-title').textContent = title;
        document.getElementById('gps-error-msg').textContent   = msg;
        showStep(stepError);
    }

    function submitCheckin(lat, lng) {
        fetch(checkinUrl, {
            method:  'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept':       'application/json',
            },
            body: JSON.stringify({ lat: lat ?? null, lng: lng ?? null }),
        })
        .then(r => {
            const ct = r.headers.get('content-type') || '';
            if (!ct.includes('application/json')) {
                throw new Error('non-json');
            }
            return r.json();
        })
        .then(data => {
            if (data.success) {
                const icon  = document.getElementById('success-icon');
                const title = document.getElementById('success-title');
                const msg   = document.getElementById('success-msg');
                const dist  = document.getElementById('success-distance');

                if (data.mismatch) {
                    icon.textContent  = '⚠️';
                    title.textContent = 'Checked In (Location Flagged)';
                    title.className   = 'fw-bold mt-3 text-warning';
                    msg.textContent   = 'Your attendance was recorded but your location was outside the expected area. An admin may review this.';
                } else if (data.isLate) {
                    icon.textContent  = '⏰';
                    title.textContent = 'Checked In (Late)';
                    title.className   = 'fw-bold mt-3 text-warning';
                    msg.textContent   = 'You arrived after the meeting started, but your attendance has been recorded.';
                } else {
                    msg.textContent = 'Your attendance has been recorded successfully.';
                }

                if (data.distance !== null && data.distance !== undefined) {
                    dist.innerHTML = '<i class="bi bi-geo-alt me-1"></i>' + data.distance + 'm from venue';
                }

                showStep(stepSuccess);
            } else if (data.already) {
                location.reload();
            } else if (data.gps_error === 'out_of_range') {
                showGpsError('Too Far from Venue', data.message);
            } else if (data.gps_error === 'location_denied') {
                showGpsError('Location Required', data.message);
            } else {
                showGpsError('Error', data.error ?? 'Something went wrong. Please try again.');
            }
        })
        .catch(() => showGpsError('Connection Error', 'Could not reach the server. Please check your connection and try again.'));
    }

    function requestLocation() {
        showStep(stepGps);

        if (! ('geolocation' in navigator)) {
            // Device has no GPS — send null, server decides based on meeting settings
            submitCheckin(null, null);
            return;
        }

        navigator.geolocation.getCurrentPosition(
            pos  => submitCheckin(pos.coords.latitude, pos.coords.longitude),
            _err => submitCheckin(null, null),
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
    }

    btnYes.addEventListener('click', requestLocation);
    btnRetry && btnRetry.addEventListener('click', requestLocation);
})();
</script>
@endif
@endsection
