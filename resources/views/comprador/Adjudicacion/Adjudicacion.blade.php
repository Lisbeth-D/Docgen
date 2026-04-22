@extends('layouts.app')
@section('content')

<div class="admin-layout">

@include('comprador.sidebar')

<div class="admin-content">
<div class="conv-wrapper">

<h2 class="conv-title">Formulario de Adjudicación</h2>

<form action="{{ route('adjudicacion.generar') }}" method="POST" enctype="multipart/form-data" class="conv-form">
@csrf

<!-- WORD -->
<div class="conv-card">
    <h3>Plantilla Word</h3>
    <div class="conv-group full">
        <label>Subir archivo Word (.docx)</label>
        <input type="file" name="archivo_word" accept=".docx" required>
    </div>
</div>

<!-- OFICIO -->
<div class="conv-card">
<h3>Datos del Oficio</h3>

<div class="conv-grid">
<div class="conv-group">
<label>Número de oficio</label>
<input type="text" name="oficio_numero">
</div>

<div class="conv-group">
<label>Fecha oficio</label>
<input type="date" name="fecha_oficio">
</div>
</div>
</div>

<!-- PROVEEDOR -->
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

<!-- PROCEDIMIENTO -->
<div class="conv-card">
<h3>Procedimiento</h3>

<div class="conv-grid">

<!-- BUSCAR PROCEDIMIENTO -->
                    <div class="conv-group">
                        <label>Buscar procedimiento</label>
                        <input type="text" id="busqueda_proc" name="numero_busqueda">
                    </div>

<div class="conv-group">
<label>Tipo</label>
<input type="text" id="procedimiento_tipo" name="procedimiento_tipo" readonly>
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

<div class="conv-group">
<label>Número contrato</label>
<input type="text" name="contrato_numero">
</div>

</div>
</div>

<!-- MONTOS -->
<div class="conv-card">
<h3>Montos</h3>

<div class="conv-grid">

<div class="conv-group">
<label>Monto mínimo</label>
<input type="number" step="0.01" id="monto_minimo" name="monto_minimo">
<small id="min_letra"></small>
</div>

<div class="conv-group">
<label>Monto máximo</label>
<input type="number" step="0.01" id="monto_maximo" name="monto_maximo">
<small id="max_letra"></small>
</div>

</div>
</div>

<!-- VIGENCIA -->
<div class="conv-card">
<h3>Vigencia</h3>

<div class="conv-grid">

<div class="conv-group">
<label>Fecha inicio</label>
<input type="date" name="fecha_inicio">
</div>

<div class="conv-group">
<label>Fecha fin</label>
<input type="date" name="fecha_fin">
</div>

</div>
</div>

<!-- PERSONAL -->
<div class="conv-card">
<h3>Responsables</h3>

<div class="conv-grid">
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

<!-- DOCUMENTOS -->
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
<input type="checkbox" name="documentos[]" value="{{ $doc }}">
{{ $doc }}
</label>
</div>
@endforeach

</div>
</div>

<button type="submit" class="conv-btn">Generar Word</button>

</form>

</div>
</div>
</div>

<!-- SCRIPT -->
<script>
document.getElementById('busqueda_proc').addEventListener('keyup', function () {

let valor = this.value;

if(valor.length < 1){
    limpiarCampos();
    return;
}

fetch(`/buscar-procedimiento/${valor}`)
.then(res => res.json())
.then(data => {

if(data){
document.getElementById('procedimiento_tipo').value = data.tipo || '';
document.getElementById('procedimiento_numero').value = data.numero || '';
document.getElementById('objeto_contrato').value = data.nombre_procedimiento || '';
}else{
limpiarCampos();
}

}).catch(()=> limpiarCampos());

});

function limpiarCampos(){
document.getElementById('procedimiento_tipo').value = '';
document.getElementById('procedimiento_numero').value = '';
document.getElementById('objeto_contrato').value = '';
}

// PREVIEW MONEDA
function numeroALetra(num){
return new Intl.NumberFormat('es-MX', {style:'currency', currency:'MXN'}).format(num);
}

document.getElementById('monto_minimo').addEventListener('input', function(){
document.getElementById('min_letra').innerText = numeroALetra(this.value || 0);
});

document.getElementById('monto_maximo').addEventListener('input', function(){
document.getElementById('max_letra').innerText = numeroALetra(this.value || 0);
});
</script>

@endsection