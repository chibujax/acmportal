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
    <div class="card-body p-0">
        <iframe src="{{ route('member.minutes.view', $minutes) }}"
                style="width:100%; height:80vh; border:0;" title="{{ $minutes->title }}"></iframe>
    </div>
</div>
@endsection
