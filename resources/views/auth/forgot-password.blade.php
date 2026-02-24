@extends('layouts.app')
@section('title', 'Forgot Password')

@section('content')
<div class="min-vh-100 d-flex align-items-center justify-content-center"
     style="background: linear-gradient(135deg, #0f3d22 0%, #1a6b3c 100%)">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-7 col-lg-5">

                <div class="text-center mb-4">
                    <div style="font-size:3rem">🦅</div>
                    <h2 class="text-white fw-bold mt-2">ACM Portal</h2>
                    <p class="text-white-50">Reset your password</p>
                </div>

                <div class="card border-0 shadow-lg" style="border-radius:16px">
                    <div class="card-body p-4 p-md-5">
                        <h5 class="fw-semibold mb-1">Forgot your password?</h5>
                        <p class="text-muted small mb-4">
                            Enter your phone number or email. If you have an email on your account,
                            we'll send a reset link. Otherwise, we'll send a 6-digit code to your phone.
                        </p>

                        @if(session('status'))
                            <div class="alert alert-success">{{ session('status') }}</div>
                        @endif

                        <form method="POST" action="{{ route('password.send') }}">
                            @csrf
                            <div class="mb-4">
                                <label class="form-label fw-medium">Phone number or email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" name="identifier"
                                           class="form-control @error('identifier') is-invalid @enderror"
                                           placeholder="e.g. 07700900123 or email@example.com"
                                           value="{{ old('identifier') }}" autofocus>
                                    @error('identifier')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <button type="submit" class="btn w-100 text-white fw-semibold"
                                    style="background:#1a6b3c; border-radius:8px; padding:.65rem">
                                <i class="bi bi-send me-2"></i>Send Reset Instructions
                            </button>
                        </form>
                    </div>
                </div>

                <p class="text-center mt-3">
                    <a href="{{ route('login') }}" class="text-white-50 small">
                        <i class="bi bi-arrow-left me-1"></i>Back to Sign In
                    </a>
                </p>

            </div>
        </div>
    </div>
</div>
@endsection
