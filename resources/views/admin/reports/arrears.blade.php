@extends('layouts.app')
@section('title','Arrears Report')
@section('page-title','Arrears Report')

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-5">
                <label class="form-label fw-medium">Select Dues Cycle</label>
                <select name="cycle_id" class="form-select">
                    <option value="">— Choose cycle —</option>
                    @foreach($cycles as $c)
                    <option value="{{ $c->id }}" {{ $cycleId == $c->id ? 'selected' : '' }}>
                        {{ $c->title }} ({{ ucfirst($c->status) }})
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-success">Generate Report</button>
            </div>
        </form>
    </div>
</div>

@if($cycleId && $arrearsMembers->isNotEmpty())
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-semibold mb-0 text-danger">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Members in Arrears ({{ $arrearsMembers->count() }})
        </h6>
        <span class="badge bg-danger fs-6">
            Total Outstanding: £{{ number_format($arrearsMembers->sum('outstanding'), 2) }}
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Spouse</th>
                    <th>Obligation</th>
                    <th>Paid</th>
                    <th class="text-danger">Outstanding</th>
                </tr>
            </thead>
            <tbody>
                @foreach($arrearsMembers as $m)
                <tr>
                    <td>
                        <a href="{{ route('admin.members.show', $m) }}" class="fw-medium text-decoration-none">
                            {{ $m->name }}
                        </a>
                    </td>
                    <td>{{ $m->phone }}</td>
                    <td>{{ $m->email ?? '—' }}</td>
                    <td class="small text-muted">{{ $m->spouseName ?? '—' }}</td>
                    <td>£{{ number_format($m->obligation, 2) }}</td>
                    <td class="text-success">£{{ number_format($m->paid, 2) }}</td>
                    <td class="fw-bold text-danger">£{{ number_format($m->outstanding, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@elseif($cycleId)
<div class="alert alert-success">
    <i class="bi bi-check-circle me-2"></i>All active members have paid for this cycle.
</div>
@endif
@endsection
