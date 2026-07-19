@extends('layouts.app')

@section('title', 'Personas')

@section('content')

<div class="admin-layout">

    @include('layouts.admin_sidebar')

    <div class="admin-content">

        <div class="card-container">

            <div class="card-header modern-header">
                <div>
                    <h2>Gestión de personas</h2>
                    <p class="subtitle">
                        Administra, filtra y actualiza personas mediante Excel.
                    </p>
                </div>

                <div class="header-actions">

                    <div class="massive-menu" id="massive_menu">

                        <button
                            type="button"
                            class="btn-table btn-massive"
                            id="btn_massive"
                            aria-expanded="false"
                            aria-controls="massive_dropdown"
                        >
                            <span class="btn-massive-content">
                                <svg
                                    class="btn-massive-icon"
                                    viewBox="0 0 24 24"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M12 3v12m0-12 4 4m-4-4-4 4"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                    <path
                                        d="M5 14v4a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>

                                <span>Carga masiva</span>
                            </span>

                            <svg
                                class="btn-massive-chevron"
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path
                                    d="m7 10 5 5 5-5"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </button>

                        <div
                            class="massive-dropdown"
                            id="massive_dropdown"
                            hidden
                        >
                            <a
                                href="{{ route('personas.plantilla-masiva') }}"
                                class="massive-option"
                            >
                                <span class="option-icon">
                                    <svg
                                        viewBox="0 0 24 24"
                                        aria-hidden="true"
                                    >
                                        <path
                                            d="M12 3v12m0 0 4-4m-4 4-4-4"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                        <path
                                            d="M5 19h14"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                        />
                                    </svg>
                                </span>

                                <span>
                                    <strong>Descargar plantilla</strong>
                                    <small>Archivo Excel para captura</small>
                                </span>
                            </a>

                            <button
                                type="button"
                                class="massive-option"
                                id="btn_open_import"
                            >
                                <span class="option-icon">
                                    <svg
                                        viewBox="0 0 24 24"
                                        aria-hidden="true"
                                    >
                                        <path
                                            d="M12 16V4m0 0 4 4m-4-4-4 4"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                        <path
                                            d="M5 20h14"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                        />
                                    </svg>
                                </span>

                                <span>
                                    <strong>Cargar archivo Excel</strong>
                                    <small>Crear y actualizar personas</small>
                                </span>
                            </button>
                        </div>

                    </div>

                    <a
                        href="{{ route('personas.create') }}"
                        class="btn-table btn-new-person"
                    >
                        <svg
                            class="btn-new-person-icon"
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >
                            <path
                                d="M12 5v14M5 12h14"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                            />
                        </svg>

                        <span>Nueva persona</span>
                    </a>

                </div>
            </div>

            @if (session('success'))
                <div class="alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert-error">
                    <strong>Revise la información:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form
                method="GET"
                action="{{ route('personas.index') }}"
                class="filter-form"
            >
                <label for="area_id">Filtrar por área</label>

                <select
                    id="area_id"
                    name="area_id"
                    onchange="this.form.submit()"
                    class="form-control"
                >
                    <option value="">
                        -- Todas las áreas --
                    </option>

                    @foreach ($areas as $area)
                        <option
                            value="{{ $area->id_area }}"
                            @selected(
                                (string) $areaId
                                === (string) $area->id_area
                            )
                        >
                            {{ $area->nombre }}
                        </option>
                    @endforeach
                </select>
            </form>

            <div class="table-container modern-table">

                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Cargo</th>
                            <th>Área</th>
                            <th>Plantilla de referencia</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($personas as $persona)
                            <tr>
                                <td>{{ $persona->getKey() }}</td>

                                <td class="bold">
                                    {{ $persona->nombre }}
                                </td>

                                <td>{{ $persona->cargo }}</td>

                                <td>
                                    {{ $persona->area->nombre ?? '-' }}
                                </td>

                                <td>
                                    {{ $persona->plantilla_referencia ?: 'Sin plantilla' }}
                                </td>

                                <td class="actions">
                                    <a
                                        href="{{ route('personas.edit', $persona->getKey()) }}"
                                        class="btn-edit"
                                    >
                                        Editar
                                    </a>

                                    <form
                                        action="{{ route('personas.destroy', $persona->getKey()) }}"
                                        method="POST"
                                        onsubmit="return confirm('¿Eliminar esta persona?')"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="btn-delete"
                                        >
                                            Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="empty">
                                    No hay registros.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

            </div>

            <div class="pagination-modern">
                {{ $personas->onEachSide(1)->links('vendor.pagination.simple-numbers') }}
            </div>

        </div>

    </div>

</div>

{{-- MODAL DE CARGA MASIVA --}}
<div
    class="import-modal"
    id="import_modal"
    hidden
>
    <div
        class="import-overlay"
        id="import_overlay"
    ></div>

    <div
        class="import-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="import_title"
    >
        <button
            type="button"
            class="modal-close"
            id="btn_close_import"
            aria-label="Cerrar"
        >
            ×
        </button>

        <h3 id="import_title">Carga masiva de personas</h3>

        <p>
            Suba la plantilla oficial en formato Excel. Las filas con ID
            actualizarán registros existentes y las filas sin ID crearán
            personas nuevas.
        </p>

        <form
            action="{{ route('personas.importar-masivo') }}"
            method="POST"
            enctype="multipart/form-data"
            id="form_import"
        >
            @csrf

            <div class="form-group full">
                <label for="archivo_personas">
                    Archivo de Excel
                </label>

                <input
                    type="file"
                    id="archivo_personas"
                    name="archivo_personas"
                    accept=".xlsx,.xls"
                    required
                >

                <small>
                    Formatos permitidos: .xlsx y .xls. Máximo 10 MB.
                </small>
            </div>

            <div class="modal-actions">
                <button
                    type="button"
                    class="btn-cancel"
                    id="btn_cancel_import"
                >
                    Cancelar
                </button>

                <button
                    type="submit"
                    class="btn-primary"
                    id="btn_import"
                >
                    Procesar carga masiva
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    .header-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 12px;
        flex-wrap: wrap;
    }

    .massive-menu {
        position: relative;
    }

    .btn-massive {
        display: inline-flex;
        min-width: 165px;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        font-family: inherit;
        line-height: 1;
        cursor: pointer;
        transition:
            background-color .2s ease,
            color .2s ease,
            border-color .2s ease;
    }

    .btn-massive:hover {
        background: #ffffff;
        color: #7A1623;
        border-color: #7A1623;
    }

    .btn-massive:focus-visible {
        outline: 3px solid rgba(122, 22, 35, .18);
        outline-offset: 2px;
    }

    .btn-massive-content {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        white-space: nowrap;
    }

    .btn-massive-icon {
        width: 18px;
        height: 18px;
        flex: 0 0 18px;
    }

    .btn-massive-chevron {
        width: 15px;
        height: 15px;
        flex: 0 0 15px;
        transition: transform .2s ease;
    }

    .btn-massive[aria-expanded="true"] .btn-massive-chevron {
        transform: rotate(180deg);
    }


    .btn-new-person {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 32px;
        white-space: nowrap;
        transition:
            background-color .2s ease,
            color .2s ease,
            border-color .2s ease;
    }

    .btn-new-person-icon {
        width: 15px;
        height: 15px;
        flex: 0 0 15px;
    }

    .massive-dropdown {
        position: absolute;
        z-index: 40;
        top: calc(100% + 8px);
        right: 0;
        width: 290px;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #fff;
        box-shadow: 0 16px 36px rgba(17, 24, 39, .16);
        transform-origin: top right;
    }

    .massive-dropdown::before {
        position: absolute;
        top: -6px;
        right: 24px;
        width: 11px;
        height: 11px;
        border-top: 1px solid #e5e7eb;
        border-left: 1px solid #e5e7eb;
        background: #fff;
        content: "";
        transform: rotate(45deg);
    }

    .massive-dropdown[hidden] {
        display: none !important;
    }

    .massive-option {
        position: relative;
        display: flex;
        width: 100%;
        min-height: 64px;
        box-sizing: border-box;
        align-items: center;
        gap: 13px;
        padding: 12px 15px;
        border: 0;
        border-bottom: 1px solid #f0f1f3;
        color: #252525;
        background: #fff;
        font-family: inherit;
        text-align: left;
        text-decoration: none;
        cursor: pointer;
        transition:
            background-color .18s ease,
            color .18s ease;
    }

    .massive-option:last-child {
        border-bottom: 0;
    }

    .massive-option:hover,
    .massive-option:focus-visible {
        color: #8d1838;
        background: #fbf5f7;
        outline: none;
    }

    .massive-option > span:last-child {
        display: flex;
        flex-direction: column;
        gap: 3px;
        min-width: 0;
    }

    .massive-option strong {
        color: inherit;
        font-size: 13px;
        font-weight: 650;
        line-height: 1.2;
    }

    .massive-option small {
        color: #6b7280;
        font-size: 11.5px;
        font-weight: 400;
        line-height: 1.25;
    }

    .option-icon {
        display: inline-grid;
        width: 34px;
        height: 34px;
        flex: 0 0 34px;
        place-items: center;
        border-radius: 8px;
        color: #8d1838;
        background: #f8e9ee;
    }

    .option-icon svg {
        width: 18px;
        height: 18px;
    }

    @media (max-width: 700px) {
        .header-actions {
            width: 100%;
            justify-content: stretch;
        }

        .massive-menu,
        .btn-massive,
        .header-actions > .btn-table {
            width: 100%;
        }

        .massive-dropdown {
            right: auto;
            left: 0;
            width: min(100%, 320px);
        }
    }

    .filter-form {
        display: grid;
        max-width: 420px;
        gap: 6px;
        margin-bottom: 20px;
    }

    .alert-success,
    .alert-error {
        margin-bottom: 18px;
        padding: 14px 18px;
        border-radius: 7px;
    }

    .alert-success {
        color: #0f5132;
        background: #d1e7dd;
        border: 1px solid #badbcc;
    }

    .alert-error {
        color: #842029;
        background: #f8d7da;
        border: 1px solid #f5c2c7;
    }

    .import-modal {
        position: fixed;
        z-index: 1000;
        inset: 0;
        display: grid;
        place-items: center;
        padding: 20px;
    }

    .import-modal[hidden] {
        display: none !important;
    }

    .import-overlay {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, .52);
    }

    .import-dialog {
        position: relative;
        z-index: 1;
        width: min(560px, 100%);
        box-sizing: border-box;
        padding: 28px;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 20px 60px rgba(0, 0, 0, .28);
    }

    .modal-close {
        position: absolute;
        top: 12px;
        right: 14px;
        border: 0;
        background: transparent;
        font-size: 28px;
        cursor: pointer;
    }

    .modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 22px;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const menu = document.getElementById('massive_menu');
    const botonMenu = document.getElementById('btn_massive');
    const dropdown = document.getElementById('massive_dropdown');

    const modal = document.getElementById('import_modal');
    const abrirModal = document.getElementById('btn_open_import');
    const cerrarModal = document.getElementById('btn_close_import');
    const cancelarModal = document.getElementById('btn_cancel_import');
    const overlay = document.getElementById('import_overlay');

    const formularioImportacion = document.getElementById('form_import');
    const botonImportar = document.getElementById('btn_import');

    botonMenu.addEventListener('click', function () {
        const abierto = !dropdown.hidden;

        dropdown.hidden = abierto;
        botonMenu.setAttribute(
            'aria-expanded',
            String(!abierto)
        );
    });

    document.addEventListener('click', function (event) {
        if (!menu.contains(event.target)) {
            dropdown.hidden = true;
            botonMenu.setAttribute('aria-expanded', 'false');
        }
    });

    abrirModal.addEventListener('click', function () {
        dropdown.hidden = true;
        modal.hidden = false;
        document.body.style.overflow = 'hidden';

        setTimeout(function () {
            document.getElementById('archivo_personas').focus();
        }, 50);
    });

    function ocultarModal() {
        modal.hidden = true;
        document.body.style.overflow = '';
    }

    cerrarModal.addEventListener('click', ocultarModal);
    cancelarModal.addEventListener('click', ocultarModal);
    overlay.addEventListener('click', ocultarModal);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.hidden) {
            ocultarModal();
        }
    });

    formularioImportacion.addEventListener('submit', function () {
        botonImportar.disabled = true;
        botonImportar.textContent = 'Procesando archivo...';
    });

    @if ($errors->has('archivo_personas'))
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
    @endif

});
</script>

@endsection