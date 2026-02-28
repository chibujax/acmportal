@extends('layouts.app')
@section('title','My Payments')
@section('page-title','My Payment History')

@section('content')
@if($payments->isEmpty())
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>You have no payment records yet.
</div>
@else
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Receipt #</th>
                    <th>Dues Cycle</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $p)
                <tr>
                    <td><code class="small">{{ $p->receipt_number ?? '—' }}</code></td>
                    <td class="small">{{ $p->duesCycle?->title ?? '—' }}</td>
                    <td class="fw-bold text-success">£{{ number_format($p->amount, 2) }}</td>
                    <td class="small text-capitalize">{{ $p->method }}</td>
                    <td class="small text-muted">{{ $p->payment_date?->format('d M Y') ?? $p->created_at->format('d M Y') }}</td>
                    <td>
                        <span class="badge {{ $p->status === 'completed' ? 'bg-success' : ($p->status === 'failed' ? 'bg-danger' : 'bg-warning text-dark') }}">
                            {{ ucfirst($p->status) }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($payments->hasPages())
    <div class="card-footer bg-white">{{ $payments->links() }}</div>
    @endif
</div>
@endif
@endsection
