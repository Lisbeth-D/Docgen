@extends('layouts.app')

@section('title', 'Historial de documentos')

@section('content')

<div class="comprador-layout">

    {{-- SIDEBAR --}}
    @include('comprador.sidebar')

    {{-- CONTENIDO PRINCIPAL --}}
    <main class="comprador-content">

        <div class="historial-container">

            {{-- ENCABEZADO --}}
            <div class="historial-header">
                <div>
                    <h1>Historial de documentos</h1>

                    <p>
                        Aquí puedes consultar y descargar los documentos que
                        has generado durante los últimos 10 días.
                    </p>
                </div>

                <div class="historial-periodo">
                    <i data-feather="clock"></i>
                    <span>Conservación: 10 días</span>
                </div>
            </div>

            {{-- MENSAJE DE ÉXITO --}}
            @if (session('success'))
                <div class="historial-alert historial-alert-success">
                    <i data-feather="check-circle"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            {{-- MENSAJE DE ERROR --}}
            @if (session('error'))
                <div class="historial-alert historial-alert-danger">
                    <i data-feather="alert-circle"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            {{-- BUSCADOR --}}
            <div class="historial-card historial-search-card">

                <form
                    action="{{ route('historial-documentos.index') }}"
                    method="GET"
                    class="historial-search-form"
                >
                    <div class="historial-search-field">

                        <label for="buscar">
                            Buscar documento
                        </label>

                        <div class="historial-search-input">
                            <i data-feather="search"></i>

                            <input
                                type="text"
                                id="buscar"
                                name="buscar"
                                value="{{ $buscar ?? request('buscar') }}"
                                placeholder="Tipo, procedimiento o nombre del archivo"
                                autocomplete="off"
                            >
                        </div>

                    </div>

                    <div class="historial-search-actions">

                        <button
                            type="submit"
                            class="historial-btn historial-btn-primary"
                        >
                            <i data-feather="search"></i>
                            <span>Buscar</span>
                        </button>

                        @if (!empty($buscar))
                            <a
                                href="{{ route('historial-documentos.index') }}"
                                class="historial-btn historial-btn-secondary"
                            >
                                <i data-feather="x"></i>
                                <span>Limpiar</span>
                            </a>
                        @endif

                    </div>
                </form>

            </div>

            {{-- TABLA --}}
            <div class="historial-card">

                <div class="historial-table-header">
                    <div>
                        <h2>Documentos generados</h2>

                        <span class="historial-count">
                            {{ $documentos->total() }}
                            {{ $documentos->total() === 1 ? 'documento' : 'documentos' }}
                        </span>
                    </div>
                </div>

                @if ($documentos->count() > 0)

                    <div class="historial-table-responsive">

                        <table class="historial-table">

                            <thead>
                                <tr>
                                    <th>Documento</th>
                                    <th>Procedimiento</th>
                                    <th>Archivo</th>
                                    <th>Fecha de creación</th>
                                    <th>Disponible hasta</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>

                            <tbody>

                                @foreach ($documentos as $documento)

                                    @php
                                        $fechaExpiracion =
                                            $documento->fecha_expiracion
                                                ?->locale('es');

                                        $diasRestantes =
                                            $documento->fecha_expiracion
                                                ? now()->startOfDay()->diffInDays(
                                                    $documento
                                                        ->fecha_expiracion
                                                        ->copy()
                                                        ->startOfDay(),
                                                    false
                                                )
                                                : null;
                                    @endphp

                                    <tr>

                                        <td data-label="Documento">
                                            <div class="historial-documento">

                                                <div class="historial-documento-icon">
                                                    <i data-feather="file-text"></i>
                                                </div>

                                                <div>
                                                    <strong>
                                                        {{ $documento->tipo_documento }}
                                                    </strong>

                                                    <small>
                                                        Documento Word
                                                    </small>
                                                </div>

                                            </div>
                                        </td>

                                        <td data-label="Procedimiento">
                                            @if ($documento->numero_procedimiento)
                                                <span class="historial-procedimiento">
                                                    {{ $documento->numero_procedimiento }}
                                                </span>
                                            @else
                                                <span class="historial-no-data">
                                                    Sin procedimiento
                                                </span>
                                            @endif
                                        </td>

                                        <td data-label="Archivo">
                                            <span
                                                class="historial-file-name"
                                                title="{{ $documento->nombre_archivo }}"
                                            >
                                                {{ $documento->nombre_archivo }}
                                            </span>

                                            @if ($documento->tamano_archivo)
                                                <small class="historial-file-size">
                                                    {{ number_format(
                                                        $documento->tamano_archivo / 1024,
                                                        2
                                                    ) }}
                                                    KB
                                                </small>
                                            @endif
                                        </td>

                                        <td data-label="Fecha de creación">
                                            <div class="historial-date">
                                                <strong>
                                                    {{ $documento->created_at
                                                        ->locale('es')
                                                        ->translatedFormat('d \d\e F \d\e Y') }}
                                                </strong>

                                                <small>
                                                    {{ $documento->created_at->format('H:i') }}
                                                    horas
                                                </small>
                                            </div>
                                        </td>

                                        <td data-label="Disponible hasta">
                                            <div class="historial-date">

                                                <strong>
                                                    {{ $fechaExpiracion
                                                        ?->translatedFormat('d \d\e F \d\e Y') }}
                                                </strong>

                                                @if ($diasRestantes !== null)

                                                    @if ($diasRestantes > 1)
                                                        <span class="historial-status historial-status-active">
                                                            {{ $diasRestantes }} días restantes
                                                        </span>
                                                    @elseif ($diasRestantes === 1)
                                                        <span class="historial-status historial-status-warning">
                                                            1 día restante
                                                        </span>
                                                    @else
                                                        <span class="historial-status historial-status-danger">
                                                            Vence hoy
                                                        </span>
                                                    @endif

                                                @endif

                                            </div>
                                        </td>

                                        <td data-label="Acciones">

                                            <div class="historial-actions">

                                                <a
                                                    href="{{ route(
                                                        'historial-documentos.descargar',
                                                        $documento
                                                    ) }}"
                                                    class="historial-action-btn historial-download-btn"
                                                >
                                                    <i data-feather="download"></i>
                                                    <span>Descargar</span>
                                                </a>

                                                <form
                                                    action="{{ route(
                                                        'historial-documentos.eliminar',
                                                        $documento
                                                    ) }}"
                                                    method="POST"
                                                    class="form-eliminar-documento"
                                                >
                                                    @csrf
                                                    @method('DELETE')

                                                    <button
                                                        type="submit"
                                                        class="historial-action-btn historial-delete-btn"
                                                    >
                                                        <i data-feather="trash-2"></i>
                                                        <span>Eliminar</span>
                                                    </button>

                                                </form>

                                            </div>

                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>

                    <div class="historial-pagination">
                        {{ $documentos->links() }}
                    </div>

                @else

                    <div class="historial-empty">

                        <div class="historial-empty-icon">
                            <i data-feather="folder"></i>
                        </div>

                        @if (!empty($buscar))

                            <h3>No se encontraron documentos</h3>

                            <p>
                                No existen documentos que coincidan con:
                                <strong>{{ $buscar }}</strong>
                            </p>

                            <a
                                href="{{ route('historial-documentos.index') }}"
                                class="historial-btn historial-btn-primary"
                            >
                                <i data-feather="arrow-left"></i>
                                <span>Ver todo el historial</span>
                            </a>

                        @else

                            <h3>Aún no tienes documentos generados</h3>

                            <p>
                                Los archivos que generes aparecerán aquí y
                                estarán disponibles durante 10 días.
                            </p>

                        @endif

                    </div>

                @endif

            </div>

        </div>

    </main>

</div>

<style>
    .comprador-layout {
        display: flex;
        width: 100%;
        min-height: calc(100vh - 80px);
        align-items: stretch;
    }

    .comprador-layout > .admin-sidebar {
        flex: 0 0 250px;
        width: 250px;
        min-width: 250px;
    }

    .comprador-content {
        flex: 1;
        min-width: 0;
        padding: 0;
        overflow-x: hidden;
    }

    .historial-container {
        width: 100%;
        max-width: 100%;
        padding: 25px;
        box-sizing: border-box;
    }

    .historial-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 25px;
    }

    .historial-header h1 {
        margin: 0 0 7px;
        font-size: 28px;
        color: #263238;
    }

    .historial-header p {
        margin: 0;
        color: #667085;
        font-size: 15px;
    }

    .historial-periodo {
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 11px 16px;
        background: #eef4ff;
        border: 1px solid #d7e3ff;
        border-radius: 8px;
        color: #315ca8;
        font-weight: 600;
        white-space: nowrap;
    }

    .historial-card {
        width: 100%;
        background: #ffffff;
        border: 1px solid #e4e7ec;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(16, 24, 40, 0.05);
        margin-bottom: 24px;
        overflow: hidden;
        box-sizing: border-box;
    }

    .historial-search-card {
        padding: 20px;
    }

    .historial-search-form {
        display: flex;
        align-items: flex-end;
        gap: 15px;
    }

    .historial-search-field {
        flex: 1;
        min-width: 0;
    }

    .historial-search-field label {
        display: block;
        margin-bottom: 7px;
        color: #344054;
        font-size: 14px;
        font-weight: 600;
    }

    .historial-search-input {
        position: relative;
    }

    .historial-search-input svg {
        position: absolute;
        top: 50%;
        left: 13px;
        width: 18px;
        height: 18px;
        color: #98a2b3;
        transform: translateY(-50%);
    }

    .historial-search-input input {
        width: 100%;
        min-height: 44px;
        padding: 10px 14px 10px 42px;
        border: 1px solid #d0d5dd;
        border-radius: 7px;
        box-sizing: border-box;
    }

    .historial-search-actions,
    .historial-actions {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .historial-btn,
    .historial-action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        border-radius: 7px;
        text-decoration: none;
        cursor: pointer;
        font-family: inherit;
        font-weight: 600;
    }

    .historial-btn {
        min-height: 42px;
        padding: 9px 16px;
        border: 1px solid transparent;
    }

    .historial-btn-primary {
        background: #315ca8;
        color: #ffffff;
    }

    .historial-btn-secondary {
        border-color: #d0d5dd;
        background: #ffffff;
        color: #344054;
    }

    .historial-alert {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
        padding: 13px 16px;
        border: 1px solid;
        border-radius: 8px;
    }

    .historial-alert-success {
        border-color: #a6f4c5;
        background: #ecfdf3;
        color: #067647;
    }

    .historial-alert-danger {
        border-color: #fecdca;
        background: #fef3f2;
        color: #b42318;
    }

    .historial-table-header {
        padding: 20px 22px;
        border-bottom: 1px solid #e4e7ec;
    }

    .historial-table-header > div {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .historial-table-header h2 {
        margin: 0;
        font-size: 18px;
    }

    .historial-count {
        padding: 3px 9px;
        background: #f2f4f7;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .historial-table-responsive {
        width: 100%;
        overflow-x: auto;
    }

    .historial-table {
        width: 100%;
        border-collapse: collapse;
    }

    .historial-table th,
    .historial-table td {
        padding: 14px 16px;
        text-align: left;
        border-bottom: 1px solid #eaecf0;
        vertical-align: middle;
    }

    .historial-table th {
        background: #f9fafb;
        color: #475467;
        font-size: 12px;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .historial-documento {
        display: flex;
        align-items: center;
        gap: 11px;
        min-width: 190px;
    }

    .historial-documento-icon {
        display: flex;
        width: 38px;
        height: 38px;
        align-items: center;
        justify-content: center;
        background: #eef4ff;
        border-radius: 8px;
        color: #315ca8;
        flex-shrink: 0;
    }

    .historial-documento strong,
    .historial-date strong {
        display: block;
    }

    .historial-file-name {
        display: block;
        max-width: 220px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .historial-status {
        display: inline-flex;
        margin-top: 4px;
        padding: 3px 8px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
    }

    .historial-status-active {
        background: #ecfdf3;
        color: #067647;
    }

    .historial-status-warning {
        background: #fffaeb;
        color: #b54708;
    }

    .historial-status-danger {
        background: #fef3f2;
        color: #b42318;
    }

    .historial-action-btn {
        min-height: 36px;
        padding: 7px 10px;
        border: 1px solid;
        background: #ffffff;
        font-size: 12px;
    }

    .historial-download-btn {
        border-color: #b2ccff;
        color: #315ca8;
    }

    .historial-delete-btn {
        border-color: #fecdca;
        color: #b42318;
    }

    .historial-empty {
        padding: 60px 20px;
        text-align: center;
    }

    .historial-empty-icon {
        display: flex;
        width: 60px;
        height: 60px;
        margin: 0 auto 16px;
        align-items: center;
        justify-content: center;
        background: #f2f4f7;
        border-radius: 50%;
    }

    .historial-pagination {
        padding: 18px 22px;
    }

    @media (max-width: 900px) {
        .comprador-layout {
            display: block;
        }

        .comprador-layout > .admin-sidebar {
            width: 100%;
            min-width: 0;
        }

        .historial-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .historial-search-form {
            align-items: stretch;
            flex-direction: column;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    });
</script>

@endsection