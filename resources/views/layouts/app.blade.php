{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'ศูนย์เปล - โรงพยาบาลหนองหาน')</title>

    <!-- Preconnect for performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#3b82f6">
    <!-- Enhanced Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Kanit:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('storage/img/stretcher.png') }}">

    <!-- Bootstrap 5 with integrity -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Animate.css for smooth animations -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <!-- เพิ่มในส่วน head ของ app.blade.php -->
    <link rel="stylesheet" href="{{ asset('ui/enhanced-animations.css') }}">
    <link rel="stylesheet" href="{{ asset('ui/mobile-overrides.css') }}">
    
    <!-- เพิ่มในส่วน scripts -->

    @vite(['resources/js/app.js'])
    @livewireStyles

    <style>
        :root {
            /* Modern Color Palette */
            --primary-color: #667eea;
            --primary-dark: #5a6fd8;
            --primary-light: #818cf8;
            --secondary-color: #764ba2;
            --success-color: #10b981;
            --success-light: #34d399;
            --warning-color: #f59e0b;
            --warning-light: #fbbf24;
            --danger-color: #ef4444;
            --danger-light: #f87171;
            --info-color: #06b6d4;
            --info-light: #22d3ee;
            --dark-color: #1f2937;
            --light-bg: #f8fafc;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #9ca3af;

            /* Shadows */
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);

            /* Gradients */
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-success: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --gradient-warning: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            --gradient-danger: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            --gradient-info: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
            --gradient-bg: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);

            /* Border Radius */
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --radius-2xl: 1.5rem;

            /* Transitions */
            --transition-fast: 0.15s ease;
            --transition-normal: 0.3s ease;
            --transition-slow: 0.5s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            position: relative;
            min-height: 100vh;
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Kanit', 'Inter', sans-serif;
            background: var(--gradient-bg);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
            margin-bottom: 120px;
            font-weight: 400;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Enhanced Typography */
        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-weight: 600;
            line-height: 1.2;
            margin-bottom: 0.5rem;
        }

        p {
            margin-bottom: 1rem;
        }

        /* Enhanced Card Styles */
        .card {
            border: 1px solid var(--border-color);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            transition: all var(--transition-normal);
            background: var(--card-bg);
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .card-header {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-bottom: 1px solid var(--border-color);
            border-radius: var(--radius-xl) var(--radius-xl) 0 0 !important;
            padding: 1.25rem 1.5rem;
            font-weight: 600;
        }

        .card-body {
            padding: 1.5rem;
        }

        .card-footer {
            background: #f8fafc;
            border-top: 1px solid var(--border-color);
            border-radius: 0 0 var(--radius-xl) var(--radius-xl) !important;
            padding: 1rem 1.5rem;
        }

        /* Enhanced Stretcher Card Styles */
        .stretcher-card {
            background: var(--card-bg);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            transition: all var(--transition-normal);
            overflow: hidden;
            position: relative;
        }

        .stretcher-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
        }

        .stretcher-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .stretcher-card.updated {
            border: 2px solid var(--success-color) !important;
            background: rgba(16, 185, 129, 0.02);
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1), var(--shadow-lg);
            animation: cardUpdate 0.6s ease-out;
        }

        .stretcher-card.urgent::before {
            background: var(--gradient-danger);
            height: 6px;
        }

        .stretcher-card.priority-high::before {
            background: var(--gradient-warning);
        }

        .stretcher-card.priority-normal::before {
            background: var(--gradient-info);
        }

        /* Enhanced Status Badges */
        .badge {
            font-size: 0.875rem;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-2xl);
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
        }

        .badge.bg-primary {
            background: var(--gradient-primary) !important;
        }

        .badge.bg-success {
            background: var(--gradient-success) !important;
        }

        .badge.bg-warning {
            background: var(--gradient-warning) !important;
            color: white !important;
        }

        .badge.bg-danger {
            background: var(--gradient-danger) !important;
        }

        .badge.bg-info {
            background: var(--gradient-info) !important;
        }

        /* Enhanced Buttons */
        .btn {
            font-weight: 500;
            border-radius: var(--radius-md);
            padding: 0.75rem 1.5rem;
            transition: all var(--transition-normal);
            border: none;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-lg);
            background: var(--primary-dark);
        }

        .btn-success {
            background: var(--gradient-success);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-lg);
        }

        .btn-warning {
            background: var(--gradient-warning);
            color: white;
        }

        .btn-info {
            background: var(--gradient-info);
            color: white;
        }

        .btn-danger {
            background: var(--gradient-danger);
            color: white;
        }

        .btn-outline-primary {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            background: transparent;
        }

        .btn-outline-primary:hover {
            background: var(--primary-color);
            transform: translateY(-1px);
        }

        .btn-outline-danger {
            border: 2px solid var(--danger-color);
            color: var(--danger-color);
            background: transparent;
        }

        .btn-outline-danger:hover {
            background: var(--danger-color);
            transform: translateY(-1px);
        }

        /* Enhanced Text Styles */
        .urgent-text {
            color: var(--danger-color) !important;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .text-primary {
            color: var(--primary-color) !important;
        }

        .text-success {
            color: var(--success-color) !important;
        }

        .text-warning {
            color: var(--warning-color) !important;
        }

        .text-danger {
            color: var(--danger-color) !important;
        }

        .text-info {
            color: var(--info-color) !important;
        }

        .text-muted {
            color: var(--text-muted) !important;
        }

        /* Enhanced Status Badge Styles */
        .status-badge {
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-2xl);
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            text-transform: none;
        }

        /* Enhanced Animations */
        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.8;
                transform: scale(1.02);
            }
        }

        @keyframes cardUpdate {
            0% {
                transform: scale(1);
                box-shadow: var(--shadow-md);
            }

            50% {
                transform: scale(1.02);
                box-shadow: 0 0 0 8px rgba(16, 185, 129, 0.2), var(--shadow-xl);
            }

            100% {
                transform: scale(1);
                box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1), var(--shadow-lg);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        .slide-in-right {
            animation: slideInRight 0.4s ease-out;
        }

        /* Enhanced Footer */
        .footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            height: 120px;
            background: var(--dark-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            box-shadow: 0 -4px 6px -1px rgb(0 0 0 / 0.1);
        }

        .footer small {
            opacity: 0.8;
            line-height: 1.5;
        }

        /* Enhanced Form Controls */
        .form-control {
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all var(--transition-normal);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-check-input {
            border: 2px solid var(--border-color);
            border-radius: 0.25rem;
            width: 1.25rem;
            height: 1.25rem;
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
        }

        .form-check-label {
            font-weight: 500;
            margin-left: 0.5rem;
        }

        /* Loading States */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .spinner-border {
            width: 1.5rem;
            height: 1.5rem;
            border-width: 0.2em;
        }

        /* Enhanced Responsive Design */
        @media (max-width: 1200px) {
            .card-body {
                padding: 1.25rem;
            }
        }

        @media (max-width: 768px) {
            body {
                margin-bottom: 100px;
            }

            .footer {
                height: 100px;
            }

            .card-body {
                padding: 1rem;
            }

            .btn {
                padding: 0.625rem 1.25rem;
                font-size: 0.9rem;
            }

            .badge {
                font-size: 0.8rem;
                padding: 0.375rem 0.75rem;
            }

            .stretcher-card:hover {
                transform: none;
            }
        }

        @media (max-width: 576px) {

            .card-header,
            .card-body,
            .card-footer {
                padding: 1rem;
            }

            .status-badge {
                font-size: 0.75rem;
                padding: 0.375rem 0.75rem;
            }
        }

        /* Print Styles */
        @media print {
            body {
                background: white !important;
                color: black !important;
            }

            .btn,
            .badge,
            .footer {
                display: none !important;
            }

            .card {
                border: 1px solid #000 !important;
                box-shadow: none !important;
                break-inside: avoid;
                margin-bottom: 1rem;
            }

            .stretcher-card::before {
                display: none;
            }
        }

        /* Dark Mode Support (Future Enhancement) */
        @media (prefers-color-scheme: dark) {
            /* Dark mode styles can be added here */
        }

        /* Accessibility Enhancements */
        .btn:focus,
        .form-control:focus,
        .form-check-input:focus {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }

        /* High Contrast Mode Support */
        @media (prefers-contrast: high) {
            .card {
                border-width: 2px;
            }

            .btn {
                border-width: 2px;
            }
        }

        /* Reduced Motion Support */
        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>

    @stack('styles')
</head>

<body>
    <main role="main">
        @yield('content')
    </main>

    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <small class="d-block mb-1">
                        <strong>Copyright &copy; 2024 Nonghan Hospital</strong> - All rights reserved.
                    </small>
                    <small class="d-block">
                        <i class="fas fa-code me-1"></i>Made with ❤️ by IT Department |
                        <i class="fas fa-rocket me-1"></i>Powered by Laravel 12 & Reverb
                    </small>
                </div>
            </div>
        </div>
    </footer>

    @livewireScripts
    <script src="{{ asset('ui/enhanced-stretcher-manager.js') }}"></script>
    <script src="{{ asset('ui/mobile-enhancements.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous">
    </script>
    @stack('scripts')
</body>

</html>
