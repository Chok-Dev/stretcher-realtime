{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'ศูนย์เปล - โรงพยาบาลหนองหาน')</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=kanit:400" rel="stylesheet" />
    <link rel="shortcut icon" href="{{ asset('storage/img/stretcher.png') }}">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    @vite(['resources/js/app.js'])
    @livewireStyles

    <style>
        * {
            padding: 0;
            margin: 0;
        }

        html {
            position: relative;
            min-height: 100%;
        }

        body {
            margin-bottom: 100px;
            font-family: 'Kanit', sans-serif;
            background-color: #f5f5f9;
        }

        .footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            height: 100px;
        }

        .stretcher-card {
            background-color: #f3f3f3;
            text-align: left;
            transition: all 0.3s ease;
        }

        .stretcher-card.updated {
            border: 2px solid #28a745 !important;
            background-color: #d4edda;
        }

        .urgent-text {
            color: #dc3545 !important;
        }

        .status-badge {
            font-size: 0.875rem;
        }

        @keyframes pulse {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }

            100% {
                opacity: 1;
            }
        }

        .pulse {
            animation: pulse 1s infinite;
        }
    </style>

    @stack('styles')
</head>

<body>
    @yield('content')

    <div class="footer d-flex align-items-center shadow-lg bg-white text-dark">
        <div class="container text-center">
            <small>Copyright &copy; 2024 Nonghan Hospital, All right reserved.</small>
            <br>
            <small>Made By: IT Department | Powered by Laravel 12 & Reverb</small>
        </div>
    </div>

    @livewireScripts
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
    @stack('scripts')
</body>

</html>
