@extends('layouts.app')
@section('title', 'Editar documento')
@section('content')
<div class="admin-layout">
    @include('comprador.sidebar')
    <div class="admin-content">
        <div class="conv-wrapper">
            <form action="{{ route('comprador.registros.documentos.update', $documento) }}" method="POST" class="conv-form">
                @method('PUT')
                <h2 class="conv-title">Editar documento para adjudicación</h2>
                <div class="conv-card">
                    @include('comprador.registros.documentos._form', ['textoBoton' => 'Actualizar documento'])
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
