@extends('layouts.app')

@section('content')

<div class="admin-layout">

    @include('comprador.sidebar')

    <div class="admin-content">

        <div class="conv-wrapper">

            <form
                action="{{ route('publicacion.generar') }}"
                method="POST"
                enctype="multipart/form-data"
                class="conv-form"
                novalidate
            >
                @csrf

                <h2 class="conv-title">
                    Formulario de Publicación
                </h2>

                {{-- ========================================== --}}
                {{-- ALERTAS GENERALES --}}
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
                        <strong>
                            No fue posible generar el documento.
                        </strong>

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
                        >

                        @error('archivo_word')
                            <span class="field-error">
                                {{ $message }}
                            </span>
                        @enderror

                    </div>

                </div>

                {{-- ========================================== --}}
                {{-- DATOS DE PUBLICACIÓN --}}
                {{-- ========================================== --}}

                <div class="conv-card">

                    <h3>Datos de publicación</h3>

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
                            >

                            @error('nombre_procedimiento')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        {{-- FECHA DE PUBLICACIÓN --}}
                        <div class="conv-group">

                            <label for="fecha_publicacion">
                                Fecha de publicación
                            </label>

                            <input
                                type="date"
                                id="fecha_publicacion"
                                name="fecha_publicacion"
                                value="{{ old('fecha_publicacion') }}"
                                class="@error('fecha_publicacion') input-error @enderror"
                            >

                            @error('fecha_publicacion')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        {{-- REVISÓ --}}
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const inputBusqueda =
        document.getElementById('busqueda_proc');

    const inputNum =
        document.getElementById('num_procedimiento');

    const inputNombre =
        document.getElementById('nombre_procedimiento');

    const inputFecha =
        document.getElementById('fecha_publicacion');

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
            `/buscar-procedimiento-publicacion/${encodeURIComponent(valor)}`,
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

                    inputNum.value =
                        data.num_procedimiento ?? '';

                    inputNombre.value =
                        data.nombre_procedimiento ?? '';

                    inputFecha.value =
                        data.fecha_publicacion ?? '';

                    mensajeBusqueda.textContent =
                        'Procedimiento encontrado. Puedes modificar los datos antes de generar el Word.';

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

        inputNum.value = '';
        inputNombre.value = '';
        inputFecha.value = '';

    }

    function limpiarMensajeBusqueda() {

        mensajeBusqueda.textContent = '';
        mensajeBusqueda.className = 'search-message';

    }

});
</script>

@endsection
