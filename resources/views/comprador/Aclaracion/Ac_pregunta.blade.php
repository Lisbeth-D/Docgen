@extends('layouts.app')

@section('content')



<div class="admin-layout">
    @include('comprador.sidebar')

    <div class="admin-content">
        <div class="conv-wrapper">

            <form
                id="form-ac-pregunta"
                action="{{ route('ac.generar') }}"
                method="POST"
                enctype="multipart/form-data"
                class="conv-form"
                novalidate
            >
                @csrf

                <h2 class="conv-title">Junta de Aclaraciones — Preguntas</h2>

                {{-- ========================================== --}}
                {{-- MENSAJES GENERALES --}}
                {{-- ========================================== --}}

                <div
                    id="alerta_formulario"
                    class="form-alert form-alert-danger"
                    role="alert"
                    aria-live="assertive"
                    hidden
                ></div>

                @if (session('error'))
                    <div class="form-alert form-alert-danger" role="alert">
                        <strong>Error:</strong>
                        {{ session('error') }}
                    </div>
                @endif

                @if (session('success'))
                    <div class="form-alert form-alert-success" role="alert">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="form-alert form-alert-danger" role="alert">
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
                        <label for="archivo_word">Archivo .docx</label>
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
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- PROCEDIMIENTO --}}
                <div class="conv-card">
                    <h3>Datos del procedimiento</h3>

                    <div class="conv-grid">
                        <div class="conv-group">
                            <label for="busqueda_proc">Número de búsqueda</label>
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
                            <div id="estado_busqueda" class="search-message" role="status" aria-live="polite" hidden></div>
                            @error('numero_busqueda')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="conv-group">
                            <label for="num_procedimiento">Número completo</label>
                            <input
                                type="text"
                                id="num_procedimiento"
                                name="num_procedimiento"
                                value="{{ old('num_procedimiento') }}"
                                maxlength="255"
                                class="@error('num_procedimiento') input-error @enderror"
                                required
                            >
                            @error('num_procedimiento')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="conv-group full">
                            <label for="nombre_procedimiento">Nombre del procedimiento</label>
                            <input
                                type="text"
                                id="nombre_procedimiento"
                                name="nombre_procedimiento"
                                value="{{ old('nombre_procedimiento') }}"
                                class="@error('nombre_procedimiento') input-error @enderror"
                                required
                            >
                            @error('nombre_procedimiento')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="conv-group">
                            <label for="fecha_ac">Fecha de la junta</label>
                            <input
                                type="date"
                                lang="es-MX"
                                id="fecha_ac"
                                name="fecha_ac"
                                value="{{ old('fecha_ac') }}"
                                class="@error('fecha_ac') input-error @enderror"
                                required
                            >
                            @error('fecha_ac')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="conv-group">
                            <label for="hora_ac">Hora de inicio</label>
                            <input
                                type="time"
                                id="hora_ac"
                                name="hora_ac"
                                value="{{ old('hora_ac') }}"
                                class="@error('hora_ac') input-error @enderror"
                                required
                            >
                            @error('hora_ac')
                                <span class="field-error">{{ $message }}</span>
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
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="conv-group">
                            <label for="area_contratante">
                                Persona del área contratante
                            </label>

                            <select
                                name="area_contratante"
                                id="area_contratante"
                                class="@error('area_contratante') input-error @enderror"
                                required
                            >
                                <option value="">Seleccionar</option>

                                @foreach ($personasContratante as $persona)
                                    <option
                                        value="{{ $persona->id }}"
                                        data-referencia="{{ $persona->plantilla_referencia }}"
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
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="conv-group">
                            <label for="area_contrato_select">
                                Área del administrador del contrato
                            </label>

                            <select id="area_contrato_select" required>
                                <option value="">Seleccionar área</option>

                                @foreach ($areasContrato as $area)
                                    <option value="{{ $area->id_area }}">
                                        {{ $area->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="conv-group">
                            <label for="persona_contrato_select">
                                Administrador del contrato
                            </label>

                            <select
                                name="admi_contrato"
                                id="persona_contrato_select"
                                class="@error('admi_contrato') input-error @enderror"
                                required
                            >
                                <option value="">
                                    Primero seleccione un área
                                </option>
                            </select>

                            @error('admi_contrato')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- OFICIOS --}}
                <div class="conv-card">
                    <h3>Oficios de preguntas y respuestas</h3>

                    <div class="conv-grid">
                        <div class="conv-group">
                            <label for="oficio_preguntas">Referencia del oficio de preguntas</label>
                            <input
                                type="text"
                                id="oficio_preguntas"
                                name="oficio_preguntas"
                                value="{{ old('oficio_preguntas') }}"
                                placeholder="Seleccione a la persona del área contratante"
                                maxlength="255"
                                required
                                class="@error('oficio_preguntas') input-error @enderror"
                            >
                            @error('oficio_preguntas')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="conv-group">
                            <label for="fecha_oficio_preguntas">Fecha del oficio de preguntas</label>
                            <input
                                type="date"
                                lang="es-MX"
                                id="fecha_oficio_preguntas"
                                name="fecha_oficio_preguntas"
                                value="{{ old('fecha_oficio_preguntas') }}"
                                required
                            
                                class="@error('fecha_oficio_preguntas') input-error @enderror">
                            @error('fecha_oficio_preguntas')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="conv-group">
                            <label for="oficio_respuestas">Referencia del oficio de respuestas</label>
                            <input
                                type="text"
                                id="oficio_respuestas"
                                name="oficio_respuestas"
                                value="{{ old('oficio_respuestas') }}"
                                placeholder="Se cargará desde la persona requirente"
                                maxlength="255"
                                required
                                class="@error('oficio_respuestas') input-error @enderror"
                            >
                            @error('oficio_respuestas')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="conv-group">
                            <label for="fecha_oficio_respuestas">Fecha del oficio de respuestas</label>
                            <input
                                type="date"
                                lang="es-MX"
                                id="fecha_oficio_respuestas"
                                name="fecha_oficio_respuestas"
                                value="{{ old('fecha_oficio_respuestas') }}"
                                required
                            
                                class="@error('fecha_oficio_respuestas') input-error @enderror">
                            @error('fecha_oficio_respuestas')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- OIC / JURÍDICO --}}
                <div class="conv-card">
                    <h3>OIC / Jurídico</h3>

                    <div class="conv-grid">
                        <div class="conv-group">
                            <label for="persona_oic">Persona OIC</label>
                            <select name="persona_oic" id="persona_oic" class="@error('persona_oic') input-error @enderror">
                                <option value="">Seleccionar</option>
                                @foreach ($personasOic as $persona)
                                    <option
                                        value="{{ $persona->id }}"
                                        data-referencia="{{ $persona->plantilla_referencia }}"
                                        @selected((string) old('persona_oic') === (string) $persona->id)
                                    >
                                        {{ $persona->nombre }}{{ $persona->cargo ? ' - ' . $persona->cargo : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('persona_oic')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="conv-group">
                            <label for="ref_oic">Referencia OIC</label>
                            <input
                                type="text"
                                name="ref_oic"
                                id="ref_oic"
                                value="{{ old('ref_oic') }}"
                                maxlength="255"
                            
                                class="@error('ref_oic') input-error @enderror">
                            @error('ref_oic')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="conv-group">
                            <label for="persona_juridico">Persona Jurídico</label>
                            <select name="persona_juridico" id="persona_juridico" class="@error('persona_juridico') input-error @enderror">
                                <option value="">Seleccionar</option>
                                @foreach ($personasJuridico as $persona)
                                    <option
                                        value="{{ $persona->id }}"
                                        data-referencia="{{ $persona->plantilla_referencia }}"
                                        @selected((string) old('persona_juridico') === (string) $persona->id)
                                    >
                                        {{ $persona->nombre }}{{ $persona->cargo ? ' - ' . $persona->cargo : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('persona_juridico')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="conv-group">
                            <label for="ref_juridico">Referencia Jurídico</label>
                            <input
                                type="text"
                                name="ref_juridico"
                                id="ref_juridico"
                                value="{{ old('ref_juridico') }}"
                                maxlength="255"
                            
                                class="@error('ref_juridico') input-error @enderror">
                            @error('ref_juridico')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- PARTICIPANTES --}}
                <div class="conv-card">
                    <h3>Participantes</h3>

                    <div class="conv-group">
                        <label for="ac_num_participantes">¿Cuántos participantes?</label>
                        <input
                            type="number"
                            id="ac_num_participantes"
                            min="0"
                            max="100"
                            step="1"
                            inputmode="numeric"
                            value="{{ count(old('participantes', [])) ?: '' }}"
                        >
                    </div>

                    @error('participantes')
                        <span class="field-error">{{ $message }}</span>
                    @enderror

                    <div id="ac_participantes_container"></div>
                </div>

                <button class="conv-btn" id="btn_generar" type="submit">
                    Generar documento
                </button>
            </form>
        </div>
    </div>
</div>


<style>
    .conv-form {
        width: 100%;
        min-width: 0;
    }

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
document.addEventListener('DOMContentLoaded', () => {
    const areasContrato = @json($areasContrato);
    const participantesAnteriores = @json(old('participantes', []));

    const administradorAnterior = @json((string) old('admi_contrato', ''));

    const buscarProcedimientoBaseUrl = @json(url('/buscar-procedimiento-ac'));

    const form = document.getElementById('form-ac-pregunta');
    const botonGenerar = document.getElementById('btn_generar');
    const archivoWord = document.getElementById('archivo_word');
    const alertaFormulario = document.getElementById('alerta_formulario');

    const inputBusqueda = document.getElementById('busqueda_proc');
    const inputNum = document.getElementById('num_procedimiento');
    const inputNombre = document.getElementById('nombre_procedimiento');
    const inputFechaAc = document.getElementById('fecha_ac');
    const inputHoraAc = document.getElementById('hora_ac');
    const inputAreaRequirente = document.getElementById('area_requirente');
    const inputAreaRequirenteNombre =
        document.getElementById('area_requirente_nombre');
    const selectAreaContratante =
        document.getElementById('area_contratante');
    const inputOficioPreguntas =
        document.getElementById('oficio_preguntas');
    const inputOficioRespuestas =
        document.getElementById('oficio_respuestas');
    const estadoBusqueda = document.getElementById('estado_busqueda');

    let temporizadorBusqueda = null;
    let controladorBusqueda = null;
    let busquedaEnCurso = false;

    inputBusqueda.addEventListener('input', () => {
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

        mostrarEstadoBusqueda('Buscando procedimiento…', 'info');

        temporizadorBusqueda = setTimeout(() => {
            buscarProcedimiento(valor);
        }, 350);
    });

    async function buscarProcedimiento(valor) {
        if (controladorBusqueda) {
            controladorBusqueda.abort();
        }

        controladorBusqueda = new AbortController();
        busquedaEnCurso = true;

        try {
            const response = await fetch(
                `${buscarProcedimientoBaseUrl}/${encodeURIComponent(valor)}`,
                {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    signal: controladorBusqueda.signal,
                }
            );

            const respuesta = await response.json().catch(() => null);

            if (!response.ok) {
                throw new Error(
                    respuesta?.message
                    || 'No fue posible consultar el procedimiento.'
                );
            }

            if (!respuesta || !respuesta.num_procedimiento) {
                throw new Error(
                    'No se encontró un procedimiento con ese número.'
                );
            }

            inputNum.value = respuesta.num_procedimiento || '';
            inputNombre.value = respuesta.nombre_procedimiento || '';
            inputFechaAc.value = respuesta.fecha_ac || '';
            inputHoraAc.value = respuesta.hora_ac || '';
            inputAreaRequirente.value =
                respuesta.area_requirente_id || '';
            inputAreaRequirenteNombre.value =
                respuesta.area_requirente_nombre || '';
            inputOficioRespuestas.value =
                respuesta.plantilla_referencia_requirente || '';

            if (!respuesta.area_requirente_id) {
                throw new Error(
                    'El procedimiento no tiene una persona requirente válida registrada.'
                );
            }

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
                error.message || 'No se encontró el procedimiento.',
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
        inputAreaRequirente.value = '';
        inputAreaRequirenteNombre.value = '';
        inputOficioRespuestas.value = '';
    }

    function mostrarEstadoBusqueda(mensaje, tipo = 'info') {
        estadoBusqueda.textContent = mensaje;
        estadoBusqueda.hidden = !mensaje;
        estadoBusqueda.classList.remove('success', 'error');

        if (mensaje) {
            estadoBusqueda.classList.add(`is-${tipo}`);
        }
    }

    function mostrarAlertaFormulario(contenido, tipo = 'danger') {
        alertaFormulario.innerHTML = '';
        alertaFormulario.hidden = false;
        alertaFormulario.className =
            `form-alert form-alert-${tipo}`;

        if (Array.isArray(contenido)) {
            const titulo = document.createElement('strong');
            titulo.textContent = 'No fue posible generar el documento.';
            alertaFormulario.appendChild(titulo);

            const lista = document.createElement('ul');

            contenido.forEach(mensaje => {
                const elemento = document.createElement('li');
                elemento.textContent = mensaje;
                lista.appendChild(elemento);
            });

            alertaFormulario.appendChild(lista);
        } else {
            alertaFormulario.textContent = contenido;
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
            const etiqueta = form.querySelector(`label[for="${CSS.escape(campo.id)}"]`);
            if (etiqueta) {
                return etiqueta.textContent.trim();
            }
        }

        return campo.name || 'Campo requerido';
    }

    function limpiarMarcasDeError() {
        form.querySelectorAll('.input-error').forEach(campo => {
            campo.classList.remove('input-error');
        });
    }

    function validarFormularioPersonalizado() {
        limpiarMarcasDeError();

        const campos = Array.from(
            form.querySelectorAll('input, select, textarea')
        ).filter(campo => {
            return !campo.disabled
                && campo.type !== 'hidden'
                && !campo.closest('[hidden]');
        });

        const invalidos = campos.filter(campo => !campo.checkValidity());

        if (invalidos.length === 0) {
            return true;
        }

        invalidos.forEach(campo => campo.classList.add('input-error'));

        const mensajes = invalidos.map(campo => {
            const nombreCampo = obtenerEtiquetaCampo(campo);

            if (campo.validity.valueMissing) {
                return `Debe completar el campo ${nombreCampo}.`;
            }

            if (campo.validity.typeMismatch) {
                return `El campo ${nombreCampo} tiene un formato no válido.`;
            }

            if (campo.validity.rangeUnderflow) {
                return `El campo ${nombreCampo} contiene un valor menor al permitido.`;
            }

            if (campo.validity.rangeOverflow) {
                return `El campo ${nombreCampo} contiene un valor mayor al permitido.`;
            }

            return `Revise el campo ${nombreCampo}.`;
        });

        const mensajesUnicos = [...new Set(mensajes)];
        mostrarAlertaFormulario(mensajesUnicos);

        const primero = invalidos[0];
        primero.focus({ preventScroll: true });
        primero.scrollIntoView({ behavior: 'smooth', block: 'center' });

        return false;
    }

    selectAreaContratante.addEventListener('change', () => {
        const opcionSeleccionada =
            selectAreaContratante.options[
                selectAreaContratante.selectedIndex
            ];

        inputOficioPreguntas.value =
            opcionSeleccionada?.dataset?.referencia || '';
    });

    const areaContratoSelect = document.getElementById('area_contrato_select');
    const personaContratoSelect = document.getElementById('persona_contrato_select');

    areaContratoSelect.addEventListener('change', () => {
        cargarPersonasPorArea(
            areasContrato,
            areaContratoSelect.value,
            personaContratoSelect
        );
    });

    function cargarPersonasPorArea(areas, areaId, selectDestino, personaSeleccionada = '') {
        selectDestino.innerHTML = '';

        if (!areaId) {
            agregarOpcion(selectDestino, '', 'Primero seleccione un área');
            return;
        }

        const area = areas.find(item => String(item.id_area) === String(areaId));

        if (!area || !Array.isArray(area.personas) || area.personas.length === 0) {
            agregarOpcion(selectDestino, '', 'Sin personas registradas');
            return;
        }

        agregarOpcion(selectDestino, '', 'Seleccionar persona');

        area.personas.forEach(persona => {
            const texto = persona.cargo
                ? `${persona.nombre} - ${persona.cargo}`
                : persona.nombre;

            agregarOpcion(
                selectDestino,
                persona.id,
                texto,
                String(persona.id) === String(personaSeleccionada)
            );
        });
    }

    function agregarOpcion(select, valor, texto, seleccionada = false) {
        const option = document.createElement('option');
        option.value = valor;
        option.textContent = texto;
        option.selected = seleccionada;
        select.appendChild(option);
    }

    function restaurarPersonaSeleccionada(areas, personaId, areaSelect, personaSelect) {
        if (!personaId) {
            return;
        }

        const area = areas.find(item =>
            Array.isArray(item.personas)
            && item.personas.some(persona => String(persona.id) === String(personaId))
        );

        if (!area) {
            return;
        }

        areaSelect.value = String(area.id_area);
        cargarPersonasPorArea(areas, area.id_area, personaSelect, personaId);
    }

    restaurarPersonaSeleccionada(
        areasContrato,
        administradorAnterior,
        areaContratoSelect,
        personaContratoSelect
    );

    const personaOic = document.getElementById('persona_oic');
    const personaJuridico = document.getElementById('persona_juridico');
    const referenciaOic = document.getElementById('ref_oic');
    const referenciaJuridico = document.getElementById('ref_juridico');

    personaOic.addEventListener('change', () => {
        referenciaOic.value = obtenerReferenciaSeleccionada(personaOic);
    });

    personaJuridico.addEventListener('change', () => {
        referenciaJuridico.value = obtenerReferenciaSeleccionada(personaJuridico);
    });

    function obtenerReferenciaSeleccionada(select) {
        const option = select.options[select.selectedIndex];
        return option?.dataset?.referencia || '';
    }

    const numeroParticipantes =
        document.getElementById('ac_num_participantes');

    const participantesContainer =
        document.getElementById('ac_participantes_container');

    /*
     * Conserva temporalmente lo capturado antes de volver a dibujar
     * las tarjetas. De esta forma no se pierden nombres, selecciones,
     * preguntas ni respuestas cuando cambia la cantidad.
     */
    function obtenerEstadoActualParticipantes() {
        const tarjetas = Array.from(
            participantesContainer.querySelectorAll('.participante-card')
        );

        return tarjetas.map((tarjeta, indice) => {
            const nombre = tarjeta.querySelector(
                `#participante_nombre_${indice}`
            )?.value || '';

            const presento = tarjeta.querySelector(
                `#participante_presento_${indice}`
            )?.value || 'NO';

            const preguntas = Array.from(
                tarjeta.querySelectorAll('.pregunta-bloque')
            ).map(bloque => ({
                pregunta:
                    bloque.querySelector(
                        'textarea[name$="[pregunta]"]'
                    )?.value || '',

                respuesta:
                    bloque.querySelector(
                        'textarea[name$="[respuesta]"]'
                    )?.value || '',
            }));

            return {
                nombre,
                pregunta: presento,
                preguntas,
            };
        });
    }

    /*
     * Renderiza inmediatamente y utiliza identificadores exclusivos
     * de este módulo para evitar conflictos con scripts antiguos.
     */
    numeroParticipantes.addEventListener('input', () => {
        const total = Math.max(
            0,
            Number.parseInt(
                numeroParticipantes.value,
                10
            ) || 0
        );

        const estadoActual =
            obtenerEstadoActualParticipantes();

        renderizarParticipantes(
            total,
            estadoActual
        );
    });

    function renderizarParticipantes(
        total,
        datosParticipantes = []
    ) {
        participantesContainer.innerHTML = '';

        for (let indice = 0; indice < total; indice++) {
            const participante =
                datosParticipantes[indice] || {};

            participantesContainer.appendChild(
                crearParticipante(
                    indice,
                    participante
                )
            );
        }
    }

    function crearParticipante(
        indice,
        participante
    ) {
        const tarjeta = document.createElement('div');

        tarjeta.className =
            'conv-card participante-card';

        tarjeta.dataset.indice = indice;
        tarjeta.style.marginTop = '15px';

        const presento =
            normalizarPresento(
                participante.pregunta
            );

        const preguntas =
            Array.isArray(participante.preguntas)
                ? participante.preguntas
                : [];

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
                        maxlength="255"
                        required
                    >
                </div>

                <div class="conv-group">
                    <label for="participante_presento_${indice}">
                        ¿Presentó preguntas?
                    </label>

                    <select
                        id="participante_presento_${indice}"
                        name="participantes[${indice}][pregunta]"
                        class="select-presento"
                    >
                        <option value="NO">
                            No presentó preguntas
                        </option>

                        <option value="SI">
                            Sí presentó preguntas
                        </option>
                    </select>
                </div>

                <div
                    class="conv-group preguntas-count-container"
                    id="preguntas_count_container_${indice}"
                >
                    <label for="num_preguntas_${indice}">
                        ¿Cuántas preguntas presentó?
                    </label>

                    <input
                        type="number"
                        min="1"
                        step="1"
                        class="num-preguntas-participante"
                        id="num_preguntas_${indice}"
                    >
                </div>
            </div>

            <div
                id="preguntas_container_${indice}"
                class="preguntas-participante-container"
            ></div>
        `;

        const nombreInput = tarjeta.querySelector(
            `#participante_nombre_${indice}`
        );

        const presentoSelect = tarjeta.querySelector(
            `#participante_presento_${indice}`
        );

        const countContainer = tarjeta.querySelector(
            `#preguntas_count_container_${indice}`
        );

        const totalPreguntasInput = tarjeta.querySelector(
            `#num_preguntas_${indice}`
        );

        const preguntasContainer = tarjeta.querySelector(
            `#preguntas_container_${indice}`
        );

        nombreInput.value =
            participante.nombre || '';

        presentoSelect.value =
            presento ? 'SI' : 'NO';

        /*
         * Cada tarjeta administra únicamente sus propios campos.
         * Cambiar un proveedor no modifica los demás participantes.
         */
        function actualizarVisibilidadPreguntas() {
            const presentaraPreguntas =
                presentoSelect.value === 'SI';

            countContainer.style.display =
                presentaraPreguntas
                    ? ''
                    : 'none';

            totalPreguntasInput.required =
                presentaraPreguntas;

            if (!presentaraPreguntas) {
                totalPreguntasInput.value = '';
                preguntasContainer.innerHTML = '';
            }
        }

        presentoSelect.addEventListener(
            'change',
            actualizarVisibilidadPreguntas
        );

        totalPreguntasInput.addEventListener(
            'input',
            () => {
                const datosActualesPreguntas =
                    obtenerPreguntasDeContainer(
                        preguntasContainer
                    );

                const totalPreguntas = Math.max(
                    0,
                    Number.parseInt(
                        totalPreguntasInput.value,
                        10
                    ) || 0
                );

                renderizarPreguntas(
                    indice,
                    totalPreguntas,
                    preguntasContainer,
                    datosActualesPreguntas
                );
            }
        );

        if (
            presento &&
            preguntas.length > 0
        ) {
            totalPreguntasInput.value =
                preguntas.length;

            renderizarPreguntas(
                indice,
                preguntas.length,
                preguntasContainer,
                preguntas
            );
        }

        actualizarVisibilidadPreguntas();

        return tarjeta;
    }

    function obtenerPreguntasDeContainer(container) {
        return Array.from(
            container.querySelectorAll('.pregunta-bloque')
        ).map(bloque => ({
            pregunta:
                bloque.querySelector(
                    'textarea[name$="[pregunta]"]'
                )?.value || '',

            respuesta:
                bloque.querySelector(
                    'textarea[name$="[respuesta]"]'
                )?.value || '',
        }));
    }

    function renderizarPreguntas(
        indiceParticipante,
        total,
        container,
        datosPreguntas = []
    ) {
        container.innerHTML = '';

        for (
            let indicePregunta = 0;
            indicePregunta < total;
            indicePregunta++
        ) {
            const datos =
                datosPreguntas[indicePregunta] || {};

            const bloque =
                document.createElement('div');

            bloque.className =
                'conv-grid pregunta-bloque';

            bloque.style.marginTop = '10px';

            bloque.innerHTML = `
                <div class="conv-group full">
                    <label
                        for="pregunta_${indiceParticipante}_${indicePregunta}"
                    >
                        Pregunta ${indicePregunta + 1}
                    </label>

                    <textarea
                        id="pregunta_${indiceParticipante}_${indicePregunta}"
                        name="participantes[${indiceParticipante}][preguntas][${indicePregunta}][pregunta]"
                        placeholder="Capture la pregunta"
                        required
                    ></textarea>
                </div>

                <div class="conv-group full">
                    <label
                        for="respuesta_${indiceParticipante}_${indicePregunta}"
                    >
                        Respuesta ${indicePregunta + 1}
                    </label>

                    <textarea
                        id="respuesta_${indiceParticipante}_${indicePregunta}"
                        name="participantes[${indiceParticipante}][preguntas][${indicePregunta}][respuesta]"
                        placeholder="Capture la respuesta"
                        required
                    ></textarea>
                </div>
            `;

            bloque.querySelector(
                'textarea[name$="[pregunta]"]'
            ).value = datos.pregunta || '';

            bloque.querySelector(
                'textarea[name$="[respuesta]"]'
            ).value = datos.respuesta || '';

            container.appendChild(bloque);
        }
    }

    function normalizarPresento(valor) {
        const texto = String(valor || 'NO')
            .trim()
            .toUpperCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');

        return texto === 'SI'
            || texto === 'SI PRESENTO'
            || texto === 'SI PRESENTO PREGUNTAS';
    }

    if (
        Array.isArray(participantesAnteriores) &&
        participantesAnteriores.length > 0
    ) {
        numeroParticipantes.value =
            participantesAnteriores.length;

        renderizarParticipantes(
            participantesAnteriores.length,
            participantesAnteriores
        );
    }

    form.addEventListener('input', event => {
        const campo = event.target;
        if (campo.matches('input, select, textarea')) {
            campo.classList.remove('input-error');
        }
    });

    form.addEventListener('change', event => {
        const campo = event.target;
        if (campo.matches('input, select, textarea')) {
            campo.classList.remove('input-error');
        }
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        ocultarAlertaFormulario();
        if (busquedaEnCurso){mostrarAlertaFormulario('Espere a que termine la búsqueda del procedimiento.','info');return;}
        if(!validarFormularioPersonalizado()){return;}
        botonGenerar.disabled=true;
        botonGenerar.textContent='Generando documento...';
        try{
            const datosFormulario=new FormData(form);
            const respuesta=await fetch(form.action,{method:'POST',body:datosFormulario,headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json, application/vnd.openxmlformats-officedocument.wordprocessingml.document'}});
            if(!respuesta.ok){
                if(respuesta.status===422){
                    const e=await respuesta.json();
                    const errs=[];Object.values(e.errors??{}).forEach(x=>x.forEach(t=>errs.push(t)));
                    mostrarAlertaFormulario(errs.length?errs:['Revise los datos capturados.']);return;
                }
                throw new Error('No fue posible generar el documento.');
            }
            const blob=await respuesta.blob();
            let nombre='Documento_generado.docx';
            const d=respuesta.headers.get('Content-Disposition');
            if(d){const m=d.match(/filename\*?=(?:UTF-8'')?["']?([^"';]+)["']?/i);if(m&&m[1])nombre=decodeURIComponent(m[1].replace(/["']/g,''));}
            const url=URL.createObjectURL(blob);const a=document.createElement('a');a.href=url;a.download=nombre;document.body.appendChild(a);a.click();a.remove();URL.revokeObjectURL(url);
            if(archivoWord){archivoWord.value='';archivoWord.classList.remove('input-error');}
            mostrarAlertaFormulario('Documento generado correctamente. Ya puede seleccionar otra plantilla Word y generar nuevamente.','success');
        }catch(error){
            console.error(error);
            mostrarAlertaFormulario(error.message||'Ocurrió un error al generar el documento.');
        }finally{
            botonGenerar.disabled=false;
            botonGenerar.textContent='Generar documento';
        }
    });
});
</script>
@endsection