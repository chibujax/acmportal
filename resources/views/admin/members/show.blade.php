@extends('layouts.app')
@section('title', $member->name)
@section('page-title', 'Member Profile')

@section('content')
<div class="row g-4">

    {{-- Left: profile card --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center p-4">
            <div class="mb-3">
                <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center"
                     style="width:72px; height:72px; font-size:1.8rem; font-weight:700">
                    {{ strtoupper(substr($member->name, 0, 1)) }}
                </div>
            </div>
            <h5 class="fw-bold mb-1">{{ $member->name }}</h5>
            <span class="badge bg-secondary mb-2">{{ ucfirst(str_replace('_', ' ', $member->role)) }}</span>

            @php
                $statusColors = ['active' => 'success', 'inactive' => 'secondary', 'suspended' => 'danger'];
            @endphp
            <span class="badge bg-{{ $statusColors[$member->status] ?? 'secondary' }} mb-3">
                {{ ucfirst($member->status) }}
            </span>

            <div class="small text-muted text-start">
                <div class="mb-1"><i class="bi bi-phone me-2"></i>{{ $member->phone }}</div>
                @if($member->email)
                <div class="mb-1">
                    <i class="bi bi-envelope me-2"></i>{{ $member->email }}
                    @if($member->hasVerifiedEmail())
                        <i class="bi bi-check-circle-fill text-success ms-1" title="Verified"></i>
                    @else
                        <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Unverified"></i>
                    @endif
                </div>
                @endif
                @if($member->gender)
                <div class="mb-1"><i class="bi bi-person-fill me-2"></i>{{ ucfirst($member->gender) }}</div>
                @endif
                @if($member->address)
                <div class="mb-1"><i class="bi bi-geo-alt me-2"></i>{{ $member->address }}</div>
                @endif
                @if($member->occupation)
                <div class="mb-1"><i class="bi bi-briefcase me-2"></i>{{ $member->occupation }}</div>
                @endif
                @if($member->date_of_birth)
                <div class="mb-1"><i class="bi bi-calendar me-2"></i>{{ $member->date_of_birth->format('d M Y') }}</div>
                @endif
            </div>
            <div class="mt-3 small text-muted">Member since {{ $member->created_at->format('F Y') }}</div>
        </div>

        {{-- Send SMS --}}
        @if(auth()->user()->isFinancialSecretary() && $member->phone)
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-white border-bottom pt-3 pb-2">
                <h6 class="fw-semibold mb-0"><i class="bi bi-phone me-2 text-secondary"></i>Send SMS</h6>
            </div>
            <div class="card-body">
                @error('message')
                <div class="alert alert-danger py-2 small mb-2">{{ $message }}</div>
                @enderror
                <form method="POST" action="{{ route('admin.members.sms', $member) }}">
                    @csrf
                    @php $smsTemplates = \App\Models\SmsTemplate::orderBy('name')->get(); @endphp
                    @if($smsTemplates->isNotEmpty())
                    <div class="mb-2">
                        <select id="member-sms-template" class="form-select form-select-sm">
                            <option value="">— Custom message —</option>
                            @foreach($smsTemplates as $tpl)
                            <option value="{{ $tpl->body }}">{{ $tpl->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <textarea name="message" id="member-sms-body" rows="3"
                              class="form-control form-control-sm mb-1"
                              maxlength="160" required
                              placeholder="Type your message…">{{ old('message') }}</textarea>
                    <div class="d-flex justify-content-between mb-2">
                        <div class="form-text"><code>{name}</code> → member's name</div>
                        <div class="form-text"><span id="member-sms-count">0</span> / 160</div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-outline-secondary w-100">
                        <i class="bi bi-send me-1"></i>Send SMS
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- Status management --}}
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-white border-bottom pt-3 pb-2">
                <h6 class="fw-semibold mb-0"><i class="bi bi-sliders me-2 text-secondary"></i>Manage</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.members.status', $member) }}" class="mb-2">
                    @csrf @method('PATCH')
                    <label class="form-label small fw-medium">Status</label>
                    <div class="input-group input-group-sm">
                        <select name="status" class="form-select">
                            @foreach(['active','inactive','suspended'] as $s)
                            <option value="{{ $s }}" {{ $member->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-outline-secondary">Save</button>
                    </div>
                </form>
                <form method="POST" action="{{ route('admin.members.role', $member) }}">
                    @csrf @method('PATCH')
                    <label class="form-label small fw-medium">Role</label>
                    <div class="input-group input-group-sm">
                        <select name="role" class="form-select">
                            @foreach(['member','financial_secretary','admin'] as $r)
                            <option value="{{ $r }}" {{ $member->role === $r ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$r)) }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-outline-secondary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Right: payments --}}
    <div class="col-md-8">
        @php
            $totalPaid = $member->payments->where('status','completed')->sum('amount');
        @endphp
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div>
                        <div class="small text-muted">Total Paid (all time)</div>
                        <div class="fs-5 fw-bold text-success">£{{ number_format($totalPaid, 2) }}</div>
                    </div>
                    <div class="ms-4">
                        <div class="small text-muted">Payments</div>
                        <div class="fs-5 fw-bold">{{ $member->payments->count() }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom pt-3 pb-2">
                <h6 class="fw-semibold mb-0"><i class="bi bi-receipt me-2 text-primary"></i>Payment History</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Cycle</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($member->payments->sortByDesc('created_at') as $p)
                        <tr>
                            <td class="small">{{ $p->payment_date ? $p->payment_date->format('d M Y') : $p->created_at->format('d M Y') }}</td>
                            <td class="small">{{ $p->duesCycle?->name ?? '—' }}</td>
                            <td class="fw-medium">£{{ number_format($p->amount, 2) }}</td>
                            <td class="small text-muted">{{ ucfirst($p->method ?? '—') }}</td>
                            <td>
                                @if($p->status === 'completed')
                                    <span class="badge bg-success">Completed</span>
                                @elseif($p->status === 'pending')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($p->status) }}</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $p->receipt_number ?? '—' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No payments recorded.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
    const smsBody    = document.getElementById('member-sms-body');
    const smsCounter = document.getElementById('member-sms-count');
    const smsTpl     = document.getElementById('member-sms-template');

    if (smsBody && smsCounter) {
        function updateSmsCount() { smsCounter.textContent = smsBody.value.length; }
        smsBody.addEventListener('input', updateSmsCount);
        updateSmsCount();
    }

    if (smsTpl && smsBody) {
        smsTpl.addEventListener('change', function () {
            if (this.value) {
                smsBody.value = this.value;
                smsBody.dispatchEvent(new Event('input'));
            }
        });
    }
</script>
@endpush
@endsection
