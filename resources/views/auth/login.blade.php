@extends('layouts.auth')

@section('title', 'Iniciar Sesión')

@section('content')

<div class="auth-card">

    <h2>Inicio de Sesión</h2>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        {{-- USERNAME --}}
        <div class="form-group">
            <label for="username">Usuario</label>
            <input type="text" name="username" required autofocus>
        </div>

        {{-- PASSWORD --}}
        <div class="form-group">
            <label for="password">Contraseña</label>
            <input id="password" type="password" name="password" required>
        </div>

        {{-- REMEMBER --}}
        <div class="form-remember">
            <label>
                <input type="checkbox" name="remember">
                Recordarme
            </label>
        </div>

        {{-- BUTTON --}}
        <button type="submit" class="btn-login">
            Ingresar
        </button>

        {{-- LINKS --}}
        <div class="auth-links">
            <a href="{{ route('password.request') }}">
                ¿Olvidaste tu contraseña?
            </a>

        </div>

    </form>

</div>

@endsection