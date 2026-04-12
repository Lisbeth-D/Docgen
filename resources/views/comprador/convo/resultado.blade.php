@extends('layouts.app')

@section('content')

<div class="admin-layout">

    @include('comprador.sidebar')

    <div class="admin-content">

        <div class="card-container">

            <h2>Procedimiento guardado</h2>

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <p><strong>Nombre:</strong> {{ $procedimiento->nombre_procedimiento }}</p>
            <p><strong>Número:</strong> {{ $procedimiento->num_procedimiento }}</p>

            <br>

            <a href="{{ route('procedimientos.descargar', $procedimiento->id) }}" class="btn-primary">
                Descargar documento
            </a>

        </div>

    </div>

</div>

@endsection