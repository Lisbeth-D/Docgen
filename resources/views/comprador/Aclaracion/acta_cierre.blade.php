@extends('layouts.app')

@section('content')

<div class="admin-layout">

    @include('comprador.sidebar')

    <div class="admin-content">

        <div class="conv-wrapper">

            <form
                id="form-acta-cierre"
                action="{{ route('actacierre.generar') }}"
                method="POST"
                enctype="multipart/form-data"
                class="conv-form"
                novalidate
            >
                @csrf

                <h2 class="conv-title">
                    Acta de Cierre
                </h2>

                {{-- ========================================= --}}
                {{-- MENSAJES GENERALES --}}
                {{-- ========================================= --}}

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

                {{-- ========================================= --}}
                {{-- WORD --}}
                {{-- ========================================= --}}

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

                        <small>Máximo 10 MB.</small>

                        @error('archivo_word')
                            <span class="field-error">
                                {{ $message }}
                            </span>
                        @enderror

                    </div>

                </div>

                {{-- ========================================= --}}
                {{-- PROCEDIMIENTO --}}
                {{-- ========================================= --}}

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
                                Hora de inicio
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

                {{-- ========================================= --}}
                {{-- HORAS --}}
                {{-- ========================================= --}}

                <div class="conv-card">

                    <h3>Horas de suspensión</h3>

                    <div class="conv-grid">

                        <div class="conv-group">

                            <label for="hora_suspendida">
                                Hora suspendida
                            </label>

                            <input
                                type="time"
                                id="hora_suspendida"
                                name="hora_suspendida"
                                value="{{ old('hora_suspendida') }}"
                                class="@error('hora_suspendida') input-error @enderror"
                                required
                            >

                            @error('hora_suspendida')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                        <div class="conv-group">

                            <label for="hora_reanudacion">
                                Hora de reanudación
                            </label>

                            <input
                                type="time"
                                id="hora_reanudacion"
                                name="hora_reanudacion"
                                value="{{ old('hora_reanudacion') }}"
                                class="@error('hora_reanudacion') input-error @enderror"
                                required
                            >

                            @error('hora_reanudacion')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                    </div>

                </div>

                {{-- ========================================= --}}
                {{-- RESPONSABLES --}}
                {{-- ========================================= --}}

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
                                value="{{ old('area_requirente_nombre') }}"
                                placeholder="Se cargará desde el procedimiento"
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
                                        @if ($persona->cargo)
                                            - {{ $persona->cargo }}
                                        @endif
                                    </option>
                                @endforeach

                            </select>

                            @error('area_contratante')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>


                        <div class="conv-group">

                            <label for="admi_contrato">
                                Administrador del contrato
                            </label>

                            <select
                                id="admi_contrato"
                                name="admi_contrato"
                                class="@error('admi_contrato') input-error @enderror"
                                required
                            >

                                <option value="">
                                    Seleccionar
                                </option>

                                @foreach ($areasContrato as $area)
                                    @if ($area->personas->isNotEmpty())
                                        <optgroup label="{{ $area->nombre }}">

                                            @foreach ($area->personas as $persona)
                                                <option
                                                    value="{{ $persona->id }}"
                                                    @selected(
                                                        (string) old('admi_contrato')
                                                        === (string) $persona->id
                                                    )
                                                >
                                                    {{ $persona->nombre }}
                                                    @if ($persona->cargo)
                                                        - {{ $persona->cargo }}
                                                    @endif
                                                </option>
                                            @endforeach

                                        </optgroup>
                                    @endif
                                @endforeach

                            </select>

                            @error('admi_contrato')
                                <span class="field-error">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                    </div>

                </div>

                {{-- ========================================= --}}
                {{-- OIC / JURÍDICO --}}
                {{-- ========================================= --}}

                <div class="conv-card">

                    <h3>OIC / Jurídico</h3>

                    <div class="conv-grid">

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
                                        @selected(
                                            (string) old('persona_oic')
                                            === (string) $persona->id
                                        )
                                    >
                                        {{ $persona->nombre }}
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
                                        @selected(
                                            (string) old('persona_juridico')
                                            === (string) $persona->id
                                        )
                                    >
                                        {{ $persona->nombre }}
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

                {{-- ========================================= --}}
                {{-- REPREGUNTAS --}}
                {{-- ========================================= --}}

                <div class="conv-card">

                    <h3>Repreguntas</h3>

                    <div class="conv-group">

                        <label for="hubo_repreguntas">
                            ¿Hubo repreguntas?
                        </label>

                        <select
                            id="hubo_repreguntas"
                            name="hubo_repreguntas"
                            class="@error('hubo_repreguntas') input-error @enderror"
                            required
                        >
                            <option
                                value="no"
                                @selected(old('hubo_repreguntas', 'no') === 'no')
                            >
                                No
                            </option>

                            <option
                                value="si"
                                @selected(old('hubo_repreguntas') === 'si')
                            >
                                Sí
                            </option>
                        </select>

                        @error('hubo_repreguntas')
                            <span class="field-error">
                                {{ $message }}
                            </span>
                        @enderror

                    </div>

                </div>

                {{-- ========================================= --}}
                {{-- PARTICIPANTES --}}
                {{-- ========================================= --}}

                <div class="conv-card">

                    <h3>Participantes</h3>

                    <div class="conv-group">

                        <label for="acta_cierre_num_participantes">
                            ¿Cuántos participantes?
                        </label>

                        <input
                            type="number"
                            id="acta_cierre_num_participantes"
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

                    <div id="acta_cierre_participantes_container"></div>

                </div>

                {{-- ========================================= --}}
                {{-- BOTÓN --}}
                {{-- ========================================= --}}

                <div class="conv-card">

                    <button
                        type="submit"
                        class="conv-btn"
                        id="btn_generar_acta_cierre"
                    >
                        Generar Acta
                    </button>

                </div>

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

    .participante-acta-cierre {
        margin-top: 15px;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const participantesAnteriores =
        @json(old('participantes', []));

    const buscarProcedimientoBaseUrl =
        @json(url('/buscar-procedimiento-actacierre'));

    const formulario =
        document.getElementById('form-acta-cierre');

    const botonGenerar =
        document.getElementById('btn_generar_acta_cierre');

    const inputBusqueda =
        document.getElementById('busqueda_proc');

    const inputNumProcedimiento =
        document.getElementById('num_procedimiento');

    const inputNombreProcedimiento =
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

    const mensajeBusqueda =
        document.getElementById('mensaje_busqueda');

    let temporizadorBusqueda = null;
    let controladorBusqueda = null;

    /*
     * Búsqueda del procedimiento.
     */
    inputBusqueda.addEventListener('input', function () {
        clearTimeout(temporizadorBusqueda);

        if (controladorBusqueda) {
            controladorBusqueda.abort();
        }

        const valor = inputBusqueda.value.trim();

        if (valor === '') {
            limpiarCamposProcedimiento();
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

    async function buscarProcedimiento(valor) {
        controladorBusqueda = new AbortController();

        try {
            const respuesta = await fetch(
                `${buscarProcedimientoBaseUrl}/${encodeURIComponent(valor)}`,
                {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    signal: controladorBusqueda.signal,
                }
            );

            if (!respuesta.ok) {
                throw new Error(
                    'No fue posible consultar el procedimiento.'
                );
            }

            const datos = await respuesta.json();

            if (!datos || !datos.num_procedimiento) {
                limpiarCamposProcedimiento();

                mensajeBusqueda.textContent =
                    'No se encontró un procedimiento con ese número.';

                mensajeBusqueda.className =
                    'search-message error';

                return;
            }

            inputNumProcedimiento.value =
                datos.num_procedimiento || '';

            inputNombreProcedimiento.value =
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

            mensajeBusqueda.textContent =
                'Procedimiento encontrado. Los datos pueden editarse.';

            mensajeBusqueda.className =
                'search-message success';
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            console.error(
                'Error al buscar el procedimiento:',
                error
            );

            limpiarCamposProcedimiento();

            mensajeBusqueda.textContent =
                'No fue posible realizar la búsqueda.';

            mensajeBusqueda.className =
                'search-message error';
        }
    }

    function limpiarCamposProcedimiento() {
        inputNumProcedimiento.value = '';
        inputNombreProcedimiento.value = '';
        inputFechaAc.value = '';
        inputHoraAc.value = '';
        inputFechaApertura.value = '';
        inputHoraApertura.value = '';
        inputAreaRequirente.value = '';
        inputAreaRequirenteNombre.value = '';
    }

    function limpiarMensajeBusqueda() {
        mensajeBusqueda.textContent = '';
        mensajeBusqueda.className = 'search-message';
    }

    /*
     * Participantes.
     *
     * Se utilizan identificadores exclusivos para evitar conflictos
     * con otros scripts del proyecto.
     */
    const inputNumeroParticipantes =
        document.getElementById(
            'acta_cierre_num_participantes'
        );

    const participantesContainer =
        document.getElementById(
            'acta_cierre_participantes_container'
        );

    const selectHuboRepreguntas =
        document.getElementById('hubo_repreguntas');

    inputNumeroParticipantes.addEventListener(
        'input',
        actualizarParticipantes
    );

    selectHuboRepreguntas.addEventListener(
        'change',
        actualizarParticipantes
    );

    function actualizarParticipantes() {
        const total = Math.max(
            0,
            Number.parseInt(
                inputNumeroParticipantes.value,
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
                '.participante-acta-cierre'
            )
        ).map(function (tarjeta) {
            return {
                nombre:
                    tarjeta.querySelector(
                        '[data-campo="nombre"]'
                    )?.value || '',

                repregunta:
                    tarjeta.querySelector(
                        '[data-campo="repregunta"]'
                    )?.value || '',

                respuesta:
                    tarjeta.querySelector(
                        '[data-campo="respuesta"]'
                    )?.value || '',
            };
        });
    }

    function renderizarParticipantes(
        total,
        datosParticipantes = []
    ) {
        participantesContainer.innerHTML = '';

        const huboRepreguntas =
            selectHuboRepreguntas.value === 'si';

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
                'conv-card participante-acta-cierre';

            tarjeta.innerHTML = `
                <h3>Participante ${indice + 1}</h3>

                <div class="conv-grid">

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

                    ${
                        huboRepreguntas
                            ? `
                                <div class="conv-group full">
                                    <label for="participante_repregunta_${indice}">
                                        Repregunta
                                    </label>

                                    <textarea
                                        id="participante_repregunta_${indice}"
                                        name="participantes[${indice}][repregunta]"
                                        data-campo="repregunta"
                                        required
                                    ></textarea>
                                </div>

                                <div class="conv-group full">
                                    <label for="participante_respuesta_${indice}">
                                        Respuesta
                                    </label>

                                    <textarea
                                        id="participante_respuesta_${indice}"
                                        name="participantes[${indice}][respuesta]"
                                        data-campo="respuesta"
                                        required
                                    ></textarea>
                                </div>
                            `
                            : ''
                    }

                </div>
            `;

            const nombreInput =
                tarjeta.querySelector(
                    '[data-campo="nombre"]'
                );

            nombreInput.value =
                datos.nombre || '';

            if (huboRepreguntas) {
                const repreguntaInput =
                    tarjeta.querySelector(
                        '[data-campo="repregunta"]'
                    );

                const respuestaInput =
                    tarjeta.querySelector(
                        '[data-campo="respuesta"]'
                    );

                repreguntaInput.value =
                    datos.repregunta || '';

                respuestaInput.value =
                    datos.respuesta || '';
            }

            participantesContainer.appendChild(
                tarjeta
            );
        }
    }

    /*
     * Restaurar participantes cuando Laravel regresa el formulario.
     */
    if (
        Array.isArray(participantesAnteriores) &&
        participantesAnteriores.length > 0
    ) {
        inputNumeroParticipantes.value =
            participantesAnteriores.length;

        renderizarParticipantes(
            participantesAnteriores.length,
            participantesAnteriores
        );
    }

    /*
     * Evita doble envío.
     */
    formulario.addEventListener('submit', function () {
        botonGenerar.disabled = true;
        botonGenerar.textContent =
            'Generando documento...';
    });

});
</script>

@endsection