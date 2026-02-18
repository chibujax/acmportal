@extends('layouts.app')
@section('title','Members')
@section('page-title','Member Management')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-3">
        <form method="GET" class="row g-2">
            <div class="col-12 col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search name, phone or email…"
                           value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="active"    {{ request('status')==='active'    ? 'selected' : '' }}>Active</option>
                    <option value="inactive"  {{ request('status')==='inactive'  ? 'selected' : '' }}>Inactive</option>
                    <option value="suspended" {{ request('status')==='suspended' ? 'selected' : '' }}>Suspended</option>
                </select>
            </div>
            <div class="col-6 col-md-3 d-flex gap-2">
                <button class="btn btn-success flex-grow-1">Filter</button>
                <a href="{{ route('admin.members.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Email Verified</th>
                        <th>Status</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($members as $m)
                    <tr>
                        <td class="text-muted small">{{ $m->id }}</td>
                        <td class="fw-medium">
                            <a href="{{ route('admin.members.show', $m) }}" class="text-decoration-none">
                                {{ $m->name }}
                            </a>
                        </td>
                        <td>{{ $m->phone }}</td>
                        <td>{{ $m->email ?? '—' }}</td>
                        <td>
                            @if(!$m->email)
                                <span class="text-muted small">—</span>
                            @elseif($m->hasVerifiedEmail())
                                <span class="badge bg-success"><i class="bi bi-check-circle"></i> Verified</span>
                            @else
                                <span class="badge bg-warning text-dark"><i class="bi bi-exclamation"></i> Pending</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-{{ $m->status }}">{{ ucfirst($m->status) }}</span>
                        </td>
                        <td class="small">{{ ucfirst(str_replace('_',' ',$m->role)) }}</td>
                        <td>
                            <a href="{{ route('admin.members.show', $m) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No members found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($members->hasPages())
    <div class="card-footer bg-white">{{ $members->links() }}</div>
    @endif
</div>
@endsection
