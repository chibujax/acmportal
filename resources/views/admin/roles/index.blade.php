@extends('layouts.app')

@section('title', 'Role Management')
@section('page-title', 'Role Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-semibold mb-0">Admin Roles</h5>
        <p class="text-muted small mb-0">Create roles and define which sections each role can access.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.roles.assign') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-person-badge me-1"></i>Assign Roles to Admins
        </a>
        <a href="{{ route('admin.roles.create') }}" class="btn btn-success btn-sm">
            <i class="bi bi-plus-lg me-1"></i>New Role
        </a>
    </div>
</div>

@if($roles->isEmpty())
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-shield fs-1 mb-2 d-block"></i>
        No roles created yet. Create a role to define what admins can access.
    </div>
</div>
@else
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Role Name</th>
                    <th>Description</th>
                    <th>Pages Access</th>
                    <th>Admins</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($roles as $role)
                <tr>
                    <td class="fw-medium">{{ $role->name }}</td>
                    <td class="text-muted small">{{ $role->description ?: '—' }}</td>
                    <td>
                        @forelse($role->pages ?? [] as $page)
                            <span class="badge bg-light text-dark border me-1">
                                {{ \App\Models\Role::$availablePages[$page] ?? $page }}
                            </span>
                        @empty
                            <span class="text-muted small">No pages assigned</span>
                        @endforelse
                    </td>
                    <td>
                        <span class="badge bg-secondary">{{ $role->users_count }}</span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-outline-secondary me-1">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="d-inline"
                              onsubmit="return confirm('Delete role \'{{ $role->name }}\'? This cannot be undone.')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
