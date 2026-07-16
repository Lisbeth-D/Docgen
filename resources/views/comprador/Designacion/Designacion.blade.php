@extends('layouts.app')

@section('content')

<div class="admin-layout">

    @include('comprador.sidebar')

    <div class="admin-content">

        <div class="conv-wrapper">

            <form
                action="{{ route('designacion.generar') }}"
                method="POST"
                enctype="multipart/form-data"
                class="conv-form"
                novalidate
            >
                @csrf

                <h2 class="conv-title">Formulario de Designación</h2>

                {{-- ========================================== --}}
                {{-- MENSAJES GENERALES --}}
                {{-- ========================================== --}}

                @if (session('error'))
                    <div class="form-alert form-alert-danger">
                        <strong>Error:</strong>
                        {{ session('error') }}
                    </div>
                @endif

                @if (session('success'))
                    <div class="form-alert form-alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="form-alert form-alert-danger">
                        <strong>No fue posible generar el documento.</strong>

                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- ========================================== --}}
                {{-- PLANTILLA WORD --}}
                {{-- ========================================== --}}

                <div class="conv-card">

                    <h3>Plantilla Word</h3>

                    <div class="conv-group full">

                        <label for="archivo_word">
                            Subir archivo Word (.docx)
                        </label>

                        <input
                            type="file"
                            id="archivo_word"
                            name="archivo_word"
                            accept=".docx"
                            class="@error('archivo_word') input-error @enderror"
                            required
                        >

                        @error('archivo_word')
                            <span class="field-error">
                                {{ $message }}
                            </span>
                        @enderror

                    </div>

                </div>

                {{-- ========================================== --}}
                {{-- DATOS DE DESIGNACIÓN --}}
                {{-- ========================================== --}}

                <div class="conv-card">

                    <h3>Datos de designación</h3>

                    <div class="conv-grid">

                        {{-- NÚMERO DE REFERENCIA --}}
                        <div class="conv-group">

                            <label for="numero_referencia">
                                Número de referencia
                            </label>

                            <input
                                type="text"
                                id="numero_referencia"
                                name="numero_referencia"
                                value="{{ old('numero_referencia') }}"
                                class="@error('numero_referencia') input-error @enderror"
                                required
                            >

                            @error('numero_referencia')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        {{-- FECHA DEL OFICIO --}}
                        <div class="conv-group">

                            <label for="fecha_oficio">
                                Fecha oficio
                            </label>

                            <input
                                type="date"
                                id="fecha_oficio"
                                name="fecha_oficio"
                                value="{{ old('fecha_oficio') }}"
                                class="@error('fecha_oficio') input-error @enderror"
                                required
                            >

                            @error('fecha_oficio')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        {{-- BÚSQUEDA DEL PROCEDIMIENTO --}}
                        <div class="conv-group">

                            <label for="busqueda_proc">
                                Buscar procedimiento
                            </label>

                            <input
                                type="text"
                                id="busqueda_proc"
                                name="numero_busqueda"
                                value="{{ old('numero_busqueda') }}"
                                placeholder="Ejemplo: 25"
                                autocomplete="off"
                                class="@error('numero_busqueda') input-error @enderror"
                                required
                            >

                            @error('numero_busqueda')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                            <span
                                id="mensaje_busqueda"
                                class="search-message"
                            ></span>

                        </div>

                        {{-- NÚMERO DEL PROCEDIMIENTO --}}
                        <div class="conv-group">

                            <label for="num_procedimiento">
                                Número procedimiento
                            </label>

                            <input
                                type="text"
                                id="num_procedimiento"
                                name="num_procedimiento"
                                value="{{ old('num_procedimiento') }}"
                                class="@error('num_procedimiento') input-error @enderror"
                                readonly
                                required
                            >

                            @error('num_procedimiento')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        {{-- NOMBRE DEL PROCEDIMIENTO --}}
                        <div class="conv-group">

                            <label for="nombre_procedimiento">
                                Nombre procedimiento
                            </label>

                            <input
                                type="text"
                                id="nombre_procedimiento"
                                name="nombre_procedimiento"
                                value="{{ old('nombre_procedimiento') }}"
                                class="@error('nombre_procedimiento') input-error @enderror"
                                readonly
                                required
                            >

                            @error('nombre_procedimiento')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        {{-- FECHA DE VISITA O MUESTRA --}}
                        <div class="conv-group">

                            <label for="fecha_vm">
                                Fecha visita/muestra
                            </label>

                            <input
                                type="date"
                                id="fecha_vm"
                                name="fecha_vm"
                                value="{{ old('fecha_vm') }}"
                                class="@error('fecha_vm') input-error @enderror"
                            >

                            @error('fecha_vm')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        {{-- HORA DE VISITA O MUESTRA --}}
                        <div class="conv-group">

                            <label for="hora_vm">
                                Hora visita/muestra
                            </label>

                            <input
                                type="time"
                                id="hora_vm"
                                name="hora_vm"
                                value="{{ old('hora_vm') }}"
                                class="@error('hora_vm') input-error @enderror"
                            >

                            @error('hora_vm')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        {{-- FECHA DE ACLARACIONES --}}
                        <div class="conv-group">

                            <label for="fecha_ac">
                                Fecha Junta de Aclaraciones
                            </label>

                            <input
                                type="date"
                                id="fecha_ac"
                                name="fecha_ac"
                                value="{{ old('fecha_ac') }}"
                                class="@error('fecha_ac') input-error @enderror"
                            >

                            @error('fecha_ac')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        {{-- HORA DE ACLARACIONES --}}
                        <div class="conv-group">

                            <label for="hora_ac">
                                Hora Junta de Aclaraciones
                            </label>

                            <input
                                type="time"
                                id="hora_ac"
                                name="hora_ac"
                                value="{{ old('hora_ac') }}"
                                class="@error('hora_ac') input-error @enderror"
                            >

                            @error('hora_ac')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        {{-- FECHA DE APERTURA --}}
                        <div class="conv-group">

                            <label for="fecha_apertura">
                                Fecha Junta de Apertura
                            </label>

                            <input
                                type="date"
                                id="fecha_apertura"
                                name="fecha_apertura"
                                value="{{ old('fecha_apertura') }}"
                                class="@error('fecha_apertura') input-error @enderror"
                            >

                            @error('fecha_apertura')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        {{-- HORA DE APERTURA --}}
                        <div class="conv-group">

                            <label for="hora_apertura">
                                Hora Junta de Apertura
                            </label>

                            <input
                                type="time"
                                id="hora_apertura"
                                name="hora_apertura"
                                value="{{ old('hora_apertura') }}"
                                class="@error('hora_apertura') input-error @enderror"
                            >

                            @error('hora_apertura')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        {{-- FECHA DEL FALLO --}}
                        <div class="conv-group">

                            <label for="fecha_fallo">
                                Fecha Junta de Fallo
                            </label>

                            <input
                                type="date"
                                id="fecha_fallo"
                                name="fecha_fallo"
                                value="{{ old('fecha_fallo') }}"
                                class="@error('fecha_fallo') input-error @enderror"
                            >

                            @error('fecha_fallo')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        {{-- HORA DEL FALLO --}}
                        <div class="conv-group">

                            <label for="hora_fallo">
                                Hora Junta de Fallo
                            </label>

                            <input
                                type="time"
                                id="hora_fallo"
                                name="hora_fallo"
                                value="{{ old('hora_fallo') }}"
                                class="@error('hora_fallo') input-error @enderror"
                            >

                            @error('hora_fallo')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        {{-- PERSONA QUE REVISÓ --}}
                        <div class="conv-group">

                            <label for="reviso_id">
                                Revisó
                            </label>

                            <select
                                id="reviso_id"
                                name="reviso_id"
                                class="@error('reviso_id') input-error @enderror"
                            >
                                <option value="">
                                    Seleccionar
                                </option>

                                @foreach ($personas as $persona)
                                    <option
                                        value="{{ $persona->id }}"
                                        @selected(
                                            old('reviso_id') == $persona->id
                                        )
                                    >
                                        {{ $persona->nombre }}
                                    </option>
                                @endforeach

                            </select>

                            @error('reviso_id')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                    </div>

                </div>

                <button
                    type="submit"
                    class="conv-btn"
                    id="btnGenerar"
                >
                    Generar Word
                </button>

            </form>

        </div>

    </div>

</div>

<style>
    .form-alert {
        width: 100%;
        box-sizing: border-box;
        margin-bottom: 20px;
        padding: 14px 18px;
        border-radius: 8px;
        font-size: 14px;
        line-height: 1.5;
    }

    .form-alert ul {
        margin: 8px 0 0 20px;
        padding: 0;
    }

    .form-alert-danger {
        color: #842029;
        background-color: #f8d7da;
        border: 1px solid #f5c2c7;
    }

    .form-alert-success {
        color: #0f5132;
        background-color: #d1e7dd;
        border: 1px solid #badbcc;
    }

    .field-error {
        display: block;
        margin-top: 5px;
        color: #b42318;
        font-size: 13px;
        font-weight: 500;
    }

    .input-error {
        border: 1px solid #b42318 !important;
        outline-color: #b42318 !important;
    }

    .search-message {
        display: block;
        min-height: 18px;
        margin-top: 5px;
        font-size: 13px;
    }

    .search-message.success {
        color: #067647;
    }

    .search-message.error {
        color: #b42318;
    }

    input[readonly] {
        background-color: #f3f4f6;
        cursor: not-allowed;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const inputBusqueda = document.getElementById('busqueda_proc');

    const inputNumProcedimiento =
        document.getElementById('num_procedimiento');

    const inputNombreProcedimiento =
        document.getElementById('nombre_procedimiento');

    const inputFechaVm =
        document.getElementById('fecha_vm');

    const inputHoraVm =
        document.getElementById('hora_vm');

    const inputFechaAc =
        document.getElementById('fecha_ac');

    const inputHoraAc =
        document.getElementById('hora_ac');

    const inputFechaApertura =
        document.getElementById('fecha_apertura');

    const inputHoraApertura =
        document.getElementById('hora_apertura');

    const inputFechaFallo =
        document.getElementById('fecha_fallo');

    const inputHoraFallo =
        document.getElementById('hora_fallo');

    const mensajeBusqueda =
        document.getElementById('mensaje_busqueda');

    let temporizadorBusqueda = null;

    inputBusqueda.addEventListener('input', function () {

        const valor = this.value.trim();

        clearTimeout(temporizadorBusqueda);

        if (valor === '') {
            limpiarCampos();
            limpiarMensajeBusqueda();
            return;
        }

        mensajeBusqueda.textContent =
            'Buscando procedimiento...';

        mensajeBusqueda.className =
            'search-message';

        temporizadorBusqueda = setTimeout(function () {
            buscarProcedimiento(valor);
        }, 350);

    });

    function buscarProcedimiento(valor) {

        fetch(
            `/buscar-procedimiento-designacion/${encodeURIComponent(valor)}`,
            {
                headers: {
                    'Accept': 'application/json'
                }
            }
        )
            .then(function (response) {

                if (!response.ok) {
                    throw new Error(
                        'Error al consultar el procedimiento.'
                    );
                }

                return response.json();

            })
            .then(function (data) {

                if (data && data.num_procedimiento) {

                    inputNumProcedimiento.value =
                        data.num_procedimiento ?? '';

                    inputNombreProcedimiento.value =
                        data.nombre_procedimiento ?? '';

                    inputFechaVm.value =
                        data.fecha_vm ?? '';

                    inputHoraVm.value =
                        data.hora_vm ?? '';

                    inputFechaAc.value =
                        data.fecha_ac ?? '';

                    inputHoraAc.value =
                        data.hora_ac ?? '';

                    inputFechaApertura.value =
                        data.fecha_apertura ?? '';

                    inputHoraApertura.value =
                        data.hora_apertura ?? '';

                    inputFechaFallo.value =
                        data.fecha_fallo ?? '';

                    inputHoraFallo.value =
                        data.hora_fallo ?? '';

                    mensajeBusqueda.textContent =
                        'Procedimiento encontrado.';

                    mensajeBusqueda.className =
                        'search-message success';

                } else {

                    limpiarCampos();

                    mensajeBusqueda.textContent =
                        'No se encontró un procedimiento con ese número.';

                    mensajeBusqueda.className =
                        'search-message error';
                }

            })
            .catch(function (error) {

                console.error(
                    'Error al buscar procedimiento:',
                    error
                );

                limpiarCampos();

                mensajeBusqueda.textContent =
                    'No fue posible realizar la búsqueda.';

                mensajeBusqueda.className =
                    'search-message error';

            });

    }

    function limpiarCampos() {

        inputNumProcedimiento.value = '';
        inputNombreProcedimiento.value = '';

        inputFechaVm.value = '';
        inputHoraVm.value = '';

        inputFechaAc.value = '';
        inputHoraAc.value = '';

        inputFechaApertura.value = '';
        inputHoraApertura.value = '';

        inputFechaFallo.value = '';
        inputHoraFallo.value = '';

    }

    function limpiarMensajeBusqueda() {

        mensajeBusqueda.textContent = '';
        mensajeBusqueda.className = 'search-message';

    }

});
</script>

@endsection
