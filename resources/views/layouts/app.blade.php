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

        @if(Auth::check())
        <div class="top-user-fixed">
            <div class="user-box" onclick="toggleUserMenu()">
                👤 {{ Auth::user()->username }}
            </div>

            <div class="user-dropdown" id="userDropdown">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">Cerrar sesión</button>
                </form>
            </div>
        </div>
        @endif

    </header>

    {{-- CONTENIDO --}}
    <main class="main-content">
        @yield('content')
    </main>

    {{-- FOOTER --}}
    <footer class="main-footer">
        © {{ date('Y') }} Adquisiciones y Servicios | 0.0.0
    </footer>

</div>

<script>
function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("collapsed");
}

function toggleOficios() {
    document.getElementById("oficiosSubmenu").classList.toggle("open");
}

function toggleAclaraciones() {
    document.getElementById("aclaracionesSubmenu").classList.toggle("open");
}

function toggleSiAplica() {
    document.getElementById("siAplicaSubmenu").classList.toggle("open");
}

function toggleUserMenu() {
    const menu = document.getElementById("userDropdown");
    menu.classList.toggle("show");
}

function toggleConfig(){
document.getElementById("configSubmenu").classList.toggle("open");
}

function toggleReportes(){
document.getElementById("reportesSubmenu").classList.toggle("open");
}

function toggleUserMenu(){
document.getElementById("userDropdown").classList.toggle("show");
}

feather.replace();
</script>

</body>
</html>