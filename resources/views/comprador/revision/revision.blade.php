@extends('layouts.app')

@section('content')

<div class="admin-layout">

    @include('comprador.sidebar')

    <div class="admin-content">

        <div class="conv-wrapper">

            <form action="{{ route('revision.generar') }}" method="POST" enctype="multipart/form-data" class="conv-form">
                @csrf
            <h2 class="conv-title">Formulario de Revisión</h2>

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
                            <label>Tipo procedimiento</label>
                            <input
                                type="text"
                                id="tipo_procedimiento"
                                name="tipo_procedimiento">
                        </div>

                        <div class="conv-group">
                            <label>Fecha de publicación</label>
                            <input type="date" name="fecha_publicacion">
                        </div>

                        <!-- REVISÓ -->
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
    const inputTipoProcedimiento = document.getElementById('tipo_procedimiento');

    inputBusqueda.addEventListener('keyup', function () {

        const valor = this.value.trim();

        if (valor === '') {
            limpiarCamposProcedimiento();
            return;
        }

        fetch(`/buscar-procedimiento/${encodeURIComponent(valor)}`)
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
                    inputTipoProcedimiento.value = data.tipo ?? '';
                } else {
                    limpiarCamposProcedimiento();
                }

            })
            .catch(error => {
                console.error('Error al buscar procedimiento:', error);
                limpiarCamposProcedimiento();
            });

    });

    function limpiarCamposProcedimiento() {
        inputNumProcedimiento.value = '';
        inputNombreProcedimiento.value = '';
        inputTipoProcedimiento.value = '';
    }

});
</script>

@endsection