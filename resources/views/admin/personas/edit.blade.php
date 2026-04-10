@extends('layouts.app')

@section('title','Editar Persona')

@section('content')

<div class="admin-layout">

@include('layouts.admin_sidebar')

<div class="admin-content">

<div class="form-wrapper">

<div class="form-card">

<h2>Editar persona</h2>

<form action="/personas/{{ $persona->id }}" method="POST">

@csrf
@method('PUT')

<div class="form-grid">

<div class="form-group">
<label>Nombre completo</label>
<input type="text" name="nombre" value="{{ $persona->nombre }}" required>
</div>

<div class="form-group">
<label>Cargo</label>
<input type="text" name="cargo" value="{{ $persona->cargo }}" required>
</div>

<div class="form-group full">
<label>Área</label>
<input type="text" name="area" value="{{ $persona->area }}" required>
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