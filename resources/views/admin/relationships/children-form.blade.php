@extends('layouts.app')
@php
    $editing = isset($child);
    $fatherIdVal   = old('father_id',   $child->father_id   ?? '');
    $motherIdVal   = old('mother_id',   $child->mother_id   ?? '');
    $fatherNameVal = $fatherIdVal ? ($members->firstWhere('id', (int)$fatherIdVal)?->name ?? '') : '';
    $motherNameVal = $motherIdVal ? ($members->firstWhere('id', (int)$motherIdVal)?->name ?? '') : '';
@endphp
@section('title', $editing ? 'Edit Child Record' : 'Add Child Record')
@section('page-title', $editing ? 'Edit Child Record' : 'Add Child Record')

@section('content')

<div class="mb-3">
    <a href="{{ route('admin.children.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Children
    </a>
</div>

<div class="card border-0 shadow-sm" style="max-width:680px">
    <div class="card-body p-4">

        @if($errors->any())
        <div class="alert alert-danger py-2 small">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form id="childForm" method="POST"
              action="{{ $editing ? route('admin.children.update', $child) : route('admin.children.store') }}">
            @csrf
            @if($editing) @method('PUT') @endif

            <div class="row g-3">

                <div class="col-sm-6">
                    <label class="form-label fw-medium">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror"
                           value="{{ old('first_name', $child->first_name ?? '') }}" required>
                    @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-sm-6">
                    <label class="form-label fw-medium">Last Name <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror"
                           value="{{ old('last_name', $child->last_name ?? '') }}" required>
                    @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-sm-6">
                    <label class="form-label fw-medium">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror"
                           value="{{ old('date_of_birth', isset($child->date_of_birth) ? $child->date_of_birth->format('Y-m-d') : '') }}">
                    @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-sm-6">
                    <label class="form-label fw-medium">Gender</label>
                    <select name="gender" class="form-select @error('gender') is-invalid @enderror">
                        <option value="">— Not specified —</option>
                        <option value="male"   {{ old('gender', $child->gender ?? '') === 'male'   ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ old('gender', $child->gender ?? '') === 'female' ? 'selected' : '' }}>Female</option>
                    </select>
                    @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Father search --}}
                <div class="col-sm-6">
                    <label class="form-label fw-medium">
                        Father <span class="text-muted fw-normal small">(at least one parent required)</span>
                    </label>
                    <div class="d-flex gap-1">
                        <div class="position-relative flex-grow-1">
                            <input type="text" id="fatherSearch"
                                   class="form-control @error('father_id') is-invalid @enderror"
                                   placeholder="Search member name…"
                                   autocomplete="off"
                                   value="{{ $fatherNameVal }}">
                            <div id="fatherDropdown"
                                 class="d-none position-absolute w-100 shadow-sm border rounded bg-white"
                                 style="max-height:200px; overflow-y:auto; z-index:1050; top:100%"></div>
                        </div>
                        <button type="button" id="fatherClear"
                                class="btn btn-outline-secondary"
                                style="{{ $fatherIdVal ? '' : 'display:none' }}"
                                onclick="clearParent('father')">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                    <input type="hidden" name="father_id" id="fatherId" value="{{ $fatherIdVal }}">
                    @error('father_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>

                {{-- Mother search --}}
                <div class="col-sm-6">
                    <label class="form-label fw-medium">
                        Mother <span class="text-muted fw-normal small">(at least one parent required)</span>
                    </label>
                    <div class="d-flex gap-1">
                        <div class="position-relative flex-grow-1">
                            <input type="text" id="motherSearch"
                                   class="form-control @error('mother_id') is-invalid @enderror"
                                   placeholder="Search member name…"
                                   autocomplete="off"
                                   value="{{ $motherNameVal }}">
                            <div id="motherDropdown"
                                 class="d-none position-absolute w-100 shadow-sm border rounded bg-white"
                                 style="max-height:200px; overflow-y:auto; z-index:1050; top:100%"></div>
                        </div>
                        <button type="button" id="motherClear"
                                class="btn btn-outline-secondary"
                                style="{{ $motherIdVal ? '' : 'display:none' }}"
                                onclick="clearParent('mother')">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                    <input type="hidden" name="mother_id" id="motherId" value="{{ $motherIdVal }}">
                    @error('mother_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label fw-medium">Notes <span class="text-muted fw-normal">(optional)</span></label>
                    <textarea name="notes" rows="2" maxlength="500"
                              class="form-control @error('notes') is-invalid @enderror"
                              placeholder="Any additional notes…">{{ old('notes', $child->notes ?? '') }}</textarea>
                    @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 d-flex gap-2 pt-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>{{ $editing ? 'Save Changes' : 'Add Child' }}
                    </button>
                    <a href="{{ route('admin.children.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>

            </div>
        </form>
    </div>
</div>

{{-- Couple warning modal --}}
<div class="modal fade" id="coupleWarningModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-warning">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-semibold">
                    <i class="bi bi-exclamation-triangle text-warning me-2"></i>Parents not linked as a couple
                </h6>
            </div>
            <div class="modal-body small">
                <p>The selected father and mother are <strong>not recorded as a couple</strong> in the system.</p>
                <p class="mb-0">Would you like to continue saving, or go back to edit the parents?</p>
            </div>
            <div class="modal-footer border-0 gap-2">
                <a href="{{ route('admin.children.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
                <button type="button" class="btn btn-sm btn-outline-primary" id="editParentsBtn"
                        data-bs-dismiss="modal">Edit parents</button>
                <button type="button" class="btn btn-sm btn-warning" id="continueAnywayBtn">
                    Continue anyway
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const members = {!! json_encode($members->map(fn($m) => ['id' => $m->id, 'name' => $m->name])->values()) !!};
const spousePairs = {!! json_encode($couples->map(fn($c) => ['a' => $c->member_id_1, 'b' => $c->member_id_2])->values()) !!};

function setupParentSearch(prefix) {
    const searchEl   = document.getElementById(prefix + 'Search');
    const hiddenEl   = document.getElementById(prefix + 'Id');
    const dropdownEl = document.getElementById(prefix + 'Dropdown');
    const clearEl    = document.getElementById(prefix + 'Clear');

    searchEl.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        hiddenEl.value = '';
        clearEl.style.display = 'none';

        if (!q) { dropdownEl.classList.add('d-none'); return; }

        const matches = members.filter(m => m.name.toLowerCase().includes(q)).slice(0, 8);

        if (!matches.length) { dropdownEl.classList.add('d-none'); return; }

        dropdownEl.innerHTML = matches.map(m =>
            `<button type="button" class="dropdown-item py-2 px-3"
                     data-id="${m.id}"
                     data-label="${m.name.replace(/"/g, '&quot;')}">
                ${m.name}
            </button>`
        ).join('');
        dropdownEl.classList.remove('d-none');

        dropdownEl.querySelectorAll('.dropdown-item').forEach(btn => {
            btn.addEventListener('click', function () {
                searchEl.value  = this.dataset.label;
                hiddenEl.value  = this.dataset.id;
                clearEl.style.display = '';
                dropdownEl.classList.add('d-none');
            });
        });
    });

    document.addEventListener('click', function (e) {
        if (!searchEl.contains(e.target) && !dropdownEl.contains(e.target)) {
            dropdownEl.classList.add('d-none');
        }
    });
}

function clearParent(prefix) {
    document.getElementById(prefix + 'Search').value = '';
    document.getElementById(prefix + 'Id').value     = '';
    document.getElementById(prefix + 'Clear').style.display = 'none';
}

function areCouple(id1, id2) {
    return spousePairs.some(p =>
        (p.a == id1 && p.b == id2) || (p.a == id2 && p.b == id1)
    );
}

document.addEventListener('DOMContentLoaded', function () {
    setupParentSearch('father');
    setupParentSearch('mother');

    const form    = document.getElementById('childForm');
    const modal   = new bootstrap.Modal(document.getElementById('coupleWarningModal'));
    let bypass    = false;

    form.addEventListener('submit', function (e) {
        if (bypass) return; // already confirmed, let it through

        const fatherId = document.getElementById('fatherId').value;
        const motherId = document.getElementById('motherId').value;

        if (fatherId && motherId && !areCouple(fatherId, motherId)) {
            e.preventDefault();
            modal.show();
        }
    });

    document.getElementById('continueAnywayBtn').addEventListener('click', function () {
        bypass = true;
        modal.hide();
        form.submit();
    });
});
</script>
@endpush

@endsection
