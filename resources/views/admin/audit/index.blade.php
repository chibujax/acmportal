@extends('layouts.app')

@section('title', 'Audit Trail')
@section('page-title', 'Audit Trail')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-semibold mb-0">Audit Trail</h5>
        <p class="text-muted small mb-0">All admin create, update, and delete activity.</p>
    </div>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('admin.audit.index') }}" class="row g-2 align-items-end">
            <div class="col-sm-3">
                <label class="form-label small fw-medium mb-1">Action</label>
                <select name="action" class="form-select form-select-sm">
                    <option value="">All Actions</option>
                    <option value="login"     {{ request('action') === 'login'     ? 'selected' : '' }}>Login</option>
                    <option value="activated" {{ request('action') === 'activated' ? 'selected' : '' }}>Activated</option>
                    <option value="created"   {{ request('action') === 'created'   ? 'selected' : '' }}>Created</option>
                    <option value="updated"  {{ request('action') === 'updated'  ? 'selected' : '' }}>Updated</option>
                    <option value="deleted"  {{ request('action') === 'deleted'  ? 'selected' : '' }}>Deleted</option>
                </select>
            </div>
            <div class="col-sm-3">
                <label class="form-label small fw-medium mb-1">Resource</label>
                <select name="resource" class="form-select form-select-sm">
                    <option value="">All Resources</option>
                    @foreach($resourceTypes as $type)
                        <option value="{{ $type }}" {{ request('resource') === $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label small fw-medium mb-1">From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-sm-2">
                <label class="form-label small fw-medium mb-1">To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-sm-2 d-flex gap-2">
                <button type="submit" class="btn btn-success btn-sm flex-fill">Filter</button>
                @if(request()->hasAny(['action','resource','date_from','date_to']))
                    <a href="{{ route('admin.audit.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Results --}}
@if($logs->isEmpty())
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-journal-text fs-1 mb-2 d-block"></i>
        No activity logs found.
    </div>
</div>
@else
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th style="width:160px">Date / Time</th>
                    <th>Admin</th>
                    <th style="width:90px">Action</th>
                    <th>Resource</th>
                    <th>Description</th>
                    <th style="width:80px" class="text-center">Changes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                @php
                    $badgeClass = match($log->action) {
                        'login'     => 'bg-info text-dark',
                        'activated' => 'bg-success',
                        'created'   => 'bg-success',
                        'updated'   => 'bg-primary',
                        'deleted'   => 'bg-danger',
                        default     => 'bg-secondary',
                    };
                    $hasChanges = $log->old_values || $log->new_values;
                @endphp
                <tr>
                    <td class="text-muted">{{ $log->created_at->format('d M Y H:i') }}</td>
                    <td>
                        @if($log->user)
                            {{ $log->user->name }}
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ $badgeClass }}">{{ ucfirst($log->action) }}</span>
                    </td>
                    <td>
                        <span class="fw-medium">{{ $log->resource_type }}</span>
                        @if($log->resource_id)
                            <span class="text-muted ms-1">#{{ $log->resource_id }}</span>
                        @endif
                    </td>
                    <td class="text-muted">{{ $log->description }}</td>
                    <td class="text-center">
                        @if($hasChanges)
                            <button class="btn btn-sm btn-outline-secondary py-0 px-2"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#changes-{{ $log->id }}">
                                <i class="bi bi-eye"></i>
                            </button>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                </tr>
                @if($hasChanges)
                <tr class="collapse" id="changes-{{ $log->id }}">
                    <td colspan="6" class="bg-light p-3">
                        <div class="row g-3">
                            @if($log->old_values)
                            <div class="col-md-6">
                                <div class="fw-medium small text-danger mb-1">Before</div>
                                <pre class="bg-white border rounded p-2 small mb-0" style="max-height:200px;overflow:auto">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </div>
                            @endif
                            @if($log->new_values)
                            <div class="col-md-6">
                                <div class="fw-medium small text-success mb-1">After</div>
                                <pre class="bg-white border rounded p-2 small mb-0" style="max-height:200px;overflow:auto">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </div>
                            @endif
                        </div>
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $logs->links() }}
</div>
@endif
@endsection
