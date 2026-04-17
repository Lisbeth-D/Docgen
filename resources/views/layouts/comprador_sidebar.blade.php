@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

<div class="admin-layout">

    {{-- SIDEBAR --}}
    <aside class="admin-sidebar" id="sidebar">
        <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
        <ul>
            <li>
                <a href="{{ route('convocatoria') }}">
                    <i data-feather="file-text"></i>
                    <span>Convocatoria</span>
                </a>
            </li>

            <li>
                <div class="menu-title" onclick="toggleOficios()">
                    <i data-feather="folder"></i>
                    <span>Oficios</span>
                    <i data-feather="chevron-down" class="chevron"></i>
                </div>

                <ul class="submenu" id="oficiosSubmenu">
                    <li>
                        <a href="{{ route('revision.form') }}">
                            <i data-feather="search"></i>
                            Revisión
                        </a>
                    </li>
                    <li>
                        <a href="#">
                            <i data-feather="upload"></i>
                            Publicación
                        </a>
                    </li>
                    <li>
                        <a href="#">
                            <i data-feather="user-check"></i>
                            Designación
                        </a>
                    </li>
                    <li>
                        <a href="#">
                            <i data-feather="award"></i>
                            Adjudicación
                        </a>
                    </li>
                </ul>
            </li>

            <li>
                <div class="menu-title" onclick="toggleAclaraciones()">
                    <i data-feather="help-circle"></i>
                    <span>Aclaraciones</span>
                    <i data-feather="chevron-down" class="chevron"></i>
                </div>

                <ul class="submenu" id="aclaracionesSubmenu">

                    {{-- SI APLICA JUNTA --}}
                    <li>
                        <div class="menu-title submenu-title" onclick="toggleSiAplica()">
                            <i data-feather="check-square"></i>
                            <span>Si aplica junta</span>
                            <i data-feather="chevron-down" class="chevron"></i>
                        </div>

                        <ul class="submenu nested" id="siAplicaSubmenu">
                            <li>
                                <a href="#">
                                    <i data-feather="file-text"></i>
                                    Acta
                                </a>
                            </li>

                            <li>
                                <a href="#">
                                    <i data-feather="users"></i>
                                    Lista Asistencia
                                </a>
                            </li>
                        </ul>
                    </li>

                    {{-- NO APLICA JUNTA --}}
                    <li>
                        <a href="#">
                            <i data-feather="x-circle"></i>
                            No aplica junta
                        </a>
                    </li>

                </ul>
            </li>

            <li>
                <a href="#">
                    <i data-feather="package"></i>
                    <span>Apertura</span>
                </a>
            </li>

            <li>
                <a href="#">
                    <i data-feather="check-circle"></i>
                    <span>Fallo</span>
                </a>
            </li>

            <li>
                <a href="#">
                    <i data-feather="file"></i>
                    <span>Contratos</span>
                </a>
            </li>

            <li>
                <a href="#">
                    <i data-feather="edit-3"></i>
                    <span>Generador de machote</span>
                </a>
            </li>
        </ul>
    </aside>
    {{-- CONTENIDO --}}
    <div class="admin-content">

        {{-- USUARIO (ya no absoluto respecto al content) --}}
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

        <div class="welcome-card">
            <h2>Bienvenido, {{ Auth::user()->username }}</h2>
            <p>Selecciona una opción del menú lateral para continuar.</p>
        </div>

    </div>

</div>

<script>
function toggleUserMenu() {
    document.getElementById("userDropdown").classList.toggle("show");
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
</script>

@endsection