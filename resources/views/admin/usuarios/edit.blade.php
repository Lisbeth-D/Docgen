@extends('layouts.app')

@section('title','Editar Usuario')

@section('content')

<div class="admin-layout">
@include('layouts.admin_sidebar')

<div class="admin-content">

<div class="form-card">

<h2>Editar usuario</h2>

<form action="/usuarios/{{ $user->id }}" method="POST">
@csrf
@method('PUT')

<div class="form-group">
<label>Nombre</label>
<input type="text" name="name" value="{{ $user->name }}" required>
</div>

<div class="form-group">
<label>Usuario</label>
<input type="text" name="username" value="{{ $user->username }}" required>
</div>

<div class="form-group">
<label>Email</label>
<input type="email" name="email" value="{{ $user->email }}" required>
</div>

<div class="form-group">
<label>Rol</label>
<select name="role">
<option value="admin" {{ $user->role == 'admin' ? 'selected' : '' }}>Admin</option>
<option value="comprador" {{ $user->role == 'comprador' ? 'selected' : '' }}>Comprador</option>
</select>
</div>

<div class="form-actions">
<button class="btn-primary">Actualizar</button>
<a href="/usuarios" class="btn-cancel">Cancelar</a>
</div>

</form>

</div>

</div>
</div>

@endsection