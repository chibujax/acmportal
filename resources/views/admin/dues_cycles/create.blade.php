@extends('layouts.app')
@section('title', 'New Dues Cycle')
@section('page-title', 'Create Dues Cycle')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="fw-semibold mb-0">
                    <i class="bi bi-plus-circle text-success me-2"></i>New Dues Cycle
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.dues-cycles.store') }}">
                    @csrf
                    @include('admin.dues_cycles._form')
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle me-1"></i>Create Cycle
                        </button>
                        <a href="{{ route('admin.dues-cycles.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
