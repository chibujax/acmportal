@extends('layouts.app')
@section('title','My Profile')
@section('page-title','My Profile')

@section('content')
<div class="row g-4">
    {{-- Profile Info --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center p-4">
            <div class="mb-3">
                <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center"
                     style="width:72px; height:72px; font-size:1.8rem; font-weight:700">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
            </div>
            <h5 class="fw-bold mb-1">{{ $user->name }}</h5>
            <span class="badge bg-secondary mb-2">{{ ucfirst($user->role) }}</span>
            <div class="small text-muted">
                <div><i class="bi bi-phone me-1"></i>{{ $user->phone }}</div>
                @if($user->email)
                <div><i class="bi bi-envelope me-1"></i>{{ $user->email }}</div>
                @endif
                @if($user->address)
                <div class="mt-1"><i class="bi bi-geo-alt me-1"></i>{{ $user->address }}</div>
                @endif
                @if($user->gender)
                <div class="mt-1"><i class="bi bi-person-fill me-1"></i>{{ ucfirst($user->gender) }}</div>
                @endif
            </div>
            <div class="mt-3 small text-muted">Member since {{ $user->created_at->format('F Y') }}</div>
        </div>
    </div>

    {{-- Edit Form --}}
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom pt-3 pb-2">
                <h6 class="fw-semibold mb-0"><i class="bi bi-pencil-square me-2 text-primary"></i>Update Profile</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('member.profile.update') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-medium">Email Address</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $user->email) }}" placeholder="your@email.com">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @if($user->email && $user->hasVerifiedEmail())
                            <div class="form-text text-success"><i class="bi bi-check-circle me-1"></i>Email verified</div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Address</label>
                        <input type="text" name="address" class="form-control"
                               value="{{ old('address', $user->address) }}" placeholder="Your home address">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Occupation</label>
                        <input type="text" name="occupation" class="form-control"
                               value="{{ old('occupation', $user->occupation) }}" placeholder="e.g. Engineer, Teacher">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Gender</label>
                        <select name="gender" class="form-select @error('gender') is-invalid @enderror">
                            <option value="">— Not specified —</option>
                            <option value="male"   {{ old('gender', $user->gender) === 'male'   ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender', $user->gender) === 'female' ? 'selected' : '' }}>Female</option>
                            <option value="other"  {{ old('gender', $user->gender) === 'other'  ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('gender')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button class="btn btn-primary">Save Changes</button>
                </form>

                @if($user->email && !$user->hasVerifiedEmail())
                <div class="alert alert-warning d-flex align-items-center gap-2 py-2 mt-3 mb-0">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div class="small flex-grow-1">Email not verified.</div>
                    <form method="POST" action="{{ route('email.resend') }}">
                        @csrf
                        <button type="submit" class="btn btn-link p-0 text-warning small">Resend</button>
                    </form>
                </div>
                @endif
            </div>
        </div>

        {{-- Change Password --}}
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-white border-bottom pt-3 pb-2">
                <h6 class="fw-semibold mb-0"><i class="bi bi-lock me-2 text-warning"></i>Change Password</h6>
            </div>
            <div class="card-body">
                <a href="{{ route('password.forgot') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-clockwise me-1"></i>Reset via Forgot Password
                </a>
                <p class="text-muted small mt-2 mb-0">
                    You'll receive a reset link by email, or an OTP via SMS if no email is set.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
