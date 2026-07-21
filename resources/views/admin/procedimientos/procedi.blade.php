@extends('layouts.app')

@section('content')

<div class="main-procedimientos">

    {{-- SIDEBAR --}}
    @include('layouts.admin_sidebar')

    {{-- CONTENIDO --}}
    <div class="contenido-procedimientos">

        <div class="card-procedimientos">

            <div class="encabezado-procedimientos">

                <div>
                    <h2 class="titulo-procedimientos">
                        Procedimientos Registrados
                    </h2>

                    <p class="subtitulo-procedimientos">
                        Consulta y descarga la información de los procedimientos registrados.
                    </p>
                </div>

                <a
                    href="{{ route('procedimientos.reporte') }}"
                    class="btn-reporte"
                >
                    <i data-feather="file-text"></i>

                    <span>Reporte</span>
                </a>

            </div>

            <div class="contenedor-tabla-procedimientos">

                <table class="tabla-procedimientos">

                    <thead>
                        <tr>
                            <th>Nombre Procedimiento</th>
                            <th>Número Procedimiento</th>
                            <th>Comprador</th>
                            <th>Área Técnica</th>
                            <th>Cargo Técnico</th>
                            <th>Monto Máximo</th>
                            <th>Vigencia Contrato</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($procedimientos as $p)

                            <tr>

                                <td>
                                    {{ $p->nombre_procedimiento }}
                                </td>

                                <td>
                                    {{ $p->num_procedimiento }}
                                </td>

                                <td>
                                    {{
                                        $p->comprador->name
                                        ?? $p->comprador->username
                                        ?? 'Sin asignar'
                                    }}
                                </td>

                                <td>
                                    {{
                                        $p->persona->nombre
                                        ?? 'Sin asignar'
                                    }}
                                </td>

                                <td>
                                    {{
                                        $p->persona->cargo
                                        ?? 'Sin asignar'
                                    }}
                                </td>

                                <td class="monto">
                                    ${{
                                        number_format(
                                            (float) $p->monto_maximo,
                                            2
                                        )
                                    }}
                                </td>

                                <td>

                                    <div class="badge-vigencia">

                                        {{
                                            $p->fecha_inicio_contrato
                                            ?: 'Sin fecha'
                                        }}

                                        al

                                        {{
                                            $p->fecha_fin_contrato
                                            ?: 'Sin fecha'
                                        }}

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td
                                    colspan="7"
                                    class="sin-registros"
                                >
                                    No hay procedimientos registrados.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

<style>
    .encabezado-procedimientos {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 24px;
    }

    .titulo-procedimientos {
        margin: 0;
    }

    .subtitulo-procedimientos {
        margin: 6px 0 0;
        color: #667085;
        font-size: 14px;
    }

    .btn-reporte {
        display: inline-flex;
        min-height: 40px;
        box-sizing: border-box;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 9px 16px;
        border: 1px solid #7a1623;
        border-radius: 7px;
        color: #ffffff;
        background: #7a1623;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition:
            color .2s ease,
            background-color .2s ease;
    }

    .btn-reporte:hover {
        color: #7a1623;
        background: #ffffff;
    }

    .btn-reporte svg {
        width: 17px;
        height: 17px;
    }

    .contenedor-tabla-procedimientos {
        width: 100%;
        overflow-x: auto;
    }

    .sin-registros {
        padding: 30px;
        color: #667085;
        text-align: center;
    }

    @media (max-width: 700px) {
        .encabezado-procedimientos {
            align-items: stretch;
            flex-direction: column;
        }

        .btn-reporte {
            width: 100%;
        }
    }
</style>

@endsection