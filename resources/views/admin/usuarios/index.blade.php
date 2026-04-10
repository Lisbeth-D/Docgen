@extends('layouts.app')

@section('title','Usuarios')

@section('content')

<div class="admin-layout">

@include('layouts.admin_sidebar')

<div class="admin-content">

<div class="card-container">

<div class="card-header">

<h2>Usuarios del sistema</h2>

<a href="/usuarios/crear" class="btn-primary">
+ Crear usuario
</a>

</div>


{{-- BUSCADOR --}}

<form method="GET" action="/usuarios" class="search-box">

<input 
type="text"
name="buscar"
placeholder="Buscar usuario..."
value="{{ $buscar }}"
>

<button type="submit">Buscar</button>

</form>


{{-- TABLA --}}

<div class="table-container">

<table class="admin-table">

<thead>
<tr>
<th>ID</th>
<th>Nombre</th>
<th>Usuario</th>
<th>Email</th>
<th>Rol</th>
<th>Acciones</th>
</tr>
</thead>

<tbody>

@foreach($users as $user)

<tr>

<td>{{ $user->id }}</td>
<td>{{ $user->name }}</td>
<td>{{ $user->username }}</td>
<td>{{ $user->email }}</td>

<td>
<span class="role-badge">
{{ $user->role }}
</span>
</td>

<td class="actions">

<a href="/usuarios/{{ $user->id }}/editar" class="btn-edit">
Editar
</a>

<form action="/usuarios/reset/{{ $user->id }}" method="POST">
@csrf
<button class="btn-reset">
Reset
</button>
</form>

<form action="/usuarios/toggle/{{ $user->id }}" method="POST">
@csrf

<button class="btn-toggle">

@if($user->activo)
Desactivar
@else
Activar
@endif

</button>

</form>

<form action="/usuarios/{{ $user->id }}" method="POST"
onsubmit="return confirm('¿Seguro que deseas eliminar este usuario?')">

@csrf
@method('DELETE')

<button class="btn-delete">
Eliminar
</button>

</form>

</td>

</tr>

@endforeach

</tbody>

</table>

</div>


{{-- PAGINACIÓN --}}

<div class="pagination-box">

{{ $users->links() }}

</div>

</div>

</div>

</div>

@endsection