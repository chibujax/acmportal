@extends('layouts.app')
@section('title','Member Payment Summary')
@section('page-title','Member Payment Summary')

@section('content')

{{-- Gender summary cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-4 fw-bold text-primary">{{ $genderCounts['male'] ?? 0 }}</div>
            <div class="small text-muted">Male Members</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-4 fw-bold text-danger">{{ $genderCounts['female'] ?? 0 }}</div>
            <div class="small text-muted">Female Members</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-4 fw-bold text-primary" style="opacity:.6">{{ $childGenderCounts['male'] ?? 0 }}</div>
            <div class="small text-muted">Male Children</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-4 fw-bold text-danger" style="opacity:.6">{{ $childGenderCounts['female'] ?? 0 }}</div>
            <div class="small text-muted">Female Children</div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-3 pb-0">
        <p class="text-muted small mb-3">All-time payment totals per member across all dues cycles.</p>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Member</th>
                    <th>Phone</th>
                    <th>Total Paid</th>
                    <th>Payments</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($members as $m)
                <tr>
                    <td class="fw-medium">{{ $m->name }}</td>
                    <td class="small text-muted">{{ $m->phone }}</td>
                    <td class="fw-bold text-success">£{{ number_format($m->total_paid ?? 0, 2) }}</td>
                    <td>{{ $m->payments_count }} payment{{ $m->payments_count != 1 ? 's' : '' }}</td>
                    <td>
                        <a href="{{ route('admin.members.show', $m) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No member data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($members->hasPages())
    <div class="card-footer bg-white">{{ $members->links() }}</div>
    @endif
</div>
@endsection
