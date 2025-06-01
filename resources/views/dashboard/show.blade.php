{{-- resources/views/dashboard/show.blade.php --}}
@extends('layouts.app')

@section('title', 'ดูข้อมูลการขอเปล')

@section('content')
    @include('sweetalert::alert')

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="text-primary">
                        <i class="fas fa-chart-bar me-2"></i>
                        ข้อมูลการขอเปล
                    </h2>

                    @if (session()->has('name'))
                        <div class="d-flex align-items-center">
                            <span class="me-3">ผู้ใช้: <strong>{{ session()->get('name') }}</strong></span>
                            <a href="{{ route('logout') }}" class="btn btn-outline-danger">
                                <i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Analytics Dashboard for Admin View -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-info">
                    <h5><i class="fas fa-info-circle me-2"></i>ข้อมูลสถิติ</h5>
                    <p class="mb-0">หน้านี้แสดงข้อมูลการขอเปลทั้งหมด สำหรับการดูสถิติและวิเคราะห์ข้อมูล</p>
                </div>
            </div>
        </div>

        <livewire:stretcher-manager />

        <!-- Statistics Charts Section -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-external-link-alt me-2"></i>รายงานและสถิติเพิ่มเติม</h5>
                    </div>
                    <div class="card-body text-center">
                        <a href="https://lookerstudio.google.com/reporting/65943f17-df0d-4aab-89d4-e11ed86171b6"
                            target="_blank" class="btn btn-primary btn-lg">
                            <i class="fas fa-chart-line me-2"></i>
                            ดูรายงานใน Google Looker Studio
                        </a>
                        <p class="text-muted mt-2">คลิกเพื่อดูรายงานและสถิติแบบละเอียดในหน้าต่างใหม่</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Real-time updates for admin dashboard
                if (window.Echo) {
                    window.Echo.channel('stretcher-updates')
                        .listen('.new.request', (e) => {
                            showToast('รายการใหม่', 'มีรายการขอเปลใหม่เข้ามา', 'info');
                        })
                        .listen('.stretcher.updated', (e) => {
                            showToast('อัปเดต', 'มีการอัปเดตสถานะรายการ', 'success');
                        });
                }
            });

            function showToast(title, message, type = 'info') {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });

                Toast.fire({
                    icon: type,
                    title: title,
                    text: message
                });
            }
        </script>
    @endpush
@endsection
