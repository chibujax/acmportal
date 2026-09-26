@extends('layouts.app')
@section('title', 'Spouse Linking')
@section('page-title', 'Spouse Linking')

@section('content')

{{-- ── Link a couple ──────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="fw-semibold mb-0"><i class="bi bi-link-45deg text-success me-2"></i>Link Spouses</h6>
        <small class="text-muted">To correct a wrong pairing, unlink it below first, then link the correct pair here.</small>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.spouses.link') }}" id="linkForm">
            @csrf
            <div class="row g-3">
                <div class="col-12 col-md-5">
                    <label class="form-label fw-medium">Member</label>
                    <input type="hidden" name="member_id" id="memberIdInput">
                    <input type="text" id="memberSearch" class="form-control @error('member_id') is-invalid @enderror"
                           placeholder="Type name or phone…" autocomplete="off">
                    <div id="memberSuggestions" class="list-group mt-1 shadow-sm" style="display:none; max-height:200px; overflow-y:auto; position:absolute; z-index:999;"></div>
                    @error('member_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-1 text-center d-none d-md-flex align-items-center justify-content-center">
                    <i class="bi bi-heart-fill text-danger"></i>
                </div>
                <div class="col-12 col-md-5">
                    <label class="form-label fw-medium">Spouse</label>
                    <input type="hidden" name="spouse_id" id="spouseIdInput">
                    <input type="text" id="spouseSearch" class="form-control @error('spouse_id') is-invalid @enderror"
                           placeholder="Type name or phone…" autocomplete="off">
                    <div id="spouseSuggestions" class="list-group mt-1 shadow-sm" style="display:none; max-height:200px; overflow-y:auto; position:absolute; z-index:999;"></div>
                    @error('spouse_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-success w-100" id="linkBtn" disabled>Link</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ── Existing pairs ─────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body pb-2">
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="search" class="form-control form-control-sm" style="max-width:280px"
                   placeholder="Search linked members…" value="{{ request('search') }}">
            <button type="submit" class="btn btn-sm btn-outline-secondary">Search</button>
            @if(request('search'))
                <a href="{{ route('admin.spouses.index') }}" class="btn btn-sm btn-link text-muted">Clear</a>
            @endif
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Member</th>
                    <th>Spouse</th>
                    <th>Linked</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($relationships as $rel)
                    <tr>
                        <td class="fw-medium">{{ $rel->member1?->name ?? '—' }}</td>
                        <td class="fw-medium">{{ $rel->member2?->name ?? '—' }}</td>
                        <td class="small text-muted">{{ $rel->created_at?->format('d M Y') ?? '—' }}</td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('admin.spouses.unlink', $rel) }}"
                                  onsubmit="return confirm('Unlink {{ $rel->member1?->name }} and {{ $rel->member2?->name }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-x-circle me-1"></i>Unlink
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">No spouse links found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($relationships->hasPages())
        <div class="card-footer bg-white border-0">
            {{ $relationships->withQueryString()->links() }}
        </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
(function () {
    const searchUrl = '{{ route('admin.spouses.search') }}';

    function wireSearch(inputId, suggestionsId, hiddenId) {
        const input       = document.getElementById(inputId);
        const suggestions = document.getElementById(suggestionsId);
        const hidden      = document.getElementById(hiddenId);
        let timer;

        input.addEventListener('input', function () {
            clearTimeout(timer);
            hidden.value = '';
            updateLinkButton();
            const q = this.value.trim();
            if (q.length < 2) {
                suggestions.style.display = 'none';
                return;
            }
            timer = setTimeout(() => {
                fetch(`${searchUrl}?q=${encodeURIComponent(q)}`)
                    .then(r => r.json())
                    .then(data => {
                        suggestions.innerHTML = '';
                        if (data.length === 0) {
                            suggestions.innerHTML = '<div class="list-group-item text-muted small">No members found.</div>';
                        } else {
                            data.forEach(m => {
                                const a = document.createElement('a');
                                a.href = '#';
                                a.className = 'list-group-item list-group-item-action small';
                                a.innerHTML = `<strong>${m.name}</strong> <span class="text-muted">${m.phone}</span>`;
                                a.addEventListener('click', function (e) {
                                    e.preventDefault();
                                    hidden.value = m.id;
                                    input.value  = m.name;
                                    suggestions.style.display = 'none';
                                    updateLinkButton();
                                });
                                suggestions.appendChild(a);
                            });
                        }
                        suggestions.style.display = 'block';
                    });
            }, 300);
        });

        document.addEventListener('click', function (e) {
            if (!suggestions.contains(e.target) && e.target !== input) {
                suggestions.style.display = 'none';
            }
        });
    }

    function updateLinkButton() {
        const memberId = document.getElementById('memberIdInput').value;
        const spouseId = document.getElementById('spouseIdInput').value;
        document.getElementById('linkBtn').disabled = !(memberId && spouseId && memberId !== spouseId);
    }

    wireSearch('memberSearch', 'memberSuggestions', 'memberIdInput');
    wireSearch('spouseSearch', 'spouseSuggestions', 'spouseIdInput');
})();
</script>
@endpush
