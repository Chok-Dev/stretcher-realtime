@extends('layouts.app')

@section('title', 'สถานะการขอเปล')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="text-primary">
            <i class="fas fa-chart-bar me-2"></i>
            สถานะการขอเปล
        </h1>
        @if(session()->has('name'))
            <div>
                <span class="me-3">{{ session()->get('name') }}</span>
                <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm">
                    กลับหน้าเข้าสู่ระบบ
                </a>
            </div>
        @endif
    </div>

    <livewire:public-stretcher-view />
</div>
@endsection