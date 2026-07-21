@extends('layouts.app')
@section('title', 'Nuevo documento')
@section('content')
<div class="admin-layout">
    @include('comprador.sidebar')
    <div class="admin-content">
        <div class="conv-wrapper">
            <form action="{{ route('comprador.registros.documentos.store') }}" method="POST" class="conv-form">
                <h2 class="conv-title">Nuevo documento para adjudicación</h2>
                <div class="conv-card">
                    @include('comprador.registros.documentos._form', ['textoBoton' => 'Guardar documento'])
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
