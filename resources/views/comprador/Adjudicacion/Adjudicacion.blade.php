@extends('layouts.app')

@section('content')

<div class="admin-layout">

    @include('comprador.sidebar')

    <div class="admin-content">

        <div class="conv-wrapper">

            <form action="{{ route('adjudicacion.generar') }}"
                  method="POST"
                  enctype="multipart/form-data"
                  class="conv-form">

                @csrf

                <h2 class="conv-title">Formulario de Adjudicación</h2>

                {{-- WORD --}}
                <div class="conv-card">
                    <h3>Plantilla Word</h3>

                    <div class="conv-group full">
                        <label>Subir archivo Word (.docx)</label>
                        <input type="file" name="archivo_word" accept=".docx" required>
                    </div>
                </div>

                {{-- DATOS DEL OFICIO --}}
                <div class="conv-card">
                    <h3>Datos del Oficio</h3>

                    <div class="conv-grid">

                        <div class="conv-group">
                            <label>Número de oficio</label>
                            <input type="text" name="oficio_numero" required>
                        </div>

                        <div class="conv-group">
                            <label>Fecha oficio</label>
                            <input type="date" name="fecha_oficio" required>
                        </div>

                    </div>
                </div>

                {{-- PROVEEDOR --}}
                <div class="conv-card">
                    <h3>Proveedor</h3>

                    <div class="conv-grid">

                        <div class="conv-group">
                            <label>Razón social</label>
                            <input type="text" name="proveedor_razon_social">
                        </div>

                        <div class="conv-group">
                            <label>RFC</label>
                            <input type="text" name="proveedor_rfc">
                        </div>

                        <div class="conv-group full">
                            <label>Domicilio</label>
                            <input type="text" name="proveedor_domicilio">
                        </div>

                        <div class="conv-group">
                            <label>Email</label>
                            <input type="email" name="proveedor_email">
                        </div>

                        <div class="conv-group">
                            <label>Teléfono</label>
                            <input type="text" name="proveedor_telefono">
                        </div>

                    </div>
                </div>

                {{-- PROCEDIMIENTO --}}
                <div class="conv-card">
                    <h3>Procedimiento</h3>

                    <div class="conv-grid">

                        <div class="conv-group">
                            <label>Buscar procedimiento</label>
                            <input type="text"
                                   id="busqueda_proc"
                                   name="numero_busqueda"
                                   placeholder="Ejemplo: 25"
                                   autocomplete="off"
                                   required>
                        </div>

                        <div class="conv-group">
                            <label>Tipo</label>
                            <input type="text"
                                   id="procedimiento_tipo"
                                   name="procedimiento_tipo">
                        </div>

                        <div class="conv-group">
                            <label>Número procedimiento</label>
                            <input type="text"
                                   id="num_procedimiento"
                                   name="num_procedimiento"
                                   required>
                        </div>

                        <div class="conv-group">
                            <label>Nombre procedimiento</label>
                            <input type="text"
                                   id="nombre_procedimiento"
                                   name="nombre_procedimiento"
                                   required>
                        </div>

                        <div class="conv-group">
                            <label>Número contrato</label>
                            <input type="text" name="contrato_numero">
                        </div>

                    </div>
                </div>

                {{-- MONTOS --}}
                <div class="conv-card">
                    <h3>Montos</h3>

                    <div class="conv-grid">

                        <div class="conv-group full">
                            <label>Tipo de contrato</label>

                            <div class="radio-row">
                                <label>
                                    <input type="radio"
                                           name="tipo_contrato_monto"
                                           value="cerrado"
                                           checked>
                                    Contrato cerrado
                                </label>

                                <label>
                                    <input type="radio"
                                           name="tipo_contrato_monto"
                                           value="abierto">
                                    Contrato abierto
                                </label>
                            </div>
                        </div>

                        <div class="conv-group" id="grupo_monto_minimo" style="display:none;">
                            <label>Monto mínimo</label>
                            <input type="number"
                                   step="0.01"
                                   id="monto_minimo"
                                   name="monto_minimo">
                            <small id="min_letra"></small>
                        </div>

                        <div class="conv-group">
                            <label>Monto máximo</label>
                            <input type="number"
                                   step="0.01"
                                   id="monto_maximo"
                                   name="monto_maximo">
                            <small id="max_letra"></small>
                        </div>

                    </div>
                </div>

                {{-- VIGENCIA --}}
                <div class="conv-card">
                    <h3>Vigencia</h3>

                    <div class="conv-grid">

                        <div class="conv-group">
                            <label>Fecha inicio</label>
                            <input type="date"
                                   id="fecha_inicio"
                                   name="fecha_inicio">
                        </div>

                        <div class="conv-group">
                            <label>Fecha fin</label>
                            <input type="date"
                                   id="fecha_fin"
                                   name="fecha_fin">
                        </div>

                    </div>
                </div>

                {{-- RESPONSABLES --}}
                <div class="conv-card">
                    <h3>Responsables</h3>

                    <div class="conv-grid">

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

                {{-- DOCUMENTOS --}}
                <div class="conv-card">
                    <h3>Documentos requeridos</h3>

                    <div class="conv-grid">

                        @php
                            $docs = [
                                "Acta Constitutiva y reformas",
                                "Poder Notarial del Representante Legal",
                                "Constancia de situación fiscal",
                                "Identificación oficial vigente",
                                "Comprobante de domicilio",
                                "Opinión de cumplimiento fiscal SAT (32-D)",
                                "Opinión de cumplimiento IMSS",
                                "Opinión de cumplimiento INFONAVIT (32-D)",
                                "Tarjeta patronal IMSS",
                                "CLABE interbancaria",
                                "Registro Único de Proveedores (RUP)"
                            ];
                        @endphp

                        @foreach($docs as $doc)
                            <div class="conv-group full">
                                <label>
                                    <input type="checkbox"
                                           name="documentos[]"
                                           value="{{ $doc }}">
                                    {{ $doc }}
                                </label>
                            </div>
                        @endforeach

                    </div>
                </div>

                <button type="submit" class="conv-btn">
                    Generar Word
                </button>

            </form>

        </div>

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const inputBusqueda = document.getElementById('busqueda_proc');

    const inputTipo = document.getElementById('procedimiento_tipo');
    const inputNum = document.getElementById('num_procedimiento');
    const inputNombre = document.getElementById('nombre_procedimiento');

    const inputMontoMinimo = document.getElementById('monto_minimo');
    const inputMontoMaximo = document.getElementById('monto_maximo');

    const inputFechaInicio = document.getElementById('fecha_inicio');
    const inputFechaFin = document.getElementById('fecha_fin');

    const grupoMontoMinimo = document.getElementById('grupo_monto_minimo');

    const minLetra = document.getElementById('min_letra');
    const maxLetra = document.getElementById('max_letra');

    const radiosTipoContrato = document.querySelectorAll('input[name="tipo_contrato_monto"]');

    inputBusqueda.addEventListener('keyup', function () {

        const valor = this.value.trim();

        if (valor === '') {
            limpiarCampos();
            return;
        }

        fetch(`/buscar-procedimiento-adjudicacion/${encodeURIComponent(valor)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la búsqueda del procedimiento');
                }

                return response.json();
            })
            .then(data => {

                if (data && data.num_procedimiento) {

                    inputTipo.value = data.tipo ?? '';
                    inputNum.value = data.num_procedimiento ?? '';
                    inputNombre.value = data.nombre_procedimiento ?? '';

                    inputMontoMaximo.value = data.monto_maximo ?? '';

                    inputFechaInicio.value = data.fecha_inicio_contrato ?? '';
                    inputFechaFin.value = data.fecha_fin_contrato ?? '';

                    aplicarTipoContrato();
                    actualizarPreviewMontos();

                } else {
                    limpiarCampos();
                }

            })
            .catch(error => {
                console.error('Error al buscar procedimiento:', error);
                limpiarCampos();
            });

    });

    radiosTipoContrato.forEach(radio => {
        radio.addEventListener('change', function () {
            aplicarTipoContrato();
            actualizarPreviewMontos();
        });
    });

    inputMontoMaximo.addEventListener('input', function () {
        aplicarTipoContrato();
        actualizarPreviewMontos();
    });

    inputMontoMinimo.addEventListener('input', actualizarPreviewMontos);

    function aplicarTipoContrato() {
        const tipoContrato = document.querySelector('input[name="tipo_contrato_monto"]:checked').value;
        const montoMaximo = parseFloat(inputMontoMaximo.value);

        if (tipoContrato === 'abierto') {
            grupoMontoMinimo.style.display = 'block';

            if (!isNaN(montoMaximo)) {
                inputMontoMinimo.value = (montoMaximo * 0.40).toFixed(2);
            }

        } else {
            grupoMontoMinimo.style.display = 'none';
            inputMontoMinimo.value = '';
            minLetra.innerText = '';
        }
    }

    function limpiarCampos() {
        inputTipo.value = '';
        inputNum.value = '';
        inputNombre.value = '';

        inputMontoMinimo.value = '';
        inputMontoMaximo.value = '';

        inputFechaInicio.value = '';
        inputFechaFin.value = '';

        minLetra.innerText = '';
        maxLetra.innerText = '';

        document.querySelector('input[name="tipo_contrato_monto"][value="cerrado"]').checked = true;
        grupoMontoMinimo.style.display = 'none';
    }

    function numeroALetra(num) {
        return new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN'
        }).format(num);
    }

    function actualizarPreviewMontos() {
        minLetra.innerText = inputMontoMinimo.value
            ? numeroALetra(inputMontoMinimo.value)
            : '';

        maxLetra.innerText = inputMontoMaximo.value
            ? numeroALetra(inputMontoMaximo.value)
            : '';
    }

});
</script>

@endsection