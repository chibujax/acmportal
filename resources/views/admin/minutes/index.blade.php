@extends('layouts.app')
@section('title', 'Meeting Minutes')
@section('page-title', 'Meeting Minutes')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-2">
        <form method="GET" class="d-flex gap-2">
            <select name="year" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                @foreach($years->merge([now()->year])->unique()->sortDesc() as $y)
                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </form>
        <span class="text-muted small">{{ $minutes->total() }} minute(s)</span>
    </div>
    <a href="{{ route('admin.minutes.create') }}" class="btn btn-success btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Upload Minutes
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Title</th>
                        <th>Meeting Date</th>
                        <th>Status</th>
                        <th>Published</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($minutes as $m)
                    <tr>
                        <td>
                            <div class="fw-medium">{{ $m->title }}</div>
                            <div class="text-muted small">{{ $m->file_name }}</div>
                        </td>
                        <td class="small">{{ $m->meeting_date->format('d M Y') }}</td>
                        <td>
                            <span class="badge {{ $m->status === 'published' ? 'bg-success' : 'bg-secondary' }}">
                                {{ ucfirst($m->status) }}
                            </span>
                        </td>
                        <td class="small text-muted">
                            {{ $m->published_at ? $m->published_at->format('d M Y') : '—' }}
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.minutes.view', $m) }}" target="_blank"
                                   class="btn btn-outline-primary"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('admin.minutes.edit', $m) }}"
                                   class="btn btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                @if($m->status === 'published')
                                <button type="button" class="btn btn-outline-info" title="Copy share link"
                                        onclick="copyShareLink('{{ route('member.minutes.show', $m) }}')">
                                    <i class="bi bi-link-45deg"></i>
                                </button>
                                <form method="POST" action="{{ route('admin.minutes.unpublish', $m) }}" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-outline-warning" title="Unpublish">
                                        <i class="bi bi-eye-slash"></i>
                                    </button>
                                </form>
                                @else
                                <form method="POST" action="{{ route('admin.minutes.publish', $m) }}" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-outline-success" title="Publish">
                                        <i class="bi bi-send-check"></i>
                                    </button>
                                </form>
                                @endif
                                <form method="POST" action="{{ route('admin.minutes.destroy', $m) }}" class="d-inline"
                                      onsubmit="return confirm('Delete these minutes?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="bi bi-journal-x fs-2 d-block mb-2"></i>
                            No minutes for {{ $year }}.
                            <a href="{{ route('admin.minutes.create') }}" class="d-block mt-2">Upload one</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($minutes->hasPages())
    <div class="card-footer bg-white">{{ $minutes->links() }}</div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function copyShareLink(url) {
    const temp = document.createElement('textarea');
    temp.value = url;
    document.body.appendChild(temp);
    temp.select();
    document.execCommand('copy');
    document.body.removeChild(temp);
    alert('Share link copied to clipboard! Members will be asked to log in before viewing.');
}
</script>
@endpush
