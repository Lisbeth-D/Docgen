@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

<div class="admin-layout">

    {{-- SIDEBAR --}}
    <aside class="admin-sidebar">

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
                    <i data-feather="edit-3"></i>
                    <span>Manual de sistema</span>
                </a>
            </li>
        </ul>

    </aside>

    {{-- CONTENIDO --}}
    <div class="admin-content">

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
    const oficios = document.getElementById("oficiosSubmenu");
    const aclaraciones = document.getElementById("aclaracionesSubmenu");

    aclaraciones.classList.remove("open");
    oficios.classList.toggle("open");
}

function toggleAclaraciones() {
    const oficios = document.getElementById("oficiosSubmenu");
    const aclaraciones = document.getElementById("aclaracionesSubmenu");

    oficios.classList.remove("open");
    aclaraciones.classList.toggle("open");
}

function toggleSiAplica() {
    document.getElementById("siAplicaSubmenu").classList.toggle("open");
}
</script>

@endsection