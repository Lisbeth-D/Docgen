@extends('layouts.app')

@section('title', 'Apertura')

@section('content')

@php
    /*
    |--------------------------------------------------------------------------
    | COLECCIONES SEGURAS
    |--------------------------------------------------------------------------
    | Evitan errores de variable indefinida mientras se termina de ajustar
    | el controlador.
    */

    $areasContratantes = $areasContratantes ?? collect();
    $areasContrato = $areasContrato ?? collect();
    $areasRequirentes = $areasRequirentes ?? collect();
    $personasJuridico = $personasJuridico ?? collect();
    $personasOic = $personasOic ?? collect();
@endphp

<div class="admin-layout">

    @include('comprador.sidebar')

    <div class="admin-content">

        <div class="conv-wrapper">

            <form action="{{ route('apertura.generar') }}"
                  method="POST"
                  enctype="multipart/form-data"
                  class="conv-form">

                @csrf

                <h2 class="conv-title">
                    Acta de Apertura
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

                        <div class="conv-group">

                            <label for="fecha_apertura">
                                Fecha de apertura
                            </label>

                            <input type="date"
                                   id="fecha_apertura"
                                   name="fecha_apertura"
                                   value="{{ old('fecha_apertura') }}">

                            @error('fecha_apertura')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        <div class="conv-group">

                            <label for="hora_apertura">
                                Hora de apertura
                            </label>

                            <input type="time"
                                   id="hora_apertura"
                                   name="hora_apertura"
                                   value="{{ old('hora_apertura') }}">

                            @error('hora_apertura')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

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

                        {{-- ÁREA DEL ADMINISTRADOR DEL CONTRATO --}}

                        <div class="conv-group">

                            <label for="area_contrato_select">
                                Área del administrador del contrato
                            </label>

                            <select id="area_contrato_select">

                                <option value="">
                                    Seleccionar área
                                </option>

                                @forelse($areasContrato as $area)

                                    <option value="{{ $area->id_area }}">
                                        {{ $area->nombre }}
                                    </option>

                                @empty

                                    <option value="" disabled>
                                        No hay áreas disponibles
                                    </option>

                                @endforelse

                            </select>

                        </div>

                        {{-- ADMINISTRADOR DEL CONTRATO --}}

                        <div class="conv-group">

                            <label for="persona_contrato_select">
                                Administrador o encargado del contrato
                            </label>

                            <select id="persona_contrato_select"
                                    name="encargado_contrato"
                                    required>

                                <option value="">
                                    Primero seleccione un área
                                </option>

                            </select>

                            @error('encargado_contrato')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        {{-- ÁREA REQUIRENTE --}}

                        <div class="conv-group">

                            <label for="area_requirente_select">
                                Área requirente
                            </label>

                            <select id="area_requirente_select">

                                <option value="">
                                    Seleccionar área
                                </option>

                                @forelse($areasRequirentes as $area)

                                    <option value="{{ $area->id_area }}">
                                        {{ $area->nombre }}
                                    </option>

                                @empty

                                    <option value="" disabled>
                                        No hay áreas disponibles
                                    </option>

                                @endforelse

                            </select>

                        </div>

                        {{-- PERSONA DEL ÁREA REQUIRENTE --}}

                        <div class="conv-group">

                            <label for="persona_requirente_select">
                                Persona del área requirente
                            </label>

                            <select id="persona_requirente_select"
                                    name="area_requirente"
                                    required>

                                <option value="">
                                    Primero seleccione un área
                                </option>

                            </select>

                            @error('area_requirente')
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
    | DATOS ENVIADOS DESDE LARAVEL
    |--------------------------------------------------------------------------
    */

    const areasContrato = @json($areasContrato);

    const areasRequirentes = @json($areasRequirentes);

    const oldEncargadoContrato =
        @json(old('encargado_contrato'));

    const oldAreaRequirente =
        @json(old('area_requirente'));

    /*
    |--------------------------------------------------------------------------
    | ELEMENTOS DEL PROCEDIMIENTO
    |--------------------------------------------------------------------------
    */

    const inputBusqueda =
        document.getElementById('numero_busqueda');

    const inputNumProcedimiento =
        document.getElementById('num_procedimiento');

    const inputNombreProcedimiento =
        document.getElementById('nombre_procedimiento');

    const inputFechaApertura =
        document.getElementById('fecha_apertura');

    const inputHoraApertura =
        document.getElementById('hora_apertura');

    const inputFechaFallo =
        document.getElementById('fecha_fallo');

    const inputHoraFallo =
        document.getElementById('hora_fallo');

    let temporizadorBusqueda = null;
    let controladorBusqueda = null;

    /*
    |--------------------------------------------------------------------------
    | BÚSQUEDA DEL PROCEDIMIENTO
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
                limpiarProcedimiento();
                return;
            }

            temporizadorBusqueda = setTimeout(function () {
                buscarProcedimiento(valor);
            }, 350);

        });

    }

    function buscarProcedimiento(valor) {

        controladorBusqueda = new AbortController();

        fetch(
            "{{ url('/apertura/buscar') }}/"
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

            if (inputBusqueda.value.trim() !== valor) {
                return;
            }

            if (data && data.num_procedimiento) {

                inputNumProcedimiento.value =
                    data.num_procedimiento ?? '';

                inputNombreProcedimiento.value =
                    data.nombre_procedimiento ?? '';

                inputFechaApertura.value =
                    data.fecha_apertura ?? '';

                inputHoraApertura.value =
                    data.hora_apertura ?? '';

                inputFechaFallo.value =
                    data.fecha_fallo ?? '';

                inputHoraFallo.value =
                    data.hora_fallo ?? '';

            } else {
                limpiarProcedimiento();
            }

        })
        .catch(function (error) {

            if (error.name === 'AbortError') {
                return;
            }

            console.error(
                'Error al buscar procedimiento:',
                error
            );

            limpiarProcedimiento();

        });

    }

    function limpiarProcedimiento() {

        if (inputNumProcedimiento) {
            inputNumProcedimiento.value = '';
        }

        if (inputNombreProcedimiento) {
            inputNombreProcedimiento.value = '';
        }

        if (inputFechaApertura) {
            inputFechaApertura.value = '';
        }

        if (inputHoraApertura) {
            inputHoraApertura.value = '';
        }

        if (inputFechaFallo) {
            inputFechaFallo.value = '';
        }

        if (inputHoraFallo) {
            inputHoraFallo.value = '';
        }

    }

    /*
    |--------------------------------------------------------------------------
    | ADMINISTRADOR DEL CONTRATO
    |--------------------------------------------------------------------------
    */

    const areaContratoSelect =
        document.getElementById('area_contrato_select');

    const personaContratoSelect =
        document.getElementById('persona_contrato_select');

    if (areaContratoSelect && personaContratoSelect) {

        areaContratoSelect.addEventListener('change', function () {

            cargarPersonasContrato(
                this.value,
                null
            );

        });

    }

    function cargarPersonasContrato(
        areaId,
        personaSeleccionada = null
    ) {

        if (!personaContratoSelect) {
            return;
        }

        personaContratoSelect.innerHTML =
            '<option value="">Seleccionar persona</option>';

        if (!areaId) {

            personaContratoSelect.innerHTML =
                '<option value="">Primero seleccione un área</option>';

            return;
        }

        const area = areasContrato.find(function (item) {
            return Number(item.id_area) === Number(areaId);
        });

        if (
            !area
            || !Array.isArray(area.personas)
            || area.personas.length === 0
        ) {

            personaContratoSelect.innerHTML =
                '<option value="">Sin personas registradas</option>';

            return;
        }

        area.personas.forEach(function (persona) {

            const option =
                document.createElement('option');

            option.value = persona.id;

            option.textContent =
                persona.nombre
                + (
                    persona.cargo
                        ? ' - ' + persona.cargo
                        : ''
                );

            if (
                personaSeleccionada
                && Number(persona.id)
                    === Number(personaSeleccionada)
            ) {
                option.selected = true;
            }

            personaContratoSelect.appendChild(option);

        });

    }

    /*
    |--------------------------------------------------------------------------
    | ÁREA REQUIRENTE
    |--------------------------------------------------------------------------
    */

    const areaRequirenteSelect =
        document.getElementById('area_requirente_select');

    const personaRequirenteSelect =
        document.getElementById('persona_requirente_select');

    if (areaRequirenteSelect && personaRequirenteSelect) {

        areaRequirenteSelect.addEventListener('change', function () {

            cargarPersonasRequirentes(
                this.value,
                null
            );

        });

    }

    function cargarPersonasRequirentes(
        areaId,
        personaSeleccionada = null
    ) {

        if (!personaRequirenteSelect) {
            return;
        }

        personaRequirenteSelect.innerHTML =
            '<option value="">Seleccionar persona</option>';

        if (!areaId) {

            personaRequirenteSelect.innerHTML =
                '<option value="">Primero seleccione un área</option>';

            return;
        }

        const area = areasRequirentes.find(function (item) {
            return Number(item.id_area) === Number(areaId);
        });

        if (
            !area
            || !Array.isArray(area.personas)
            || area.personas.length === 0
        ) {

            personaRequirenteSelect.innerHTML =
                '<option value="">Sin personas registradas</option>';

            return;
        }

        area.personas.forEach(function (persona) {

            const option =
                document.createElement('option');

            option.value = persona.id;

            option.textContent =
                persona.nombre
                + (
                    persona.cargo
                        ? ' - ' + persona.cargo
                        : ''
                );

            if (
                personaSeleccionada
                && Number(persona.id)
                    === Number(personaSeleccionada)
            ) {
                option.selected = true;
            }

            personaRequirenteSelect.appendChild(option);

        });

    }

    /*
    |--------------------------------------------------------------------------
    | RECUPERAR VALORES DESPUÉS DE VALIDACIÓN
    |--------------------------------------------------------------------------
    */

    if (oldEncargadoContrato) {

        const areaEncontrada =
            areasContrato.find(function (area) {

                return Array.isArray(area.personas)
                    && area.personas.some(function (persona) {
                        return Number(persona.id)
                            === Number(oldEncargadoContrato);
                    });

            });

        if (areaEncontrada && areaContratoSelect) {

            areaContratoSelect.value =
                areaEncontrada.id_area;

            cargarPersonasContrato(
                areaEncontrada.id_area,
                oldEncargadoContrato
            );

        }

    }

    if (oldAreaRequirente) {

        const areaEncontrada =
            areasRequirentes.find(function (area) {

                return Array.isArray(area.personas)
                    && area.personas.some(function (persona) {
                        return Number(persona.id)
                            === Number(oldAreaRequirente);
                    });

            });

        if (areaEncontrada && areaRequirenteSelect) {

            areaRequirenteSelect.value =
                areaEncontrada.id_area;

            cargarPersonasRequirentes(
                areaEncontrada.id_area,
                oldAreaRequirente
            );

        }

    }

});
</script>

@endsection