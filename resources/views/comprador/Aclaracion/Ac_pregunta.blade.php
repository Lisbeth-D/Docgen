@extends('layouts.app')

@section('content')

<div class="admin-layout">

    @include('comprador.sidebar')

    <div class="admin-content">

        <div class="conv-wrapper">

            <h2 class="conv-title">Junta de Aclaraciones pregunta</h2>

            <form action="{{ route('ac.generar') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <!-- ARCHIVO WORD -->
                <div class="conv-card">
                    <h3>Plantilla Word</h3>

                    <div class="conv-group full">
                        <input type="file" name="archivo_word" accept=".docx" required>
                    </div>
                </div>

                <!-- PROCEDIMIENTO -->
                <div class="conv-card">
                    <h3>Datos del Procedimiento</h3>

                    <div class="conv-grid">

                        <div class="conv-group">
                            <label>Número de procedimiento</label>
                            <input
                                type="text"
                                id="busqueda_proc"
                                name="numero_busqueda"
                                placeholder="Ejemplo: 25"
                                autocomplete="off"
                                required>
                        </div>

                        <div class="conv-group">
                            <label>Número completo</label>
                            <input
                                type="text"
                                id="num_procedimiento"
                                name="num_procedimiento">
                        </div>

                        <div class="conv-group">
                            <label>Nombre</label>
                            <input
                                type="text"
                                id="nombre_procedimiento"
                                name="nombre_procedimiento">
                        </div>

                        <div class="conv-group">
                            <label>Fecha junta</label>
                            <input
                                type="date"
                                id="fecha_ac"
                                name="fecha_ac">
                        </div>

                        <div class="conv-group">
                            <label>Hora inicio</label>
                            <input
                                type="time"
                                id="hora_ac"
                                name="hora_ac">
                        </div>

                    </div>
                </div>

                <!-- RESPONSABLES -->
                <div class="conv-card">
                    <h3>Responsables</h3>

                    <div class="conv-grid">

                        <div class="conv-group">
                            <label>Área requirente</label>
                            <select id="area_requirente_select" required>
                                <option value="">Seleccionar área</option>

                                @foreach($areasRequirentes as $area)
                                    <option value="{{ $area->id }}">
                                        {{ $area->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="conv-group">
                            <label>Persona área requirente</label>
                            <select name="area_requirente" id="persona_requirente_select" required>
                                <option value="">Primero seleccione un área</option>
                            </select>
                        </div>

                        <div class="conv-group">
                            <label>Área contratante</label>
                            <select name="area_contratante" required>
                                <option value="">Seleccionar</option>

                                @foreach($personasContratante as $p)
                                    <option value="{{ $p->id }}">
                                        {{ $p->nombre }} - {{ $p->cargo }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                    </div>
                </div>

                <!-- OIC / JURIDICO -->
                <div class="conv-card">
                    <h3>OIC / Jurídico</h3>

                    <div class="conv-grid">

                        <div class="conv-group">
                            <label>Persona OIC</label>
                            <select name="persona_oic" id="persona_oic">
                                <option value="">Seleccionar</option>

                                @foreach($personasOic as $p)
                                    <option
                                        value="{{ $p->id }}"
                                        data-referencia="{{ $p->plantilla_referencia }}">
                                        {{ $p->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="conv-group">
                            <label>Referencia OIC</label>
                            <input type="text" name="ref_oic" id="ref_oic">
                        </div>

                        <div class="conv-group">
                            <label>Persona Jurídico</label>
                            <select name="persona_juridico" id="persona_juridico">
                                <option value="">Seleccionar</option>

                                @foreach($personasJuridico as $p)
                                    <option
                                        value="{{ $p->id }}"
                                        data-referencia="{{ $p->plantilla_referencia }}">
                                        {{ $p->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="conv-group">
                            <label>Referencia Jurídico</label>
                            <input type="text" name="ref_juridico" id="ref_juridico">
                        </div>

                    </div>
                </div>

                <!-- PARTICIPANTES -->
                <div class="conv-card">
                    <h3>Participantes</h3>

                    <div class="conv-group">
                        <label>¿Cuántos participantes?</label>
                        <input type="number" id="num_participantes" min="1">
                    </div>

                    <div id="participantes_container"></div>
                </div>

                <button class="conv-btn">Generar Documento</button>

            </form>

        </div>

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const areasRequirentes = @json($areasRequirentes);

    const inputBusqueda = document.getElementById('busqueda_proc');
    const inputNum = document.getElementById('num_procedimiento');
    const inputNombre = document.getElementById('nombre_procedimiento');
    const inputFechaAc = document.getElementById('fecha_ac');
    const inputHoraAc = document.getElementById('hora_ac');

    inputBusqueda.addEventListener('keyup', function () {

        const valor = this.value.trim();

        if (valor === '') {
            limpiarProcedimiento();
            return;
        }

        fetch(`/buscar-procedimiento-ac/${encodeURIComponent(valor)}`)
            .then(response => {

                if (!response.ok) {
                    throw new Error('Error en la búsqueda del procedimiento');
                }

                return response.json();
            })
            .then(data => {

                if (data && data.num_procedimiento) {
                    inputNum.value = data.num_procedimiento ?? '';
                    inputNombre.value = data.nombre_procedimiento ?? '';
                    inputFechaAc.value = data.fecha_ac ?? '';
                    inputHoraAc.value = data.hora_ac ?? '';
                } else {
                    limpiarProcedimiento();
                }

            })
            .catch(error => {
                console.error('Error al buscar procedimiento:', error);
                limpiarProcedimiento();
            });

    });

    function limpiarProcedimiento() {
        inputNum.value = '';
        inputNombre.value = '';
        inputFechaAc.value = '';
        inputHoraAc.value = '';
    }

    const areaSelect = document.getElementById('area_requirente_select');
    const personaSelect = document.getElementById('persona_requirente_select');

    areaSelect.addEventListener('change', function () {

        const areaId = parseInt(this.value);

        personaSelect.innerHTML = '<option value="">Seleccionar persona</option>';

        if (!areaId) {
            personaSelect.innerHTML = '<option value="">Primero seleccione un área</option>';
            return;
        }

        const area = areasRequirentes.find(a => a.id === areaId);

        if (!area || !area.personas || area.personas.length === 0) {
            personaSelect.innerHTML = '<option value="">Sin personas registradas</option>';
            return;
        }

        area.personas.forEach(persona => {
            const option = document.createElement('option');
            option.value = persona.id;
            option.textContent = `${persona.nombre} - ${persona.cargo}`;
            personaSelect.appendChild(option);
        });

    });

    document.getElementById('persona_oic').addEventListener('change', function () {
        const referencia = this.options[this.selectedIndex].dataset.referencia ?? '';
        document.getElementById('ref_oic').value = referencia;
    });

    document.getElementById('persona_juridico').addEventListener('change', function () {
        const referencia = this.options[this.selectedIndex].dataset.referencia ?? '';
        document.getElementById('ref_juridico').value = referencia;
    });

    document.getElementById('num_participantes').addEventListener('input', function () {

        const container = document.getElementById('participantes_container');
        container.innerHTML = '';

        const total = parseInt(this.value);

        if (!total || total < 1) return;

        for (let i = 0; i < total; i++) {

            container.innerHTML += `
                <div class="conv-card" style="margin-top: 15px;">
                    <h3>Participante ${i + 1}</h3>

                    <div class="conv-grid">

                        <div class="conv-group full">
                            <label>Nombre, razón o denominación social</label>
                            <input
                                type="text"
                                name="participantes[${i}][nombre]"
                                placeholder="Empresa ${i + 1}"
                                required>
                        </div>

                        <div class="conv-group">
                            <label>¿Presentó preguntas?</label>
                            <select
                                name="participantes[${i}][pregunta]"
                                class="select-presento"
                                data-index="${i}">
                                <option value="NO">No presentó preguntas</option>
                                <option value="SI">Sí presentó preguntas</option>
                            </select>
                        </div>

                        <div class="conv-group preguntas-count-container" id="preguntas_count_container_${i}" style="display:none;">
                            <label>¿Cuántas preguntas presentó?</label>
                            <input
                                type="number"
                                min="1"
                                class="num-preguntas-participante"
                                data-index="${i}"
                                id="num_preguntas_${i}">
                        </div>

                    </div>

                    <div id="preguntas_container_${i}" class="preguntas-participante-container"></div>
                </div>
            `;
        }

        inicializarEventosParticipantes();

    });

    function inicializarEventosParticipantes() {

        document.querySelectorAll('.select-presento').forEach(select => {

            select.addEventListener('change', function () {

                const index = this.dataset.index;
                const countContainer = document.getElementById(`preguntas_count_container_${index}`);
                const preguntasContainer = document.getElementById(`preguntas_container_${index}`);
                const countInput = document.getElementById(`num_preguntas_${index}`);

                if (this.value === 'SI') {
                    countContainer.style.display = 'block';
                } else {
                    countContainer.style.display = 'none';
                    countInput.value = '';
                    preguntasContainer.innerHTML = '';
                }

            });

        });

        document.querySelectorAll('.num-preguntas-participante').forEach(input => {

            input.addEventListener('input', function () {

                const index = this.dataset.index;
                const total = parseInt(this.value);
                const container = document.getElementById(`preguntas_container_${index}`);

                container.innerHTML = '';

                if (!total || total < 1) return;

                for (let p = 0; p < total; p++) {
                    container.innerHTML += `
                        <div class="conv-group full" style="margin-top: 10px;">
                            <label>Pregunta ${p + 1}</label>
                            <textarea
                                name="participantes[${index}][preguntas][${p}][pregunta]"
                                placeholder="Pregunta ${p + 1}"
                                required></textarea>
                        </div>

                        <div class="conv-group full">
                            <label>Respuesta ${p + 1}</label>
                            <textarea
                                name="participantes[${index}][preguntas][${p}][respuesta]"
                                placeholder="Respuesta ${p + 1}"
                                required></textarea>
                        </div>
                    `;
                }

            });

        });

    }

});
</script>

@endsection