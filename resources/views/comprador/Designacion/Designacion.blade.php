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

                        <div class="conv-group">
                            <label>Número de referencia</label>
                            <input type="text" name="numero_referencia" required>
                        </div>

                        <div class="conv-group">
                            <label>Fecha oficio</label>
                            <input type="date" name="fecha_oficio" required>
                        </div>

                        <div class="conv-group">
                            <label>Buscar procedimiento</label>
                            <input
                                type="text"
                                id="busqueda_proc"
                                name="numero_busqueda"
                                placeholder="Ejemplo: 25"
                                autocomplete="off"
                                required>
                        </div>

                        <div class="conv-group">
                            <label>Número procedimiento</label>
                            <input
                                type="text"
                                id="num_procedimiento"
                                name="num_procedimiento"
                                required>
                        </div>

                        <div class="conv-group">
                            <label>Nombre procedimiento</label>
                            <input
                                type="text"
                                id="nombre_procedimiento"
                                name="nombre_procedimiento"
                                required>
                        </div>

                        <div class="conv-group">
                            <label>Fecha visita/muestra</label>
                            <input
                                type="text"
                                id="fecha_vm"
                                name="fecha_vm">
                        </div>

                        <div class="conv-group">
                            <label>Hora visita/muestra</label>
                            <input
                                type="text"
                                id="hora_vm"
                                name="hora_vm">
                        </div>

                        <div class="conv-group">
                            <label>Fecha Junta de Aclaraciones</label>
                            <input
                                type="date"
                                id="fecha_ac"
                                name="fecha_ac">
                        </div>

                        <div class="conv-group">
                            <label>Hora Junta de Aclaraciones</label>
                            <input
                                type="time"
                                id="hora_ac"
                                name="hora_ac">
                        </div>

                        <div class="conv-group">
                            <label>Fecha Junta de Apertura</label>
                            <input
                                type="date"
                                id="fecha_apertura"
                                name="fecha_apertura">
                        </div>

                        <div class="conv-group">
                            <label>Hora Junta de Apertura</label>
                            <input
                                type="time"
                                id="hora_apertura"
                                name="hora_apertura">
                        </div>

                        <div class="conv-group">
                            <label>Fecha Junta de Fallo</label>
                            <input
                                type="date"
                                id="fecha_fallo"
                                name="fecha_fallo">
                        </div>

                        <div class="conv-group">
                            <label>Hora Junta de Fallo</label>
                            <input
                                type="time"
                                id="hora_fallo"
                                name="hora_fallo">
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

    const inputNumProcedimiento = document.getElementById('num_procedimiento');
    const inputNombreProcedimiento = document.getElementById('nombre_procedimiento');

    const inputFechaVm = document.getElementById('fecha_vm');
    const inputHoraVm = document.getElementById('hora_vm');

    const inputFechaAc = document.getElementById('fecha_ac');
    const inputHoraAc = document.getElementById('hora_ac');

    const inputFechaApertura = document.getElementById('fecha_apertura');
    const inputHoraApertura = document.getElementById('hora_apertura');

    const inputFechaFallo = document.getElementById('fecha_fallo');
    const inputHoraFallo = document.getElementById('hora_fallo');

    inputBusqueda.addEventListener('keyup', function () {

        const valor = this.value.trim();

        if (valor === '') {
            limpiarCampos();
            return;
        }

        fetch(`/buscar-procedimiento-designacion/${encodeURIComponent(valor)}`)
            .then(response => {

                if (!response.ok) {
                    throw new Error('Error en la búsqueda del procedimiento');
                }

                return response.json();
            })
            .then(data => {

                if (data && data.num_procedimiento) {
                    inputNumProcedimiento.value = data.num_procedimiento ?? '';
                    inputNombreProcedimiento.value = data.nombre_procedimiento ?? '';

                    inputFechaVm.value = data.fecha_vm ?? '';
                    inputHoraVm.value = data.hora_vm ?? '';

                    inputFechaAc.value = data.fecha_ac ?? '';
                    inputHoraAc.value = data.hora_ac ?? '';

                    inputFechaApertura.value = data.fecha_apertura ?? '';
                    inputHoraApertura.value = data.hora_apertura ?? '';

                    inputFechaFallo.value = data.fecha_fallo ?? '';
                    inputHoraFallo.value = data.hora_fallo ?? '';
                } else {
                    limpiarCampos();
                }

            })
            .catch(error => {
                console.error('Error al buscar procedimiento:', error);
                limpiarCampos();
            });

    });

    function limpiarCampos() {
        inputNumProcedimiento.value = '';
        inputNombreProcedimiento.value = '';

        inputFechaVm.value = '';
        inputHoraVm.value = '';

        inputFechaAc.value = '';
        inputHoraAc.value = '';

        inputFechaApertura.value = '';
        inputHoraApertura.value = '';

        inputFechaFallo.value = '';
        inputHoraFallo.value = '';
    }

});
</script>

@endsection