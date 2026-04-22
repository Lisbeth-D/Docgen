@extends('layouts.app')
@section('content')

<div class="admin-layout">

```
@include('comprador.sidebar')

<div class="admin-content">

    <div class="conv-wrapper">

        <h2 class="conv-title">Formulario de Publicación</h2>

        <form action="{{ route('publicacion.generar') }}" method="POST" enctype="multipart/form-data" class="conv-form">
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
                <h3>Datos de publicación</h3>

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

                    <!-- FECHA PUBLICACIÓN (AUTO DESDE BD) -->
                    <div class="conv-group">
                        <label>Fecha de publicación</label>
                        <input type="date" id="fecha_publicacion" name="fecha_publicacion" readonly>
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
```

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

                        // 🔥 FECHA DESDE BD
                        document.getElementById('fecha_publicacion').value = data.fecha_publicacion;

                    } else {
                        document.getElementById('num_procedimiento').value = '';
                        document.getElementById('nombre_procedimiento').value = '';
                        document.getElementById('fecha_publicacion').value = '';
                    }

                });
        }
    });

});
</script>

@endsection
