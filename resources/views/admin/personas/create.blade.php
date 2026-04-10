@extends('layouts.app')

@section('title','Registrar Persona')

@section('content')

<div class="admin-layout">

    {{-- SIDEBAR --}}
    @include('layouts.admin_sidebar')

    <div class="admin-content">

        <div class="form-wrapper">

            <div class="form-card">

                <h2>Registrar nueva persona</h2>
                <p class="form-subtitle">
                    Ingresa la información de la persona que estará disponible para los compradores.
                </p>

                <form action="/personas" method="POST">
                    @csrf

                    <div class="form-grid">

                        {{-- NOMBRE --}}
                        <div class="form-group">
                            <label>Nombre completo</label>
                            <input 
                                type="text"
                                name="nombre"
                                required
                                placeholder="Ej. Juan Pérez">
                        </div>

                        {{-- CARGO --}}
                        <div class="form-group">
                            <label>Cargo</label>
                            <input 
                                type="text"
                                name="cargo"
                                required
                                placeholder="Ej. Director Jurídico">
                        </div>

                        {{-- ÁREA --}}
                        <div class="form-group full">
                            <label>Área</label>
                            <input 
                                type="text"
                                name="area"
                                required
                                placeholder="Ej. Área Administrativa">
                        </div>

                    </div>

                    {{-- BOTONES --}}
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            Guardar persona
                        </button>

                        <a href="/personas" class="btn-cancel">
                            Cancelar
                        </a>
                    </div>

                </form>

            </div>

        </div>

    </div>

</div>

@endsection