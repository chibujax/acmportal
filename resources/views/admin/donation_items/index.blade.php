@extends('layouts.app')
@section('title', 'Donation Items – ' . $duesCycle->title)
@section('page-title', 'Donation Items: ' . $duesCycle->title)

@section('content')
<div class="d-flex gap-2 mb-3">
    <a href="{{ route('admin.dues-cycles.show', $duesCycle) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Cycle
    </a>
    <a href="{{ route('admin.donation-items.create', $duesCycle) }}" class="btn btn-sm btn-warning text-white">
        <i class="bi bi-plus me-1"></i>Record Item
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Summary --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-4 fw-bold text-warning">{{ $items->count() }}</div>
            <div class="small text-muted">Total Items</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-4 fw-bold text-success">
                £{{ number_format($items->whereNotNull('estimated_value')->sum('estimated_value'), 2) }}
            </div>
            <div class="small text-muted">Est. Total Value</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-4 fw-bold text-primary">{{ $items->where('item_type', 'money')->count() }}</div>
            <div class="small text-muted">Cash Items</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-4 fw-bold text-secondary">{{ $items->where('item_type', 'other')->count() }}</div>
            <div class="small text-muted">Other Items</div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="fw-semibold mb-0"><i class="bi bi-box-seam text-warning me-2"></i>All Donations</h6>
    </div>
    @if($items->isEmpty())
    <div class="card-body text-muted small">No donation items recorded yet.</div>
    @else
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Member</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Quantity</th>
                    <th>Est. Value</th>
                    <th>Date</th>
                    <th>Recorded By</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>
                        <div class="fw-medium small">{{ $item->user->name }}</div>
                        <div class="text-muted" style="font-size:.72rem">{{ $item->user->phone }}</div>
                    </td>
                    <td>
                        <span class="badge {{ $item->item_type === 'money' ? 'bg-success' : 'bg-secondary' }}">
                            {{ ucfirst($item->item_type) }}
                        </span>
                    </td>
                    <td class="small">{{ $item->description }}</td>
                    <td class="small text-muted">{{ $item->quantity ?? '—' }}</td>
                    <td class="small fw-medium">{{ $item->formattedValue() }}</td>
                    <td class="small text-muted">{{ $item->donation_date->format('d M Y') }}</td>
                    <td class="small text-muted">{{ $item->recordedBy->name }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.donation-items.destroy', $item) }}"
                              onsubmit="return confirm('Remove this donation item?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
