@extends('layouts.app')

@section('title', 'Registros')

@section('content')

<div class="admin-layout">

    @include('comprador.sidebar')

    <div class="admin-content">
        <div class="card-container">

            <div class="card-header">
                <div>
                    <h2>Registros</h2>
                    <p class="subtitle">
                        Herramientas de apoyo para actualizar información del sistema.
                    </p>
                </div>
            </div>

            @if (session('success'))
                <div class="alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert-error">
                    <strong>No fue posible completar la operación:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="registro-card">
                <div class="registro-icon">
                    <i data-feather="users"></i>
                </div>

                <div class="registro-content">
                    <h3>Registro y actualización de personal de la GMS</h3>

                    <p>
                        Descargue la plantilla oficial para consultar, registrar o
                        actualizar personal. Las filas con ID modificarán registros
                        existentes y las filas sin ID crearán personas nuevas.
                    </p>

                    <div class="registro-actions">
                        <a
                            href="{{ route('comprador.registros.personas.plantilla') }}"
                            class="btn-registro"
                        >
                            <i data-feather="download"></i>
                            <span>Descargar plantilla</span>
                        </a>

                        <button
                            type="button"
                            class="btn-registro btn-secondary-registro"
                            id="btn_open_import"
                        >
                            <i data-feather="upload"></i>
                            <span>Cargar archivo Excel</span>
                        </button>
                    </div>
                </div>
            </section>

            <section class="registro-card">
                <div class="registro-icon">
                    <i data-feather="file-text"></i>
                </div>

                <div class="registro-content">
                    <h3>Documentos para adjudicación</h3>

                    <p>
                        Administre los nombres, leyendas, orden, obligatoriedad y
                        estado de los documentos que pueden integrarse al Word.
                    </p>

                    <div class="registro-actions">
                        <a
                            href="{{ route('comprador.registros.documentos.index') }}"
                            class="btn-registro"
                        >
                            <i data-feather="settings"></i>
                            <span>Administrar documentos</span>
                        </a>
                    </div>
                </div>
            </section>

            <section class="registro-card proximo">
                <div class="registro-icon">
                    <i data-feather="plus-circle"></i>
                </div>

                <div class="registro-content">
                    <h3>Más herramientas próximamente</h3>
                    <p>
                        Esta ruta podrá incorporar nuevas funciones para los compradores.
                    </p>
                </div>
            </section>

        </div>
    </div>

</div>

<div class="import-modal" id="import_modal" hidden>
    <div class="import-overlay" id="import_overlay"></div>

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

        <h3 id="import_title">
            Carga masiva de personal de la GMS
        </h3>

        <p>
            Seleccione la plantilla oficial en formato Excel.
        </p>

        <form
            action="{{ route('comprador.registros.personas.importar') }}"
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
                    Procesar archivo
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.registro-card {
    display: flex;
    gap: 20px;
    align-items: flex-start;
    margin-bottom: 20px;
    padding: 24px;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 8px 24px rgba(17, 24, 39, .06);
}

.registro-icon {
    display: grid;
    width: 48px;
    height: 48px;
    flex: 0 0 48px;
    place-items: center;
    border-radius: 12px;
    color: #7a1623;
    background: #f8e9ee;
}

.registro-content {
    flex: 1;
}

.registro-content h3 {
    margin: 0 0 8px;
}

.registro-content p {
    max-width: 760px;
    margin: 0;
    color: #626262;
    line-height: 1.6;
}

.registro-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 20px;
}

.btn-registro {
    display: inline-flex;
    min-height: 38px;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 8px 14px;
    border: 1px solid #7a1623;
    border-radius: 7px;
    color: #fff;
    background: #7a1623;
    font-family: inherit;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
}

.btn-registro:hover {
    color: #7a1623;
    background: #fff;
}

.btn-secondary-registro {
    color: #7a1623;
    background: #fff;
}

.btn-secondary-registro:hover {
    color: #fff;
    background: #7a1623;
}

.proximo {
    opacity: .78;
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

@media (max-width: 680px) {
    .registro-card {
        flex-direction: column;
    }

    .registro-actions,
    .btn-registro {
        width: 100%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('import_modal');
    const abrir = document.getElementById('btn_open_import');
    const cerrar = document.getElementById('btn_close_import');
    const cancelar = document.getElementById('btn_cancel_import');
    const overlay = document.getElementById('import_overlay');
    const formulario = document.getElementById('form_import');
    const boton = document.getElementById('btn_import');

    function mostrarModal() {
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
    }

    function ocultarModal() {
        modal.hidden = true;
        document.body.style.overflow = '';
    }

    abrir.addEventListener('click', mostrarModal);
    cerrar.addEventListener('click', ocultarModal);
    cancelar.addEventListener('click', ocultarModal);
    overlay.addEventListener('click', ocultarModal);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.hidden) {
            ocultarModal();
        }
    });

    formulario.addEventListener('submit', function () {
        boton.disabled = true;
        boton.textContent = 'Procesando archivo...';
    });

    @if ($errors->has('archivo_personas'))
        mostrarModal();
    @endif
});
</script>

@endsection
