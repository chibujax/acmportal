@extends('layouts.app')

@section('title', 'Contact Log')
@section('page-title', 'Contact Log')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-semibold mb-0">Contact Log</h5>
        <p class="text-muted small mb-0">Every SMS/email sent from the portal — absentee follow-ups and bulk messages.</p>
    </div>
    <a href="{{ route('admin.messages.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Messaging
    </a>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-4">
                <label class="form-label small fw-medium mb-1">Sent Batch</label>
                <select name="batch_id" class="form-select form-select-sm">
                    <option value="">All sends</option>
                    @foreach($batches as $batch)
                        <option value="{{ $batch->batch_id }}" {{ request('batch_id') === $batch->batch_id ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::parse($batch->sent_at)->format('d M Y H:i') }}
                            — {{ ucfirst(str_replace('_', ' ', $batch->context)) }}
                            ({{ strtoupper($batch->channel) }}, {{ $batch->recipient_count }} recipient{{ $batch->recipient_count === 1 ? '' : 's' }})
                            @if($batch->preview) — "{{ $batch->preview }}" @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-3">
                <label class="form-label small fw-medium mb-1">Member</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Name or phone…" value="{{ request('search') }}">
            </div>
            <div class="col-sm-2">
                <label class="form-label small fw-medium mb-1">Channel</label>
                <select name="channel" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="sms"   {{ request('channel')==='sms'   ? 'selected' : '' }}>SMS</option>
                    <option value="email" {{ request('channel')==='email' ? 'selected' : '' }}>Email</option>
                </select>
            </div>
            <div class="col-sm-3">
                <label class="form-label small fw-medium mb-1">Reason</label>
                <select name="context" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="consecutive_absentee" {{ request('context')==='consecutive_absentee' ? 'selected' : '' }}>Consecutive Absentee</option>
                    <option value="meeting_absentee"      {{ request('context')==='meeting_absentee'      ? 'selected' : '' }}>Meeting Absentee</option>
                    <option value="bulk"                  {{ request('context')==='bulk'                  ? 'selected' : '' }}>Bulk Message</option>
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label small fw-medium mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="sent"   {{ request('status')==='sent'   ? 'selected' : '' }}>Sent</option>
                    <option value="failed" {{ request('status')==='failed' ? 'selected' : '' }}>Failed</option>
                </select>
            </div>
            <div class="col-sm-3">
                <label class="form-label small fw-medium mb-1">From</label>
                <input type="datetime-local" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-sm-3">
                <label class="form-label small fw-medium mb-1">To</label>
                <input type="datetime-local" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-sm-3 d-flex gap-2">
                <button type="submit" class="btn btn-success btn-sm flex-fill">Filter</button>
                @if(request()->hasAny(['search','channel','context','status','date_from','date_to','batch_id']))
                    <a href="{{ route('admin.contact-log.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                @endif
                <a href="{{ route('admin.contact-log.export') }}?{{ http_build_query(request()->query()) }}" class="btn btn-outline-success btn-sm">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export
                </a>
                <a href="{{ route('admin.contact-log.print') }}?{{ http_build_query(request()->query()) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-printer me-1"></i>Print / PDF
                </a>
            </div>
        </form>
    </div>
</div>

@if($logs->isEmpty())
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-chat-dots fs-1 mb-2 d-block"></i>
        No contact history recorded yet.
    </div>
</div>
@else
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th style="width:150px">Date / Time</th>
                    <th>Member</th>
                    <th style="width:80px">Channel</th>
                    <th>Reason</th>
                    <th>Message</th>
                    <th style="width:80px">Status</th>
                    <th>Sent By</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                <tr>
                    <td class="text-muted">{{ $log->created_at->format('d M Y H:i') }}</td>
                    <td>
                        @if($log->user)
                            <a href="{{ route('admin.members.show', $log->user) }}" class="text-decoration-none">{{ $log->user->name }}</a>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td><span class="badge bg-light text-dark border">{{ strtoupper($log->channel) }}</span></td>
                    <td class="small text-muted">
                        {{ ucfirst(str_replace('_', ' ', $log->context)) }}
                        @if($log->meeting)
                            <div style="font-size:.7rem">{{ $log->meeting->title }}</div>
                        @endif
                    </td>
                    <td class="small" style="max-width:320px">
                        @if($log->subject)<div class="fw-medium">{{ $log->subject }}</div>@endif
                        <div class="text-muted text-truncate" style="max-width:320px" title="{{ $log->message }}">{{ $log->message }}</div>
                    </td>
                    <td>
                        <span class="badge {{ $log->status === 'sent' ? 'bg-success' : 'bg-danger' }}">{{ ucfirst($log->status) }}</span>
                    </td>
                    <td class="small">{{ $log->sentBy?->name ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $logs->links() }}
</div>
@endif
@endsection
