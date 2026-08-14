@extends('layouts.app')
@section('title', 'Edit Minutes')
@section('page-title', 'Edit Meeting Minutes')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="fw-semibold mb-0">
                    <i class="bi bi-journal-text text-success me-2"></i>Minutes Details
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.minutes.update', $minutes) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-medium">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title"
                               class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title', $minutes->title) }}" required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Meeting Date <span class="text-danger">*</span></label>
                        <input type="date" name="meeting_date"
                               class="form-control @error('meeting_date') is-invalid @enderror"
                               value="{{ old('meeting_date', $minutes->meeting_date->format('Y-m-d')) }}" required>
                        @error('meeting_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">Minutes File (PDF)</label>
                        <div class="mb-2">
                            <a href="{{ route('admin.minutes.view', $minutes) }}" target="_blank" class="small">
                                <i class="bi bi-file-earmark-pdf me-1"></i>{{ $minutes->file_name }}
                            </a>
                        </div>
                        <input type="file" name="file" accept="application/pdf"
                               class="form-control @error('file') is-invalid @enderror">
                        <div class="form-text">Leave blank to keep the current file. Uploading a new one replaces it.</div>
                        @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle me-1"></i>Save Changes
                        </button>
                        <a href="{{ route('admin.minutes.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
