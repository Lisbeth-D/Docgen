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

                        <div class="conv-group">
                            <label>Fecha VM</label>
                            <input type="date" name="fecha_vm">
                        </div>

                        <div class="conv-group">
                            <label>Fecha ACL</label>
                            <input type="date" name="fecha_acl">
                        </div>

                        <div class="conv-group">
                            <label>Hora ACL</label>
                            <input type="time" name="hora_acl">
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

                        <!-- 🔥 RESPONSABLE TÉCNICO -->
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

                        <!-- 🔥 RESPONSABLE ADMIN -->
                        <div class="conv-group">
                            <label>Responsable administrativo</label>

                            <select name="resp_admin" id="resp_admin">
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
                            <label>Cargo administrativo</label>
                            <input type="text" id="cargo_admin" readonly>
                        </div>

                        <div class="conv-group">
                            <label>Monto máximo</label>
                            <input type="number" step="0.01" name="monto_maximo">
                        </div>

                        <div class="conv-group">
                            <label>Monto mínimo</label>
                            <input type="number" step="0.01" name="monto_minimo">
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

<!--SCRIPT AUTOCOMPLETADO -->
<script>
document.getElementById('resp_tecnico').addEventListener('change', function() {
    let cargo = this.options[this.selectedIndex].getAttribute('data-cargo');
    document.getElementById('cargo_tecnico').value = cargo || '';
});

document.getElementById('resp_admin').addEventListener('change', function() {
    let cargo = this.options[this.selectedIndex].getAttribute('data-cargo');
    document.getElementById('cargo_admin').value = cargo || '';
});
</script>

@endsection