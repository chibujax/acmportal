@extends('layouts.app')
@section('title','My Dashboard')
@section('page-title','My Dashboard')

@section('content')

<!-- Email verification banner -->
@if(auth()->user()->email && !auth()->user()->hasVerifiedEmail())
<div class="alert alert-warning d-flex align-items-center gap-3 mb-4">
    <i class="bi bi-envelope-exclamation fs-4"></i>
    <div class="flex-grow-1">
        <strong>Verify your email address</strong><br>
        <small>A verification link was sent to <strong>{{ auth()->user()->email }}</strong>.
        Please check your inbox.</small>
    </div>
    <form method="POST" action="{{ route('email.resend') }}">
        @csrf
        <button class="btn btn-warning btn-sm">Resend</button>
    </form>
</div>
@endif

@if(!auth()->user()->email)
<div class="alert alert-info d-flex align-items-center gap-3 mb-4">
    <i class="bi bi-envelope fs-4"></i>
    <div class="flex-grow-1">
        <strong>Add your email address</strong> to receive payment receipts and reminders.
    </div>
    <a href="{{ route('member.profile') }}" class="btn btn-info btn-sm text-white">Add Email</a>
</div>
@endif

<!-- Welcome -->
<div class="mb-4">
    <h5 class="fw-bold">Welcome back, {{ auth()->user()->name }} 👋</h5>
    <p class="text-muted mb-0">Member since {{ auth()->user()->created_at->format('F Y') }}</p>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#d1fae5; color:#065f46">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div>
                    <div class="fs-5 fw-bold">£{{ number_format($totalPaid, 2) }}</div>
                    <div class="text-muted small">Total Paid</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#dbeafe; color:#1d4ed8">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div>
                    <div class="fs-5 fw-bold">{{ $activeCycles->count() }}</div>
                    <div class="text-muted small">Active Cycles</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#fee2e2; color:#991b1b">
                    <i class="bi bi-exclamation-circle"></i>
                </div>
                <div>
                    <div class="fs-5 fw-bold">
                        £{{ number_format($activeCycles->sum('user_remaining'), 2) }}
                    </div>
                    <div class="text-muted small">Outstanding</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Active Dues Cycles -->
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="fw-semibold"><i class="bi bi-calendar2-check text-success me-2"></i>Active Dues & Levies</h6>
            </div>
            <div class="card-body">
                @forelse($activeCycles as $cycle)
                <div class="p-3 rounded mb-3" style="border:1px solid #e5e7eb">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="fw-semibold">{{ $cycle->title }}</div>
                            <small class="text-muted">
                                {{ $cycle->start_date->format('d M Y') }} – {{ $cycle->end_date->format('d M Y') }}
                            </small>
                        </div>
                        <span class="badge bg-success">Active</span>
                    </div>

                    <div class="progress mb-2" style="height:8px; border-radius:4px">
                        <div class="progress-bar bg-success" style="width:{{ $cycle->user_percent }}%"></div>
                    </div>

                    @if($cycle->is_pledge_based && $cycle->my_items->isNotEmpty())
                    <ul class="list-unstyled mb-2 small text-muted">
                        @foreach($cycle->my_items as $item)
                        <li class="d-flex justify-content-between align-items-center py-1 border-bottom">
                            <span>
                                <i class="bi bi-box-seam me-1 text-warning"></i>{{ $item->description }}
                                @if($item->quantity) &middot; {{ $item->quantity }} @endif
                            </span>
                            <div class="d-flex gap-1">
                                <span class="badge bg-secondary">
                                    {{ $item->estimated_value ? $item->formattedValue() : ucfirst($item->item_type) }}
                                </span>
                                @if($item->is_fulfilled)
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Received</span>
                                @else
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @endif
                            </div>
                        </li>
                        @endforeach
                    </ul>
                    @endif

                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            @if($cycle->is_pledge_based && $cycle->pledge_amount !== null)
                                Pledge: <strong>£{{ number_format($cycle->pledge_amount, 2) }}</strong>
                                &middot; Paid: <strong>£{{ number_format($cycle->user_paid, 2) }}</strong>
                            @elseif($cycle->is_pledge_based && $cycle->my_items->isEmpty())
                                <span class="text-warning">No pledge recorded yet</span>
                                &middot; Paid: <strong>£{{ number_format($cycle->user_paid, 2) }}</strong>
                            @elseif($cycle->is_pledge_based)
                                Paid: <strong>£{{ number_format($cycle->user_paid, 2) }}</strong>
                            @else
                                Paid: <strong>£{{ number_format($cycle->user_paid, 2) }}</strong>
                                / £{{ number_format($cycle->user_obligation, 2) }}
                            @endif
                        </small>
                        @if($cycle->user_remaining > 0 && $cycle->user_obligation > 0)
                            <div class="text-end">
                                <button class="btn btn-sm btn-outline-primary" disabled>
                                    <i class="bi bi-credit-card me-1"></i>Pay by Card
                                </button>
                                <div class="text-muted mt-1" style="font-size:.7rem">Coming soon</div>
                            </div>
                        @elseif($cycle->user_remaining <= 0 && $cycle->user_obligation > 0)
                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Fully Paid</span>
                        @endif
                    </div>
                </div>
                @empty
                <p class="text-muted small">No active dues cycles at the moment.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Recent Payments -->
    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between">
                <h6 class="fw-semibold mb-0"><i class="bi bi-receipt text-primary me-2"></i>Recent Payments</h6>
                <a href="{{ route('member.payments') }}" class="btn btn-sm btn-outline-secondary">All</a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($recentPayments as $p)
                    <li class="list-group-item px-3 py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small fw-medium">{{ $p->duesCycle?->title ?? 'General' }}</div>
                                <div class="text-muted" style="font-size:.72rem">
                                    {{ ucfirst($p->method) }} &middot; {{ $p->created_at->format('d M Y') }}
                                </div>
                            </div>
                            <span class="badge {{ $p->isCompleted() ? 'bg-success' : 'bg-warning text-dark' }}">
                                £{{ number_format($p->amount,2) }}
                            </span>
                        </div>
                    </li>
                    @empty
                    <li class="list-group-item text-muted small px-3 py-3">No payments recorded yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>


@endsection
