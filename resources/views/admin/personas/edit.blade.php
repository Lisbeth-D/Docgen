@extends('layouts.app')

@section('title','Editar Persona')

@section('content')

<div class="admin-layout">

@include('layouts.admin_sidebar')

<div class="admin-content">

<div class="form-wrapper">

<div class="form-card">

<h2>Editar persona</h2>

{{-- ERRORES --}}
@if ($errors->any())
    <div class="alert-error">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="/personas/{{ $persona->id }}" method="POST">

@csrf
@method('PUT')

<div class="form-grid">

{{-- NOMBRE --}}
<div class="form-group">
<label>Nombre completo</label>
<input 
    type="text" 
    name="nombre" 
    value="{{ old('nombre', $persona->nombre) }}" 
    required>
</div>

{{-- CARGO --}}
<div class="form-group">
<label>Cargo</label>
<input 
    type="text" 
    name="cargo" 
    value="{{ old('cargo', $persona->cargo) }}" 
    required>
</div>

{{-- ÁREA --}}
<div class="form-group full">
<label>Área</label>

<select name="area_id" required>
    <option value="">Selecciona un área</option>

    @foreach($areas as $area)
        <option value="{{ $area->id }}"
            {{ old('area_id', $persona->area_id) == $area->id ? 'selected' : '' }}>
            {{ $area->nombre }}
        </option>
    @endforeach

</select>

</div>

{{-- 🔥 PLANTILLA DE REFERENCIA --}}
<div class="form-group full">
<label>Plantilla de referencia</label>

<input 
    type="text" 
    name="plantilla_referencia" 
    value="{{ old('plantilla_referencia', $persona->plantilla_referencia) }}" 
    required
    placeholder="Ej: SABG/OIC/VSS/{NUMERO}/2026">

<small style="color:#888;">
    Usa {NUMERO} como marcador dinámico
</small>

</div>

</div>

<div class="form-actions">

<button type="submit" class="btn-primary">
Actualizar
</button>

<a href="/personas" class="btn-cancel">
Cancelar
</a>

</div>

</form>

</div>

</div>

</div>

</div>

@endsection