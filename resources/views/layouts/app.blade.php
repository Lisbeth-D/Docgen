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
            <div class="header-user">
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
        <span>
            © {{ date('Y') }} Adquisiciones y Servicios | 0.0.0
        </span>

        <span class="footer-author">
            Author: lisbethd060@gmail.com
        </span>
    </footer>

</div>

<script>
function toggleById(id) {
    const element = document.getElementById(id);

    if (element) {
        element.classList.toggle("open");
    }
}

function closeById(id) {
    const element = document.getElementById(id);

    if (element) {
        element.classList.remove("open");
    }
}

function toggleSidebar() {
    const sidebar = document.getElementById("sidebar");

    if (sidebar) {
        sidebar.classList.toggle("collapsed");
    }
}

function toggleUserMenu() {
    const userDropdown = document.getElementById("userDropdown");

    if (userDropdown) {
        userDropdown.classList.toggle("show");
    }
}

/* =========================
   MENÚ COMPRADOR
========================= */

function toggleOficios() {
    closeById("aclaracionesSubmenu");
    closeById("siAplicaSubmenu");
    closeById("falloSubmenu");

    toggleById("oficiosSubmenu");
}

function toggleAclaraciones() {
    closeById("oficiosSubmenu");
    closeById("falloSubmenu");

    toggleById("aclaracionesSubmenu");
}

function toggleSiAplica() {
    toggleById("siAplicaSubmenu");
}

function toggleFallo() {
    closeById("oficiosSubmenu");
    closeById("aclaracionesSubmenu");
    closeById("siAplicaSubmenu");

    toggleById("falloSubmenu");
}

/* =========================
   MENÚ ADMIN
========================= */

function toggleConfig() {
    closeById("reportesSubmenu");

    toggleById("configSubmenu");
}

function toggleReportes() {
    closeById("configSubmenu");

    toggleById("reportesSubmenu");
}

document.addEventListener("DOMContentLoaded", function () {
    if (typeof feather !== "undefined") {
        feather.replace();
    }
});
</script>

</body>
</html>