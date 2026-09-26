@extends('errors.layout')

@section('title', '404 – Page Not Found')

@section('content')
    <div class="error-icon"><i class="bi bi-search"></i></div>
    <div class="error-code">404</div>
    <h4 class="mt-3">Page Not Found</h4>
    <p class="text-muted">The page you're looking for doesn't exist or has been moved.</p>
@endsection
