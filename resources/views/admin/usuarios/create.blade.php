@extends('layouts.app')

@section('title','Crear Usuario')

@section('content')

<div class="admin-layout">
<div class="admin-content">

<div class="form-card">

<h2>➕ Crear nuevo usuario</h2>

<form action="/usuarios" method="POST">

@csrf

<div class="form-group">

<label>Nombre completo</label>

<input 
type="text"
name="name"
required
placeholder="Nombre completo">

</div>


<div class="form-group">

<label>Nombre de usuario</label>

<input 
type="text"
name="username"
required
placeholder="Usuario">

</div>


<div class="form-group">

<label>Email</label>

<input 
type="email"
name="email"
required
placeholder="correo@ejemplo.com">

</div>


<div class="form-group">

<label>Contraseña</label>

<input 
type="password"
name="password"
required>

</div>


<div class="form-group">

<label>Rol</label>

<select name="role">

<option value="comprador">Comprador</option>
<option value="admin">Administrador</option>

</select>

</div>


<div class="form-actions">

<button type="submit" class="btn-primary">
Crear usuario
</button>

<a href="/usuarios" class="btn-cancel">
Cancelar
</a>

</div>

</form>

</div>

</div>
</div>

@endsection