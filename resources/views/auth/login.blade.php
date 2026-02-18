@extends('layouts.app')

@section('title', 'Sign In')

@section('content')
<div class="min-vh-100 d-flex align-items-center justify-content-center"
     style="background: linear-gradient(135deg, #0f3d22 0%, #1a6b3c 100%)">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-7 col-lg-5">

                <div class="text-center mb-4">
                    <div style="font-size:3rem">🦅</div>
                    <h2 class="text-white fw-bold mt-2">ACM Portal</h2>
                    <p class="text-white-50">Abia Community Manchester</p>
                </div>

                <div class="card border-0 shadow-lg" style="border-radius:16px">
                    <div class="card-body p-4 p-md-5">
                        <h5 class="fw-semibold mb-4">Sign in to your account</h5>

                        <form method="POST" action="{{ route('login.post') }}">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label fw-medium">Phone or Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" name="login" class="form-control @error('login') is-invalid @enderror"
                                           placeholder="e.g. 07700900123 or email@example.com"
                                           value="{{ old('login') }}" autofocus>
                                    @error('login')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" name="password" id="passwordField"
                                           class="form-control @error('password') is-invalid @enderror"
                                           placeholder="Enter your password">
                                    <button type="button" class="btn btn-outline-secondary"
                                            onclick="togglePassword()">
                                        <i class="bi bi-eye" id="eyeIcon"></i>
                                    </button>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-4 d-flex align-items-center justify-content-between">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                                    <label class="form-check-label" for="remember">Remember me</label>
                                </div>
                            </div>

                            <button type="submit" class="btn w-100 text-white fw-semibold"
                                    style="background:var(--acm-green,#1a6b3c); border-radius:8px; padding:.65rem">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                            </button>
                        </form>
                    </div>
                </div>

                <p class="text-center text-white-50 small mt-3">
                    New member? You will receive a registration link from the admin.
                </p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function togglePassword() {
    const f = document.getElementById('passwordField');
    const i = document.getElementById('eyeIcon');
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
