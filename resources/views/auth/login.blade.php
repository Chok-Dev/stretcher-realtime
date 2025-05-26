@extends('layouts.app')

@section('title', 'เข้าสู่ระบบ - ศูนย์เปล')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white text-center py-4">
                    <h3 class="mb-0">
                        <i class="fas fa-hospital-symbol me-2"></i>
                        ศูนย์เปล
                    </h3>
                    <small>โรงพยาบาลหนองหาน</small>
                </div>

                <div class="card-body p-4">
                    <form method="POST" action="{{ route('login.post') }}">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">ชื่อผู้ใช้</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-user"></i>
                                </span>
                                <input 
                                    id="name" 
                                    type="text"
                                    class="form-control @error('name') is-invalid @enderror" 
                                    name="name"
                                    value="{{ old('name') }}" 
                                    required 
                                    autocomplete="username" 
                                    autofocus
                                    placeholder="กรอกชื่อผู้ใช้">
                                @error('name')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label">รหัสผ่าน</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input 
                                    id="password" 
                                    type="password"
                                    class="form-control @error('password') is-invalid @enderror" 
                                    name="password"
                                    required 
                                    autocomplete="current-password"
                                    placeholder="กรอกรหัสผ่าน">
                                @error('password')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" name="action" value="user" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                เข้าสู่ระบบ (ทีมเปล)
                            </button>
                            <button type="submit" name="action" value="admin" class="btn btn-outline-success">
                                <i class="fas fa-chart-bar me-2"></i>
                                ดูข้อมูลการขอเปล
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="text-center mt-3">
                <small class="text-muted">
                    เวอร์ชัน Real-time พร้อม Laravel Reverb
                </small>
            </div>
        </div>
    </div>
</div>
@endsection