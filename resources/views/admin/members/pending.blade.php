@extends('layouts.app')
@section('title','Pending Members')
@section('page-title','Pending Members')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="fw-semibold mb-0">
            <i class="bi bi-person-plus text-warning me-2"></i>Pending Registration
        </h6>
        <form method="POST" action="{{ route('admin.members.invites') }}">
            @csrf
            <button class="btn btn-primary btn-sm">
                <i class="bi bi-send me-1"></i>Send All Pending Invites
            </button>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Invited</th>
                        <th>Registration Link</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($members as $m)
                    @php
                        $tok = $m->registrationToken;
                        $url = $tok ? route('register.form', $tok->token) : null;
                    @endphp
                    <tr>
                        <td class="fw-medium">{{ $m->name }}</td>
                        <td>{{ $m->phone }}</td>
                        <td>{{ $m->email ?? '—' }}</td>
                        <td>
                            @if($m->status === 'invited')
                                <span class="badge bg-warning text-dark">Invited</span>
                            @elseif($m->status === 'failed')
                                <span class="badge bg-danger">Failed</span>
                            @else
                                <span class="badge bg-secondary">Pending</span>
                            @endif
                        </td>
                        <td class="small text-muted">{{ $m->invited_at?->diffForHumans() ?? '—' }}</td>
                        <td>
                            @if($url)
                            <div class="input-group input-group-sm" style="max-width:220px">
                                <input type="text" class="form-control form-control-sm"
                                       value="{{ $url }}" readonly id="link-{{ $m->id }}">
                                <button class="btn btn-outline-secondary btn-sm"
                                        onclick="copyLink('link-{{ $m->id }}')" title="Copy">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                            @else
                            <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.members.invites') }}">
                                @csrf
                                <input type="hidden" name="member_ids[]" value="{{ $m->id }}">
                                <button class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-send"></i>
                                    {{ $m->status === 'invited' ? 'Resend' : 'Send' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            No pending members. <a href="{{ route('admin.members.import') }}">Import some?</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($members->hasPages())
    <div class="card-footer bg-white border-0">
        {{ $members->links() }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function copyLink(id) {
    const el = document.getElementById(id);
    el.select();
    document.execCommand('copy');
    alert('Link copied to clipboard!');
}
</script>
@endpush
