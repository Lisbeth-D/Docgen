@extends('layouts.app')

@section('title', 'Panel Administrador')

@section('content')

<div class="admin-layout">
    @include('layouts.admin_sidebar')

    {{-- CONTENIDO --}}
    <div class="admin-content">

        {{-- USUARIO --}}
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

        {{-- BIENVENIDA --}}
        <div class="welcome-card">
            <h2>Panel Administrador</h2>
            <p>Bienvenido {{ Auth::user()->username }}, administra el sistema desde el menú lateral.</p>
        </div>

    </div>

</div>

<script>

function toggleUserMenu(){
    document.getElementById("userDropdown").classList.toggle("show");
}

function toggleConfig(){
    document.getElementById("configSubmenu").classList.toggle("open");
}

function toggleReportes(){
    document.getElementById("reportesSubmenu").classList.toggle("open");
}

feather.replace()

</script>



@endsection