@extends('layouts.app')

@section('content')

<div class="admin-layout">

    @include('comprador.sidebar')

    <div class="admin-content">

        <div class="conv-wrapper">

            <form
                action="{{ route('adjudicacion.generar') }}"
                method="POST"
                enctype="multipart/form-data"
                class="conv-form"
                novalidate
            >
                @csrf

                <h2 class="conv-title">
                    Formulario de Adjudicación
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

                        <small class="form-help">
                            Utilice una sola plantilla para contratos abiertos y cerrados.
                            En el Word coloque la etiqueta ${leyenda_monto} donde debe
                            aparecer el texto completo del importe.
                        </small>

                        @error('archivo_word')
                            <span class="field-error">
                                {{ $message }}
                            </span>
                        @enderror

                    </div>

                </div>

                {{-- ========================================== --}}
                {{-- DATOS DEL OFICIO --}}
                {{-- ========================================== --}}

                <div class="conv-card">

                    <h3>Datos del oficio</h3>

                    <div class="conv-grid">

                        <div class="conv-group">

                            <label for="oficio_numero">
                                Número de oficio
                            </label>

                            <input
                                type="text"
                                id="oficio_numero"
                                name="oficio_numero"
                                value="{{ old('oficio_numero') }}"
                                class="@error('oficio_numero') input-error @enderror"
                            >

                            @error('oficio_numero')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        <div class="conv-group">

                            <label for="fecha_oficio">
                                Fecha del oficio
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

                    </div>

                </div>

                {{-- ========================================== --}}
                {{-- DATOS DEL PROVEEDOR --}}
                {{-- ========================================== --}}

                <div class="conv-card">

                    <h3>Proveedor</h3>

                    <div class="conv-grid">

                        <div class="conv-group">

                            <label for="proveedor_razon_social">
                                Razón social
                            </label>

                            <input
                                type="text"
                                id="proveedor_razon_social"
                                name="proveedor_razon_social"
                                value="{{ old('proveedor_razon_social') }}"
                                class="@error('proveedor_razon_social') input-error @enderror"
                            >

                            @error('proveedor_razon_social')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        <div class="conv-group">

                            <label for="proveedor_rfc">
                                RFC
                            </label>

                            <input
                                type="text"
                                id="proveedor_rfc"
                                name="proveedor_rfc"
                                value="{{ old('proveedor_rfc') }}"
                                class="@error('proveedor_rfc') input-error @enderror"
                            >

                            @error('proveedor_rfc')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        <div class="conv-group full">

                            <label for="proveedor_domicilio">
                                Domicilio
                            </label>

                            <input
                                type="text"
                                id="proveedor_domicilio"
                                name="proveedor_domicilio"
                                value="{{ old('proveedor_domicilio') }}"
                                class="@error('proveedor_domicilio') input-error @enderror"
                            >

                            @error('proveedor_domicilio')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        <div class="conv-group">

                            <label for="proveedor_email">
                                Correo electrónico
                            </label>

                            <input
                                type="email"
                                id="proveedor_email"
                                name="proveedor_email"
                                value="{{ old('proveedor_email') }}"
                                class="@error('proveedor_email') input-error @enderror"
                            >

                            @error('proveedor_email')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        <div class="conv-group">

                            <label for="proveedor_telefono">
                                Teléfono
                            </label>

                            <input
                                type="text"
                                id="proveedor_telefono"
                                name="proveedor_telefono"
                                value="{{ old('proveedor_telefono') }}"
                                class="@error('proveedor_telefono') input-error @enderror"
                            >

                            @error('proveedor_telefono')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                    </div>

                </div>

                {{-- ========================================== --}}
                {{-- PROCEDIMIENTO --}}
                {{-- ========================================== --}}

                <div class="conv-card">

                    <h3>Procedimiento</h3>

                    <div class="conv-grid">

                        <div class="conv-group">

                            <label for="busqueda_proc">
                                Buscar procedimiento
                                <span class="optional-text">(Opcional)</span>
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

                            <small class="form-help">
                                Puede buscar un procedimiento registrado o capturar todos los datos manualmente.
                            </small>

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

                        <div class="conv-group">

                            <label for="procedimiento_tipo">
                                Tipo de procedimiento
                            </label>

                            <input
                                type="text"
                                id="procedimiento_tipo"
                                name="procedimiento_tipo"
                                value="{{ old('procedimiento_tipo') }}"
                                class="@error('procedimiento_tipo') input-error @enderror"
                            >

                            @error('procedimiento_tipo')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        <div class="conv-group">

                            <label for="num_procedimiento">
                                Número de procedimiento
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

                        <div class="conv-group">

                            <label for="nombre_procedimiento">
                                Nombre del procedimiento
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

                        <div class="conv-group">

                            <label for="contrato_numero">
                                Número de contrato
                            </label>

                            <input
                                type="text"
                                id="contrato_numero"
                                name="contrato_numero"
                                value="{{ old('contrato_numero') }}"
                                class="@error('contrato_numero') input-error @enderror"
                            >

                            @error('contrato_numero')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                    </div>

                    <p class="form-help">
                        Los datos autocompletados pueden modificarse antes de generar el Word.
                        Los cambios solo se aplicarán al documento generado.
                    </p>

                </div>

                {{-- ========================================== --}}
                {{-- MONTOS --}}
                {{-- ========================================== --}}

                <div class="conv-card">

                    <h3>Tipo de contrato y monto</h3>

                    <div class="conv-grid">

                        <div class="conv-group full">

                            <div class="radio-row">

                                <label class="radio-option">
                                    <input
                                        type="radio"
                                        name="tipo_contrato_monto"
                                        value="cerrado"
                                        @checked(
                                            old('tipo_contrato_monto', 'cerrado') === 'cerrado'
                                        )
                                    >
                                    Contrato cerrado
                                </label>

                                <label class="radio-option">
                                    <input
                                        type="radio"
                                        name="tipo_contrato_monto"
                                        value="abierto"
                                        @checked(
                                            old('tipo_contrato_monto') === 'abierto'
                                        )
                                    >
                                    Contrato abierto
                                </label>

                            </div>

                            @error('tipo_contrato_monto')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        <div
                            class="conv-group"
                            id="grupo_monto_minimo"
                            style="display: none;"
                        >

                            <label for="monto_minimo">
                                Monto mínimo
                            </label>

                            <input
                                type="text"
                                id="monto_minimo"
                                name="monto_minimo"
                                value="{{ old('monto_minimo') }}"
                                inputmode="decimal"
                                placeholder="Ejemplo: 1,350,596.80"
                                class="@error('monto_minimo') input-error @enderror"
                            >

                            <small
                                id="min_letra"
                                class="money-preview"
                            ></small>

                            @error('monto_minimo')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        <div class="conv-group">

                            <label
                                for="monto_maximo"
                                id="label_monto_maximo"
                            >
                                Monto del contrato
                            </label>

                            <input
                                type="text"
                                id="monto_maximo"
                                name="monto_maximo"
                                value="{{ old('monto_maximo') }}"
                                inputmode="decimal"
                                placeholder="Ejemplo: 3,376,492.00"
                                class="@error('monto_maximo') input-error @enderror"
                            >

                            <small
                                id="max_letra"
                                class="money-preview"
                            ></small>

                            <small
                                id="ayuda_monto_maximo"
                                class="form-help"
                            ></small>

                            @error('monto_maximo')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                    </div>

                </div>

                {{-- ========================================== --}}
                {{-- VIGENCIA --}}
                {{-- ========================================== --}}

                <div class="conv-card">

                    <h3>Vigencia</h3>

                    <div class="conv-grid">

                        <div class="conv-group">

                            <label for="fecha_inicio">
                                Fecha de inicio
                            </label>

                            <input
                                type="date"
                                id="fecha_inicio"
                                name="fecha_inicio"
                                value="{{ old('fecha_inicio') }}"
                                class="@error('fecha_inicio') input-error @enderror"
                            >

                            @error('fecha_inicio')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        <div class="conv-group">

                            <label for="fecha_fin">
                                Fecha de término
                            </label>

                            <input
                                type="date"
                                id="fecha_fin"
                                name="fecha_fin"
                                value="{{ old('fecha_fin') }}"
                                class="@error('fecha_fin') input-error @enderror"
                            >

                            @error('fecha_fin')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                    </div>

                </div>

                {{-- ========================================== --}}
                {{-- RESPONSABLES --}}
                {{-- ========================================== --}}

                <div class="conv-card">

                    <h3>Responsables</h3>

                    <div class="conv-grid">

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

                {{-- ========================================== --}}
                {{-- DOCUMENTOS REQUERIDOS --}}
                {{-- ========================================== --}}

                <div class="conv-card">

                    <h3>Documentos requeridos</h3>

                    @php
                        $documentosSeleccionados = array_map(
                            'strval',
                            old('documentos', [])
                        );
                    @endphp

                    <div class="documents-grid">

                        @forelse ($documentos as $documento)

                            <label class="document-option">

                                @if ($documento->obligatorio)
                                    <input
                                        type="hidden"
                                        name="documentos[]"
                                        value="{{ $documento->id_documento }}"
                                    >

                                    <input type="checkbox" checked disabled>
                                @else
                                    <input
                                        type="checkbox"
                                        name="documentos[]"
                                        value="{{ $documento->id_documento }}"
                                        @checked(
                                            in_array(
                                                (string) $documento->id_documento,
                                                $documentosSeleccionados,
                                                true
                                            )
                                        )
                                    >
                                @endif

                                <span>
                                    {{ $documento->nombre }}

                                    @if ($documento->obligatorio)
                                        <small class="required-document">
                                            Obligatorio
                                        </small>
                                    @endif
                                </span>

                            </label>

                        @empty
                            <p class="form-help">
                                No existen documentos activos en el catálogo.
                            </p>
                        @endforelse

                    </div>

                    @error('documentos')
                        <span class="field-error">{{ $message }}</span>
                    @enderror

                    @error('documentos.*')
                        <span class="field-error">{{ $message }}</span>
                    @enderror

                </div>

                <button
                    type="submit"
                    class="conv-btn"
                    id="btn_generar"
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
        font-weight: 600;
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

    .form-help {
        display: block;
        margin: 6px 0 0;
        color: #667085;
        font-size: 13px;
        line-height: 1.5;
    }

    .optional-text {
        color: #667085;
        font-size: 12px;
        font-weight: 400;
    }

    .radio-row {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-top: 8px;
    }

    .radio-option {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        cursor: pointer;
    }

    .radio-option input {
        width: auto;
        margin: 0;
    }

    .money-preview {
        display: block;
        min-height: 18px;
        margin-top: 5px;
        color: #475467;
        font-size: 13px;
    }

    .documents-grid {
        display: grid;
        grid-template-columns: repeat(
            auto-fit,
            minmax(310px, 1fr)
        );
        gap: 10px;
    }

    .document-option {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 11px 12px;
        border: 1px solid #d0d5dd;
        border-radius: 8px;
        cursor: pointer;
        line-height: 1.4;
    }

    .document-option:hover {
        background-color: #f9fafb;
    }

    .document-option input {
        width: auto;
        margin-top: 3px;
        flex-shrink: 0;
    }
    .required-document {
        display: inline-block;
        margin-left: 7px;
        padding: 2px 7px;
        border-radius: 999px;
        color: #7a1623;
        background: #f8e9ee;
        font-size: 11px;
        font-weight: 700;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const inputBusqueda =
        document.getElementById('busqueda_proc');

    const inputTipo =
        document.getElementById('procedimiento_tipo');

    const inputNum =
        document.getElementById('num_procedimiento');

    const inputNombre =
        document.getElementById('nombre_procedimiento');

    const inputMontoMinimo =
        document.getElementById('monto_minimo');

    const inputMontoMaximo =
        document.getElementById('monto_maximo');

    const inputFechaInicio =
        document.getElementById('fecha_inicio');

    const inputFechaFin =
        document.getElementById('fecha_fin');

    const grupoMontoMinimo =
        document.getElementById('grupo_monto_minimo');

    const minLetra =
        document.getElementById('min_letra');

    const maxLetra =
        document.getElementById('max_letra');

    const labelMontoMaximo =
        document.getElementById('label_monto_maximo');

    const ayudaMontoMaximo =
        document.getElementById('ayuda_monto_maximo');

    const mensajeBusqueda =
        document.getElementById('mensaje_busqueda');

    const radiosTipoContrato =
        document.querySelectorAll(
            'input[name="tipo_contrato_monto"]'
        );

    let temporizadorBusqueda = null;
    let controladorBusqueda = null;

    /*
     * Mantener visible el monto mínimo cuando Laravel
     * regresa el formulario por una validación.
     */
    aplicarTipoContrato(false);
    actualizarPreviewMontos();

    inputBusqueda.addEventListener('input', function () {

        const valor = this.value.trim();

        clearTimeout(temporizadorBusqueda);

        if (controladorBusqueda) {
            controladorBusqueda.abort();
        }

        /*
         * La búsqueda es opcional.
         * No se limpian los campos capturados manualmente.
         */
        if (valor === '') {
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

    radiosTipoContrato.forEach(function (radio) {

        radio.addEventListener('change', function () {
            aplicarTipoContrato(true);
            actualizarPreviewMontos();
        });

    });

    inputMontoMaximo.addEventListener('input', function () {
        aplicarTipoContrato(false);
        actualizarPreviewMontos();
    });

    inputMontoMinimo.addEventListener(
        'input',
        actualizarPreviewMontos
    );

    function buscarProcedimiento(valor) {

        controladorBusqueda = new AbortController();

        fetch(
            `/buscar-procedimiento-adjudicacion/${encodeURIComponent(valor)}`,
            {
                headers: {
                    'Accept': 'application/json'
                },
                signal: controladorBusqueda.signal
            }
        )
            .then(function (response) {

                if (!response.ok) {
                    throw new Error(
                        'Error en la búsqueda del procedimiento.'
                    );
                }

                return response.json();

            })
            .then(function (data) {

                if (inputBusqueda.value.trim() !== valor) {
                    return;
                }

                if (data && data.num_procedimiento) {

                    inputTipo.value =
                        data.tipo ?? '';

                    inputNum.value =
                        data.num_procedimiento ?? '';

                    inputNombre.value =
                        data.nombre_procedimiento ?? '';

                    inputMontoMaximo.value =
                        normalizarMontoVisible(
                            data.monto_maximo ?? ''
                        );

                    inputFechaInicio.value =
                        data.fecha_inicio_contrato ?? '';

                    inputFechaFin.value =
                        data.fecha_fin_contrato ?? '';

                    /*
                     * En contratos abiertos se calcula
                     * automáticamente el 40 %.
                     */
                    aplicarTipoContrato(true);
                    actualizarPreviewMontos();

                    mensajeBusqueda.textContent =
                        'Procedimiento encontrado. Puede modificar los datos antes de generar el Word.';

                    mensajeBusqueda.className =
                        'search-message success';

                } else {

                    /*
                     * No se limpian los campos porque el usuario
                     * puede continuar capturándolos manualmente.
                     */
                    mensajeBusqueda.textContent =
                        'No se encontró el procedimiento. Puede capturar todos los datos manualmente.';

                    mensajeBusqueda.className =
                        'search-message error';
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

                /*
                 * Tampoco se eliminan datos manuales si falla la consulta.
                 */
                mensajeBusqueda.textContent =
                    'No fue posible realizar la búsqueda. Puede continuar capturando la información manualmente.';

                mensajeBusqueda.className =
                    'search-message error';

            });

    }

    function aplicarTipoContrato(recalcularMinimo) {

        const radioSeleccionado =
            document.querySelector(
                'input[name="tipo_contrato_monto"]:checked'
            );

        if (!radioSeleccionado) {
            grupoMontoMinimo.style.display = 'none';
            return;
        }

        const tipoContrato =
            radioSeleccionado.value;

        if (tipoContrato === 'abierto') {

            grupoMontoMinimo.style.display = 'block';
            labelMontoMaximo.textContent = 'Monto máximo';
            ayudaMontoMaximo.textContent =
                'Para contrato abierto se utilizarán el monto mínimo y el monto máximo.';

            if (
                recalcularMinimo ||
                inputMontoMinimo.value.trim() === ''
            ) {
                const montoMaximo = convertirMontoNumero(
                    inputMontoMaximo.value
                );

                if (montoMaximo !== null) {
                    inputMontoMinimo.value =
                        formatearMontoCampo(
                            montoMaximo * 0.40
                        );
                }
            }

        } else {

            grupoMontoMinimo.style.display = 'none';
            inputMontoMinimo.value = '';
            minLetra.textContent = '';

            labelMontoMaximo.textContent = 'Monto del contrato';
            ayudaMontoMaximo.textContent =
                'Para contrato cerrado este será el único importe incorporado al Word.';

        }

    }

    function convertirMontoNumero(valor) {

        if (
            valor === null ||
            String(valor).trim() === ''
        ) {
            return null;
        }

        let monto = String(valor)
            .trim()
            .replace(/\$/g, '')
            .replace(/\s/g, '');

        /*
         * Formato europeo:
         * 1.234.567,89
         */
        if (
            /^-?\d{1,3}(\.\d{3})*,\d{1,2}$/.test(monto)
        ) {
            monto = monto
                .replace(/\./g, '')
                .replace(',', '.');
        } else {
            /*
             * Formato habitual:
             * 1,234,567.89
             */
            monto = monto.replace(/,/g, '');
        }

        const numero = Number(monto);

        return Number.isFinite(numero)
            ? numero
            : null;

    }

    function formatearMontoCampo(numero) {

        return new Intl.NumberFormat(
            'en-US',
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        ).format(numero);

    }

    function normalizarMontoVisible(valor) {

        const numero = convertirMontoNumero(valor);

        return numero !== null
            ? formatearMontoCampo(numero)
            : '';

    }

    function actualizarPreviewMontos() {

        const montoMinimo = convertirMontoNumero(
            inputMontoMinimo.value
        );

        const montoMaximo = convertirMontoNumero(
            inputMontoMaximo.value
        );

        minLetra.textContent =
            montoMinimo !== null
                ? numeroComoMoneda(montoMinimo)
                : '';

        maxLetra.textContent =
            montoMaximo !== null
                ? numeroComoMoneda(montoMaximo)
                : '';

    }

    function numeroComoMoneda(numero) {

        return new Intl.NumberFormat(
            'es-MX',
            {
                style: 'currency',
                currency: 'MXN',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        ).format(numero);

    }

    function limpiarMensajeBusqueda() {

        mensajeBusqueda.textContent = '';
        mensajeBusqueda.className = 'search-message';

    }

});
</script>

@endsection