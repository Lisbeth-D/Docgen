@extends('layouts.app')

@section('content')

<div class="admin-layout">

    @include('comprador.sidebar')

    <div class="admin-content">

        <div class="conv-wrapper">

        <form class="conv-form">

            <h2 class="conv-title">
                Acta de Junta de Aclaraciones
            </h2>

            <form action="{{ route('ac.generar') }}"
                  method="POST"
                  enctype="multipart/form-data">

                @csrf

                {{-- ========================================= --}}
                {{-- PLANTILLA WORD --}}
                {{-- ========================================= --}}
                <div class="conv-card">

                    <h3>Plantilla Word</h3>

                    <div class="conv-group full">
                        <input type="file"
                               name="archivo_word"
                               accept=".docx"
                               required>
                    </div>

                </div>

                {{-- ========================================= --}}
                {{-- PROCEDIMIENTO --}}
                {{-- ========================================= --}}
                <div class="conv-card">

                    <h3>Datos del Procedimiento</h3>

                    <div class="conv-grid">

                        <div class="conv-group">
                            <label>Número de procedimiento</label>

                            <input type="text"
                                   id="busqueda_proc"
                                   name="numero_busqueda"
                                   required>
                        </div>

                        <div class="conv-group">
                            <label>Número completo</label>

                            <input type="text"
                                   id="num_procedimiento"
                                   readonly>
                        </div>

                        <div class="conv-group">
                            <label>Nombre</label>

                            <input type="text"
                                   id="nombre_procedimiento"
                                   readonly>
                        </div>

                        <div class="conv-group">
                            <label>Fecha junta</label>

                            <input type="date"
                                   id="fecha_ac"
                                   readonly>
                        </div>

                        <div class="conv-group">
                            <label>Hora junta</label>

                            <input type="time"
                                   id="hora_ac"
                                   readonly>
                        </div>

                        <div class="conv-group">
                            <label>Fecha apertura</label>

                            <input type="date"
                                   id="fecha_apertura"
                                   readonly>
                        </div>

                        <div class="conv-group">
                            <label>Hora apertura</label>

                            <input type="time"
                                   id="hora_apertura"
                                   readonly>
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

                            <label>Área requirente</label>

                            <select name="area_requirente" required>

                                <option value="">
                                    Seleccionar
                                </option>

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

                                <option value="">
                                    Seleccionar
                                </option>

                                @foreach($personas as $p)

                                    <option value="{{ $p->id }}">
                                        {{ $p->nombre }} - {{ $p->cargo }}
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

                    <h3>OIC / Jurídico</h3>

                    <div class="conv-grid">

                        <div class="conv-group">

                            <label>Referencia OIC</label>

                            <input type="text"
                                   name="ref_oic">

                        </div>

                        <div class="conv-group">

                            <label>Persona OIC</label>

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

                            <label>Referencia Jurídico</label>

                            <input type="text"
                                   name="ref_juridico">

                        </div>

                        <div class="conv-group">

                            <label>Persona Jurídico</label>

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
                {{-- PARTICIPANTES --}}
                {{-- ========================================= --}}
                <div class="conv-card">

                    <h3>Empresas participantes</h3>

                    <div class="conv-group">

                        <label>
                            ¿Cuántas empresas se presentaron?
                        </label>

                        <input type="number"
                               id="num_participantes"
                               min="1">

                    </div>

                    <div id="participantes_container"></div>

                </div>

                {{-- ========================================= --}}
                {{-- BOTON --}}
                {{-- ========================================= --}}
                <div class="conv-card">

                    <button type="submit" class="btn btn-primary">
                        Generar acta
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<script>

document.addEventListener('DOMContentLoaded', function () {

    // =========================================
    // BUSCAR PROCEDIMIENTO
    // =========================================

    document.getElementById('busqueda_proc')
        .addEventListener('keyup', function () {

        let valor = this.value;

        if (valor.length >= 1) {

            fetch(`/buscar-procedimiento-ac/${valor}`)

                .then(res => res.json())

                .then(data => {

                    if (data) {

                        document.getElementById('num_procedimiento').value =
                            data.num_procedimiento ?? '';

                        document.getElementById('nombre_procedimiento').value =
                            data.nombre_procedimiento ?? '';

                        document.getElementById('fecha_ac').value =
                            data.fecha_ac ?? '';

                        document.getElementById('hora_ac').value =
                            data.hora_ac ?? '';

                        document.getElementById('fecha_apertura').value =
                            data.fecha_apertura ?? '';

                        document.getElementById('hora_apertura').value =
                            data.hora_apertura ?? '';
                    }
                });
        }
    });

    // =========================================
    // PARTICIPANTES
    // =========================================

    document.getElementById('num_participantes')
        .addEventListener('input', function () {

        let container =
            document.getElementById('participantes_container');

        container.innerHTML = '';

        let total = parseInt(this.value);

        if (!total || total < 1) return;

        for (let i = 0; i < total; i++) {

            container.innerHTML += `

                <div class="conv-group">

                    <label>
                        Empresa ${i + 1}
                    </label>

                    <input type="text"
                           name="participantes[${i}][nombre]"
                           placeholder="Nombre de empresa"
                           required>

                </div>

            `;
        }
    });

});

</script>

@endsection