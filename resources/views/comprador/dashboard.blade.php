@extends('layouts.app')

@section('content')

<div class="admin-layout">

    @include('comprador.sidebar')

    <div class="admin-content">
        <div class="welcome-card">
            <h2>Bienvenido, {{ Auth::user()->username }}</h2>
            <p>Selecciona una opción del menú lateral para continuar.</p>
        </div>
    </div>

</div>

@endsection