@extends('layouts.app')
@section('title', $minutes->title)
@section('page-title','Meeting Minutes')

@section('content')
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h6 class="fw-semibold mb-1">{{ $minutes->title }}</h6>
            <div class="text-muted small">
                Meeting date: {{ $minutes->meeting_date->format('d M Y') }}
                &middot; Published {{ $minutes->published_at->format('d M Y') }}
                @if($minutes->isNew())
                    <span class="badge bg-success ms-1" style="font-size:.62rem">New</span>
                @endif
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('member.minutes.view', $minutes) }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm">
                <i class="bi bi-eye me-1"></i>View
            </a>
            <a href="{{ route('member.minutes.download', $minutes) }}" class="btn btn-success btn-sm">
                <i class="bi bi-download me-1"></i>Download
            </a>
            <a href="{{ route('member.minutes.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Back to Minutes
            </a>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
        <i class="bi bi-file-earmark-pdf text-danger" style="font-size:3rem"></i>
        <p class="text-muted mt-3 mb-3">
            Opens in your device's own PDF viewer for the best experience
        </p>
        <a href="{{ route('member.minutes.view', $minutes) }}" target="_blank" rel="noopener" class="btn btn-primary">
            <i class="bi bi-eye me-1"></i>View {{ $minutes->title }}
        </a>
    </div>
    <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap gap-2" id="viewers">
        <span class="text-muted small">
            <i class="bi bi-eye me-1"></i>Viewed by {{ $viewCount }} {{ Str::plural('member', $viewCount) }}
            <span class="mx-1">&middot;</span>
            <i class="bi bi-download me-1"></i>Downloaded by {{ $downloadCount }} {{ Str::plural('member', $downloadCount) }}
        </span>
        @if($viewers !== null && $viewers->isNotEmpty())
            <button class="btn btn-link btn-sm p-0" type="button" data-bs-toggle="collapse" data-bs-target="#viewer-list">
                See who viewed
            </button>
        @endif
    </div>
    @if($viewers !== null && $viewers->isNotEmpty())
    <div class="collapse" id="viewer-list">
        <ul class="list-group list-group-flush">
            @foreach($viewers as $viewer)
            <li class="list-group-item d-flex justify-content-between align-items-center small">
                <span>{{ $viewer->name }}</span>
                <span class="text-muted text-end">
                    @if($viewer->pivot->view_count > 0)
                        <span title="Views"><i class="bi bi-eye me-1"></i>{{ $viewer->pivot->view_count }}</span>
                    @endif
                    @if($viewer->pivot->download_count > 0)
                        <span class="ms-2" title="Downloads"><i class="bi bi-download me-1"></i>{{ $viewer->pivot->download_count }}</span>
                    @endif
                    <span class="ms-2">{{ \Carbon\Carbon::parse($viewer->pivot->last_viewed_at)->format('d M Y, H:i') }}</span>
                </span>
            </li>
            @endforeach
        </ul>
    </div>
    @endif
</div>
@endsection
