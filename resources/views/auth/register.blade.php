@extends('layouts.auth')

@section('title', 'Registrarse')

@section('content')

<div class="auth-card">

    <h2>Crear Cuenta</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="form-group">
            <label>Usuario</label>
            <input type="text"
                   name="username"
                   value="{{ old('username') }}"
                   required>
        </div>

        <div class="form-group">
            <label>Nombre</label>
            <input type="text"
                   name="name"
                   value="{{ old('name') }}"
                   required>
        </div>

        <div class="form-group">
            <label>Correo electrónico</label>
            <input type="email"
                   name="email"
                   value="{{ old('email') }}"
                   required>
        </div>

        <div class="form-group">
            <label>Contraseña</label>
            <input type="password"
                   name="password"
                   required>
        </div>

        <div class="form-group">
            <label>Confirmar contraseña</label>
            <input type="password"
                   name="password_confirmation"
                   required>
        </div>

        <button type="submit" class="btn-login">
            Registrarse
        </button>

        <div class="auth-links">
            <a href="{{ route('login') }}">
                ¿Ya tienes cuenta? Inicia sesión
            </a>
        </div>

    </form>

</div>

@endsection