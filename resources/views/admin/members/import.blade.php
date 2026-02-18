@extends('layouts.app')
@section('title','CSV Import')
@section('page-title','Import Members via CSV')

@section('content')
<div class="row g-4">
    <!-- Upload Form -->
    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="fw-semibold"><i class="bi bi-upload text-success me-2"></i>Upload CSV File</h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info small mb-3">
                    <strong>CSV Format:</strong><br>
                    Required columns: <code>name</code>, <code>phone</code><br>
                    Optional: <code>email</code><br>
                    First row must be the header row.
                </div>

                <a href="data:text/csv;charset=utf-8,name,phone,email%0AJohn Doe,07700900123,john@example.com"
                   download="acm_import_template.csv"
                   class="btn btn-outline-secondary btn-sm mb-3">
                    <i class="bi bi-download me-1"></i> Download Template
                </a>

                <form method="POST" action="{{ route('admin.members.import.post') }}"
                      enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-medium">Select CSV File</label>
                        <input type="file" name="csv_file" accept=".csv,.txt"
                               class="form-control @error('csv_file') is-invalid @enderror" required>
                        @error('csv_file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-cloud-upload me-2"></i>Import Members
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Import Batches -->
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-semibold mb-0"><i class="bi bi-table text-primary me-2"></i>Import Batches</h6>
                <a href="{{ route('admin.members.pending') }}" class="btn btn-sm btn-outline-primary">
                    View Pending
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Batch ID</th>
                                <th>Total</th>
                                <th>Registered</th>
                                <th>Invited</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($batches as $batch)
                            <tr>
                                <td><code class="small">{{ $batch->import_batch }}</code></td>
                                <td>{{ $batch->total }}</td>
                                <td><span class="badge bg-success">{{ $batch->registered }}</span></td>
                                <td><span class="badge bg-warning text-dark">{{ $batch->invited }}</span></td>
                                <td>
                                    <form method="POST" action="{{ route('admin.members.invites') }}">
                                        @csrf
                                        <input type="hidden" name="batch" value="{{ $batch->import_batch }}">
                                        <button class="btn btn-sm btn-primary">
                                            <i class="bi bi-send me-1"></i>Send Invites
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-muted small text-center py-4">No imports yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if(session('import_errors'))
        <div class="card border-warning mt-3">
            <div class="card-header bg-warning text-dark">
                <i class="bi bi-exclamation-triangle me-1"></i> Import Warnings
            </div>
            <ul class="list-group list-group-flush">
                @foreach(session('import_errors') as $err)
                <li class="list-group-item small">{{ $err }}</li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
</div>
@endsection
