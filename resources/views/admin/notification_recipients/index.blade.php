@extends('layouts.app')
@section('title', 'Alert Recipients')
@section('page-title', 'Cron Alert Recipients')

@section('content')
<div class="row g-4">

    {{-- Add new recipient --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3 pb-2">
                <h6 class="fw-semibold mb-0"><i class="bi bi-person-plus me-2 text-primary"></i>Add Recipient</h6>
                <p class="small text-muted mb-0 mt-1">These people will be notified by the monthly cron job when the consecutive absentees report is ready.</p>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.notification-recipients.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" required maxlength="100">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Email</label>
                        <input type="email" name="email" class="form-control form-control-sm @error('email') is-invalid @enderror"
                               value="{{ old('email') }}" maxlength="255">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Phone</label>
                        <input type="text" name="phone" class="form-control form-control-sm @error('phone') is-invalid @enderror"
                               value="{{ old('phone') }}" maxlength="20" placeholder="07xxx or +447xxx">
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <p class="small text-muted">At least one of email or phone is required.</p>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-circle me-1"></i>Add Recipient
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Recipients list --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3 pb-2">
                <h6 class="fw-semibold mb-0"><i class="bi bi-bell me-2 text-primary"></i>Current Recipients</h6>
            </div>

            @if($recipients->isEmpty())
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-bell-slash fs-2 d-block mb-2"></i>
                No recipients configured yet. Add one on the left.
            </div>
            @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th class="text-center">Active</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recipients as $r)
                        <tr class="{{ $r->active ? '' : 'table-secondary text-muted' }}">
                            <td class="fw-medium">{{ $r->name }}</td>
                            <td>{{ $r->email ?? '—' }}</td>
                            <td>{{ $r->phone ?? '—' }}</td>
                            <td class="text-center">
                                <form method="POST" action="{{ route('admin.notification-recipients.toggle', $r) }}" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-link p-0" title="{{ $r->active ? 'Deactivate' : 'Activate' }}">
                                        @if($r->active)
                                            <i class="bi bi-toggle-on text-success fs-5"></i>
                                        @else
                                            <i class="bi bi-toggle-off text-secondary fs-5"></i>
                                        @endif
                                    </button>
                                </form>
                            </td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('admin.notification-recipients.destroy', $r) }}"
                                      onsubmit="return confirm('Remove {{ addslashes($r->name) }} from recipients?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Remove">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white border-0 small text-muted py-2">
                {{ $recipients->count() }} recipient(s) &mdash; {{ $recipients->where('active', true)->count() }} active
            </div>
            @endif
        </div>

        <div class="card border-0 shadow-sm mt-3">
            <div class="card-body py-2 px-3">
                <p class="small text-muted mb-1"><i class="bi bi-info-circle me-1"></i><strong>When does the cron run?</strong></p>
                <p class="small text-muted mb-0">Every <strong>last Saturday of the month</strong>, active recipients receive a notification to review the <a href="{{ route('admin.meetings.consecutive-absentees') }}">Consecutive Absentees</a> report.</p>
            </div>
        </div>
    </div>

</div>
@endsection
