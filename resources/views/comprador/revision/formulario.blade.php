@extends('layouts.app')
@section('content')

<div class="admin-layout">

    @include('comprador.sidebar')

    <div class="admin-content">

        <div class="conv-wrapper">

            <h2 class="conv-title">Formulario de Revisión</h2>

            <form action="{{ route('revision.generar') }}" method="POST" enctype="multipart/form-data" class="conv-form">
                @csrf

                <!-- ARCHIVO WORD -->
                <div class="conv-card">
                    <h3>Plantilla Word</h3>

                    <div class="conv-group full">
                        <label>Subir archivo Word (.docx)</label>
                        <input type="file" name="archivo_word" accept=".docx" required>
                    </div>
                </div>

                <!-- DATOS GENERALES -->
                <div class="conv-card">
                    <h3>Datos generales</h3>

                    <div class="conv-grid">

                        <div class="conv-group">
                            <label>Número de referencia</label>
                            <input type="text" name="numero_referencia" required>
                        </div>

                        <div class="conv-group">
                            <label>Fecha oficio</label>
                            <input type="date" name="fecha_oficio" required>
                        </div>

                        <div class="conv-group">
                            <label>Número de procedimiento (solo número)</label>
                            <input type="text" name="numero_busqueda" placeholder="Ej: 25" required>
                        </div>

                    </div>
                </div>

                <!-- PERSONAS -->
                <div class="conv-card">
                    <h3>Responsables</h3>

                    <div class="conv-grid">

                        <div class="conv-group">
                            <label>Revisó</label>
                            <select name="reviso" required>
                                <option value="">Seleccionar persona</option>

                                @foreach($personas as $persona)
                                    <option value="{{ $persona->id }}">
                                        {{ $persona->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="conv-group">
                            <label>Elaboró</label>
                            <input type="text" value="{{ auth()->user()->name }}" readonly>
                        </div>

                    </div>
                </div>

                <button type="submit" class="conv-btn">
                    Generar y descargar Word
                </button>

            </form>

        </div>

    </div>

</div>

@endsection