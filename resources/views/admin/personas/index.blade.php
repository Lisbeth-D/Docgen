@extends('layouts.app')

@section('title','Personas')

@section('content')

<div class="admin-layout">
@include('layouts.admin_sidebar')

<div class="admin-content">

<div class="card-container">

    {{-- HEADER --}}
    <div class="card-header modern-header">
        <div>
            <h2>Gestión de personas</h2>
            <p class="subtitle">Administra las personas disponibles en el sistema</p>
        </div>

        <a href="/personas/crear" class="btn-table">
        + Nueva persona
        </a>
    </div>

    {{-- BUSCADOR --}}
    <form method="GET" action="/personas" class="search-box modern-search">
        <input 
            type="text" 
            name="buscar" 
            placeholder="Buscar por nombre, cargo o área..." 
            value="{{ $buscar }}">
        <button type="submit">Buscar</button>
    </form>

    {{-- TABLA --}}
    <div class="table-container modern-table">

        <table class="admin-table">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Cargo</th>
                    <th>Área</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>

            <tbody>

            @forelse($personas as $persona)

                <tr>
                    <td>{{ $persona->id }}</td>
                    <td class="bold">{{ $persona->nombre }}</td>
                    <td>{{ $persona->cargo }}</td>
                    <td>{{ $persona->area }}</td>

                    <td class="actions">

                        <a href="/personas/{{ $persona->id }}/editar" class="btn-edit">
                        Editar
                        </a>

                        <form action="/personas/{{ $persona->id }}" method="POST"
                        onsubmit="return confirm('¿Eliminar persona?')">

                        @csrf
                        @method('DELETE')

                        <button class="btn-delete">Eliminar</button>

                        </form>

                    </td>
                </tr>

            @empty

                <tr>
                    <td colspan="5" class="empty">
                        No hay registros disponibles
                    </td>
                </tr>

            @endforelse

            </tbody>

        </table>

    </div>

    {{-- PAGINACIÓN --}}
    <div class="pagination-box modern-pagination">
        {{ $personas->links() }}
    </div>

</div>
@endsection