<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>

    <!-- BOOTSTRAP -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">

    <!--CSS-->    
    <link rel="stylesheet" href="{{ asset('assets/css/app.css?v1') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/header.css') }}">

    @stack('styles')

</head>

<body>

    <div class="layout-wrapper">

        <div class="content">
            @include("layout.header")

            <div class="container-xxl px-0 body-content">
                <div id="main" class="main-content">
                    @yield('content')
                </div>
            </div>

            @include("layout.footer")
        </div>

    </div>

    <script src="{{ asset('assets/js/header.js') }}"></script>

    <!--BOOTSTRAP -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO"
        crossorigin="anonymous"></script>

        
    @stack('scripts')
    
</body>
</html>