@extends('layouts.app')
@section('title','Payments')
@section('page-title','Payments')

@php
    $currentSort = request('sort', 'payment_date');
    $currentDir  = request('direction', 'desc');
    $sortLink = function (string $col) use ($currentSort, $currentDir): string {
        $dir = ($currentSort === $col && $currentDir === 'desc') ? 'asc' : 'desc';
        $icon = $currentSort === $col
            ? ($currentDir === 'asc' ? ' <i class="bi bi-caret-up-fill"></i>' : ' <i class="bi bi-caret-down-fill"></i>')
            : ' <i class="bi bi-caret-down text-muted opacity-50"></i>';
        $url = request()->fullUrlWithQuery(['sort' => $col, 'direction' => $dir, 'page' => 1]);
        return "<a href=\"{$url}\" class=\"text-dark text-decoration-none\">{$icon}</a>";
    };
@endphp
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h6 class="mb-0 text-muted">All payments — cash, bank transfer and online (Stripe)</h6>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.payments.stripe-events.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-journal-text me-1"></i>Stripe Events
        </a>
        <a href="{{ route('admin.payments.create') }}" class="btn btn-success btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Record New Payment
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-3">
        <form method="GET" class="row g-2">
            <div class="col-12 col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search member name or phone…"
                           value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <select name="cycle_id" class="form-select">
                    <option value="">All Cycles</option>
                    @foreach($cycles as $c)
                        <option value="{{ $c->id }}" {{ request('cycle_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->title }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="method" class="form-select">
                    <option value="">All Methods</option>
                    <option value="stripe"         {{ request('method')==='stripe'         ? 'selected' : '' }}>Stripe</option>
                    <option value="manual"         {{ request('method')==='manual'         ? 'selected' : '' }}>Manual</option>
                    <option value="bank_transfer"  {{ request('method')==='bank_transfer'   ? 'selected' : '' }}>Bank Transfer</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="completed" {{ request('status')==='completed' ? 'selected' : '' }}>Completed</option>
                    <option value="pending"   {{ request('status')==='pending'   ? 'selected' : '' }}>Pending</option>
                    <option value="failed"    {{ request('status')==='failed'    ? 'selected' : '' }}>Failed</option>
                    <option value="refunded"  {{ request('status')==='refunded'  ? 'selected' : '' }}>Refunded</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="per_page" class="form-select">
                    @foreach([10, 20, 50, 100] as $n)
                        <option value="{{ $n }}" {{ request('per_page', 20) == $n ? 'selected' : '' }}>{{ $n }} / page</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-1">
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
                        <th>Member {!! $sortLink('member') !!}</th>
                        <th>Cycle</th>
                        <th>Method</th>
                        <th>Amount {!! $sortLink('amount') !!}</th>
                        <th>Date {!! $sortLink('payment_date') !!}</th>
                        <th>Recorded By</th>
                        <th>Status {!! $sortLink('status') !!}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $p)
                    <tr>
                        <td><code class="small">{{ $p->receipt_number ?? '—' }}</code></td>
                        <td>
                            <div class="fw-medium small">{{ $p->user->name }}</div>
                            <div class="text-muted" style="font-size:.72rem">{{ $p->user->phone }}</div>
                        </td>
                        <td class="small">{{ $p->duesCycle?->title ?? '—' }}</td>
                        <td class="small"><span class="badge bg-light text-dark border">{{ ucfirst(str_replace('_', ' ', $p->method)) }}</span></td>
                        <td class="fw-semibold text-success">£{{ number_format($p->amount, 2) }}</td>
                        <td class="small text-muted">{{ $p->payment_date?->format('d M Y') ?? '—' }}</td>
                        <td class="small">{{ $p->recordedBy?->name ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $p->status === 'completed' ? 'bg-success' : ($p->status === 'failed' ? 'bg-danger' : ($p->status === 'pending' ? 'bg-secondary' : 'bg-warning text-dark')) }}">
                                {{ ucfirst($p->status) }}
                            </span>
                            @if($p->status === 'failed' && $p->gateway_response)
                                <div class="text-muted" style="font-size:.68rem">{{ $p->gateway_response }}</div>
                            @endif
                            @if(($recentFailedCounts[$p->user_id] ?? 0) >= 3)
                                <span class="badge bg-danger mt-1" title="Repeated failed card attempts in the last hour — possible card testing">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ $recentFailedCounts[$p->user_id] }} fails/hr
                                </span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.payments.show', $p) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No payments recorded.</td></tr>
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
