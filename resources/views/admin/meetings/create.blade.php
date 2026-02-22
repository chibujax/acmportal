@extends('layouts.app')
@section('title', 'Create Meeting')
@section('page-title', 'Create New Meeting')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="fw-semibold mb-0">
                    <i class="bi bi-calendar-plus text-success me-2"></i>Meeting Details
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.meetings.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-medium">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title"
                               class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title', 'ACM General Meeting – ' . now()->format('F Y')) }}"
                               placeholder="e.g. ACM General Meeting – March 2026"
                               required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-medium">Date <span class="text-danger">*</span></label>
                            <input type="date" name="meeting_date"
                                   class="form-control @error('meeting_date') is-invalid @enderror"
                                   value="{{ old('meeting_date') }}" required>
                            @error('meeting_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-medium">Time <span class="text-danger">*</span></label>
                            <input type="time" name="meeting_time"
                                   class="form-control @error('meeting_time') is-invalid @enderror"
                                   value="{{ old('meeting_time', '18:00') }}" required>
                            @error('meeting_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Venue</label>
                        <input type="text" name="venue"
                               class="form-control @error('venue') is-invalid @enderror"
                               value="{{ old('venue') }}"
                               placeholder="e.g. Chorlton Irish Club, Manchester">
                        @error('venue')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">Description <span class="text-muted small">(optional)</span></label>
                        <textarea name="description" rows="3"
                                  class="form-control @error('description') is-invalid @enderror"
                                  placeholder="Agenda or notes about this meeting">{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- ── Location & GPS ───────────────────────────── --}}
                    <div class="card border-0 bg-light mb-4">
                        <div class="card-body pb-2">
                            <h6 class="fw-semibold mb-3">
                                <i class="bi bi-geo-alt text-success me-1"></i> Location & GPS Check-In
                            </h6>

                            <div class="mb-3">
                                <label class="form-label fw-medium">Venue Postcode <span class="text-danger">*</span></label>
                                <input type="text" name="venue_postcode"
                                       class="form-control @error('venue_postcode') is-invalid @enderror"
                                       value="{{ old('venue_postcode') }}"
                                       placeholder="e.g. M21 9WQ"
                                       style="text-transform:uppercase">
                                <div class="form-text">Coordinates are looked up automatically from the postcode.</div>
                                @error('venue_postcode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label class="form-label fw-medium">GPS Radius (metres)</label>
                                    <input type="number" name="venue_radius"
                                           class="form-control @error('venue_radius') is-invalid @enderror"
                                           value="{{ old('venue_radius', 150) }}"
                                           min="50" max="1000">
                                    <div class="form-text">Members must be within this distance to check in.</div>
                                    @error('venue_radius')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-medium">If Outside Range</label>
                                    <select name="gps_failure_action"
                                            class="form-select @error('gps_failure_action') is-invalid @enderror">
                                        <option value="reject" {{ old('gps_failure_action', 'reject') === 'reject' ? 'selected' : '' }}>
                                            Block &amp; contact admin
                                        </option>
                                        <option value="flag" {{ old('gps_failure_action') === 'flag' ? 'selected' : '' }}>
                                            Allow but flag for review
                                        </option>
                                    </select>
                                    @error('gps_failure_action')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle me-1"></i>Create Meeting
                        </button>
                        <a href="{{ route('admin.meetings.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
