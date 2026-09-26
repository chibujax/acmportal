@extends('layouts.app')
@section('title', 'My Family & Relationships')
@section('page-title', 'Family & Relationships')

@section('content')


{{-- ── Spouse Section ─────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="fw-semibold mb-0">
            <i class="bi bi-heart text-danger me-2"></i>Spouse
        </h6>
    </div>
    <div class="card-body">
        @if($spouse)
            {{-- Spouse is linked --}}
            <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded">
                <div>
                    <div class="fw-medium">{{ $spouse->name }}</div>
                    <div class="small text-muted">{{ $spouse->phone }}</div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-success"><i class="bi bi-link-45deg me-1"></i>Linked</span>
                    <form method="POST" action="{{ route('member.relationships.spouse.unlink') }}"
                          onsubmit="return confirm('Remove spouse link with {{ $spouse->name }}? This affects dues obligations from the next cycle.')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-x-circle me-1"></i>Unlink
                        </button>
                    </form>
                </div>
            </div>
            <div class="alert alert-info mt-3 mb-0 small">
                <i class="bi bi-info-circle me-1"></i>
                You and {{ $spouse->name }} share the annual dues obligation. One payment of the full amount covers both of you.
            </div>
        @else
            {{-- Link a spouse --}}
            <p class="text-muted small mb-3">
                Link your spouse (must already be a registered member). Once linked, annual dues will be calculated as a shared obligation.
            </p>
            <form method="POST" action="{{ route('member.relationships.spouse.link') }}" id="spouseLinkForm">
                @csrf
                <input type="hidden" name="spouse_id" id="spouseIdInput">
                <div class="mb-3">
                    <label class="form-label fw-medium">Search for your spouse</label>
                    <input type="text" id="spouseSearch" class="form-control"
                           placeholder="Type name or phone number…"
                           autocomplete="off">
                    <div id="spouseSuggestions" class="list-group mt-1 shadow-sm" style="display:none; max-height:200px; overflow-y:auto;"></div>
                    <div id="spouseSelected" class="mt-2 text-success small" style="display:none">
                        <i class="bi bi-check-circle me-1"></i><span id="spouseSelectedName"></span>
                    </div>
                </div>
                <button type="submit" class="btn btn-success btn-sm" id="linkSpouseBtn" disabled>
                    <i class="bi bi-link-45deg me-1"></i>Link Spouse
                </button>
            </form>

            <script>
            (function () {
                const searchInput   = document.getElementById('spouseSearch');
                const suggestions   = document.getElementById('spouseSuggestions');
                const spouseIdInput = document.getElementById('spouseIdInput');
                const selectedDiv   = document.getElementById('spouseSelected');
                const selectedName  = document.getElementById('spouseSelectedName');
                const linkBtn       = document.getElementById('linkSpouseBtn');
                let timer;

                searchInput.addEventListener('input', function () {
                    clearTimeout(timer);
                    const q = this.value.trim();
                    if (q.length < 2) {
                        suggestions.style.display = 'none';
                        return;
                    }
                    timer = setTimeout(() => {
                        fetch(`/member/relationships/spouse/search?q=${encodeURIComponent(q)}`)
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
                                            spouseIdInput.value = m.id;
                                            selectedName.textContent = m.name + ' (' + m.phone + ')';
                                            selectedDiv.style.display = 'block';
                                            searchInput.value = m.name;
                                            suggestions.style.display = 'none';
                                            linkBtn.disabled = false;
                                        });
                                        suggestions.appendChild(a);
                                    });
                                }
                                suggestions.style.display = 'block';
                            });
                    }, 300);
                });

                document.addEventListener('click', function (e) {
                    if (!suggestions.contains(e.target) && e.target !== searchInput) {
                        suggestions.style.display = 'none';
                    }
                });
            })();
            </script>
        @endif
    </div>
</div>

{{-- ── Children Section ───────────────────────────────────── --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-semibold mb-0">
            <i class="bi bi-people text-primary me-2"></i>Children
        </h6>
        <button class="btn btn-sm btn-outline-success" type="button" data-bs-toggle="collapse"
                data-bs-target="#addChildForm">
            <i class="bi bi-plus me-1"></i>Add Child
        </button>
    </div>

    {{-- Add child form (collapsed by default) --}}
    <div class="collapse" id="addChildForm">
        <div class="card-body border-top bg-light">
            <form method="POST" action="{{ route('member.relationships.children.add') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label fw-medium">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name"
                               class="form-control @error('first_name') is-invalid @enderror"
                               value="{{ old('first_name') }}" required>
                        @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-medium">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="last_name"
                               class="form-control @error('last_name') is-invalid @enderror"
                               value="{{ old('last_name') }}" required>
                        @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-medium">Date of Birth <span class="text-muted small">(optional)</span></label>
                        <input type="date" name="date_of_birth"
                               class="form-control @error('date_of_birth') is-invalid @enderror"
                               value="{{ old('date_of_birth') }}">
                        @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-medium">Gender <span class="text-muted small">(optional)</span></label>
                        <select name="gender" class="form-select @error('gender') is-invalid @enderror">
                            <option value="">— Not specified —</option>
                            <option value="male"   {{ old('gender') === 'male'   ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female</option>
                        </select>
                        @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-medium">Your Role</label>
                        <select name="parent_role" class="form-select">
                            <option value="father">Father</option>
                            <option value="mother">Mother</option>
                        </select>
                        <div class="form-text">Your spouse will be auto-linked as the other parent.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-medium">Notes <span class="text-muted small">(optional)</span></label>
                        <textarea name="notes" rows="2" class="form-control"
                                  placeholder="Any additional notes">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="bi bi-check-circle me-1"></i>Save Child
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                            data-bs-toggle="collapse" data-bs-target="#addChildForm">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card-body">
        @if($children->isEmpty())
            <p class="text-muted text-center py-3 mb-0">No children records added yet.</p>
        @else
            <div class="row g-3">
                @foreach($children as $child)
                    <div class="col-12 col-md-6">
                        <div class="border rounded p-3 position-relative">
                            <div class="fw-medium">
                                {{ $child->fullName() }}
                                @if($child->gender)
                                    <span class="badge bg-light text-secondary border ms-1" style="font-size:.7rem">{{ ucfirst($child->gender) }}</span>
                                @endif
                            </div>
                            @if($child->date_of_birth)
                                <div class="small text-muted">
                                    Born {{ $child->date_of_birth->format('d M Y') }}
                                    ({{ $child->age() }} years)
                                </div>
                            @endif
                            <div class="small text-muted mt-1">
                                @if($child->father)
                                    <i class="bi bi-person me-1"></i>Father: {{ $child->father->name }}
                                @endif
                                @if($child->mother)
                                    <span class="ms-2"><i class="bi bi-person me-1"></i>Mother: {{ $child->mother->name }}</span>
                                @endif
                            </div>
                            @if($child->notes)
                                <div class="small text-muted mt-1 fst-italic">{{ $child->notes }}</div>
                            @endif
                            <form method="POST"
                                  action="{{ route('member.relationships.children.remove', $child) }}"
                                  class="position-absolute top-0 end-0 m-2"
                                  onsubmit="return confirm('Remove this child record?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-link text-danger p-0">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
