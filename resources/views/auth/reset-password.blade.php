@extends('layouts.app')
@section('title', 'Set New Password')

@section('content')
<div class="min-vh-100 d-flex align-items-center justify-content-center"
     style="background: linear-gradient(135deg, #0f3d22 0%, #1a6b3c 100%)">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-7 col-lg-5">

                <div class="text-center mb-4">
                    <div style="font-size:3rem">🦅</div>
                    <h2 class="text-white fw-bold mt-2">ACM Portal</h2>
                    <p class="text-white-50">Choose a new password</p>
                </div>

                <div class="card border-0 shadow-lg" style="border-radius:16px">
                    <div class="card-body p-4 p-md-5">
                        <h5 class="fw-semibold mb-4">Set your new password</h5>

                        <form method="POST" action="{{ route('password.update') }}">
                            @csrf
                            <input type="hidden" name="via"   value="{{ $via }}">
                            @if($via === 'email')
                                <input type="hidden" name="token" value="{{ $token }}">
                                <input type="hidden" name="email" value="{{ $email }}">
                            @endif

                            <div class="mb-3">
                                <label class="form-label fw-medium">New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" name="password" id="passwordField"
                                           class="form-control @error('password') is-invalid @enderror"
                                           placeholder="Minimum 8 characters" autofocus>
                                    <button type="button" class="btn btn-outline-secondary"
                                            onclick="togglePassword('passwordField','eye1')">
                                        <i class="bi bi-eye" id="eye1"></i>
                                    </button>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-medium">Confirm New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" name="password_confirmation" id="confirmField"
                                           class="form-control"
                                           placeholder="Repeat your password">
                                    <button type="button" class="btn btn-outline-secondary"
                                            onclick="togglePassword('confirmField','eye2')">
                                        <i class="bi bi-eye" id="eye2"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="submit" class="btn w-100 text-white fw-semibold"
                                    style="background:#1a6b3c; border-radius:8px; padding:.65rem">
                                <i class="bi bi-check-circle me-2"></i>Save New Password
                            </button>
                        </form>
                    </div>
                </div>

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
