@extends('layouts.app')

@section('title', 'No aplica junta')

@section('content')

<div class="admin-layout">

    @include('comprador.sidebar')

    <div class="admin-content">

        <div class="conv-wrapper">

            <form
                id="form-no-aplica"
                action="{{ route('noaplica.generar') }}"
                method="POST"
                enctype="multipart/form-data"
                class="conv-form"
                novalidate
            >
                @csrf

                <h2 class="conv-title">
                    No aplica junta
                </h2>

                <p class="form-subtitle">
                    Complete la información para generar el documento.
                </p>

                {{-- MENSAJES GENERALES --}}
                <div
                    id="alerta_formulario"
                    class="form-alert form-alert-danger"
                    role="alert"
                    aria-live="assertive"
                    hidden
                ></div>

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

                {{-- WORD --}}
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
                            accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                            class="@error('archivo_word') input-error @enderror"
                            required
                        >

                        <small>Máximo 10 MB.</small>

                        @error('archivo_word')
                            <span class="field-error">
                                {{ $message }}
                            </span>
                        @enderror

                    </div>

                </div>

                {{-- DATOS --}}
                <div class="conv-card">

                    <h3>Datos del procedimiento</h3>

                    <div class="conv-grid">

                        <div class="conv-group">

                            <label for="numero_busqueda">
                                Número de búsqueda
                            </label>

                            <input
                                type="text"
                                id="numero_busqueda"
                                name="numero_busqueda"
                                value="{{ old('numero_busqueda') }}"
                                placeholder="Ejemplo: 25"
                                maxlength="100"
                                autocomplete="off"
                                class="@error('numero_busqueda') input-error @enderror"
                                required
                            >

                            <div
                                id="estado_busqueda"
                                class="search-message"
                                role="status"
                                aria-live="polite"
                                hidden
                            ></div>

                            @error('numero_busqueda')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        <div class="conv-group">

                            <label for="num_procedimiento">
                                Número completo del procedimiento
                            </label>

                            <input
                                type="text"
                                id="num_procedimiento"
                                name="num_procedimiento"
                                value="{{ old('num_procedimiento') }}"
                                maxlength="255"
                                class="@error('num_procedimiento') input-error @enderror"
                            >

                            @error('num_procedimiento')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        <div class="conv-group full">

                            <label for="nombre_procedimiento">
                                Nombre del procedimiento
                            </label>

                            <input
                                type="text"
                                id="nombre_procedimiento"
                                name="nombre_procedimiento"
                                value="{{ old('nombre_procedimiento') }}"
                                maxlength="1000"
                                class="@error('nombre_procedimiento') input-error @enderror"
                            >

                            @error('nombre_procedimiento')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        <div class="conv-group">

                            <label for="fecha_apertura">
                                Fecha de apertura
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

                        <div class="conv-group">

                            <label for="correo_comprador">
                                Correo del comprador
                            </label>

                            <input
                                type="email"
                                id="correo_comprador"
                                name="correo_comprador"
                                value="{{ old('correo_comprador', Auth::user()->email ?? '') }}"
                                maxlength="255"
                                class="@error('correo_comprador') input-error @enderror"
                            >

                            @error('correo_comprador')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        <div class="conv-group">

                            <label for="reviso_id">
                                Revisó
                            </label>

                            <select
                                id="reviso_id"
                                name="reviso_id"
                                class="@error('reviso_id') input-error @enderror"
                                required
                            >

                                <option value="">
                                    Seleccionar
                                </option>

                                @foreach ($revisores as $persona)
                                    <option
                                        value="{{ $persona->id }}"
                                        @selected(
                                            (string) old('reviso_id')
                                            === (string) $persona->id
                                        )
                                    >
                                        {{ $persona->nombre }}
                                        {{ $persona->cargo ? ' - ' . $persona->cargo : '' }}
                                    </option>
                                @endforeach

                            </select>

                            @error('reviso_id')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        <div class="conv-group">

                            <label for="elaboro_visual">
                                Elaboró
                            </label>

                            <input
                                type="text"
                                id="elaboro_visual"
                                value="{{ $textoElaboro }}"
                                readonly
                            >

                            <small>
                                Se obtiene automáticamente del usuario autenticado.
                            </small>

                        </div>

                    </div>

                </div>

                <div class="conv-card">

                    <button
                        type="submit"
                        class="conv-btn"
                        id="btn_generar"
                    >
                        Generar documento
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<style>
    .form-alert {
        display: block;
        width: 100%;
        box-sizing: border-box;
        margin-bottom: 20px;
        padding: 14px 18px;
        border-radius: 8px;
        font-size: 14px;
        line-height: 1.5;
    }

    .form-alert[hidden] {
        display: none !important;
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

    .form-alert-info {
        color: #055160;
        background-color: #cff4fc;
        border: 1px solid #b6effb;
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

    .search-message[hidden] {
        display: none !important;
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

    const buscarProcedimientoBaseUrl =
        @json(url('/no-aplica-junta/buscar'));

    const formulario =
        document.getElementById('form-no-aplica');

    const botonGenerar =
        document.getElementById('btn_generar');

    const alertaFormulario =
        document.getElementById('alerta_formulario');

    const numeroBusqueda =
        document.getElementById('numero_busqueda');

    const numProcedimiento =
        document.getElementById('num_procedimiento');

    const nombreProcedimiento =
        document.getElementById('nombre_procedimiento');

    const fechaApertura =
        document.getElementById('fecha_apertura');

    const estadoBusqueda =
        document.getElementById('estado_busqueda');

    let temporizadorBusqueda = null;
    let controladorBusqueda = null;
    let busquedaEnCurso = false;

    numeroBusqueda.addEventListener('input', function () {
        clearTimeout(temporizadorBusqueda);

        if (controladorBusqueda) {
            controladorBusqueda.abort();
        }

        busquedaEnCurso = false;
        limpiarCampos();
        ocultarAlertaFormulario();

        const valor = numeroBusqueda.value.trim();

        if (valor === '') {
            mostrarEstadoBusqueda('');
            return;
        }

        mostrarEstadoBusqueda(
            'Buscando procedimiento...',
            'info'
        );

        temporizadorBusqueda = setTimeout(function () {
            buscarProcedimiento(valor);
        }, 350);
    });

    async function buscarProcedimiento(valor) {
        controladorBusqueda = new AbortController();
        busquedaEnCurso = true;

        try {
            const respuestaHttp = await fetch(
                `${buscarProcedimientoBaseUrl}/${encodeURIComponent(valor)}`,
                {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    signal: controladorBusqueda.signal,
                }
            );

            const datos =
                await respuestaHttp.json().catch(() => null);

            if (!respuestaHttp.ok) {
                throw new Error(
                    'No fue posible consultar el procedimiento.'
                );
            }

            if (!datos || !datos.num_procedimiento) {
                throw new Error(
                    'No se encontró un procedimiento con ese número.'
                );
            }

            numProcedimiento.value =
                datos.num_procedimiento || '';

            nombreProcedimiento.value =
                datos.nombre_procedimiento || '';

            fechaApertura.value =
                datos.fecha_apertura || '';

            mostrarEstadoBusqueda(
                'Procedimiento encontrado. Los datos fueron cargados y pueden editarse.',
                'success'
            );
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            limpiarCampos();

            mostrarEstadoBusqueda(
                error.message
                || 'No fue posible realizar la búsqueda.',
                'error'
            );
        } finally {
            busquedaEnCurso = false;
        }
    }

    function limpiarCampos() {
        numProcedimiento.value = '';
        nombreProcedimiento.value = '';
        fechaApertura.value = '';
    }

    function mostrarEstadoBusqueda(
        mensaje,
        tipo = 'info'
    ) {
        estadoBusqueda.textContent = mensaje;
        estadoBusqueda.hidden = !mensaje;
        estadoBusqueda.className =
            'search-message';

        if (tipo === 'success') {
            estadoBusqueda.classList.add('success');
        }

        if (tipo === 'error') {
            estadoBusqueda.classList.add('error');
        }
    }

    function mostrarAlertaFormulario(
        contenido,
        tipo = 'danger'
    ) {
        alertaFormulario.innerHTML = '';
        alertaFormulario.hidden = false;
        alertaFormulario.className =
            `form-alert form-alert-${tipo}`;

        if (Array.isArray(contenido)) {
            const titulo =
                document.createElement('strong');

            titulo.textContent =
                'No fue posible generar el documento.';

            alertaFormulario.appendChild(titulo);

            const lista =
                document.createElement('ul');

            contenido.forEach(function (mensaje) {
                const elemento =
                    document.createElement('li');

                elemento.textContent = mensaje;
                lista.appendChild(elemento);
            });

            alertaFormulario.appendChild(lista);
        } else {
            alertaFormulario.textContent =
                contenido;
        }

        alertaFormulario.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });
    }

    function ocultarAlertaFormulario() {
        alertaFormulario.innerHTML = '';
        alertaFormulario.hidden = true;
        alertaFormulario.className =
            'form-alert form-alert-danger';
    }

    function obtenerEtiquetaCampo(campo) {
        if (campo.id) {
            const etiqueta = formulario.querySelector(
                `label[for="${CSS.escape(campo.id)}"]`
            );

            if (etiqueta) {
                return etiqueta.textContent.trim();
            }
        }

        return campo.name || 'campo requerido';
    }

    function validarFormularioPersonalizado() {
        formulario
            .querySelectorAll('.input-error')
            .forEach(function (campo) {
                campo.classList.remove('input-error');
            });

        const campos = Array.from(
            formulario.querySelectorAll(
                'input, select, textarea'
            )
        ).filter(function (campo) {
            return !campo.disabled
                && campo.type !== 'hidden'
                && !campo.closest('[hidden]');
        });

        const invalidos = campos.filter(function (campo) {
            return !campo.checkValidity();
        });

        if (invalidos.length === 0) {
            return true;
        }

        invalidos.forEach(function (campo) {
            campo.classList.add('input-error');
        });

        const mensajes = invalidos.map(function (campo) {
            const etiqueta =
                obtenerEtiquetaCampo(campo);

            if (campo.validity.valueMissing) {
                return `Debe completar el campo ${etiqueta}.`;
            }

            if (campo.validity.typeMismatch) {
                return `El campo ${etiqueta} tiene un formato no válido.`;
            }

            return `Revise el campo ${etiqueta}.`;
        });

        mostrarAlertaFormulario(
            [...new Set(mensajes)]
        );

        invalidos[0].focus({
            preventScroll: true,
        });

        invalidos[0].scrollIntoView({
            behavior: 'smooth',
            block: 'center',
        });

        return false;
    }

    formulario.addEventListener('input', function (event) {
        if (
            event.target.matches(
                'input, select, textarea'
            )
        ) {
            event.target.classList.remove(
                'input-error'
            );
        }
    });

    formulario.addEventListener('change', function (event) {
        if (
            event.target.matches(
                'input, select, textarea'
            )
        ) {
            event.target.classList.remove(
                'input-error'
            );
        }
    });

    formulario.addEventListener('submit', function (event) {
        event.preventDefault();
        ocultarAlertaFormulario();

        if (busquedaEnCurso) {
            mostrarAlertaFormulario(
                'Espere a que termine la búsqueda del procedimiento.',
                'info'
            );

            return;
        }

        if (!validarFormularioPersonalizado()) {
            return;
        }

        botonGenerar.disabled = true;
        botonGenerar.textContent =
            'Generando documento...';

        formulario.submit();
    });

});
</script>

@endsection