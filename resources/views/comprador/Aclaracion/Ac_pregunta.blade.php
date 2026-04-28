@extends('layouts.app')
@section('content')

<div class="admin-layout">

    @include('comprador.sidebar')

    <div class="admin-content">

        <div class="conv-wrapper">

            <h2 class="conv-title">Junta de Aclaraciones</h2>

            <form action="{{ route('ac.generar') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <!-- 🔥 ARCHIVO WORD (ARRIBA) -->
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
                            <input type="text" id="busqueda_proc" name="numero_busqueda" required>
                        </div>

                        <div class="conv-group">
                            <label>Número completo</label>
                            <input type="text" id="num_procedimiento" readonly>
                        </div>

                        <div class="conv-group">
                            <label>Nombre</label>
                            <input type="text" id="nombre_procedimiento" readonly>
                        </div>

                        <div class="conv-group">
                            <label>Fecha junta</label>
                            <input type="date" id="fecha_ac" readonly>
                        </div>

                        <div class="conv-group">
                            <label>Hora inicio</label>
                            <input type="time" id="hora_ac" readonly>
                        </div>

                    </div>
                </div>

                <!-- RESPONSABLES -->
                <div class="conv-card">
                    <h3>Responsables</h3>

                    <div class="conv-grid">

                        <div class="conv-group">
                            <label>Área requirente</label>
                            <select name="area_requirente" required>
                                <option value="">Seleccionar</option>
                                @foreach($personas as $p)
                                    <option value="{{ $p->id }}">
                                        {{ $p->nombre }} - {{ $p->cargo }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="conv-group">
                            <label>Área contratante</label>
                            <select name="area_contratante" required>
                                <option value="">Seleccionar</option>
                                @foreach($personas as $p)
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
                            <label>Referencia OIC</label>
                            <input type="text" name="ref_oic">
                        </div>

                        <div class="conv-group">
                            <label>Persona OIC</label>
                            <select name="persona_oic">
                                <option value="">Seleccionar</option>
                                @foreach($personas as $p)
                                    <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="conv-group">
                            <label>Referencia Jurídico</label>
                            <input type="text" name="ref_juridico">
                        </div>

                        <div class="conv-group">
                            <label>Persona Jurídico</label>
                            <select name="persona_juridico">
                                <option value="">Seleccionar</option>
                                @foreach($personas as $p)
                                    <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                                @endforeach
                            </select>
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

                <!-- PREGUNTAS -->
                <div class="conv-card">
                    <h3>Preguntas</h3>

                    <div class="conv-group">
                        <label>¿Cuántas preguntas?</label>
                        <input type="number" id="num_preguntas" min="1">
                    </div>

                    <div id="preguntas_container"></div>
                </div>

                <button class="conv-btn">Generar Documento</button>

            </form>

        </div>

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // 🔍 BUSCAR PROCEDIMIENTO
    document.getElementById('busqueda_proc').addEventListener('keyup', function () {

        let valor = this.value;

        if (valor.length >= 1) {

            fetch(`/buscar-procedimiento/${valor}`)
                .then(res => res.json())
                .then(data => {

                    if (data) {
                        document.getElementById('num_procedimiento').value = data.num_procedimiento;
                        document.getElementById('nombre_procedimiento').value = data.nombre_procedimiento;
                        document.getElementById('fecha_ac').value = data.fecha_ac;
                        document.getElementById('hora_ac').value = data.hora_ac;
                    }
                });
        }
    });

    // 👥 PARTICIPANTES (CORREGIDO)
    document.getElementById('num_participantes').addEventListener('input', function () {

        let container = document.getElementById('participantes_container');
        container.innerHTML = '';

        let total = parseInt(this.value);

        if (!total || total < 1) return;

        for (let i = 0; i < total; i++) {

            container.innerHTML += `
                <div class="conv-group">
                    <input type="text" name="participantes[${i}][nombre]" placeholder="Empresa ${i+1}" required>

                    <select name="participantes[${i}][pregunta]">
                        <option value="SI">Sí presentó preguntas</option>
                        <option value="NO">No presentó preguntas</option>
                    </select>
                </div>
            `;
        }
    });

    // ❓ PREGUNTAS (CORREGIDO)
    document.getElementById('num_preguntas').addEventListener('input', function () {

        let container = document.getElementById('preguntas_container');
        container.innerHTML = '';

        let total = parseInt(this.value);

        if (!total || total < 1) return;

        for (let i = 0; i < total; i++) {

            container.innerHTML += `
                <div class="conv-group">
                    <textarea name="preguntas[${i}][pregunta]" placeholder="Pregunta ${i+1}" required></textarea>
                    <textarea name="preguntas[${i}][respuesta]" placeholder="Respuesta ${i+1}" required></textarea>
                </div>
            `;
        }

    });

});
</script>

@endsection