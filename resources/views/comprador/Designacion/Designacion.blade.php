@extends('layouts.app')
@section('content')

<div class="admin-layout">

    @include('comprador.sidebar')

    <div class="admin-content">

        <div class="conv-wrapper">

            <h2 class="conv-title">Formulario de Designación</h2>

            <form action="{{ route('designacion.generar') }}" method="POST" enctype="multipart/form-data" class="conv-form">
                @csrf

                <!-- WORD -->
                <div class="conv-card">
                    <h3>Plantilla Word</h3>

                    <div class="conv-group full">
                        <label>Subir archivo Word (.docx)</label>
                        <input type="file" name="archivo_word" accept=".docx" required>
                    </div>
                </div>

                <!-- DATOS -->
                <div class="conv-card">
                    <h3>Datos de designación</h3>

                    <div class="conv-grid">

                        <!-- REFERENCIA -->
                        <div class="conv-group">
                            <label>Número de referencia</label>
                            <input type="text" name="numero_referencia">
                        </div>

                        <!-- FECHA OFICIO -->
                        <div class="conv-group">
                            <label>Fecha oficio</label>
                            <input type="date" name="fecha_oficio">
                        </div>

                        <!-- BUSCAR PROCEDIMIENTO -->
                        <div class="conv-group">
                            <label>Buscar procedimiento</label>
                            <input type="text" id="busqueda_proc" name="numero_busqueda">
                        </div>

                        <!-- AUTO -->
                        <div class="conv-group">
                            <label>Número procedimiento</label>
                            <input type="text" id="num_procedimiento" readonly>
                        </div>

                        <div class="conv-group">
                            <label>Nombre procedimiento</label>
                            <input type="text" id="nombre_procedimiento" readonly>
                        </div>

                        <!-- VISITA / MUESTRA -->
                        <div class="conv-group">
                            <label>Fecha visita/muestra</label>
                            <input type="text" id="fecha_vm" name="fecha_vm" readonly>
                        </div>

                        <div class="conv-group">
                            <label>Hora visita/muestra</label>
                            <input type="text" id="hora_vm" name="hora_vm" readonly>
                        </div>

                        <!-- ACLARACIONES -->
                        <div class="conv-group">
                            <label>Fecha Junta de Aclaraciones</label>
                            <input type="date" id="fecha_ac" name="fecha_ac" readonly>
                        </div>

                        <div class="conv-group">
                            <label>Hora Junta de Aclaraciones</label>
                            <input type="time" id="hora_ac" name="hora_ac" readonly>
                        </div>

                        <!-- APERTURA -->
                        <div class="conv-group">
                            <label>Fecha Junta de Apertura</label>
                            <input type="date" id="fecha_apertura" name="fecha_apertura" readonly>
                        </div>

                        <div class="conv-group">
                            <label>Hora Junta de Apertura</label>
                            <input type="time" id="hora_apertura" name="hora_apertura" readonly>
                        </div>

                        <!-- FALLO -->
                        <div class="conv-group">
                            <label>Fecha Junta de Fallo</label>
                            <input type="date" id="fecha_fallo" name="fecha_fallo" readonly>
                        </div>

                        <div class="conv-group">
                            <label>Hora Junta de Fallo</label>
                            <input type="time" id="hora_fallo" name="hora_fallo" readonly>
                        </div>

                        <!-- REVISO -->
                        <div class="conv-group">
                            <label>Revisó</label>
                            <select name="reviso_id">
                                <option value="">Seleccionar</option>
                                @foreach($personas as $p)
                                    <option value="{{ $p->id }}">
                                        {{ $p->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                    </div>
                </div>

                <button type="submit" class="conv-btn">
                    Generar Word
                </button>

            </form>

        </div>

    </div>

</div>

<!-- SCRIPT -->
<script>
document.addEventListener('DOMContentLoaded', function () {

    const inputBusqueda = document.getElementById('busqueda_proc');

    inputBusqueda.addEventListener('keyup', function () {

        let valor = this.value;

        if (valor.length >= 1) {

            fetch(`/buscar-procedimiento/${valor}`)
                .then(res => res.json())
                .then(data => {

                    if (data) {
                        document.getElementById('num_procedimiento').value = data.num_procedimiento;
                        document.getElementById('nombre_procedimiento').value = data.nombre_procedimiento;

                        // FECHAS
                        document.getElementById('fecha_vm').value = data.fecha_vm;
                        document.getElementById('fecha_ac').value = data.fecha_ac;
                        document.getElementById('fecha_apertura').value = data.fecha_apertura;
                        document.getElementById('fecha_fallo').value = data.fecha_fallo;

                        // HORAS
                        document.getElementById('hora_vm').value = data.hora_vm;
                        document.getElementById('hora_ac').value = data.hora_ac;
                        document.getElementById('hora_apertura').value = data.hora_apertura;
                        document.getElementById('hora_fallo').value = data.hora_fallo;

                    } else {
                        limpiarCampos();
                    }

                })
                .catch(() => limpiarCampos());
        } else {
            limpiarCampos();
        }
    });

    function limpiarCampos() {
        document.getElementById('num_procedimiento').value = '';
        document.getElementById('nombre_procedimiento').value = '';

        document.getElementById('fecha_vm').value = '';
        document.getElementById('fecha_ac').value = '';
        document.getElementById('fecha_apertura').value = '';
        document.getElementById('fecha_fallo').value = '';

        document.getElementById('hora_vm').value = '';
        document.getElementById('hora_ac').value = '';
        document.getElementById('hora_apertura').value = '';
        document.getElementById('hora_fallo').value = '';
    }

});
</script>

@endsection