@extends('layouts.app')
@section('title','Meeting Minutes')
@section('page-title','Meeting Minutes')

@section('content')
@if($minutes->isEmpty())
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>No minutes have been published yet.
</div>
@else
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Title</th>
                    <th>Meeting Date</th>
                    <th>Published</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($minutes as $m)
                <tr>
                    <td>
                        <div class="fw-medium">{{ $m->title }}</div>
                        @if($m->isNew())
                            <span class="badge bg-success" style="font-size:.62rem">New</span>
                        @endif
                    </td>
                    <td class="small">{{ $m->meeting_date->format('d M Y') }}</td>
                    <td class="small text-muted">{{ $m->published_at->format('d M Y') }}</td>
                    <td class="text-end">
                        <a href="{{ route('member.minutes.show', $m) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye me-1"></i>View
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($minutes->hasPages())
    <div class="card-footer bg-white">{{ $minutes->links() }}</div>
    @endif
</div>
@endif
@endsection
