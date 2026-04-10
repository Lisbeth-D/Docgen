@extends('layouts.app')

@section('title','Actividad del sistema')

@section('content')

<div class="admin-layout">
@include('layouts.admin_sidebar')

<div class="admin-content">

<div class="card-container">

<div class="card-header">
<h2>Actividad del sistema</h2>
</div>

<div class="stats-box">

    <div class="stat-card">
        <p>Total de usuarios registrados</p>
        <h2>{{ $totalUsuarios }}</h2>
    </div>

    <div class="stat-card">
        <p>Total de personas registradas</p>
        <h2>{{ $totalPersonas }}</h2>
    </div>

</div>

<div class="chart-container">
    <canvas id="graficaSistema"></canvas>
</div>
</div>
</div>
</div>

<script src="{{ asset('js/chart.js') }}"></script>

<script>
const ctx = document.getElementById('graficaSistema');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Usuarios', 'Personas'],
        datasets: [{
            label: 'Registros del sistema',
            data: [
                {{ $totalUsuarios }},
                {{ $totalPersonas }}
            ],
            backgroundColor: [
                '#7A1623',
                '#1976d2'
            ]
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>

@endsection