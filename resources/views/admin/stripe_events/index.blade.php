@extends('layouts.app')

@section('title', 'Stripe Events')
@section('page-title', 'Stripe Events')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-semibold mb-0">Stripe Events</h5>
        <p class="text-muted small mb-0">Every webhook Stripe has sent us — for investigating declines, disputes and stuck payments. Card/billing details are redacted.</p>
    </div>
    <a href="{{ route('admin.payments.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Payments
    </a>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-3">
                <label class="form-label small fw-medium mb-1">Member</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Name or phone…" value="{{ request('search') }}">
            </div>
            <div class="col-sm-3">
                <label class="form-label small fw-medium mb-1">Event Type</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    @foreach($types as $t)
                        <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>{{ $t }}</option>
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
                @if(request()->hasAny(['search','type','date_from','date_to']))
                    <a href="{{ route('admin.payments.stripe-events.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

@if($events->isEmpty())
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-journal-text fs-1 mb-2 d-block"></i>
        No Stripe events recorded yet.
    </div>
</div>
@else
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th style="width:160px">Received</th>
                    <th>Type</th>
                    <th>Member</th>
                    <th style="width:80px" class="text-center">Payload</th>
                </tr>
            </thead>
            <tbody>
                @foreach($events as $event)
                @php
                    $badgeClass = match(true) {
                        str_contains($event->type, 'succeeded')  => 'bg-success',
                        str_contains($event->type, 'failed')     => 'bg-danger',
                        str_contains($event->type, 'dispute')     => 'bg-warning text-dark',
                        default                                    => 'bg-secondary',
                    };
                @endphp
                <tr>
                    <td class="text-muted">{{ $event->received_at->format('d M Y H:i:s') }}</td>
                    <td><span class="badge {{ $badgeClass }}">{{ $event->type }}</span></td>
                    <td>
                        @if($event->payment?->user)
                            <a href="{{ route('admin.members.show', $event->payment->user) }}" class="text-decoration-none">
                                {{ $event->payment->user->name }}
                            </a>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-secondary py-0 px-2"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#payload-{{ $event->id }}">
                            <i class="bi bi-eye"></i>
                        </button>
                    </td>
                </tr>
                <tr class="collapse" id="payload-{{ $event->id }}">
                    <td colspan="4" class="bg-light p-3">
                        <pre class="bg-white border rounded p-2 small mb-0" style="max-height:200px;overflow-y:auto;white-space:pre-wrap;word-break:break-word;overflow-wrap:anywhere">{{ json_encode($event->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $events->links() }}
</div>
@endif
@endsection
