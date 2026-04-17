@extends('layouts.app')
@section('content')

<div class="admin-layout">

    @include('comprador.sidebar')

    <div class="admin-content">

        <div class="conv-wrapper">

            <h2 class="conv-title">Formulario de Revisión</h2>

            <form action="{{ route('revision.generar') }}" method="POST" enctype="multipart/form-data" class="conv-form">
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
                    <h3>Datos de revisión</h3>

                    <div class="conv-grid">

                        <div class="conv-group">
                            <label>Número de referencia</label>
                            <input type="text" name="numero_referencia">
                        </div>

                        <div class="conv-group">
                            <label>Fecha oficio</label>
                            <input type="date" name="fecha_oficio">
                        </div>

                        <!-- BUSCADOR -->
                        <div class="conv-group">
                            <label>Buscar procedimiento (solo número)</label>
                            <input type="text" id="busqueda_proc">
                        </div>

                        <!-- AUTO -->
                        <div class="conv-group">
                            <label>Número procedimiento</label>
                            <input type="text" name="num_procedimiento" id="num_procedimiento" readonly>
                        </div>

                        <div class="conv-group">
                            <label>Nombre procedimiento</label>
                            <input type="text" name="nombre_procedimiento" id="nombre_procedimiento" readonly>
                        </div>

                        <div class="conv-group">
                            <label>Tipo procedimiento</label>
                            <input type="text" name="tipo_procedimiento" id="tipo_procedimiento" readonly>
                        </div>

                        <!-- REVISO -->
                        <div class="conv-group">
                            <label>Revisó</label>
                            <select name="reviso_id">
                                <option value="">Seleccionar</option>
                                @foreach($personas as $p)
                                    <option value="{{ $p->id }}">{{ $p->nombre }}</option>
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
                        document.getElementById('tipo_procedimiento').value = data.tipo;
                    } else {
                        document.getElementById('num_procedimiento').value = '';
                        document.getElementById('nombre_procedimiento').value = '';
                        document.getElementById('tipo_procedimiento').value = '';
                    }

                });
        }
    });

});
</script>

@endsection