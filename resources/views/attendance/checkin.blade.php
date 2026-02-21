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

                        {{-- ERROR --}}
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
                            <a href="{{ route('member.dashboard') }}" class="btn btn-success mt-3 w-100">
                                Go to Dashboard
                            </a>

                        {{-- ALREADY CHECKED IN --}}
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
                            </div>
                            <a href="{{ route('member.attendance') }}" class="btn btn-outline-success w-100">
                                View My Attendance
                            </a>

                        {{-- SUCCESS --}}
                        @elseif(isset($success) && $success)
                            <div style="font-size:3rem">{{ isset($isLate) && $isLate ? '⏰' : '✅' }}</div>
                            <h5 class="fw-bold mt-3 {{ isset($isLate) && $isLate ? 'text-warning' : 'text-success' }}">
                                {{ isset($isLate) && $isLate ? 'Checked In (Late)' : 'Checked In!' }}
                            </h5>

                            @if(isset($isLate) && $isLate)
                                <p class="text-muted small">You arrived after the meeting started, but your attendance has been recorded.</p>
                            @else
                                <p class="text-muted">Your attendance has been recorded.</p>
                            @endif

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

                <p class="text-center text-white-50 small mt-3">
                    Signed in as <strong class="text-white">{{ auth()->user()->name }}</strong>
                    &middot;
                    <a href="{{ route('logout') }}"
                       onclick="event.preventDefault(); document.getElementById('qr-logout').submit();"
                       class="text-white-50">Sign out</a>
                    <form id="qr-logout" method="POST" action="{{ route('logout') }}" class="d-none">@csrf</form>
                </p>

            </div>
        </div>
    </div>
</div>
@endsection
