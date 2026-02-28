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

    {{-- Profile Detail / Edit --}}
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom pt-3 pb-2 d-flex align-items-center justify-content-between">
                <h6 class="fw-semibold mb-0" id="profile-card-title">
                    <i class="bi bi-person-lines-fill me-2 text-primary"></i>Profile Details
                </h6>
                <button type="button" class="btn btn-outline-primary btn-sm" id="edit-btn" onclick="enterEditMode()">
                    <i class="bi bi-pencil me-1"></i>Edit
                </button>
            </div>

            <div class="card-body">

                {{-- Flash messages --}}
                @if(session('success'))
                <div class="alert alert-success py-2 small">{{ session('success') }}</div>
                @endif
                <div class="alert alert-info py-2 small d-none" id="no-change-alert">
                    <i class="bi bi-info-circle me-1"></i>Nothing to change.
                </div>

                {{-- VIEW MODE --}}
                <div id="view-mode">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted fw-normal small">Email</dt>
                        <dd class="col-sm-8">{{ $user->email ?: '—' }}
                            @if($user->email && $user->hasVerifiedEmail())
                                <span class="badge bg-success-subtle text-success ms-1 small">verified</span>
                            @elseif($user->email)
                                <span class="badge bg-warning-subtle text-warning ms-1 small">unverified</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4 text-muted fw-normal small">Address</dt>
                        <dd class="col-sm-8">{{ $user->address ?: '—' }}</dd>

                        <dt class="col-sm-4 text-muted fw-normal small">Occupation</dt>
                        <dd class="col-sm-8">{{ $user->occupation ?: '—' }}</dd>

                        <dt class="col-sm-4 text-muted fw-normal small">Gender</dt>
                        <dd class="col-sm-8">{{ $user->gender ? ucfirst($user->gender) : '—' }}</dd>
                    </dl>

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

                {{-- EDIT MODE --}}
                <div id="edit-mode" class="d-none">
                    <form method="POST" action="{{ route('member.profile.update') }}" id="profile-form">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-medium">Email Address</label>
                            <input type="email" name="email" id="f-email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $user->email) }}" placeholder="your@email.com"
                                   data-original="{{ $user->email }}">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium">Address</label>
                            <input type="text" name="address" id="f-address" class="form-control"
                                   value="{{ old('address', $user->address) }}" placeholder="Your home address"
                                   data-original="{{ $user->address }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium">Occupation</label>
                            <input type="text" name="occupation" id="f-occupation" class="form-control"
                                   value="{{ old('occupation', $user->occupation) }}" placeholder="e.g. Engineer, Teacher"
                                   data-original="{{ $user->occupation }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium">Gender</label>
                            <select name="gender" id="f-gender"
                                    class="form-select @error('gender') is-invalid @enderror"
                                    data-original="{{ $user->gender }}">
                                <option value="">— Not specified —</option>
                                <option value="male"   {{ old('gender', $user->gender) === 'male'   ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('gender', $user->gender) === 'female' ? 'selected' : '' }}>Female</option>
                                <option value="other"  {{ old('gender', $user->gender) === 'other'  ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('gender')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="exitEditMode()">Cancel</button>
                        </div>
                    </form>
                </div>
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

@push('scripts')
<script>
function enterEditMode() {
    document.getElementById('view-mode').classList.add('d-none');
    document.getElementById('edit-mode').classList.remove('d-none');
    document.getElementById('edit-btn').classList.add('d-none');
    document.getElementById('profile-card-title').innerHTML =
        '<i class="bi bi-pencil-square me-2 text-primary"></i>Edit Profile';
    document.getElementById('no-change-alert').classList.add('d-none');
}

function exitEditMode() {
    document.getElementById('edit-mode').classList.add('d-none');
    document.getElementById('view-mode').classList.remove('d-none');
    document.getElementById('edit-btn').classList.remove('d-none');
    document.getElementById('profile-card-title').innerHTML =
        '<i class="bi bi-person-lines-fill me-2 text-primary"></i>Profile Details';
    document.getElementById('no-change-alert').classList.add('d-none');
}

document.getElementById('profile-form').addEventListener('submit', function (e) {
    const fields = ['f-email', 'f-address', 'f-occupation', 'f-gender'];
    const changed = fields.some(id => {
        const el = document.getElementById(id);
        return (el.value || '') !== (el.dataset.original || '');
    });

    if (!changed) {
        e.preventDefault();
        document.getElementById('no-change-alert').classList.remove('d-none');
    }
});

// If there were validation errors, stay in edit mode on page load
@if($errors->any())
enterEditMode();
@endif
</script>
@endpush
@endsection
