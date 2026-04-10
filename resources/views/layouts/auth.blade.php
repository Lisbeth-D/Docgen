<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>@yield('title')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="app-body">

<div class="page-container">

    {{-- HEADER --}}
    <header class="main-header">
        <img src="{{ asset('img/logo.png') }}" alt="Logo">
    </header>

    {{-- CONTENIDO LOGIN --}}
    <main class="main-content auth-content">
        @yield('content')
    </main>

    {{-- FOOTER --}}
    <footer class="main-footer">
        © {{ date('Y') }} Adquisiciones y Servicios | 0.0.0
    </footer>

</div>

</body>
</html>