@extends('errors.layout')

@section('title', '500 – Server Error')

@section('content')
    <div class="error-icon"><i class="bi bi-exclamation-triangle"></i></div>
    <div class="error-code">500</div>
    <h4 class="mt-3">Something Went Wrong</h4>
    <p class="text-muted">An unexpected error occurred. Our team has been notified. Please try again shortly.</p>
@endsection
