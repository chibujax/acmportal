@extends('layouts.app')
@section('title', 'Activate Your Account')

@section('content')
<div class="min-vh-100 d-flex align-items-center justify-content-center"
     style="background: linear-gradient(135deg, #0f3d22 0%, #1a6b3c 100%)">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-7 col-lg-5">

                <div class="text-center mb-4">
                    <img src="{{ asset('logo.jpg') }}" alt="ACM Portal" style="height:80px; object-fit:contain">
                    <h2 class="text-white fw-bold mt-2">ACM Portal</h2>
                    <p class="text-white-50">Abia Community Manchester</p>
                </div>

                @if($expired)

                    <div class="card border-0 shadow-lg text-center p-5" style="border-radius:16px">
                        <div style="font-size:3rem">⛔</div>
                        <h4 class="fw-bold mt-3">Link Expired or Invalid</h4>
                        <p class="text-muted mt-2">
                            This activation link has either expired or already been used.
                            Please contact the administrator to receive a new invite.
                        </p>
                        <a href="{{ route('login') }}" class="btn btn-success mt-3">Back to Login</a>
                    </div>

                @else

                    <div class="card border-0 shadow-lg" style="border-radius:16px">
                        <div class="card-body p-4 p-md-5">
                            <h5 class="fw-semibold mb-1">Activate your account</h5>
                            <p class="text-muted small mb-4">Welcome, {{ $name }}. Choose a password to get started.</p>

                            @if($errors->any())
                                <div class="alert alert-danger small">
                                    {{ $errors->first() }}
                                </div>
                            @endif

                            <form method="POST" action="{{ route('activate.post', ['token' => $token]) }}">
                                @csrf

                                <div class="mb-3">
                                    <label class="form-label fw-medium">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                        <input type="password" name="password" id="passwordField"
                                               class="form-control @error('password') is-invalid @enderror"
                                               placeholder="Min. 10 characters, with a letter and a number" autofocus>
                                        <button type="button" class="btn btn-outline-secondary"
                                                onclick="togglePassword('passwordField', 'eye1')">
                                            <i class="bi bi-eye" id="eye1"></i>
                                        </button>
                                        @error('password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-medium">Confirm Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                        <input type="password" name="password_confirmation" id="confirmField"
                                               class="form-control"
                                               placeholder="Repeat your password">
                                        <button type="button" class="btn btn-outline-secondary"
                                                onclick="togglePassword('confirmField', 'eye2')">
                                            <i class="bi bi-eye" id="eye2"></i>
                                        </button>
                                    </div>
                                </div>

                                <button type="submit" class="btn w-100 text-white fw-semibold"
                                        style="background:#1a6b3c; border-radius:8px; padding:.65rem">
                                    <i class="bi bi-check-circle me-2"></i>Activate My Account
                                </button>
                            </form>
                        </div>
                    </div>

                    <p class="text-center text-white-50 small mt-3">
                        Already activated? <a href="{{ route('login') }}" class="text-white-50">Sign in here</a>
                    </p>

                @endif

            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function togglePassword(fieldId, iconId) {
    const f = document.getElementById(fieldId);
    const i = document.getElementById(iconId);
    if (f.type === 'password') {
        f.type = 'text';
        i.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        f.type = 'password';
        i.classList.replace('bi-eye-slash', 'bi-eye');
    }
}
</script>
@endpush
@endsection
