<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\ProcedimientoController;
use App\Http\Controllers\RevisionController;
use App\Http\Controllers\PublicacionController;
use App\Http\Controllers\AdjudicacionController;
use App\Http\Controllers\DesignacionController;
use App\Http\Controllers\AcPreguntaController;
use App\Http\Controllers\AclaracionController;
use App\Http\Controllers\ActaCierreController;
use App\Http\Controllers\NoAplicaController;
use App\Http\Controllers\AperturaController;
use App\Http\Controllers\FalloController;

/*
|--------------------------------------------------------------------------
| Ruta de inicio
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('auth.login');
});

/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS (USUARIO AUTENTICADO)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | RUTAS COMPRADOR
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:comprador')->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('/convocatoria', [ProcedimientoController::class, 'convocatoria'])
            ->name('convocatoria');

        Route::post('/procedimientos', [ProcedimientoController::class, 'store'])
            ->name('procedimientos.store');

        Route::get('/procedimientos/{id}', [ProcedimientoController::class, 'show'])
            ->name('procedimientos.show');

        Route::get('/procedimientos/{id}/descargar', [ProcedimientoController::class, 'descargar'])
            ->name('procedimientos.descargar');

        /*
        |--------------------------------------------------------------------------
        | REVISIÓN
        |--------------------------------------------------------------------------
        */

        Route::get('/revision', [RevisionController::class, 'index'])
            ->name('revision.form');

        Route::post('/revision', [RevisionController::class, 'generar'])
            ->name('revision.generar');

        // ESTA RUTA ES LA QUE TE FALTABA
        Route::get('/buscar-procedimiento/{valor}', [RevisionController::class, 'buscarProcedimiento'])
            ->name('revision.buscar');

        /*
        |--------------------------------------------------------------------------
        | PUBLICACIÓN
        |--------------------------------------------------------------------------
        */

        Route::get('/publicacion', [PublicacionController::class, 'index'])
            ->name('publicacion.index');

        Route::post('/publicacion/generar', [PublicacionController::class, 'generar'])
            ->name('publicacion.generar');

        /*
        |--------------------------------------------------------------------------
        | ADJUDICACIÓN
        |--------------------------------------------------------------------------
        */

        Route::get('/adjudicacion', [AdjudicacionController::class, 'index'])
            ->name('adjudicacion.index');

        Route::post('/adjudicacion/generar', [AdjudicacionController::class, 'generar'])
            ->name('adjudicacion.generar');
        
        Route::get('/buscar-procedimiento-adjudicacion/{valor}', [AdjudicacionController::class, 'buscarProcedimiento'])
             ->name('adjudicacion.buscar');

        /*
        |--------------------------------------------------------------------------
        | DESIGNACIÓN
        |--------------------------------------------------------------------------
        */

        Route::get('/designacion', [DesignacionController::class, 'index'])
            ->name('designacion.index');

        Route::post('/designacion/generar', [DesignacionController::class, 'generar'])
            ->name('designacion.generar');

        Route::get('/buscar-procedimiento-designacion/{valor}', [DesignacionController::class, 'buscarProcedimiento'])
    ->name('designacion.buscar');

        /*
        |--------------------------------------------------------------------------
        | ACTA DE PREGUNTAS
        |--------------------------------------------------------------------------
        */

        Route::get('/ac-pregunta', [AcPreguntaController::class, 'index'])
            ->name('ac.index');

        Route::post('/ac-pregunta/generar', [AcPreguntaController::class, 'generar'])
            ->name('ac.generar');

        Route::get('/buscar-procedimiento-ac/{valor}', [AcPreguntaController::class, 'buscarProcedimiento']);

        /*
        |--------------------------------------------------------------------------
        | ACTA DE ACLARACIÓN
        |--------------------------------------------------------------------------
        */

        Route::get('/acta', [AclaracionController::class, 'index'])
            ->name('acta.index');

        Route::post('/acta/generar', [AclaracionController::class, 'generar'])
            ->name('acta.generar');

        Route::get('/buscar-procedimiento-acta/{valor}', [AclaracionController::class, 'buscarProcedimiento']);

        /*
        |--------------------------------------------------------------------------
        | ACTA DE CIERRE
        |--------------------------------------------------------------------------
        */

        Route::get('/acta-cierre', [ActaCierreController::class, 'index'])
            ->name('actacierre.index');

        Route::post('/acta-cierre/generar', [ActaCierreController::class, 'generar'])
            ->name('actacierre.generar');

        Route::get('/buscar-procedimiento-actacierre/{valor}', [ActaCierreController::class, 'buscarProcedimiento']);

        Route::get('/no-aplica-junta', [NoAplicaController::class, 'index'])
        ->name('noaplica.index');

        Route::get('/no-aplica-junta/buscar/{valor}', [NoAplicaController::class, 'buscarProcedimiento'])
            ->name('noaplica.buscar');

        Route::post('/no-aplica-junta/generar', [NoAplicaController::class, 'generar'])
            ->name('noaplica.generar');

        /*
        |--------------------------------------------------------------------------
        | apertura
        |--------------------------------------------------------------------------
        */

        Route::get('/apertura', [AperturaController::class, 'index'])
            ->name('apertura.index');

        Route::get('/apertura/buscar/{valor}', [AperturaController::class, 'buscarProcedimiento'])
            ->name('apertura.buscar');

        Route::post('/apertura/generar', [AperturaController::class, 'generar'])
            ->name('apertura.generar');

         /*
        |--------------------------------------------------------------------------
        | fallo
        |--------------------------------------------------------------------------
        */


        Route::get('/fallo/acta', [FalloController::class, 'indexActa'])
        ->name('fallo.acta.index');

        Route::post('/fallo/acta/generar', [FalloController::class, 'generarActa'])
            ->name('fallo.acta.generar');

        Route::get('/fallo/buscar/{valor}', [FalloController::class, 'buscarProcedimiento']);

        Route::get('/fallo/dictamen', function () {
            return view('comprador.Fallo.dictamenFallo');
        })->name('fallo.dictamen.index');
    });

    /*
    |--------------------------------------------------------------------------
    | RUTAS ADMIN
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth', 'role:admin'])->group(function () {

        Route::get('/admin/dashboard', [DashboardController::class, 'adminDashboard'])
            ->name('admin.dashboard');

        Route::get('/usuarios', [UserController::class, 'index']);
        Route::get('/usuarios/crear', [UserController::class, 'create']);
        Route::post('/usuarios', [UserController::class, 'store']);
        Route::delete('/usuarios/{id}', [UserController::class, 'destroy']);
        Route::put('/usuarios/{id}', [UserController::class, 'update']);
        Route::get('/usuarios/{id}/editar', [UserController::class, 'edit']);

        Route::post('/usuarios/reset/{id}', [UserController::class, 'resetPassword']);

        Route::post('/usuarios/toggle/{id}', [UserController::class, 'toggleActivo']);

        Route::get('/admin/reportes/actividad', [UserController::class, 'actividad'])
            ->middleware(['auth', 'role:admin']);

        Route::get('/personas', [PersonaController::class, 'index']);
        Route::get('/personas/crear', [PersonaController::class, 'create'])
            ->name('personas.create');
        Route::get('/personas/{id}/editar', [PersonaController::class, 'edit']);
        Route::put('/personas/{id}', [PersonaController::class, 'update']);
        Route::post('/personas', [PersonaController::class, 'store']);
        Route::delete('/personas/{id}', [PersonaController::class, 'destroy']);

        Route::get('/procedimientos', [ProcedimientoController::class, 'procedi']);
    });
});

/*
|--------------------------------------------------------------------------
| LOGOUT
|--------------------------------------------------------------------------
*/

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->name('logout');

/*
|--------------------------------------------------------------------------
| AUTH ROUTES (Laravel Breeze)
|--------------------------------------------------------------------------
*/

require __DIR__ . '/auth.php';