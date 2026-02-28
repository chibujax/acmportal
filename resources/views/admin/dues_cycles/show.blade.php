@extends('layouts.app')
@section('title', $duesCycle->title)
@section('page-title', $duesCycle->title)

@section('content')
<div class="d-flex gap-2 mb-3 flex-wrap">
    <a href="{{ route('admin.dues-cycles.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
    <a href="{{ route('admin.dues-cycles.edit', $duesCycle) }}" class="btn btn-sm btn-outline-primary">
        <i class="bi bi-pencil me-1"></i>Edit
    </a>
    <a href="{{ route('admin.dues-cycles.export', $duesCycle) }}" class="btn btn-sm btn-outline-success">
        <i class="bi bi-download me-1"></i>Export CSV
    </a>
    @if($duesCycle->is_pledge_based)
    <a href="{{ route('admin.pledges.index', $duesCycle) }}" class="btn btn-sm btn-outline-info">
        <i class="bi bi-hand-thumbs-up me-1"></i>Manage Pledges
    </a>
    @endif
    @if($duesCycle->accepts_items && !$duesCycle->is_pledge_based)
    <a href="{{ route('admin.donation-items.index', $duesCycle) }}" class="btn btn-sm btn-outline-warning">
        <i class="bi bi-box-seam me-1"></i>Donation Items
    </a>
    @endif
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-4 fw-bold text-success">£{{ number_format($totalCollected, 2) }}</div>
            <div class="small text-muted">Collected</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-4 fw-bold text-primary">£{{ number_format($totalObligation, 2) }}</div>
            <div class="small text-muted">Total Obligation</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-4 fw-bold text-success">{{ $members->where('settled', true)->count() }}</div>
            <div class="small text-muted">Settled</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-4 fw-bold text-danger">{{ $members->where('settled', false)->count() }}</div>
            <div class="small text-muted">Outstanding</div>
        </div>
    </div>
</div>

{{-- Cycle Details --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-2 small">
            <div class="col-6 col-md-3"><span class="text-muted">Type:</span> {{ str_replace('_', ' ', $duesCycle->type) }}</div>
            <div class="col-6 col-md-3"><span class="text-muted">Period:</span> {{ $duesCycle->start_date->format('d M Y') }} – {{ $duesCycle->end_date->format('d M Y') }}</div>
            <div class="col-6 col-md-3"><span class="text-muted">Payment:</span> {{ $duesCycle->payment_options }}</div>
            <div class="col-6 col-md-3">
                <span class="text-muted">Status:</span>
                @if($duesCycle->status === 'active')
                    <span class="badge bg-success">Active</span>
                @elseif($duesCycle->status === 'closed')
                    <span class="badge bg-secondary">Closed</span>
                @else
                    <span class="badge bg-warning text-dark">Draft</span>
                @endif
            </div>
        </div>
        @if($duesCycle->description)
            <p class="text-muted small mt-2 mb-0">{{ $duesCycle->description }}</p>
        @endif
        <div class="mt-2 small d-flex flex-wrap gap-2">
            @if($duesCycle->couple_shared)
            <span class="badge bg-info text-dark"><i class="bi bi-people me-1"></i>Couple shared</span>
            @endif
            @if($duesCycle->is_pledge_based)
            <span class="badge bg-warning text-dark"><i class="bi bi-hand-thumbs-up me-1"></i>Pledge-based</span>
            @endif
            @if($duesCycle->accepts_items)
            <span class="badge bg-secondary"><i class="bi bi-box-seam me-1"></i>Accepts item donations</span>
            @endif
        </div>
    </div>
</div>

{{-- Per-member payment matrix --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-3 pb-0">
        <h6 class="fw-semibold mb-0"><i class="bi bi-people me-2 text-primary"></i>Member Payment Status</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Member</th>
                    <th>Spouse</th>
                    <th>Obligation</th>
                    <th>Paid</th>
                    <th>Remaining</th>
                    <th>Progress</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($members as $member)
                    <tr>
                        <td>
                            <a href="{{ route('admin.members.show', $member) }}" class="text-decoration-none fw-medium">
                                {{ $member->name }}
                            </a>
                            <div class="small text-muted">{{ $member->phone }}</div>
                        </td>
                        <td class="small text-muted">{{ $member->spouseName ?? '—' }}</td>
                        <td>£{{ number_format($member->obligation, 2) }}</td>
                        <td>£{{ number_format($member->paid, 2) }}</td>
                        <td>
                            @if($member->remaining > 0)
                                <span class="text-danger">£{{ number_format($member->remaining, 2) }}</span>
                            @else
                                <span class="text-success">—</span>
                            @endif
                        </td>
                        <td style="width:120px">
                            <div class="progress" style="height:6px">
                                <div class="progress-bar {{ $member->settled ? 'bg-success' : 'bg-warning' }}"
                                     style="width:{{ $member->percent }}%"></div>
                            </div>
                            <div class="small text-muted mt-1">{{ $member->percent }}%</div>
                        </td>
                        <td>
                            @if($member->settled)
                                <span class="badge bg-success">Settled</span>
                            @else
                                <span class="badge bg-danger">Outstanding</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@if($duesCycle->accepts_items && $donationItems->isNotEmpty())
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
        <h6 class="fw-semibold mb-0"><i class="bi bi-box-seam text-warning me-2"></i>Donation Items</h6>
        <a href="{{ route('admin.donation-items.create', $duesCycle) }}" class="btn btn-sm btn-outline-warning">
            <i class="bi bi-plus me-1"></i>Add Item
        </a>
    </div>
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
                </tr>
            </thead>
            <tbody>
                @foreach($donationItems as $item)
                <tr>
                    <td class="fw-medium small">{{ $item->user->name }}</td>
                    <td>
                        <span class="badge {{ $item->item_type === 'money' ? 'bg-success' : 'bg-secondary' }}">
                            {{ ucfirst($item->item_type) }}
                        </span>
                    </td>
                    <td class="small">{{ $item->description }}</td>
                    <td class="small text-muted">{{ $item->quantity ?? '—' }}</td>
                    <td class="small">{{ $item->formattedValue() }}</td>
                    <td class="small text-muted">{{ $item->donation_date->format('d M Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@elseif($duesCycle->accepts_items)
<div class="card border-0 shadow-sm mt-4">
    <div class="card-body d-flex justify-content-between align-items-center">
        <span class="text-muted small">No donation items recorded yet.</span>
        <a href="{{ route('admin.donation-items.create', $duesCycle) }}" class="btn btn-sm btn-outline-warning">
            <i class="bi bi-plus me-1"></i>Record Item
        </a>
    </div>
</div>
@endif

@endsection
