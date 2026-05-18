@extends('layouts.app')

@section('content')

<div class="main-procedimientos">

    {{-- SIDEBAR --}}
    @include('layouts.admin_sidebar')

    {{-- CONTENIDO --}}
    <div class="contenido-procedimientos">

        <div class="card-procedimientos">

            <h2 class="titulo-procedimientos">
                Procedimientos Registrados
            </h2>

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

                    @foreach($procedimientos as $p)

                        <tr>

                            <td>{{ $p->nombre_procedimiento }}</td>

                            <td>{{ $p->num_procedimiento }}</td>

                            <td>{{ $p->user_id }}</td>

                            <td>{{ $p->id_persona }}</td>

                            <td>Área Técnica</td>

                            <td class="monto">
                                ${{ number_format($p->monto_maximo, 2) }}
                            </td>

                            <td>

                                <div class="badge-vigencia">

                                    {{ $p->fecha_inicio_contrato }}

                                    al

                                    {{ $p->fecha_fin_contrato }}

                                </div>

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection