@extends('layouts.app')

@section('title', 'Dictamen de Fallo')

@section('content')
<div class="admin-layout">
    @include('comprador.sidebar')

    <div class="admin-content">
        <div class="conv-wrapper">
            <form id="form-dictamen-fallo"
                  action="{{ route('dictamen.fallo.generar') }}"
                  method="POST"
                  enctype="multipart/form-data"
                  class="conv-form"
                  novalidate>
                @csrf

                <h2 class="conv-title">Dictamen de Fallo</h2>
                <p class="form-subtitle">
                    Capture y revise la información antes de generar el documento.
                </p>

                <div id="alerta_formulario"
                     class="form-alert form-alert-danger"
                     role="alert"
                     aria-live="assertive"
                     hidden></div>

                @if(session('error'))
                    <div class="form-alert form-alert-danger">
                        <strong>Error:</strong> {{ session('error') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="form-alert form-alert-danger">
                        <strong>No fue posible generar el documento.</strong>
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="conv-card">
                    <h3>Plantilla Word</h3>
                    <div class="conv-group full">
                        <label for="archivo_word">Subir archivo Word (.docx)</label>
                        <input type="file"
                               id="archivo_word"
                               name="archivo_word"
                               accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                               class="@error('archivo_word') input-error @enderror"
                               required>
                        <small>Máximo 10 MB.</small>
                        @error('archivo_word')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="conv-card">
                    <h3>Datos del procedimiento</h3>
                    <div class="conv-grid">
                        <div class="conv-group">
                            <label for="numero_busqueda">Número de búsqueda</label>
                            <input type="text"
                                   id="numero_busqueda"
                                   name="numero_busqueda"
                                   value="{{ old('numero_busqueda') }}"
                                   placeholder="Ejemplo: 49"
                                   maxlength="100"
                                   autocomplete="off"
                                   required>
                            <div id="estado_busqueda"
                                 class="search-message"
                                 role="status"
                                 aria-live="polite"
                                 hidden></div>
                        </div>

                        <div class="conv-group">
                            <label for="num_procedimiento">Número del procedimiento</label>
                            <input type="text"
                                   id="num_procedimiento"
                                   name="num_procedimiento"
                                   value="{{ old('num_procedimiento') }}"
                                   maxlength="255"
                                   required>
                        </div>

                        <div class="conv-group full">
                            <label for="nombre_procedimiento">Nombre del procedimiento</label>
                            <input type="text"
                                   id="nombre_procedimiento"
                                   name="nombre_procedimiento"
                                   value="{{ old('nombre_procedimiento') }}"
                                   maxlength="1000"
                                   required>
                        </div>

                        <div class="conv-group">
                            <label for="fecha_publicacion">Fecha de publicación</label>
                            <input type="date" id="fecha_publicacion" name="fecha_publicacion"
                                   value="{{ old('fecha_publicacion') }}" required>
                        </div>

                        <div class="conv-group">
                            <label for="fecha_acl">Fecha de aclaraciones</label>
                            <input type="date" id="fecha_acl" name="fecha_acl"
                                   value="{{ old('fecha_acl') }}" required>
                            <small>Se obtiene de procedimientos.fecha_ac.</small>
                        </div>

                        <div class="conv-group">
                            <label for="conv_dispo">Disponibilidad de la convocatoria</label>
                            <input type="date" id="conv_dispo" name="conv_dispo"
                                   value="{{ old('conv_dispo') }}" required>
                            <small>Se calcula restando un día a la fecha de aclaraciones y puede editarse.</small>
                        </div>

                        <div class="conv-group">
                            <label for="fecha_apertura">Fecha de apertura</label>
                            <input type="date" id="fecha_apertura" name="fecha_apertura"
                                   value="{{ old('fecha_apertura') }}" required>
                        </div>

                        <div class="conv-group">
                            <label for="fecha_fallo">Fecha del fallo</label>
                            <input type="date" id="fecha_fallo" name="fecha_fallo"
                                   value="{{ old('fecha_fallo') }}" required>
                        </div>

                        <div class="conv-group">
                            <label for="hora_fallo">Hora del fallo</label>
                            <input type="time" id="hora_fallo" name="hora_fallo"
                                   value="{{ old('hora_fallo') }}" required>
                        </div>

                        <div class="conv-group full">
                            <label for="proposicion_tecnica">Proposición técnica</label>
                            <input type="text"
                                   id="proposicion_tecnica"
                                   name="proposicion_tecnica"
                                   value="{{ old('proposicion_tecnica') }}"
                                   placeholder="Nombre del área requirente"
                                   maxlength="500"
                                   required>
                        </div>

                        <div class="conv-group full">
                            <label for="area_requirente_nombre">Responsable del área requirente</label>
                            <input type="text"
                                   id="area_requirente_nombre"
                                   value="{{ old('area_requirente_nombre') }}"
                                   readonly>
                            <input type="hidden"
                                   id="area_requirente_id"
                                   value="{{ old('area_requirente_id') }}">
                        </div>
                    </div>
                </div>

                <div class="conv-card">
                    <h3>Licitantes participantes</h3>
                    <div class="conv-grid">
                        <div class="conv-group">
                            <label for="num_lici">Número de licitantes</label>
                            <input type="number"
                                   id="num_lici"
                                   name="num_lici"
                                   value="{{ old('num_lici', 1) }}"
                                   min="1"
                                   max="100"
                                   required>
                        </div>
                    </div>
                    <div id="contenedor_licitantes" class="dynamic-list"></div>
                </div>

                <div class="conv-card">
                    <h3>Oficios</h3>
                    <div class="conv-grid">
                        <div class="conv-group">
                            <label for="oficio_solicitante">Oficio solicitante</label>
                            <input type="text"
                                   id="oficio_solicitante"
                                   name="oficio_solicitante"
                                   value="{{ old('oficio_solicitante') }}"
                                   maxlength="500"
                                   required>
                            <small>Se carga desde plantilla_referencias de la Coordinadora General.</small>
                        </div>

                        <div class="conv-group">
                            <label for="fecha_oficio_solicitante">Fecha del oficio solicitante</label>
                            <input type="date"
                                   id="fecha_oficio_solicitante"
                                   name="fecha_oficio_solicitante"
                                   value="{{ old('fecha_oficio_solicitante') }}"
                                   required>
                        </div>

                        <div class="conv-group">
                            <label for="oficio_respuesta">Oficio de respuesta</label>
                            <input type="text"
                                   id="oficio_respuesta"
                                   name="oficio_respuesta"
                                   value="{{ old('oficio_respuesta') }}"
                                   maxlength="500"
                                   required>
                            <small>Se carga desde plantilla_referencias de la persona requirente.</small>
                        </div>

                        <div class="conv-group">
                            <label for="fecha_oficio_respuesta">Fecha del oficio de respuesta</label>
                            <input type="date"
                                   id="fecha_oficio_respuesta"
                                   name="fecha_oficio_respuesta"
                                   value="{{ old('fecha_oficio_respuesta') }}"
                                   required>
                        </div>
                    </div>
                </div>

                <div class="conv-card">
                    <h3>Adjudicación y contrato</h3>
                    <div class="conv-grid">
                        <div class="conv-group full">
                            <label for="proveedor_adjudicado">Proveedor adjudicado</label>
                            <input type="text"
                                   id="proveedor_adjudicado"
                                   name="proveedor_adjudicado"
                                   value="{{ old('proveedor_adjudicado') }}"
                                   maxlength="500"
                                   required>
                        </div>

                        <div class="conv-group">
                            <label for="monto_maximo">Monto máximo</label>
                            <input type="number" id="monto_maximo" name="monto_maximo"
                                   value="{{ old('monto_maximo') }}" min="0" step="0.01" required>
                        </div>

                        <div class="conv-group">
                            <label for="monto_minimo">Monto mínimo (40%)</label>
                            <input type="number" id="monto_minimo" name="monto_minimo"
                                   value="{{ old('monto_minimo') }}" min="0" step="0.01" readonly required>
                        </div>

                        <div class="conv-group">
                            <label for="monto_fianza">Monto de la fianza (10%)</label>
                            <input type="number" id="monto_fianza" name="monto_fianza"
                                   value="{{ old('monto_fianza') }}" min="0" step="0.01" readonly required>
                        </div>

                        <div class="conv-group">
                            <label for="numero_contrato">Número de contrato</label>
                            <input type="text" id="numero_contrato" name="numero_contrato"
                                   value="{{ old('numero_contrato') }}" maxlength="255" required>
                        </div>

                        <div class="conv-group">
                            <label for="fecha_inicio_contrato">Inicio de vigencia</label>
                            <input type="date" id="fecha_inicio_contrato" name="fecha_inicio_contrato"
                                   value="{{ old('fecha_inicio_contrato') }}" required>
                        </div>

                        <div class="conv-group">
                            <label for="fecha_fin_contrato">Fin de vigencia</label>
                            <input type="date" id="fecha_fin_contrato" name="fecha_fin_contrato"
                                   value="{{ old('fecha_fin_contrato') }}" required>
                        </div>
                    </div>
                </div>

                <button type="submit" class="conv-btn" id="btn_generar">
                    Generar Dictamen de Fallo
                </button>
            </form>
        </div>
    </div>
</div>

<style>
.form-alert{width:100%;box-sizing:border-box;margin-bottom:20px;padding:14px 18px;border-radius:8px;line-height:1.5}
.form-alert[hidden],.search-message[hidden]{display:none!important}
.form-alert-danger{color:#842029;background:#f8d7da;border:1px solid #f5c2c7}
.form-alert-info{color:#055160;background:#cff4fc;border:1px solid #b6effb}
.field-error,.search-message.error{color:#b42318}.search-message.success{color:#067647}
.input-error{border:1px solid #b42318!important;outline-color:#b42318!important}
.search-message{min-height:18px;margin-top:5px;font-size:13px}.dynamic-list{display:grid;gap:12px;margin-top:16px}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const buscarBaseUrl = @json(url('/dictamen-fallo/buscar'));
    const nombresAnteriores = @json(old('nombres_licitantes', []));
    const form = document.getElementById('form-dictamen-fallo');
    const btn = document.getElementById('btn_generar');
    const alerta = document.getElementById('alerta_formulario');
    const busqueda = document.getElementById('numero_busqueda');
    const estado = document.getElementById('estado_busqueda');
    const numLici = document.getElementById('num_lici');
    const contenedor = document.getElementById('contenedor_licitantes');
    const montoMax = document.getElementById('monto_maximo');
    const montoMin = document.getElementById('monto_minimo');
    const montoFianza = document.getElementById('monto_fianza');

    const campos = {};
    [
        'num_procedimiento','nombre_procedimiento','fecha_publicacion','fecha_acl',
        'conv_dispo','fecha_apertura','fecha_fallo','hora_fallo','proposicion_tecnica',
        'area_requirente_nombre','area_requirente_id','oficio_solicitante','oficio_respuesta',
        'monto_maximo','monto_minimo','monto_fianza','fecha_inicio_contrato','fecha_fin_contrato'
    ].forEach(id => campos[id] = document.getElementById(id));

    let timer = null;
    let controller = null;
    let buscando = false;

    busqueda.addEventListener('input', function () {
        clearTimeout(timer);
        if (controller) controller.abort();
        limpiar();
        const valor = this.value.trim();
        if (!valor) return mostrarEstado('');
        mostrarEstado('Buscando procedimiento...', 'info');
        timer = setTimeout(() => buscar(valor), 350);
    });

    async function buscar(valor) {
        controller = new AbortController();
        buscando = true;
        try {
            const response = await fetch(`${buscarBaseUrl}/${encodeURIComponent(valor)}`, {
                headers: {'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},
                signal: controller.signal
            });
            const data = await response.json().catch(() => null);
            if (!response.ok || !data || !data.num_procedimiento) {
                throw new Error('No se encontró un procedimiento con ese número.');
            }
            if (!data.area_requirente_id) {
                throw new Error('El procedimiento no tiene una persona requirente válida.');
            }
            Object.keys(campos).forEach(clave => {
                if (Object.prototype.hasOwnProperty.call(data, clave)) {
                    campos[clave].value = data[clave] ?? '';
                }
            });
            calcularMontos();
            mostrarEstado('Procedimiento encontrado. Los datos fueron cargados y pueden editarse.', 'success');
        } catch (error) {
            if (error.name === 'AbortError') return;
            limpiar();
            mostrarEstado(error.message || 'No fue posible realizar la búsqueda.', 'error');
        } finally {
            buscando = false;
        }
    }

    function limpiar() {
        Object.values(campos).forEach(campo => { if (campo) campo.value = ''; });
    }

    function mostrarEstado(mensaje, tipo='info') {
        estado.textContent = mensaje;
        estado.hidden = !mensaje;
        estado.className = 'search-message';
        if (tipo === 'success' || tipo === 'error') estado.classList.add(tipo);
    }

    campos.fecha_acl.addEventListener('change', function () {
        if (!this.value) return campos.conv_dispo.value = '';
        const fecha = new Date(`${this.value}T12:00:00`);
        fecha.setDate(fecha.getDate() - 1);
        campos.conv_dispo.value = fecha.toISOString().slice(0, 10);
    });

    numLici.addEventListener('input', generarLicitantes);

    function generarLicitantes() {
        const cantidad = Math.max(1, Math.min(100, Number(numLici.value) || 1));
        const actuales = Array.from(contenedor.querySelectorAll('input')).map(input => input.value);
        contenedor.innerHTML = '';
        for (let i = 0; i < cantidad; i++) {
            const grupo = document.createElement('div');
            grupo.className = 'conv-group full';
            const label = document.createElement('label');
            label.htmlFor = `nombre_licitante_${i}`;
            label.textContent = `Nombre del licitante ${i + 1}`;
            const input = document.createElement('input');
            input.type = 'text';
            input.id = `nombre_licitante_${i}`;
            input.name = 'nombres_licitantes[]';
            input.maxLength = 500;
            input.required = true;
            input.value = actuales[i] ?? nombresAnteriores[i] ?? '';
            grupo.append(label, input);
            contenedor.appendChild(grupo);
        }
    }
    generarLicitantes();

    montoMax.addEventListener('input', calcularMontos);
    function calcularMontos() {
        const maximo = Number(montoMax.value) || 0;
        montoMin.value = (maximo * .40).toFixed(2);
        montoFianza.value = (maximo * .10).toFixed(2);
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        alerta.hidden = true;
        if (buscando) return mostrarAlerta('Espere a que termine la búsqueda.', 'info');
        const invalidos = Array.from(form.querySelectorAll('input,select,textarea'))
            .filter(campo => !campo.disabled && !campo.checkValidity());
        if (invalidos.length) {
            invalidos.forEach(campo => campo.classList.add('input-error'));
            mostrarAlerta('Revise los campos obligatorios del formulario.');
            return invalidos[0].focus();
        }
        btn.disabled = true;
        btn.textContent = 'Generando documento...';
        form.submit();
    });

    form.addEventListener('input', event => event.target.classList.remove('input-error'));

    function mostrarAlerta(mensaje, tipo='danger') {
        alerta.textContent = mensaje;
        alerta.hidden = false;
        alerta.className = `form-alert form-alert-${tipo}`;
        alerta.scrollIntoView({behavior:'smooth', block:'start'});
    }
});
</script>
@endsection