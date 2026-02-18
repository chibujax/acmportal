@extends('layouts.app')
@section('title','Manual Payments')
@section('page-title','Manual Payments')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h6 class="mb-0 text-muted">All cash / bank-transfer payments</h6>
    <a href="{{ route('admin.payments.create') }}" class="btn btn-success btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Record New Payment
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-3">
        <form method="GET" class="row g-2">
            <div class="col-12 col-md-7">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search member name or phone…"
                           value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="completed" {{ request('status')==='completed' ? 'selected' : '' }}>Completed</option>
                    <option value="failed"    {{ request('status')==='failed'    ? 'selected' : '' }}>Failed</option>
                    <option value="refunded"  {{ request('status')==='refunded'  ? 'selected' : '' }}>Refunded</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <button class="btn btn-success w-100">Filter</button>
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Receipt #</th>
                        <th>Member</th>
                        <th>Cycle</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Recorded By</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $p)
                    <tr>
                        <td><code class="small">{{ $p->receipt_number }}</code></td>
                        <td>
                            <div class="fw-medium small">{{ $p->user->name }}</div>
                            <div class="text-muted" style="font-size:.72rem">{{ $p->user->phone }}</div>
                        </td>
                        <td class="small">{{ $p->duesCycle?->title ?? '—' }}</td>
                        <td class="fw-semibold text-success">£{{ number_format($p->amount, 2) }}</td>
                        <td class="small text-muted">{{ $p->payment_date?->format('d M Y') ?? '—' }}</td>
                        <td class="small">{{ $p->recordedBy?->name ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $p->status === 'completed' ? 'bg-success' : ($p->status === 'failed' ? 'bg-danger' : 'bg-warning text-dark') }}">
                                {{ ucfirst($p->status) }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.payments.show', $p) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No manual payments recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($payments->hasPages())
    <div class="card-footer bg-white">{{ $payments->links() }}</div>
    @endif
</div>
@endsection
