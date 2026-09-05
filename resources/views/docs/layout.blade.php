@extends('layouts.app')
@section('title', ($current['title'] ?? 'Help') . ' – Help')
@section('page-title', 'Help & Documentation')

@section('content')
<div class="row g-4">
    <div class="col-12 col-lg-3">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body position-relative">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="docs-search" class="form-control" placeholder="Search help articles…" autocomplete="off">
                </div>
                <div id="docs-search-results" class="list-group mt-2 d-none position-absolute w-100 shadow-sm" style="z-index:1050; left:0; padding:0 1rem;"></div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-2">
                @foreach($nav as $namespaceSlug => $namespace)
                    <div class="px-2 py-2 fw-semibold text-uppercase small text-muted">
                        <i class="bi {{ $namespace['icon'] ?? 'bi-journal' }} me-1"></i>{{ $namespace['label'] }}
                    </div>
                    @foreach($namespace['sections'] as $sectionSlug => $section)
                        <div class="px-3 py-1 small fw-medium text-secondary">{{ $section['label'] }}</div>
                        @foreach($section['articles'] as $articleSlug => $article)
                            <a href="{{ route('docs.show', [$namespaceSlug, $sectionSlug, $articleSlug]) }}"
                               class="list-group-item list-group-item-action border-0 py-1 px-4 small
                                      {{ $current['namespace'] === $namespaceSlug && $current['section'] === $sectionSlug && $current['article'] === $articleSlug ? 'fw-bold text-success' : '' }}">
                                {{ $article['title'] }}
                            </a>
                        @endforeach
                    @endforeach
                @endforeach
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-9">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb small">
                        <li class="breadcrumb-item">{{ $current['namespaceLabel'] }}</li>
                        <li class="breadcrumb-item">{{ $current['sectionLabel'] }}</li>
                        <li class="breadcrumb-item active">{{ $current['title'] }}</li>
                    </ol>
                </nav>

                <h3 class="fw-bold mb-4">{{ $current['title'] }}</h3>

                @include($contentView)

                <hr class="my-4">
                <div class="d-flex justify-content-between">
                    @if($prev)
                        <a href="{{ route('docs.show', [$prev['namespace'], $prev['section'], $prev['article']]) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left me-1"></i>{{ $prev['title'] }}
                        </a>
                    @else
                        <span></span>
                    @endif
                    @if($next)
                        <a href="{{ route('docs.show', [$next['namespace'], $next['section'], $next['article']]) }}" class="btn btn-outline-secondary btn-sm">
                            {{ $next['title'] }}<i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fuse.js@7.0.0"></script>
<script src="{{ asset('docs-assets/search.js') }}" data-search-url="{{ route('docs.search-index') }}"></script>
@endpush
