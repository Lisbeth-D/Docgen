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
            <p class="subtitle">Filtrar personas por área</p>
        </div>

        <a href="/personas/crear" class="btn-table">
            + Nueva persona
        </a>
    </div>

    {{-- FILTRO POR ÁREA --}}
    <form method="GET" action="/personas" style="margin-bottom: 20px;">
        
        <select name="area_id" onchange="this.form.submit()" class="form-control">
            <option value="">-- Todas las áreas --</option>

            @foreach($areas as $area)
                <option value="{{ $area->id }}"
                    {{ $area_id == $area->id ? 'selected' : '' }}>
                    {{ $area->nombre }}
                </option>
            @endforeach

        </select>

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
                    <th>Plantilla Referencia</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>

            <tbody>

            @forelse($personas as $persona)

                <tr>
                    <td>{{ $persona->id }}</td>

                    <td class="bold">
                        {{ $persona->nombre }}
                    </td>

                    <td>
                        {{ $persona->cargo }}
                    </td>

                    <td>
                        {{ $persona->area->nombre ?? '-' }}
                    </td>

                    {{--  PLANTILLA (NUEVO SISTEMA) --}}
                    <td>
                        {{ $persona->plantilla_referencia ?? 'Sin plantilla' }}
                    </td>

                    <td class="actions">

                        {{-- EDITAR --}}
                        <a href="/personas/{{ $persona->id }}/editar" class="btn-edit">
                            Editar
                        </a>

                        {{-- ELIMINAR --}}
                        <form action="/personas/{{ $persona->id }}" method="POST"
                            onsubmit="return confirm('¿Eliminar persona?')">

                            @csrf
                            @method('DELETE')

                            <button class="btn-delete">
                                Eliminar
                            </button>

                        </form>

                    </td>
                </tr>

            @empty

                <tr>
                    <td colspan="6" class="empty">
                        No hay registros
                    </td>
                </tr>

            @endforelse

            </tbody>

        </table>

    </div>

    {{-- PAGINACIÓN --}}
    <div class="pagination-modern">
        {{ $personas->onEachSide(1)->links('vendor.pagination.simple-numbers') }}
    </div>

</div>

</div>

</div>

@endsection