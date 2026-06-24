@extends('layouts.app')

@section('content')

<div class="admin-layout">

    @include('comprador.sidebar')

    <div class="admin-content">

        <div class="conv-wrapper">

        <form class="conv-form">

            <h2 class="conv-title">
                Acta de Cierre
            </h2>

            <form
                action="{{ route('actacierre.generar') }}"
                method="POST"
                enctype="multipart/form-data"
            >

                @csrf

                {{-- ========================================= --}}
                {{-- WORD --}}
                {{-- ========================================= --}}
                <div class="conv-card">

                    <h3>Plantilla Word</h3>

                    <div class="conv-group full">

                        <input
                            type="file"
                            name="archivo_word"
                            accept=".docx"
                            required
                        >

                    </div>

                </div>

                {{-- ========================================= --}}
                {{-- PROCEDIMIENTO --}}
                {{-- ========================================= --}}
                <div class="conv-card">

                    <h3>
                        Datos del Procedimiento
                    </h3>

                    <div class="conv-grid">

                        <div class="conv-group">

                            <label>
                                Número de procedimiento
                            </label>

                            <input
                                type="text"
                                id="busqueda_proc"
                                name="numero_busqueda"
                                required
                            >

                        </div>

                        <div class="conv-group">

                            <label>
                                Número completo
                            </label>

                            <input
                                type="text"
                                id="num_procedimiento"
                                readonly
                            >

                        </div>

                        <div class="conv-group">

                            <label>
                                Nombre
                            </label>

                            <input
                                type="text"
                                id="nombre_procedimiento"
                                readonly
                            >

                        </div>

                        <div class="conv-group">

                            <label>
                                Fecha junta
                            </label>

                            <input
                                type="date"
                                id="fecha_ac"
                                readonly
                            >

                        </div>

                        <div class="conv-group">

                            <label>
                                Hora inicio
                            </label>

                            <input
                                type="time"
                                id="hora_ac"
                                readonly
                            >

                        </div>

                    </div>

                </div>

                {{-- ========================================= --}}
                {{-- HORAS --}}
                {{-- ========================================= --}}
                <div class="conv-card">

                    <h3>
                        Horas de suspensión
                    </h3>

                    <div class="conv-grid">

                        <div class="conv-group">

                            <label>
                                Hora suspendida
                            </label>

                            <input
                                type="time"
                                name="hora_suspendida"
                                required
                            >

                        </div>

                        <div class="conv-group">

                            <label>
                                Hora reanudación
                            </label>

                            <input
                                type="time"
                                name="hora_reanudacion"
                                required
                            >

                        </div>

                    </div>

                </div>

                {{-- ========================================= --}}
                {{-- RESPONSABLES --}}
                {{-- ========================================= --}}
                <div class="conv-card">

                    <h3>
                        Responsables
                    </h3>

                    <div class="conv-grid">

                        <div class="conv-group">

                            <label>
                                Área requirente
                            </label>

                            <select
                                name="area_requirente"
                                required
                            >

                                <option value="">
                                    Seleccionar
                                </option>

                                @foreach($personas as $p)

                                    <option value="{{ $p->id }}">
                                        {{ $p->nombre }}
                                        -
                                        {{ $p->cargo }}
                                    </option>

                                @endforeach

                            </select>

                        </div>

                        <div class="conv-group">

                            <label>
                                Área contratante
                            </label>

                            <select
                                name="area_contratante"
                                required
                            >

                                <option value="">
                                    Seleccionar
                                </option>

                                @foreach($personas as $p)

                                    <option value="{{ $p->id }}">
                                        {{ $p->nombre }}
                                        -
                                        {{ $p->cargo }}
                                    </option>

                                @endforeach

                            </select>

                        </div>

                    </div>

                </div>

                {{-- ========================================= --}}
                {{-- OIC / JURIDICO --}}
                {{-- ========================================= --}}
                <div class="conv-card">

                    <h3>
                        OIC / Jurídico
                    </h3>

                    <div class="conv-grid">

                        <div class="conv-group">

                            <label>
                                Persona OIC
                            </label>

                            <select name="persona_oic">

                                <option value="">
                                    Seleccionar
                                </option>

                                @foreach($personas as $p)

                                    <option value="{{ $p->id }}">
                                        {{ $p->nombre }}
                                    </option>

                                @endforeach

                            </select>

                        </div>

                        <div class="conv-group">

                            <label>
                                Persona Jurídico
                            </label>

                            <select name="persona_juridico">

                                <option value="">
                                    Seleccionar
                                </option>

                                @foreach($personas as $p)

                                    <option value="{{ $p->id }}">
                                        {{ $p->nombre }}
                                    </option>

                                @endforeach

                            </select>

                        </div>

                    </div>

                </div>

                {{-- ========================================= --}}
                {{-- REPREGUNTAS --}}
                {{-- ========================================= --}}
                <div class="conv-card">

                    <h3>
                        Repreguntas
                    </h3>

                    <div class="conv-group">

                        <label>
                            ¿Hubo repreguntas?
                        </label>

                        <select id="hubo_repreguntas"
                                name="hubo_repreguntas">

                            <option value="no">
                                NO
                            </option>

                            <option value="si">
                                SI
                            </option>

                        </select>

                    </div>

                </div>

                {{-- ========================================= --}}
                {{-- PARTICIPANTES --}}
                {{-- ========================================= --}}
                <div class="conv-card">

                    <h3>
                        Participantes
                    </h3>

                    <div class="conv-group">

                        <label>
                            ¿Cuántos participantes?
                        </label>

                        <input
                            type="number"
                            id="num_participantes"
                            min="1"
                        >

                    </div>

                    <div id="participantes_container"></div>

                </div>

                {{-- ========================================= --}}
                {{-- BOTON --}}
                {{-- ========================================= --}}
                <div class="conv-card">

                    <button
                        type="submit"
                        class="btn-primary"
                    >

                        Generar Acta

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        // =====================================
        // BUSCAR PROCEDIMIENTO
        // =====================================

        document.getElementById(
            'busqueda_proc'
        ).addEventListener(
            'keyup',
            function () {

                let valor = this.value;

                if (valor.length >= 1) {

                    fetch(
                        `/buscar-procedimiento-actacierre/${valor}`
                    )
                    .then(res => res.json())
                    .then(data => {

                        if (data) {

                            document.getElementById(
                                'num_procedimiento'
                            ).value =
                                data.num_procedimiento;

                            document.getElementById(
                                'nombre_procedimiento'
                            ).value =
                                data.nombre_procedimiento;

                            document.getElementById(
                                'fecha_ac'
                            ).value =
                                data.fecha_ac;

                            document.getElementById(
                                'hora_ac'
                            ).value =
                                data.hora_ac;
                        }
                    });
                }
            }
        );

        // =====================================
        // PARTICIPANTES
        // =====================================

        document.getElementById(
            'num_participantes'
        ).addEventListener(
            'input',
            renderParticipantes
        );

        document.getElementById(
            'hubo_repreguntas'
        ).addEventListener(
            'change',
            renderParticipantes
        );

        function renderParticipantes() {

            let container =
                document.getElementById(
                    'participantes_container'
                );

            container.innerHTML = '';

            let total =
                parseInt(
                    document.getElementById(
                        'num_participantes'
                    ).value
                );

            let hubo =
                document.getElementById(
                    'hubo_repreguntas'
                ).value;

            if (!total || total < 1) {
                return;
            }

            for (let i = 0; i < total; i++) {

                // =========================
                // SI HUBO REPREGUNTAS
                // =========================

                if (hubo == 'si') {

                    container.innerHTML += `

                        <div class="conv-card">

                            <h4>
                                Empresa ${i + 1}
                            </h4>

                            <div class="conv-group">

                                <input
                                    type="text"
                                    name="participantes[${i}][nombre]"
                                    placeholder="Empresa"
                                    required
                                >

                            </div>

                            <div class="conv-group">

                                <textarea
                                    name="participantes[${i}][repregunta]"
                                    placeholder="Repregunta"
                                    required
                                ></textarea>

                            </div>

                            <div class="conv-group">

                                <textarea
                                    name="participantes[${i}][respuesta]"
                                    placeholder="Respuesta"
                                    required
                                ></textarea>

                            </div>

                        </div>

                    `;

                } else {

                    // =========================
                    // NO HUBO REPREGUNTAS
                    // =========================

                    container.innerHTML += `

                        <div class="conv-group">

                            <input
                                type="text"
                                name="participantes[${i}][nombre]"
                                placeholder="Empresa ${i + 1}"
                                required
                            >

                        </div>

                    `;
                }
            }
        }

    }
);

</script>

@endsection