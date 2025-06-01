
{{-- resources/views/auth/login.blade.php --}}
@extends('layouts.app')

@section('title', 'เข้าสู่ระบบ - ศูนย์เปล')

@section('content')
    @include('sweetalert::alert')
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-lg">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0 text-center">
                            <i class="fas fa-bed me-2"></i>
                            ศูนย์เปล - โรงพยาบาลหนองหาน
                        </h4>
                    </div>

                    <div class="card-body p-4">
                        <form method="POST" action="{{ route('login') }}">
                            @csrf

                            <div class="row mb-3">
                                <label for="name" class="col-md-4 col-form-label text-md-end">
                                    <strong>ชื่อผู้ใช้</strong>
                                </label>
                                <div class="col-md-6">
                                    <input id="name" type="text"
                                        class="form-control @error('name') is-invalid @enderror" name="name"
                                        value="{{ old('name') }}" required autocomplete="username" autofocus>

                                    @error('name')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-4">
                                <label for="password" class="col-md-4 col-form-label text-md-end">
                                    <strong>รหัสผ่าน</strong>
                                </label>
                                <div class="col-md-6">
                                    <input id="password" type="password"
                                        class="form-control @error('password') is-invalid @enderror" name="password"
                                        required autocomplete="current-password">

                                    @error('password')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-0">
                                <div class="col-md-8 offset-md-4">
                                    <button type="submit" name="action" value="user"
                                        class="btn btn-primary btn-lg me-3">
                                        <i class="fas fa-user me-2"></i>
                                        เข้าสู่ระบบ (ทีมเปล)
                                    </button>

                                    <button type="submit" name="action" value="admin" class="btn btn-success btn-lg">
                                        <i class="fas fa-chart-bar me-2"></i>
                                        ดูข้อมูลการขอเปล
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title text-primary">ข้อมูลการใช้งาน</h5>
                            <p class="card-text">
                                <strong>ทีมเปล:</strong> สามารถรับงาน ส่งงาน และอัปเดตสถานะได้<br>
                                <strong>ดูข้อมูล:</strong> สามารถดูสถิติและรายการขอเปลทั้งหมดได้
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
    @endpush
@endsection
