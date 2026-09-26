@extends('layouts.app')
@section('title', 'Upload Minutes')
@section('page-title', 'Upload Meeting Minutes')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="fw-semibold mb-0">
                    <i class="bi bi-journal-plus text-success me-2"></i>Minutes Details
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.minutes.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-medium">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title"
                               class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title') }}"
                               placeholder="e.g. ACM General Meeting – March 2026"
                               required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Meeting Date <span class="text-danger">*</span></label>
                        <input type="date" name="meeting_date"
                               class="form-control @error('meeting_date') is-invalid @enderror"
                               value="{{ old('meeting_date') }}" required>
                        @error('meeting_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">Minutes File (PDF) <span class="text-danger">*</span></label>
                        <input type="file" name="file" accept="application/pdf"
                               class="form-control @error('file') is-invalid @enderror" required>
                        <div class="form-text">PDF only, up to 10MB.</div>
                        @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="publishNow" name="publish_now" value="1" {{ old('publish_now') ? 'checked' : '' }}>
                        <label class="form-check-label" for="publishNow">
                            Publish immediately (visible to members right away)
                        </label>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle me-1"></i>Save Minutes
                        </button>
                        <a href="{{ route('admin.minutes.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
