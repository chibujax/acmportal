@extends('layouts.app')
@section('title', 'Create Account')
@section('content')
<div class="min-vh-100 d-flex align-items-center justify-content-center"
     style="background: linear-gradient(135deg, #0f3d22 0%, #1a6b3c 100%)">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-7 col-lg-5">

                <div class="text-center mb-4">
                    <div style="font-size:3rem">🦅</div>
                    <h2 class="text-white fw-bold mt-2">Create Your Account</h2>
                    <p class="text-white-50">Welcome, {{ $pendingMember->name }}</p>
                </div>

                <div class="card border-0 shadow-lg" style="border-radius:16px">
                    <div class="card-body p-4 p-md-5">

                        <div class="alert alert-info d-flex align-items-center gap-2 mb-4">
                            <i class="bi bi-info-circle-fill"></i>
                            <small>Your phone number <strong>{{ $pendingMember->phone }}</strong> has been pre-registered.
                            Please confirm it below and set a password.</small>
                        </div>

                        <form method="POST" action="{{ route('register.post') }}">
                            @csrf
                            <input type="hidden" name="token" value="{{ $token }}">

                            <div class="mb-3">
                                <label class="form-label fw-medium">Phone Number <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-phone"></i></span>
                                    <input type="text" name="phone"
                                           class="form-control @error('phone') is-invalid @enderror"
                                           value="{{ old('phone', $pendingMember->phone) }}" required>
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">
                                    Email Address <span class="text-muted small">(optional)</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="email"
                                           class="form-control @error('email') is-invalid @enderror"
                                           value="{{ old('email', $pendingMember->email) }}"
                                           placeholder="your@email.com">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted">If provided, we will send a verification email.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">Password <span class="text-danger">*</span></label>
                                <input type="password" name="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       placeholder="Minimum 8 characters" required>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-medium">Confirm Password <span class="text-danger">*</span></label>
                                <input type="password" name="password_confirmation"
                                       class="form-control" placeholder="Repeat password" required>
                            </div>

                            <button type="submit" class="btn w-100 text-white fw-semibold"
                                    style="background:var(--acm-green,#1a6b3c); border-radius:8px; padding:.65rem">
                                <i class="bi bi-check-circle me-2"></i>Create Account
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
