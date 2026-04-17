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

                        <!-- TIPO PROCEDIMIENTO -->
                        <div class="conv-group">
                            <label>Tipo de procedimiento</label>
                            <select name="id_tipo_procedimiento" required>
                                <option value="">Seleccionar</option>
                                @foreach($tipos as $tipo)
                                    <option value="{{ $tipo->id_tipo_procedimiento }}">
                                        {{ $tipo->nombre_tipo }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

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
                        <div id="campo_vm" class="conv-group full" style="display:none;">
                            <div class="conv-group">
                                <label>Fecha VM</label>
                                <input type="date" name="fecha_vm" id="fecha_vm">
                            </div>

                            <div class="conv-group">
                                <label>Hora VM</label>
                                <input type="time" name="hora_vm" id="hora_vm">
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

                        <!-- CAMPOS ACL -->
                        <div id="campo_acl" class="conv-group full" style="display:none;">
                            <div class="conv-group">
                                <label>Fecha ACL</label>
                                <input type="date" name="fecha_acl" id="fecha_acl">
                            </div>

                            <div class="conv-group">
                                <label>Hora ACL</label>
                                <input type="time" name="hora_acl" id="hora_acl">
                            </div>
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

                        <!-- RESPONSABLE -->
                        <div class="conv-group">
                            <label>Responsable técnico</label>

                            <select name="resp_tecnico" id="resp_tecnico">
                                <option value="">Seleccionar persona</option>

                                @foreach($personas as $persona)
                                    <option value="{{ $persona->id }}"
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
                            <label>Fecha inicio contrato</label>
                            <input type="date" name="fecha_inicio_contrato">
                        </div>

                        <div class="conv-group">
                            <label>Fecha fin contrato</label>
                            <input type="date" name="fecha_fin_contrato">
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

<!-- SCRIPT -->
<script>
document.addEventListener('DOMContentLoaded', function () {

    // AUTOCOMPLETAR CARGO
    const resp = document.getElementById('resp_tecnico');
    if (resp) {
        resp.addEventListener('change', function() {
            let cargo = this.options[this.selectedIndex].getAttribute('data-cargo');
            document.getElementById('cargo_tecnico').value = cargo || '';
        });
    }

    // VISITA O MUESTRA
    const vm = document.getElementById('aplica_vm');
    if (vm) {
        vm.addEventListener('change', function () {
            let activa = this.value === 'SI';
            document.getElementById('campo_vm').style.display = activa ? 'block' : 'none';

            if (!activa) {
                document.getElementById('fecha_vm').value = '';
                document.getElementById('hora_vm').value = '';
            }
        });

        vm.dispatchEvent(new Event('change'));
    }

    // ACL
    const acl = document.getElementById('aplica_acl');
    if (acl) {
        acl.addEventListener('change', function () {
            let activa = this.value === 'SI';
            document.getElementById('campo_acl').style.display = activa ? 'block' : 'none';

            if (!activa) {
                document.getElementById('fecha_acl').value = '';
                document.getElementById('hora_acl').value = '';
            }
        });

        acl.dispatchEvent(new Event('change'));
    }

});
</script>

@endsection