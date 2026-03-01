@extends('layouts.app')
@section('title', 'SMS Templates')
@section('page-title', 'SMS Templates')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="text-muted small">Reusable SMS message templates with placeholder support.</div>
    <a href="{{ route('admin.sms-templates.create') }}" class="btn btn-sm btn-success">
        <i class="bi bi-plus-circle me-1"></i>New Template
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Message Body</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($templates as $tpl)
                <tr>
                    <td class="fw-medium">{{ $tpl->name }}</td>
                    <td class="small text-muted" style="max-width:400px">{{ $tpl->body }}</td>
                    <td class="text-end">
                        <a href="{{ route('admin.sms-templates.edit', $tpl) }}" class="btn btn-sm btn-outline-primary me-1">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.sms-templates.destroy', $tpl) }}" class="d-inline"
                              onsubmit="return confirm('Delete this template?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="text-center text-muted py-4">No templates yet. <a href="{{ route('admin.sms-templates.create') }}">Create one</a>.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3 small text-muted">
    <strong>Available placeholders:</strong>
    <code>{name}</code> member name &nbsp;·&nbsp;
    <code>{amount}</code> outstanding balance &nbsp;·&nbsp;
    <code>{cycle}</code> dues cycle name
</div>
@endsection
