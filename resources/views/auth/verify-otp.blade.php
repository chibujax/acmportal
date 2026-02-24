@extends('layouts.app')
@section('title', 'Enter Verification Code')

@section('content')
<div class="min-vh-100 d-flex align-items-center justify-content-center"
     style="background: linear-gradient(135deg, #0f3d22 0%, #1a6b3c 100%)">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-7 col-lg-5">

                <div class="text-center mb-4">
                    <div style="font-size:3rem">🦅</div>
                    <h2 class="text-white fw-bold mt-2">ACM Portal</h2>
                    <p class="text-white-50">Enter your verification code</p>
                </div>

                <div class="card border-0 shadow-lg" style="border-radius:16px">
                    <div class="card-body p-4 p-md-5">
                        <h5 class="fw-semibold mb-1">Check your phone</h5>
                        <p class="text-muted small mb-4">
                            We sent a 6-digit code to your registered phone number.
                            Enter it below. The code expires in <strong>10 minutes</strong>.
                        </p>

                        @if(session('status'))
                            <div class="alert alert-info small">{{ session('status') }}</div>
                        @endif

                        <form method="POST" action="{{ route('password.otp.verify') }}">
                            @csrf
                            <div class="mb-4">
                                <label class="form-label fw-medium">6-digit code</label>
                                <input type="text" name="otp" inputmode="numeric" maxlength="6"
                                       class="form-control form-control-lg text-center fw-bold
                                              tracking-widest @error('otp') is-invalid @enderror"
                                       placeholder="_ _ _ _ _ _"
                                       style="letter-spacing:.5rem; font-size:1.5rem"
                                       autofocus autocomplete="one-time-code">
                                @error('otp')
                                    <div class="invalid-feedback text-center">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn w-100 text-white fw-semibold"
                                    style="background:#1a6b3c; border-radius:8px; padding:.65rem">
                                <i class="bi bi-shield-check me-2"></i>Verify Code
                            </button>
                        </form>
                    </div>
                </div>

                <p class="text-center mt-3">
                    <a href="{{ route('password.forgot') }}" class="text-white-50 small">
                        <i class="bi bi-arrow-left me-1"></i>Start again
                    </a>
                </p>

            </div>
        </div>
    </div>
</div>
@endsection
