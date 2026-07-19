@extends('layouts.app')

@section('content')

<div class="admin-layout">

    @include('comprador.sidebar')

    <div class="admin-content">

        <div class="conv-wrapper">

            <form
                id="form-acta-aclaracion"
                action="{{ route('ac.generar') }}"
                method="POST"
                enctype="multipart/form-data"
                class="conv-form"
                novalidate
            >
                @csrf

                <h2 class="conv-title">
                    Acta de Junta de Aclaraciones
                </h2>

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

                {{-- PLANTILLA WORD --}}
                <div class="conv-card">

                    <h3>Plantilla Word</h3>

                    <div class="conv-group full">

                        <label for="archivo_word">
                            Archivo .docx
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

                {{-- PROCEDIMIENTO --}}
                <div class="conv-card">

                    <h3>Datos del procedimiento</h3>

                    <div class="conv-grid">

                        <div class="conv-group">

                            <label for="busqueda_proc">
                                Número de búsqueda
                            </label>

                            <input
                                type="text"
                                id="busqueda_proc"
                                name="numero_busqueda"
                                value="{{ old('numero_busqueda') }}"
                                placeholder="Ejemplo: 49"
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
                                Número completo
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

                            <label for="fecha_ac">
                                Fecha de la junta
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

                        <div class="conv-group">

                            <label for="hora_ac">
                                Hora de la junta
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

                            <label for="hora_apertura">
                                Hora de apertura
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

                    </div>

                </div>

                {{-- RESPONSABLES --}}
                <div class="conv-card">

                    <h3>Responsables</h3>

                    <div class="conv-grid">

                        <div class="conv-group">

                            <label for="area_requirente_nombre">
                                Área requirente
                            </label>

                            <input
                                type="text"
                                id="area_requirente_nombre"
                                name="area_requirente_nombre"
                                value="{{ old('area_requirente_nombre') }}"
                                placeholder="Se cargará automáticamente desde el procedimiento"
                                readonly
                            >

                            <input
                                type="hidden"
                                id="area_requirente"
                                name="area_requirente"
                                value="{{ old('area_requirente') }}"
                            >

                            @error('area_requirente')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        <div class="conv-group">

                            <label for="area_contratante">
                                Área contratante
                            </label>

                            <select
                                id="area_contratante"
                                name="area_contratante"
                                class="@error('area_contratante') input-error @enderror"
                                required
                            >

                                <option value="">
                                    Seleccionar
                                </option>

                                @foreach ($personasContratante as $persona)
                                    <option
                                        value="{{ $persona->id }}"
                                        @selected(
                                            (string) old('area_contratante')
                                            === (string) $persona->id
                                        )
                                    >
                                        {{ $persona->nombre }}
                                        {{ $persona->cargo ? ' - ' . $persona->cargo : '' }}
                                    </option>
                                @endforeach

                            </select>

                            @error('area_contratante')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                    </div>

                </div>

                {{-- OIC / JURÍDICO --}}
                <div class="conv-card">

                    <h3>OIC / Jurídico</h3>

                    <div class="conv-grid">

                        <div class="conv-group">

                            <label for="ref_oic">
                                Referencia OIC
                            </label>

                            <input
                                type="text"
                                id="ref_oic"
                                name="ref_oic"
                                value="{{ old('ref_oic') }}"
                                maxlength="255"
                                class="@error('ref_oic') input-error @enderror"
                            >

                            @error('ref_oic')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        <div class="conv-group">

                            <label for="persona_oic">
                                Persona OIC
                            </label>

                            <select
                                id="persona_oic"
                                name="persona_oic"
                                class="@error('persona_oic') input-error @enderror"
                            >

                                <option value="">
                                    Seleccionar
                                </option>

                                @foreach ($personasOic as $persona)
                                    <option
                                        value="{{ $persona->id }}"
                                        data-referencia="{{ $persona->plantilla_referencia }}"
                                        @selected(
                                            (string) old('persona_oic')
                                            === (string) $persona->id
                                        )
                                    >
                                        {{ $persona->nombre }}
                                        {{ $persona->cargo ? ' - ' . $persona->cargo : '' }}
                                    </option>
                                @endforeach

                            </select>

                            @error('persona_oic')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        <div class="conv-group">

                            <label for="ref_juridico">
                                Referencia Jurídico
                            </label>

                            <input
                                type="text"
                                id="ref_juridico"
                                name="ref_juridico"
                                value="{{ old('ref_juridico') }}"
                                maxlength="255"
                                class="@error('ref_juridico') input-error @enderror"
                            >

                            @error('ref_juridico')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        <div class="conv-group">

                            <label for="persona_juridico">
                                Persona Jurídico
                            </label>

                            <select
                                id="persona_juridico"
                                name="persona_juridico"
                                class="@error('persona_juridico') input-error @enderror"
                            >

                                <option value="">
                                    Seleccionar
                                </option>

                                @foreach ($personasJuridico as $persona)
                                    <option
                                        value="{{ $persona->id }}"
                                        data-referencia="{{ $persona->plantilla_referencia }}"
                                        @selected(
                                            (string) old('persona_juridico')
                                            === (string) $persona->id
                                        )
                                    >
                                        {{ $persona->nombre }}
                                        {{ $persona->cargo ? ' - ' . $persona->cargo : '' }}
                                    </option>
                                @endforeach

                            </select>

                            @error('persona_juridico')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                    </div>

                </div>

                {{-- PARTICIPANTES --}}
                <div class="conv-card">

                    <h3>Empresas participantes</h3>

                    <div class="conv-group">

                        <label for="aclaracion_num_participantes">
                            ¿Cuántas empresas se presentaron?
                        </label>

                        <input
                            type="number"
                            id="aclaracion_num_participantes"
                            min="0"
                            max="100"
                            step="1"
                            inputmode="numeric"
                            value="{{ count(old('participantes', [])) ?: '' }}"
                        >

                    </div>

                    @error('participantes')
                        <span class="field-error">
                            {{ $message }}
                        </span>
                    @enderror

                    <div id="aclaracion_participantes_container"></div>

                </div>

                {{-- BOTÓN --}}
                <div class="conv-card">

                    <button
                        type="submit"
                        class="conv-btn"
                        id="btn_generar"
                    >
                        Generar acta
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

    .participante-card {
        margin-top: 15px;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const participantesAnteriores =
        @json(old('participantes', []));

    const buscarProcedimientoBaseUrl =
        @json(url('/buscar-procedimiento-ac'));

    const formulario =
        document.getElementById('form-acta-aclaracion');

    const botonGenerar =
        document.getElementById('btn_generar');

    const alertaFormulario =
        document.getElementById('alerta_formulario');

    const inputBusqueda =
        document.getElementById('busqueda_proc');

    const inputNum =
        document.getElementById('num_procedimiento');

    const inputNombre =
        document.getElementById('nombre_procedimiento');

    const inputFechaAc =
        document.getElementById('fecha_ac');

    const inputHoraAc =
        document.getElementById('hora_ac');

    const inputFechaApertura =
        document.getElementById('fecha_apertura');

    const inputHoraApertura =
        document.getElementById('hora_apertura');

    const inputAreaRequirente =
        document.getElementById('area_requirente');

    const inputAreaRequirenteNombre =
        document.getElementById('area_requirente_nombre');

    const estadoBusqueda =
        document.getElementById('estado_busqueda');

    let temporizadorBusqueda = null;
    let controladorBusqueda = null;
    let busquedaEnCurso = false;

    inputBusqueda.addEventListener('input', function () {
        clearTimeout(temporizadorBusqueda);

        if (controladorBusqueda) {
            controladorBusqueda.abort();
        }

        busquedaEnCurso = false;
        limpiarProcedimiento();
        ocultarAlertaFormulario();

        const valor = inputBusqueda.value.trim();

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

            if (!datos.area_requirente_id) {
                throw new Error(
                    'El procedimiento no tiene una persona requirente válida registrada.'
                );
            }

            inputNum.value =
                datos.num_procedimiento || '';

            inputNombre.value =
                datos.nombre_procedimiento || '';

            inputFechaAc.value =
                datos.fecha_ac || '';

            inputHoraAc.value =
                datos.hora_ac || '';

            inputFechaApertura.value =
                datos.fecha_apertura || '';

            inputHoraApertura.value =
                datos.hora_apertura || '';

            inputAreaRequirente.value =
                datos.area_requirente_id || '';

            inputAreaRequirenteNombre.value =
                datos.area_requirente_nombre || '';

            mostrarEstadoBusqueda(
                'Procedimiento encontrado. Los datos fueron cargados y pueden editarse.',
                'success'
            );
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            limpiarProcedimiento();

            mostrarEstadoBusqueda(
                error.message
                || 'No fue posible realizar la búsqueda.',
                'error'
            );
        } finally {
            busquedaEnCurso = false;
        }
    }

    function limpiarProcedimiento() {
        inputNum.value = '';
        inputNombre.value = '';
        inputFechaAc.value = '';
        inputHoraAc.value = '';
        inputFechaApertura.value = '';
        inputHoraApertura.value = '';
        inputAreaRequirente.value = '';
        inputAreaRequirenteNombre.value = '';
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

            if (campo.validity.rangeUnderflow) {
                return `El campo ${etiqueta} contiene un valor menor al permitido.`;
            }

            if (campo.validity.rangeOverflow) {
                return `El campo ${etiqueta} contiene un valor mayor al permitido.`;
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

    /*
     * Completa automáticamente las referencias.
     */
    const personaOic =
        document.getElementById('persona_oic');

    const personaJuridico =
        document.getElementById('persona_juridico');

    const referenciaOic =
        document.getElementById('ref_oic');

    const referenciaJuridico =
        document.getElementById('ref_juridico');

    personaOic.addEventListener('change', function () {
        referenciaOic.value =
            obtenerReferenciaSeleccionada(
                personaOic
            );
    });

    personaJuridico.addEventListener('change', function () {
        referenciaJuridico.value =
            obtenerReferenciaSeleccionada(
                personaJuridico
            );
    });

    function obtenerReferenciaSeleccionada(select) {
        const opcion =
            select.options[select.selectedIndex];

        return opcion?.dataset?.referencia || '';
    }

    /*
     * Participantes dinámicos.
     */
    const numeroParticipantes =
        document.getElementById(
            'aclaracion_num_participantes'
        );

    const participantesContainer =
        document.getElementById(
            'aclaracion_participantes_container'
        );

    numeroParticipantes.addEventListener(
        'input',
        actualizarParticipantes
    );

    function actualizarParticipantes() {
        const total = Math.max(
            0,
            Number.parseInt(
                numeroParticipantes.value,
                10
            ) || 0
        );

        const datosActuales =
            obtenerParticipantesActuales();

        renderizarParticipantes(
            total,
            datosActuales
        );
    }

    function obtenerParticipantesActuales() {
        return Array.from(
            participantesContainer.querySelectorAll(
                '.participante-card'
            )
        ).map(function (tarjeta) {
            return {
                nombre:
                    tarjeta.querySelector(
                        '[data-campo="nombre"]'
                    )?.value || '',
            };
        });
    }

    function renderizarParticipantes(
        total,
        datosParticipantes = []
    ) {
        participantesContainer.innerHTML = '';

        for (
            let indice = 0;
            indice < total;
            indice++
        ) {
            const datos =
                datosParticipantes[indice] || {};

            const tarjeta =
                document.createElement('div');

            tarjeta.className =
                'conv-card participante-card';

            tarjeta.innerHTML = `
                <h3>Empresa ${indice + 1}</h3>

                <div class="conv-group full">

                    <label for="participante_nombre_${indice}">
                        Nombre, razón o denominación social
                    </label>

                    <input
                        type="text"
                        id="participante_nombre_${indice}"
                        name="participantes[${indice}][nombre]"
                        data-campo="nombre"
                        maxlength="255"
                        required
                    >

                </div>
            `;

            tarjeta.querySelector(
                '[data-campo="nombre"]'
            ).value = datos.nombre || '';

            participantesContainer.appendChild(
                tarjeta
            );
        }
    }

    if (
        Array.isArray(participantesAnteriores)
        && participantesAnteriores.length > 0
    ) {
        numeroParticipantes.value =
            participantesAnteriores.length;

        renderizarParticipantes(
            participantesAnteriores.length,
            participantesAnteriores
        );
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