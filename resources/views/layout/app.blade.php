<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">

    <!-- ✅ IMPORTANT: Remix Icons (your Discover section uses ri-... classes) -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('assets/css/navbar.css') }}?v={{ filemtime(public_path('assets/css/navbar.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/footer.css') }}?v={{ filemtime(public_path('assets/css/footer.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/header.css') }}">

    @stack('styles')
</head>


<body class="@yield('bodyClass')">

    <div id="your-element-selector"></div>

    <div class="content">
        <div>
            @include("partials.navbar")
            <div id="main">
                @yield('content')
                @if(trim($__env->yieldContent('hideFooter')) !== '1')
                    @include("partials.footer_modern")
                @endif
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r134/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vanta@latest/dist/vanta.dots.min.js"></script>

    <script src="{{ asset('assets/js/navbar.js') }}?v={{ filemtime(public_path('assets/js/navbar.js')) }}"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO"
        crossorigin="anonymous"></script>

    @stack('scripts')
</body>
</html>
