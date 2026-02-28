@extends('layouts.app')
@section('title', 'Dues Cycles')
@section('page-title', 'Dues Cycles')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0">Manage membership dues cycles and levy campaigns.</p>
    <a href="{{ route('admin.dues-cycles.create') }}" class="btn btn-success btn-sm">
        <i class="bi bi-plus-circle me-1"></i> New Dues Cycle
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Period</th>
                    <th>Status</th>
                    <th>Collected</th>
                    <th>Payers</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($cycles as $cycle)
                    <tr>
                        <td class="fw-medium">{{ $cycle->title }}</td>
                        <td>
                            <span class="badge bg-secondary">
                                {{ str_replace('_', ' ', $cycle->type) }}
                            </span>
                        </td>
                        <td>£{{ number_format($cycle->amount, 2) }}</td>
                        <td class="small text-muted">
                            {{ $cycle->start_date->format('d M Y') }} –
                            {{ $cycle->end_date->format('d M Y') }}
                        </td>
                        <td>
                            @if($cycle->status === 'active')
                                <span class="badge bg-success">Active</span>
                            @elseif($cycle->status === 'closed')
                                <span class="badge bg-secondary">Closed</span>
                            @else
                                <span class="badge bg-warning text-dark">Draft</span>
                            @endif
                        </td>
                        <td class="text-success fw-medium">
                            £{{ number_format($cycle->collected ?? 0, 2) }}
                        </td>
                        <td>{{ $cycle->payers ?? 0 }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.dues-cycles.show', $cycle) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye me-1"></i>View
                            </a>
                            <a href="{{ route('admin.dues-cycles.edit', $cycle) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No dues cycles found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
