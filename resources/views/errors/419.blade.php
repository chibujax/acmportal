@extends('errors.layout')

@section('title', 'Session Expired')

@section('content')
    <div class="error-icon"><i class="bi bi-clock-history"></i></div>
    <div class="error-code">419</div>
    <h4 class="mt-3">Session Expired</h4>
    <p class="text-muted">Your session has expired. You will be redirected to the login page.</p>
    <meta http-equiv="refresh" content="3;url={{ route('login') }}">
@endsection
