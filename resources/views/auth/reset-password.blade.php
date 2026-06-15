@extends('layouts.auth')

@section('title', 'Restablecer Contraseña')

@section('content')

<div class="login-wrapper">

    <div class="login-card">

        <h3 class="login-title">
            Restablecer Contraseña
        </h3>

        <p style="font-size:14px; margin-bottom:20px;">
            Ingresa tu nueva contraseña para recuperar el acceso al sistema.
        </p>

        {{-- ERRORES --}}
        @if ($errors->any())
            <div class="alert alert-danger text-center">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.store') }}">
            @csrf

            {{-- TOKEN --}}
            <input type="hidden"
                   name="token"
                   value="{{ $request->route('token') }}">

            {{-- CORREO --}}
            <div class="form-group">
                <label>Correo electrónico</label>

                <input type="email"
                       name="email"
                       value="{{ old('email', $request->email) }}"
                       required
                       readonly>
            </div>

            {{-- NUEVA CONTRASEÑA --}}
            <div class="form-group">
                <label>Nueva contraseña</label>

                <input type="password"
                       name="password"
                       required
                       autocomplete="new-password">
            </div>

            {{-- CONFIRMAR CONTRASEÑA --}}
            <div class="form-group">
                <label>Confirmar contraseña</label>

                <input type="password"
                       name="password_confirmation"
                       required
                       autocomplete="new-password">
            </div>

            <button type="submit" class="login-btn">
                Restablecer contraseña
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