@extends('layouts.app')
@section('title', 'Admin Dashboard')
@section('page-title', 'Dashboard')

@section('content')

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    @php
        $cards = [
            ['icon'=>'bi-people-fill','label'=>'Total Members','value'=> $stats['total_members'],'color'=>'#1a6b3c','bg'=>'#d1fae5'],
            ['icon'=>'bi-person-check-fill','label'=>'Active Members','value'=> $stats['active_members'],'color'=>'#1d4ed8','bg'=>'#dbeafe'],
            ['icon'=>'bi-person-plus-fill','label'=>'Pending Invites','value'=> $stats['pending_invites'],'color'=>'#d97706','bg'=>'#fef3c7'],
            ['icon'=>'bi-cash-coin','label'=>'This Month (GBP)','value'=>'£'.number_format($stats['total_collected'],2),'color'=>'#7c3aed','bg'=>'#ede9fe'],
        ];
    @endphp
    @foreach($cards as $card)
    <div class="col-6 col-xl-3">
        <div class="card stat-card h-100 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:{{ $card['bg'] }}; color:{{ $card['color'] }}">
                    <i class="bi {{ $card['icon'] }}"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold">{{ $card['value'] }}</div>
                    <div class="text-muted small">{{ $card['label'] }}</div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="row g-4">
    <!-- Active Dues Cycles -->
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 pt-3 pb-0 d-flex align-items-center justify-content-between">
                <h6 class="fw-semibold mb-0"><i class="bi bi-calendar-check text-success me-2"></i>Active Dues Cycles</h6>
            </div>
            <div class="card-body">
                @forelse($stats['active_dues_cycles'] as $cycle)
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="fw-medium">{{ $cycle->title }}</span>
                        <span class="text-muted small">{{ $cycle->paid_count }} / {{ $stats['active_members'] }} paid</span>
                    </div>
                    <div class="progress" style="height:8px; border-radius:4px">
                        @php
                            $pct = $stats['active_members'] > 0
                                ? min(100, round($cycle->paid_count / $stats['active_members'] * 100))
                                : 0;
                        @endphp
                        <div class="progress-bar bg-success" style="width:{{ $pct }}%"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-1">
                        <small class="text-muted">{{ $pct }}% collection rate</small>
                        <small class="text-muted">£{{ number_format($cycle->amount, 2) }} per member</small>
                    </div>
                </div>
                @empty
                <p class="text-muted small">No active dues cycles.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Recent Payments -->
    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 pt-3 pb-0 d-flex align-items-center justify-content-between">
                <h6 class="fw-semibold mb-0"><i class="bi bi-receipt text-primary me-2"></i>Recent Payments</h6>
                <a href="{{ route('admin.reports.financial') }}" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($stats['recent_payments'] as $p)
                    <li class="list-group-item px-3 py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-medium small">{{ $p->user->name }}</div>
                                <div class="text-muted" style="font-size:.75rem">
                                    {{ $p->duesCycle?->title ?? 'General' }}
                                    &middot; {{ $p->created_at->diffForHumans() }}
                                </div>
                            </div>
                            <span class="badge bg-success">£{{ number_format($p->amount, 2) }}</span>
                        </div>
                    </li>
                    @empty
                    <li class="list-group-item text-muted small px-3">No payments yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row g-3 mt-1">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex flex-wrap gap-2">
                <a href="{{ route('admin.members.import') }}" class="btn btn-success">
                    <i class="bi bi-upload me-1"></i> Import Members (CSV)
                </a>
                <a href="{{ route('admin.members.pending') }}" class="btn btn-warning">
                    <i class="bi bi-person-plus me-1"></i> Pending Invites
                </a>
                <a href="{{ route('admin.payments.create') }}" class="btn btn-primary">
                    <i class="bi bi-cash-coin me-1"></i> Record Payment
                </a>
                <a href="{{ route('admin.reports.arrears') }}" class="btn btn-danger">
                    <i class="bi bi-exclamation-triangle me-1"></i> View Arrears
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
