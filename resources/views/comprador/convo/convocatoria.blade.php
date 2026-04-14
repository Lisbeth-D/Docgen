@extends('layouts.app')
@section('content')

<div class="admin-layout">

    @include('comprador.sidebar')

    <div class="admin-content">

        <div class="conv-wrapper">

            <h2 class="conv-title">Formulario de Convocatoria</h2>

            <form action="{{ route('procedimientos.store') }}" method="POST" enctype="multipart/form-data" class="conv-form">
                @csrf

                <!-- ARCHIVO WORD -->
                <div class="conv-card">
                    <h3>Plantilla Word</h3>

                    <div class="conv-group full">
                        <label>Subir archivo Word (.docx)</label>
                        <input type="file" name="archivo_word" accept=".docx" required>
                    </div>
                </div>

                <!-- DATOS PRINCIPALES -->
                <div class="conv-card">
                    <h3>Datos principales</h3>

                    <div class="conv-grid">

                        <div class="conv-group full">
                            <label>Nombre del procedimiento</label>
                            <input type="text" name="nombre_procedimiento" required>
                        </div>

                        <div class="conv-group">
                            <label>Número de procedimiento</label>
                            <input type="text" name="num_procedimiento" required>
                        </div>

                        <div class="conv-group">
                            <label>Fecha publicación</label>
                            <input type="date" name="fecha_publicacion">
                        </div>

                        <!-- VISITA O MUESTRA -->
                        <div class="conv-group">
                            <label>¿Aplica visita o muestra?</label>
                            <select id="aplica_vm" name="aplica_vm">
                                <option value="">Seleccionar</option>
                                <option value="SI">Sí</option>
                                <option value="NO">No</option>
                            </select>
                        </div>

                        <!-- CAMPOS VM -->
                        <div id="campo_vm" style="display:none;" class="conv-group full">

                            <div class="conv-group">
                                <label>Fecha VM</label>
                                <input type="date" name="fecha_vm" id="fecha_vm" disabled>
                            </div>

                            <div class="conv-group">
                                <label>Hora VM</label>
                                <input type="time" name="hora_vm" id="hora_vm" disabled>
                            </div>

                        </div>

                        <!-- ACL -->
                        <div class="conv-group">
                            <label>¿Aplica junta de aclaraciones?</label>
                            <select name="aplica_acl" id="aplica_acl">
                                <option value="NO" selected>No</option>
                                <option value="SI">Sí</option>
                            </select>
                        </div>

                        <div class="conv-group">
                            <label>Fecha ACL</label>
                            <input type="date" name="fecha_acl" id="fecha_acl" disabled>
                        </div>

                        <div class="conv-group">
                            <label>Hora ACL</label>
                            <input type="time" name="hora_acl" id="hora_acl" disabled>
                        </div>

                        <div class="conv-group">
                            <label>Fecha apertura</label>
                            <input type="date" name="fecha_apertura">
                        </div>

                        <div class="conv-group">
                            <label>Hora apertura</label>
                            <input type="time" name="hora_apertura">
                        </div>

                        <div class="conv-group">
                            <label>Fecha fallo</label>
                            <input type="date" name="fecha_fallo">
                        </div>

                        <div class="conv-group">
                            <label>Hora fallo</label>
                            <input type="time" name="hora_fallo">
                        </div>

                    </div>
                </div>

                <!-- DATOS DOCUMENTO -->
                <div class="conv-card">
                    <h3>Datos para documento</h3>

                    <div class="conv-grid">

                        <div class="conv-group">
                            <label>Número de partida</label>
                            <input type="text" name="num_partida">
                        </div>

                        <div class="conv-group">
                            <label>Nombre de partida</label>
                            <input type="text" name="partida_nombre">
                        </div>

                        <div class="conv-group">
                            <label>Número de requisición</label>
                            <input type="text" name="num_requisicion">
                        </div>

                        <!-- RESPONSABLE TÉCNICO -->
                        <div class="conv-group">
                            <label>Responsable técnico</label>

                            <select name="resp_tecnico" id="resp_tecnico">
                                <option value="">Seleccionar persona</option>

                                @foreach($personas as $persona)
                                    <option value="{{ $persona->nombre }}"
                                            data-cargo="{{ $persona->cargo }}">
                                        {{ $persona->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="conv-group">
                            <label>Cargo técnico</label>
                            <input type="text" name="cargo_tecnico" id="cargo_tecnico" readonly>
                        </div>

                        <div class="conv-group">
                            <label>Monto máximo</label>
                            <input type="text" name="monto_maximo">
                        </div>

                        <div class="conv-group">
                            <label>Monto mínimo</label>
                            <input type="text" name="monto_minimo">
                        </div>

                        <div class="conv-group">
                            <label>Plazo contrato</label>
                            <input type="text" name="plazo_contrato">
                        </div>

                    </div>
                </div>

                <button type="submit" class="conv-btn">
                    Guardar y descargar Word
                </button>

            </form>

        </div>

    </div>

</div>

<!-- SCRIPT GENERAL -->
<script>

// AUTOCOMPLETAR CARGOS
document.getElementById('resp_tecnico').addEventListener('change', function() {
    let cargo = this.options[this.selectedIndex].getAttribute('data-cargo');
    document.getElementById('cargo_tecnico').value = cargo || '';
});

document.getElementById('resp_admin').addEventListener('change', function() {
    let cargo = this.options[this.selectedIndex].getAttribute('data-cargo');
    document.getElementById('cargo_admin').value = cargo || '';
});

// VISITA O MUESTRA
document.getElementById('aplica_vm').addEventListener('change', function () {

    let activa = this.value === 'SI';

    document.getElementById('campo_vm').style.display = activa ? 'block' : 'none';
    document.getElementById('fecha_vm').disabled = !activa;
    document.getElementById('hora_vm').disabled = !activa;

    if (!activa) {
        document.getElementById('fecha_vm').value = '';
        document.getElementById('hora_vm').value = '';
    }
});

// ACL
document.getElementById('aplica_acl').addEventListener('change', function () {

    let activa = this.value === 'SI';

    document.getElementById('fecha_acl').disabled = !activa;
    document.getElementById('hora_acl').disabled = !activa;

    if (!activa) {
        document.getElementById('fecha_acl').value = '';
        document.getElementById('hora_acl').value = '';
    }
});

</script>

@endsection