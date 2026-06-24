@extends('layouts.app')

@section('title', 'Apertura')

@section('content')

<div class="admin-layout">

    @include('comprador.sidebar')

    <div class="admin-content">

        <div class="conv-wrapper">

            <form class="conv-form">

                <h2 class="conv-title">
                    Acta de Apertura
                </h2>

            <form action="{{ route('apertura.generar') }}"
                  method="POST"
                  enctype="multipart/form-data"
                  class="conv-form">

                @csrf

                {{-- WORD --}}
                <div class="conv-card">
                    <h3>Plantilla Word</h3>

                    <div class="conv-group full">
                        <label>Subir archivo Word (.docx)</label>
                        <input type="file"
                               name="archivo_word"
                               accept=".docx"
                               required>
                    </div>
                </div>

                {{-- DATOS DEL PROCEDIMIENTO --}}
                <div class="conv-card">
                    <h3>Datos del procedimiento</h3>

                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger">
                            Revise los campos obligatorios.
                        </div>
                    @endif

                    <div class="conv-grid">

                        <div class="conv-group">
                            <label>Buscar procedimiento</label>
                            <input type="text"
                                   name="numero_busqueda"
                                   id="numero_busqueda"
                                   value="{{ old('numero_busqueda') }}"
                                   placeholder="Ejemplo: 25"
                                   autocomplete="off"
                                   required>
                        </div>

                        <div class="conv-group">
                            <label>Número procedimiento</label>
                            <input type="text"
                                   id="num_procedimiento"
                                   name="num_procedimiento"
                                   value="{{ old('num_procedimiento') }}"
                                   readonly>
                        </div>

                        <div class="conv-group full">
                            <label>Nombre procedimiento</label>
                            <input type="text"
                                   id="nombre_procedimiento"
                                   name="nombre_procedimiento"
                                   value="{{ old('nombre_procedimiento') }}"
                                   readonly>
                        </div>

                        <div class="conv-group">
                            <label>Fecha Apertura</label>
                            <input type="date"
                                   id="fecha_apertura"
                                   name="fecha_apertura"
                                   value="{{ old('fecha_apertura') }}">
                        </div>

                        <div class="conv-group">
                            <label>Hora Apertura</label>
                            <input type="time"
                                   id="hora_apertura"
                                   name="hora_apertura"
                                   value="{{ old('hora_apertura') }}">
                        </div>

                        <div class="conv-group">
                            <label>Fecha Fallo</label>
                            <input type="date"
                                   id="fecha_fallo"
                                   name="fecha_fallo"
                                   value="{{ old('fecha_fallo') }}">
                        </div>

                        <div class="conv-group">
                            <label>Hora Fallo</label>
                            <input type="time"
                                   id="hora_fallo"
                                   name="hora_fallo"
                                   value="{{ old('hora_fallo') }}">
                        </div>

                    </div>
                </div>

                {{-- PERSONAS --}}
                <div class="conv-card">
                    <h3>Participantes</h3>

                    <div class="conv-grid">

                        <div class="conv-group">
                            <label>Área contratante</label>
                            <select name="area_contratante" required>
                                <option value="">Seleccionar</option>

                                @foreach($areasContratantes as $persona)
                                    <option value="{{ $persona->id }}"
                                        {{ old('area_contratante') == $persona->id ? 'selected' : '' }}>
                                        {{ $persona->nombre }} - {{ $persona->cargo }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="conv-group">
                            <label>Encargado contrato</label>
                            <select name="encargado_contrato" required>
                                <option value="">Seleccionar</option>

                                @foreach($encargadosContrato as $persona)
                                    <option value="{{ $persona->id }}"
                                        {{ old('encargado_contrato') == $persona->id ? 'selected' : '' }}>
                                        {{ $persona->nombre }} - {{ $persona->cargo }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="conv-group full">
                            <label>Área requirente</label>
                            <select name="area_requirente" required>
                                <option value="">Seleccionar</option>

                                @foreach($areasRequirentes as $persona)
                                    <option value="{{ $persona->id }}"
                                        {{ old('area_requirente') == $persona->id ? 'selected' : '' }}>
                                        {{ $persona->nombre }} - {{ $persona->cargo }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="conv-group">
                            <label>Persona Jurídico</label>
                            <select name="persona_juridico" required>
                                <option value="">Seleccionar</option>

                                @foreach($personasJuridico as $persona)
                                    <option value="{{ $persona->id }}"
                                        {{ old('persona_juridico') == $persona->id ? 'selected' : '' }}>
                                        {{ $persona->nombre }} - {{ $persona->cargo }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="conv-group">
                            <label>Persona OIC</label>
                            <select name="persona_oic" required>
                                <option value="">Seleccionar</option>

                                @foreach($personasOic as $persona)
                                    <option value="{{ $persona->id }}"
                                        {{ old('persona_oic') == $persona->id ? 'selected' : '' }}>
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

    const inputBusqueda = document.getElementById('numero_busqueda');

    const inputNumProcedimiento = document.getElementById('num_procedimiento');
    const inputNombreProcedimiento = document.getElementById('nombre_procedimiento');

    const inputFechaApertura = document.getElementById('fecha_apertura');
    const inputHoraApertura = document.getElementById('hora_apertura');

    const inputFechaFallo = document.getElementById('fecha_fallo');
    const inputHoraFallo = document.getElementById('hora_fallo');

    inputBusqueda.addEventListener('keyup', function () {

        const valor = this.value.trim();

        if (valor === '') {
            limpiarCampos();
            return;
        }

        fetch("{{ url('/apertura/buscar') }}/" + encodeURIComponent(valor), {
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => {

            if (!response.ok) {
                throw new Error('Error en la búsqueda del procedimiento');
            }

            return response.json();
        })
        .then(data => {

            if (data && data.num_procedimiento) {
                inputNumProcedimiento.value = data.num_procedimiento ?? '';
                inputNombreProcedimiento.value = data.nombre_procedimiento ?? '';

                inputFechaApertura.value = data.fecha_apertura ?? '';
                inputHoraApertura.value = data.hora_apertura ?? '';

                inputFechaFallo.value = data.fecha_fallo ?? '';
                inputHoraFallo.value = data.hora_fallo ?? '';
            } else {
                limpiarCampos();
            }

        })
        .catch(error => {
            console.error('Error al buscar procedimiento:', error);
            limpiarCampos();
        });

    });

    function limpiarCampos() {
        inputNumProcedimiento.value = '';
        inputNombreProcedimiento.value = '';

        inputFechaApertura.value = '';
        inputHoraApertura.value = '';

        inputFechaFallo.value = '';
        inputHoraFallo.value = '';
    }

});
</script>

@endsection