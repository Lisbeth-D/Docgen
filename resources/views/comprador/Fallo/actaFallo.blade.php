@extends('layouts.app')

@section('title', 'Acta de Fallo')

@section('content')

@php
    /*
    |--------------------------------------------------------------------------
    | COLECCIONES SEGURAS
    |--------------------------------------------------------------------------
    | Evitan errores en caso de que alguna colección llegue vacía.
    */

    $areasContratantes = $areasContratantes ?? collect();
    $encargadosContrato = $encargadosContrato ?? collect();
    $areasRequirentes = $areasRequirentes ?? collect();
    $personasOic = $personasOic ?? collect();
    $personasJuridico = $personasJuridico ?? collect();
@endphp

<div class="admin-layout">

    @include('comprador.sidebar')

    <div class="admin-content">

        <div class="conv-wrapper">

            <form action="{{ route('fallo.acta.generar') }}"
                  method="POST"
                  enctype="multipart/form-data"
                  class="conv-form">

                @csrf

                <h2 class="conv-title">
                    Acta de Fallo
                </h2>

                {{-- ======================================== --}}
                {{-- MENSAJES --}}
                {{-- ======================================== --}}

                @if(session('error'))

                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>

                @endif

                @if($errors->any())

                    <div class="alert alert-danger">

                        <strong>
                            Revise los campos del formulario:
                        </strong>

                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>

                    </div>

                @endif

                {{-- ======================================== --}}
                {{-- PLANTILLA WORD --}}
                {{-- ======================================== --}}

                <div class="conv-card">

                    <h3>Plantilla Word</h3>

                    <div class="conv-group full">

                        <label for="archivo_word">
                            Subir archivo Word (.docx)
                        </label>

                        <input type="file"
                               id="archivo_word"
                               name="archivo_word"
                               accept=".docx"
                               required>

                        @error('archivo_word')
                            <span class="field-error">
                                {{ $message }}
                            </span>
                        @enderror

                    </div>

                </div>

                {{-- ======================================== --}}
                {{-- DATOS DEL PROCEDIMIENTO --}}
                {{-- ======================================== --}}

                <div class="conv-card">

                    <h3>Datos del procedimiento</h3>

                    <div class="conv-grid">

                        {{-- BÚSQUEDA --}}

                        <div class="conv-group">

                            <label for="numero_busqueda">
                                Buscar procedimiento
                            </label>

                            <input type="text"
                                   id="numero_busqueda"
                                   name="numero_busqueda"
                                   value="{{ old('numero_busqueda') }}"
                                   placeholder="Ejemplo: 25"
                                   autocomplete="off"
                                   required>

                            @error('numero_busqueda')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        {{-- NÚMERO DEL PROCEDIMIENTO --}}

                        <div class="conv-group">

                            <label for="num_procedimiento">
                                Número del procedimiento
                            </label>

                            <input type="text"
                                   id="num_procedimiento"
                                   name="num_procedimiento"
                                   value="{{ old('num_procedimiento') }}"
                                   readonly>

                        </div>

                        {{-- NOMBRE DEL PROCEDIMIENTO --}}

                        <div class="conv-group full">

                            <label for="nombre_procedimiento">
                                Nombre del procedimiento
                            </label>

                            <input type="text"
                                   id="nombre_procedimiento"
                                   name="nombre_procedimiento"
                                   value="{{ old('nombre_procedimiento') }}"
                                   readonly>

                        </div>

                        {{-- FECHA DEL FALLO --}}

                        <div class="conv-group">

                            <label for="fecha_fallo">
                                Fecha del fallo
                            </label>

                            <input type="date"
                                   id="fecha_fallo"
                                   name="fecha_fallo"
                                   value="{{ old('fecha_fallo') }}">

                            @error('fecha_fallo')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        {{-- HORA DEL FALLO --}}

                        <div class="conv-group">

                            <label for="hora_fallo">
                                Hora del fallo
                            </label>

                            <input type="time"
                                   id="hora_fallo"
                                   name="hora_fallo"
                                   value="{{ old('hora_fallo') }}">

                            @error('hora_fallo')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                    </div>

                </div>

                {{-- ======================================== --}}
                {{-- PARTICIPANTES --}}
                {{-- ======================================== --}}

                <div class="conv-card">

                    <h3>Participantes</h3>

                    <div class="conv-grid">

                        {{-- ÁREA CONTRATANTE --}}

                        <div class="conv-group">

                            <label for="area_contratante">
                                Área contratante
                            </label>

                            <select id="area_contratante"
                                    name="area_contratante"
                                    required>

                                <option value="">
                                    Seleccionar
                                </option>

                                @forelse($areasContratantes as $persona)

                                    <option value="{{ $persona->id }}"
                                        {{ (string) old('area_contratante') === (string) $persona->id
                                            ? 'selected'
                                            : '' }}>

                                        {{ $persona->nombre }}

                                        @if(!empty($persona->cargo))
                                            - {{ $persona->cargo }}
                                        @endif

                                    </option>

                                @empty

                                    <option value="" disabled>
                                        No hay personas registradas en Adquisiciones y Servicios
                                    </option>

                                @endforelse

                            </select>

                            @error('area_contratante')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        {{-- ENCARGADO DEL CONTRATO --}}

                        <div class="conv-group">

                            <label for="encargado_contrato">
                                Encargado del contrato
                            </label>

                            <select id="encargado_contrato"
                                    name="encargado_contrato"
                                    required>

                                <option value="">
                                    Seleccionar
                                </option>

                                @forelse($encargadosContrato as $persona)

                                    <option value="{{ $persona->id }}"
                                        {{ (string) old('encargado_contrato') === (string) $persona->id
                                            ? 'selected'
                                            : '' }}>

                                        {{ $persona->nombre }}

                                        @if(!empty($persona->cargo))
                                            - {{ $persona->cargo }}
                                        @endif

                                    </option>

                                @empty

                                    <option value="" disabled>
                                        No hay personas registradas
                                    </option>

                                @endforelse

                            </select>

                            @error('encargado_contrato')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        {{-- ÁREA REQUIRENTE --}}

                        <div class="conv-group full">

                            <label for="area_requirente">
                                Área requirente
                            </label>

                            <select id="area_requirente"
                                    name="area_requirente"
                                    required>

                                <option value="">
                                    Seleccionar
                                </option>

                                @forelse($areasRequirentes as $persona)

                                    <option value="{{ $persona->id }}"
                                        {{ (string) old('area_requirente') === (string) $persona->id
                                            ? 'selected'
                                            : '' }}>

                                        {{ $persona->nombre }}

                                        @if(!empty($persona->cargo))
                                            - {{ $persona->cargo }}
                                        @endif

                                    </option>

                                @empty

                                    <option value="" disabled>
                                        No hay personas disponibles
                                    </option>

                                @endforelse

                            </select>

                            @error('area_requirente')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        {{-- PERSONA DEL OIC --}}

                        <div class="conv-group">

                            <label for="persona_oic">
                                Persona del OIC
                            </label>

                            <select id="persona_oic"
                                    name="persona_oic"
                                    required>

                                <option value="">
                                    Seleccionar
                                </option>

                                @forelse($personasOic as $persona)

                                    <option value="{{ $persona->id }}"
                                        {{ (string) old('persona_oic') === (string) $persona->id
                                            ? 'selected'
                                            : '' }}>

                                        {{ $persona->nombre }}

                                        @if(!empty($persona->cargo))
                                            - {{ $persona->cargo }}
                                        @endif

                                    </option>

                                @empty

                                    <option value="" disabled>
                                        No hay personas registradas en el OIC
                                    </option>

                                @endforelse

                            </select>

                            @error('persona_oic')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        {{-- PERSONA DE JURÍDICO --}}

                        <div class="conv-group">

                            <label for="persona_juridico">
                                Persona de Jurídico
                            </label>

                            <select id="persona_juridico"
                                    name="persona_juridico"
                                    required>

                                <option value="">
                                    Seleccionar
                                </option>

                                @forelse($personasJuridico as $persona)

                                    <option value="{{ $persona->id }}"
                                        {{ (string) old('persona_juridico') === (string) $persona->id
                                            ? 'selected'
                                            : '' }}>

                                        {{ $persona->nombre }}

                                        @if(!empty($persona->cargo))
                                            - {{ $persona->cargo }}
                                        @endif

                                    </option>

                                @empty

                                    <option value="" disabled>
                                        No hay personas registradas en Jurídico
                                    </option>

                                @endforelse

                            </select>

                            @error('persona_juridico')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                    </div>

                </div>

                {{-- ======================================== --}}
                {{-- BOTÓN --}}
                {{-- ======================================== --}}

                <button type="submit"
                        class="conv-btn">

                    Generar Word

                </button>

            </form>

        </div>

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | ELEMENTOS
    |--------------------------------------------------------------------------
    */

    const inputBusqueda =
        document.getElementById('numero_busqueda');

    const inputNumeroProcedimiento =
        document.getElementById('num_procedimiento');

    const inputNombreProcedimiento =
        document.getElementById('nombre_procedimiento');

    const inputFechaFallo =
        document.getElementById('fecha_fallo');

    const inputHoraFallo =
        document.getElementById('hora_fallo');

    let temporizadorBusqueda = null;

    let controladorBusqueda = null;

    /*
    |--------------------------------------------------------------------------
    | BUSCAR PROCEDIMIENTO
    |--------------------------------------------------------------------------
    */

    if (inputBusqueda) {

        inputBusqueda.addEventListener('input', function () {

            const valor = this.value.trim();

            clearTimeout(temporizadorBusqueda);

            if (controladorBusqueda) {
                controladorBusqueda.abort();
            }

            if (valor === '') {
                limpiarCampos();
                return;
            }

            temporizadorBusqueda = setTimeout(function () {
                buscarProcedimiento(valor);
            }, 350);

        });

    }

    /*
    |--------------------------------------------------------------------------
    | REALIZAR PETICIÓN
    |--------------------------------------------------------------------------
    */

    function buscarProcedimiento(valor) {

        controladorBusqueda = new AbortController();

        fetch(
            "{{ url('/fallo/buscar') }}/"
            + encodeURIComponent(valor),
            {
                method: 'GET',

                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },

                signal: controladorBusqueda.signal
            }
        )
        .then(function (response) {

            if (!response.ok) {
                throw new Error(
                    'No fue posible buscar el procedimiento.'
                );
            }

            return response.json();

        })
        .then(function (data) {

            /*
             * Evita que una petición anterior sobrescriba
             * una búsqueda más reciente.
             */

            if (inputBusqueda.value.trim() !== valor) {
                return;
            }

            if (data && data.num_procedimiento) {

                inputNumeroProcedimiento.value =
                    data.num_procedimiento ?? '';

                inputNombreProcedimiento.value =
                    data.nombre_procedimiento ?? '';

                inputFechaFallo.value =
                    data.fecha_fallo ?? '';

                inputHoraFallo.value =
                    data.hora_fallo ?? '';

            } else {
                limpiarCampos();
            }

        })
        .catch(function (error) {

            if (error.name === 'AbortError') {
                return;
            }

            console.error(
                'Error al buscar el procedimiento:',
                error
            );

            limpiarCampos();

        });

    }

    /*
    |--------------------------------------------------------------------------
    | LIMPIAR CAMPOS
    |--------------------------------------------------------------------------
    */

    function limpiarCampos() {

        if (inputNumeroProcedimiento) {
            inputNumeroProcedimiento.value = '';
        }

        if (inputNombreProcedimiento) {
            inputNombreProcedimiento.value = '';
        }

        if (inputFechaFallo) {
            inputFechaFallo.value = '';
        }

        if (inputHoraFallo) {
            inputHoraFallo.value = '';
        }

    }

});
</script>

@endsection