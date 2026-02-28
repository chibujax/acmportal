@extends('layouts.app')
@section('title', "Members' Children")
@section('page-title', "Members' Children")

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body pb-2">
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="search" class="form-control form-control-sm" style="max-width:280px"
                   placeholder="Search by child name or parent…"
                   value="{{ request('search') }}">
            <button type="submit" class="btn btn-sm btn-outline-secondary">Search</button>
            @if(request('search'))
                <a href="{{ route('admin.children.index') }}" class="btn btn-sm btn-link text-muted">Clear</a>
            @endif
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Child</th>
                    <th>Gender</th>
                    <th>Date of Birth</th>
                    <th>Age</th>
                    <th>Father</th>
                    <th>Mother</th>
                    <th>Added By</th>
                    <th>Notes</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($children as $child)
                    <tr>
                        <td class="fw-medium">{{ $child->fullName() }}</td>
                        <td class="small">
                            @if($child->gender === 'male')
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Male</span>
                            @elseif($child->gender === 'female')
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Female</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="small">{{ $child->date_of_birth ? $child->date_of_birth->format('d M Y') : '—' }}</td>
                        <td class="small text-muted">{{ $child->age() !== null ? $child->age() . ' yrs' : '—' }}</td>
                        <td class="small">{{ $child->father?->name ?? '—' }}</td>
                        <td class="small">{{ $child->mother?->name ?? '—' }}</td>
                        <td class="small text-muted">{{ $child->addedBy?->name ?? '—' }}</td>
                        <td class="small text-muted">{{ $child->notes ?? '—' }}</td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('admin.children.destroy', $child) }}"
                                  onsubmit="return confirm('Delete this child record?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">No children records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($children->hasPages())
        <div class="card-footer bg-white border-0">
            {{ $children->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection
