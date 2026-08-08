@extends('layouts.app')

@section('title', 'Stripe Reconciliation')
@section('page-title', 'Stripe Reconciliation')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-semibold mb-0">Stripe Reconciliation</h5>
        <p class="text-muted small mb-0">Stripe card payments only — for matching against what actually lands in the bank.</p>
    </div>
</div>

{{-- Totals summary --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="fs-4 fw-bold text-success">£{{ number_format($totals['completed'], 2) }}</div>
                <div class="text-muted small">Completed</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="fs-4 fw-bold text-danger">£{{ number_format($totals['refunded'], 2) }}</div>
                <div class="text-muted small">Refunded</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="fs-4 fw-bold text-secondary">£{{ number_format($totals['pending'], 2) }}</div>
                <div class="text-muted small">Pending</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="fs-4 fw-bold text-muted">{{ $totals['failed'] }}</div>
                <div class="text-muted small">Failed Attempts</div>
            </div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-3">
                <label class="form-label small fw-medium mb-1">Member</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Name or phone…" value="{{ request('search') }}">
            </div>
            <div class="col-sm-2">
                <label class="form-label small fw-medium mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="completed" {{ request('status')==='completed' ? 'selected' : '' }}>Completed</option>
                    <option value="pending"   {{ request('status')==='pending'   ? 'selected' : '' }}>Pending</option>
                    <option value="failed"    {{ request('status')==='failed'    ? 'selected' : '' }}>Failed</option>
                    <option value="refunded"  {{ request('status')==='refunded'  ? 'selected' : '' }}>Refunded</option>
                </select>
            </div>
            <div class="col-sm-3">
                <label class="form-label small fw-medium mb-1">From</label>
                <input type="datetime-local" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-sm-3">
                <label class="form-label small fw-medium mb-1">To</label>
                <input type="datetime-local" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-sm-1 d-flex gap-2">
                <button type="submit" class="btn btn-success btn-sm flex-fill">Filter</button>
            </div>
            <div class="col-12 d-flex gap-2 justify-content-end">
                @if(request()->hasAny(['search','status','date_from','date_to']))
                    <a href="{{ route('admin.stripe-reconciliation.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                @endif
                <a href="{{ route('admin.stripe-reconciliation.export') }}?{{ http_build_query(request()->query()) }}" class="btn btn-outline-success btn-sm">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export
                </a>
            </div>
        </form>
    </div>
</div>

@if($payments->isEmpty())
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-credit-card fs-1 mb-2 d-block"></i>
        No Stripe payments match this filter.
    </div>
</div>
@else
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Member</th>
                    <th>Cycle</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Receipt #</th>
                    <th>Stripe Reference</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $p)
                <tr>
                    <td class="text-muted">{{ ($p->payment_date ?? $p->created_at)->format('d M Y H:i') }}</td>
                    <td>
                        @if($p->user)
                            <a href="{{ route('admin.members.show', $p->user) }}" class="text-decoration-none">{{ $p->user->name }}</a>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="small">{{ $p->duesCycle?->title ?? '—' }}</td>
                    <td class="fw-semibold">£{{ number_format($p->amount, 2) }}</td>
                    <td>
                        <span class="badge {{ $p->status === 'completed' ? 'bg-success' : ($p->status === 'failed' ? 'bg-danger' : ($p->status === 'pending' ? 'bg-secondary' : 'bg-warning text-dark')) }}">
                            {{ ucfirst($p->status) }}
                        </span>
                    </td>
                    <td><code class="small">{{ $p->receipt_number ?? '—' }}</code></td>
                    <td><code class="small text-muted">{{ $p->gateway_reference ?? '—' }}</code></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $payments->links() }}
</div>
@endif
@endsection
