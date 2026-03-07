@extends('layouts.app')

@section('title', 'Create Role')
@section('page-title', 'Create Role')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom pt-3 pb-2">
                <h6 class="fw-semibold mb-0">New Admin Role</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.roles.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-medium">Role Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}"
                               class="form-control @error('name') is-invalid @enderror"
                               placeholder="e.g. Financial Secretary" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Description</label>
                        <input type="text" name="description" value="{{ old('description') }}"
                               class="form-control @error('description') is-invalid @enderror"
                               placeholder="Brief description of this role">
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">Page Access</label>
                        <p class="text-muted small mb-2">Select which admin sections this role can access.</p>
                        @error('pages')<div class="alert alert-danger py-2 small mb-2">{{ $message }}</div>@enderror
                        <div class="row g-2">
                            @foreach($availablePages as $slug => $label)
                            <div class="col-sm-6">
                                <div class="form-check border rounded p-3">
                                    <input class="form-check-input" type="checkbox"
                                           name="pages[]" value="{{ $slug }}"
                                           id="page_{{ $slug }}"
                                           {{ in_array($slug, old('pages', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-medium" for="page_{{ $slug }}">
                                        {{ $label }}
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg me-1"></i>Create Role
                        </button>
                        <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
