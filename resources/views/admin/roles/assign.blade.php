@extends('layouts.app')

@section('title', 'Assign Roles')
@section('page-title', 'Assign Roles to Admins')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-semibold mb-0">Assign Roles to Admins</h5>
        <p class="text-muted small mb-0">Set which roles each admin user holds.</p>
    </div>
    <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back to Roles
    </a>
</div>

@if($roles->isEmpty())
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-2"></i>
    No roles exist yet. <a href="{{ route('admin.roles.create') }}">Create a role</a> before assigning.
</div>
@elseif($admins->isEmpty())
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    No admin users found. Go to <a href="{{ route('admin.members.index') }}">Members</a>, open a member's profile, and change their <strong>Role</strong> to <em>Admin</em> first.
</div>
@else
<div class="alert alert-secondary d-flex align-items-center gap-2 py-2 mb-3">
    <i class="bi bi-info-circle"></i>
    <span class="small">To promote a member to admin, open their profile in
        <a href="{{ route('admin.members.index') }}">Member Management</a>
        and change their Role to <em>Admin</em>. They will then appear here for role assignment.</span>
</div>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Admin User</th>
                    <th>Current Roles</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($admins as $admin)
                <tr>
                    <td>
                        <div class="fw-medium">{{ $admin->name }}</div>
                        <div class="small text-muted">{{ $admin->email }}</div>
                    </td>
                    <td>
                        @forelse($admin->roles as $r)
                            <span class="badge bg-success me-1">{{ $r->name }}</span>
                        @empty
                            <span class="text-muted small">No roles assigned</span>
                        @endforelse
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.members.show', $admin) }}"
                           class="btn btn-sm btn-outline-secondary me-1" title="View Profile">
                            <i class="bi bi-person"></i>
                        </a>
                        <button class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#assignModal"
                                data-user-id="{{ $admin->id }}"
                                data-user-name="{{ $admin->name }}"
                                data-role-ids="{{ $admin->roles->pluck('id')->join(',') }}">
                            <i class="bi bi-shield-check me-1"></i>Assign Roles
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Assign Modal --}}
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.roles.assign.update') }}">
                @csrf
                <input type="hidden" name="user_id" id="modalUserId">
                <div class="modal-header">
                    <h5 class="modal-title">Roles for <span id="modalUserName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if($roles->isEmpty())
                        <p class="text-muted">No roles available.</p>
                    @else
                    <div class="row g-2">
                        @foreach($roles as $role)
                        <div class="col-12">
                            <div class="form-check border rounded p-3">
                                <input class="form-check-input modal-role-check" type="checkbox"
                                       name="role_ids[]" value="{{ $role->id }}"
                                       id="modal_role_{{ $role->id }}">
                                <label class="form-check-label" for="modal_role_{{ $role->id }}">
                                    <span class="fw-medium">{{ $role->name }}</span>
                                    @if($role->description)
                                        <br><small class="text-muted">{{ $role->description }}</small>
                                    @endif
                                    <br>
                                    @foreach($role->pages ?? [] as $page)
                                        <span class="badge bg-light text-dark border me-1 mt-1" style="font-size:.7rem">
                                            {{ \App\Models\Role::$availablePages[$page] ?? $page }}
                                        </span>
                                    @endforeach
                                </label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Roles</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('assignModal').addEventListener('show.bs.modal', function(event) {
    const btn = event.relatedTarget;
    const userId = btn.getAttribute('data-user-id');
    const userName = btn.getAttribute('data-user-name');
    const assignedIds = btn.getAttribute('data-role-ids').split(',').filter(Boolean);

    document.getElementById('modalUserId').value = userId;
    document.getElementById('modalUserName').textContent = userName;

    document.querySelectorAll('.modal-role-check').forEach(function(checkbox) {
        checkbox.checked = assignedIds.includes(checkbox.value);
    });
});
</script>
@endpush
@endsection
