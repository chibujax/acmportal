@extends('errors.layout')

@section('title', '403 – Access Denied')

@section('content')
    <div class="error-icon"><i class="bi bi-shield-lock"></i></div>
    <div class="error-code">403</div>
    <h4 class="mt-3">Access Denied</h4>
    <p class="text-muted">You don't have permission to view this page.</p>
@endsection
