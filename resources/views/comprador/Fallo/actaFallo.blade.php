@extends('layouts.app')

@section('title', 'Acta de Fallo')

@section('content')

<div class="admin-layout">

    @include('comprador.sidebar')

    <div class="admin-content">

        <div class="conv-wrapper">

            <form action="{{ route('fallo.acta.generar') }}"
                  method="POST"
                  enctype="multipart/form-data"
                  class="conv-form">

                @csrf

                <h2 class="conv-title">Acta de Fallo</h2>

                {{-- PLANTILLA WORD --}}
                <div class="conv-card">

                    <h3>Plantilla Word</h3>

                    <div class="conv-group full">
                        <label>Subir archivo Word (.docx)</label>
                        <input
                            type="file"
                            name="archivo_word"
                            accept=".docx"
                            required>
                    </div>

                </div>

                {{-- DATOS DEL PROCEDIMIENTO --}}
                <div class="conv-card">

                    <h3>Procedimiento</h3>

                    <div class="conv-grid">

                        <div class="conv-group">
                            <label>Buscar procedimiento</label>
                            <input
                                type="text"
                                id="numero_busqueda"
                                name="numero_busqueda"
                                placeholder="Ejemplo: 25"
                                autocomplete="off"
                                required>
                        </div>

                        <div class="conv-group">
                            <label>Número procedimiento</label>
                            <input
                                type="text"
                                id="num_procedimiento"
                                name="num_procedimiento"
                                readonly>
                        </div>

                        <div class="conv-group full">
                            <label>Nombre procedimiento</label>
                            <input
                                type="text"
                                id="nombre_procedimiento"
                                name="nombre_procedimiento"
                                readonly>
                        </div>

                        <div class="conv-group">
                            <label>Fecha de fallo</label>
                            <input
                                type="date"
                                id="fecha_fallo"
                                name="fecha_fallo">
                        </div>

                        <div class="conv-group">
                            <label>Hora de fallo</label>
                            <input
                                type="time"
                                id="hora_fallo"
                                name="hora_fallo">
                        </div>

                    </div>

                </div>

                {{-- PARTICIPANTES --}}
                <div class="conv-card">

                    <h3>Participantes</h3>

                    <div class="conv-grid">

                        <div class="conv-group">
                            <label>Área contratante</label>

                            <select name="area_contratante" required>

                                <option value="">Seleccionar...</option>

                                @foreach($areasContratantes as $persona)

                                    <option value="{{ $persona->id }}">
                                        {{ $persona->nombre }} - {{ $persona->cargo }}
                                    </option>

                                @endforeach

                            </select>

                        </div>

                        <div class="conv-group">
                            <label>Encargado del contrato</label>

                            <select name="encargado_contrato" required>

                                <option value="">Seleccionar...</option>

                                @foreach($encargadosContrato as $persona)

                                    <option value="{{ $persona->id }}">
                                        {{ $persona->nombre }} - {{ $persona->cargo }}
                                    </option>

                                @endforeach

                            </select>

                        </div>

                        <div class="conv-group full">
                            <label>Área requirente</label>

                            <select name="area_requirente" required>

                                <option value="">Seleccionar...</option>

                                @foreach($areasRequirentes as $persona)

                                    <option value="{{ $persona->id }}">
                                        {{ $persona->nombre }} - {{ $persona->cargo }}
                                    </option>

                                @endforeach

                            </select>

                        </div>

                        <div class="conv-group">
                            <label>Persona OIC</label>

                            <select name="persona_oic" required>

                                <option value="">Seleccionar...</option>

                                @foreach($personasOic as $persona)

                                    <option value="{{ $persona->id }}">
                                        {{ $persona->nombre }} - {{ $persona->cargo }}
                                    </option>

                                @endforeach

                            </select>

                        </div>

                        <div class="conv-group">
                            <label>Persona Jurídico</label>

                            <select name="persona_juridico" required>

                                <option value="">Seleccionar...</option>

                                @foreach($personasJuridico as $persona)

                                    <option value="{{ $persona->id }}">
                                        {{ $persona->nombre }} - {{ $persona->cargo }}
                                    </option>

                                @endforeach

                            </select>

                        </div>

                    </div>

                </div>

                <button type="submit" class="conv-btn">
                    Generar Word
                </button>

            </form>

        </div>

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const buscar = document.getElementById('numero_busqueda');

    buscar.addEventListener('keyup', function () {

        const valor = this.value.trim();

        if (valor === '') {
            limpiar();
            return;
        }

        fetch(`/fallo/buscar/${encodeURIComponent(valor)}`)
            .then(response => response.json())
            .then(data => {

                if (!data) {
                    limpiar();
                    return;
                }

                document.getElementById('num_procedimiento').value =
                    data.num_procedimiento ?? '';

                document.getElementById('nombre_procedimiento').value =
                    data.nombre_procedimiento ?? '';

                document.getElementById('fecha_fallo').value =
                    data.fecha_fallo ?? '';

                document.getElementById('hora_fallo').value =
                    data.hora_fallo ?? '';

            })
            .catch(() => limpiar());

    });

    function limpiar() {

        document.getElementById('num_procedimiento').value = '';
        document.getElementById('nombre_procedimiento').value = '';
        document.getElementById('fecha_fallo').value = '';
        document.getElementById('hora_fallo').value = '';

    }

});
</script>

@endsection

