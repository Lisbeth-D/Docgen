@extends('layouts.app')
@section('title', 'Documentos para adjudicación')
@section('content')
<div class="admin-layout">
    @include('comprador.sidebar')
    <div class="admin-content">
        <div class="card-container">
            <div class="card-header modern-header">
                <div>
                    <h2>Documentos para adjudicación</h2>
                    <p class="subtitle">Administra las leyendas que pueden incorporarse al Word de adjudicación.</p>
                </div>
                <a href="{{ route('comprador.registros.documentos.create') }}" class="btn-table">Nuevo documento</a>
            </div>

            @if (session('success'))
                <div class="alert-success">{{ session('success') }}</div>
            @endif

            <div class="table-container modern-table">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Orden</th><th>Nombre</th><th>Leyenda para Word</th>
                            <th>Obligatorio</th><th>Estado</th><th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($documentos as $documento)
                            <tr>
                                <td>{{ $documento->orden }}</td>
                                <td class="bold">{{ $documento->nombre }}</td>
                                <td class="leyenda-cell">{{ $documento->leyenda }}</td>
                                <td>{{ $documento->obligatorio ? 'Sí' : 'No' }}</td>
                                <td>{{ $documento->activo ? 'Activo' : 'Inactivo' }}</td>
                                <td class="actions">
                                    <a href="{{ route('comprador.registros.documentos.edit', $documento) }}" class="btn-edit">Editar</a>
                                    <form action="{{ route('comprador.registros.documentos.estado', $documento) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn-table">{{ $documento->activo ? 'Desactivar' : 'Activar' }}</button>
                                    </form>
                                    <form action="{{ route('comprador.registros.documentos.destroy', $documento) }}" method="POST" onsubmit="return confirm('¿Eliminar definitivamente este documento?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-delete">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="empty">No hay documentos registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pagination-modern">
                {{ $documentos->onEachSide(1)->links('vendor.pagination.simple-numbers') }}
            </div>
        </div>
    </div>
</div>
<style>
.leyenda-cell{min-width:340px;white-space:normal;line-height:1.5}.alert-success{margin-bottom:18px;padding:14px 18px;border-radius:7px;color:#0f5132;background:#d1e7dd;border:1px solid #badbcc}
</style>
@endsection
