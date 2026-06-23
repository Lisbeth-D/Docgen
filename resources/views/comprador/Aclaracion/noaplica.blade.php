@extends('layouts.app')

@section('title', 'No aplica junta')

@section('content')

<div class="admin-layout">

    @include('comprador.sidebar')

    <div class="admin-content">

        <div class="form-wrapper">

            <div class="form-card">

                <h2>No aplica junta</h2>

                <p class="form-subtitle">
                    Complete la información para generar el documento.
                </p>

                @if(session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger">
                        Revise los campos obligatorios.
                    </div>
                @endif

                <form action="{{ route('noaplica.generar') }}"
                      method="POST"
                      enctype="multipart/form-data">

                    @csrf

                    <div class="form-grid">

                        {{-- Plantilla Word --}}
                        <div class="form-group full">
                            <label>Plantilla Word (.docx)</label>
                            <input type="file"
                                   name="archivo_word"
                                   accept=".docx"
                                   required>
                        </div>

                        {{-- Número para búsqueda --}}
                        <div class="form-group">
                            <label>Número de procedimiento</label>
                            <input type="text"
                                   name="numero_busqueda"
                                   id="numero_busqueda"
                                   value="{{ old('numero_busqueda') }}"
                                   placeholder="Ejemplo: 25"
                                   autocomplete="off"
                                   required>
                        </div>

                        {{-- Número completo --}}
                        <div class="form-group">
                            <label>Número procedimiento</label>
                            <input type="text"
                                   id="num_procedimiento"
                                   name="num_procedimiento"
                                   value="{{ old('num_procedimiento') }}"
                                   readonly>
                        </div>

                        {{-- Nombre --}}
                        <div class="form-group full">
                            <label>Nombre procedimiento</label>
                            <input type="text"
                                   id="nombre_procedimiento"
                                   name="nombre_procedimiento"
                                   value="{{ old('nombre_procedimiento') }}"
                                   readonly>
                        </div>

                        {{-- Fecha --}}
                        <div class="form-group">
                            <label>Fecha apertura</label>
                            <input type="date"
                                   id="fecha_apertura"
                                   name="fecha_apertura"
                                   value="{{ old('fecha_apertura') }}">
                        </div>

                        {{-- Correo --}}
                        <div class="form-group">
                            <label>Correo comprador</label>
                            <input type="email"
                                   name="correo_comprador"
                                   value="{{ old('correo_comprador', Auth::user()->email ?? '') }}">
                        </div>

                        {{-- Revisó --}}
                        <div class="form-group">
                            <label>Revisó</label>

                            <select name="reviso_id" required>
                                <option value="">Seleccione...</option>

                                @foreach($revisores as $persona)
                                    <option value="{{ $persona->id }}"
                                        {{ old('reviso_id') == $persona->id ? 'selected' : '' }}>
                                        {{ $persona->nombre }} - {{ $persona->cargo }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Elaboró --}}
                        <div class="form-group">
                            <label>Elaboró</label>
                            <input type="text"
                                   name="elaboro"
                                   value="{{ old('elaboro', Auth::user()->name ?? '') }}"
                                   required>
                        </div>

                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            Generar documento
                        </button>
                    </div>

                </form>

            </div>

        </div>

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const numeroBusqueda = document.getElementById('numero_busqueda');
    const numProcedimiento = document.getElementById('num_procedimiento');
    const nombreProcedimiento = document.getElementById('nombre_procedimiento');
    const fechaApertura = document.getElementById('fecha_apertura');

    let timer = null;

    numeroBusqueda.addEventListener('input', function () {
        clearTimeout(timer);

        let valor = this.value.trim();

        if (valor === '') {
            limpiarCampos();
            return;
        }

        timer = setTimeout(function () {
            buscarProcedimiento(valor);
        }, 300);
    });

    numeroBusqueda.addEventListener('blur', function () {
        let valor = this.value.trim();

        if (valor !== '') {
            buscarProcedimiento(valor);
        }
    });

    function buscarProcedimiento(valor) {
        fetch("{{ url('/no-aplica-junta/buscar') }}/" + encodeURIComponent(valor), {
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la búsqueda');
            }

            return response.json();
        })
        .then(data => {
            if (!data) {
                limpiarCampos();
                return;
            }

            numProcedimiento.value = data.num_procedimiento || '';
            nombreProcedimiento.value = data.nombre_procedimiento || '';
            fechaApertura.value = data.fecha_apertura || '';
        })
        .catch(error => {
            console.error('Error al buscar procedimiento:', error);
            limpiarCampos();
        });
    }

    function limpiarCampos() {
        numProcedimiento.value = '';
        nombreProcedimiento.value = '';
        fechaApertura.value = '';
    }

});
</script>

@endsection