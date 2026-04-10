@extends('layouts.app')

@section('content')

<div class="admin-layout">

    @include('comprador.sidebar')

    <div class="admin-content">
        <div class="welcome-card">
            <h2>Formulario de Convocatoria</h2>

            <form action="{{ route('procedimientos.store') }}" method="POST">
                @csrf

                <h3>Datos principales</h3>

                <input type="text" name="nombre_procedimiento" placeholder="Nombre del procedimiento"><br><br>

                <input type="text" name="num_procedimiento" placeholder="Número de procedimiento"><br><br>

                <label>Fecha publicación</label><br>
                <input type="date" name="fecha_publicacion"><br><br>

                <label>Fecha VM</label><br>
                <input type="date" name="fecha_vm"><br><br>

                <label>Fecha ACL</label><br>
                <input type="date" name="fecha_acl"><br><br>

                <label>Hora ACL</label><br>
                <input type="time" name="hora_acl"><br><br>

                <label>Fecha apertura</label><br>
                <input type="date" name="fecha_apertura"><br><br>

                <label>Hora apertura</label><br>
                <input type="time" name="hora_apertura"><br><br>

                <label>Fecha fallo</label><br>
                <input type="date" name="fecha_fallo"><br><br>

                <label>Hora fallo</label><br>
                <input type="time" name="hora_fallo"><br><br>

                <hr>

                <h3>Datos para documento (no se guardan)</h3>

                <input type="text" name="partida_nombre" placeholder="Nombre de partida"><br><br>

                <input type="text" name="num_requisicion" placeholder="Número de requisición"><br><br>

                <input type="text" name="resp_tecnico" placeholder="Responsable técnico"><br><br>

                <input type="text" name="cargo_tecnico" placeholder="Cargo técnico"><br><br>

                <input type="text" name="resp_admin" placeholder="Responsable administrativo"><br><br>

                <input type="number" step="0.01" name="monto_maximo" placeholder="Monto máximo"><br><br>

                <input type="number" step="0.01" name="monto_minimo" placeholder="Monto mínimo"><br><br>

                <input type="text" name="plazo_contrato" placeholder="Plazo contrato"><br><br>

                <input type="text" name="ruta_documento" placeholder="Ruta documento"><br><br>

                <input type="date" name="fecha_creacion"><br><br>

                <button type="submit">Guardar</button>

            </form>
        </div>

    </div>

</div>

@endsection