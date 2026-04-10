@extends('layouts.auth')

@section('title', 'Recuperar Contraseña')

@section('content')

<div class="login-wrapper">

    <div class="login-card">

        <h3 class="login-title">¿Olvidaste tu contraseña?</h3>

        <p style="font-size:14px; margin-bottom:20px;">
            No hay problema. Ingresa tu correo electrónico y te enviaremos un enlace
            para restablecer tu contraseña.
        </p>

        {{-- MENSAJE DE ÉXITO --}}
        @if (session('status'))
            <div class="alert alert-success text-center">
                {{ session('status') }}
            </div>
        @endif

        {{-- ERRORES --}}
        @if ($errors->any())
            <div class="alert alert-danger text-center">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            {{-- EMAIL --}}
            <div class="form-group">
                <label>Correo electrónico</label>
                <input type="email"
                       name="email"
                       value="{{ old('email') }}"
                       required
                       autofocus>
            </div>

            <button type="submit" class="login-btn">
                Enviar enlace de recuperación
            </button>

            <div class="extra-links">
                <a href="{{ route('login') }}">
                    Volver al inicio de sesión
                </a>
            </div>

        </form>

    </div>

</div>

@endsection