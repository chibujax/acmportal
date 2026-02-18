@extends('layouts.app')
@section('title','Invalid Link')
@section('content')
<div class="min-vh-100 d-flex align-items-center justify-content-center"
     style="background: linear-gradient(135deg, #0f3d22 0%, #1a6b3c 100%)">
    <div class="col-12 col-md-5">
        <div class="card border-0 shadow-lg text-center p-5" style="border-radius:16px">
            <div style="font-size:3rem">⛔</div>
            <h4 class="fw-bold mt-3">Link Expired or Invalid</h4>
            <p class="text-muted mt-2">This registration link has either expired or already been used.
               Please contact the administrator to receive a new invite.</p>
            <a href="{{ route('login') }}" class="btn btn-success mt-3">Back to Login</a>
        </div>
    </div>
</div>
@endsection
