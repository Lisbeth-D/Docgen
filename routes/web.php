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
use App\Http\Controllers\DictFalloController;
use App\Http\Controllers\RegistroCompradorController;
use App\Http\Controllers\DocumentoAdjudicacionController;
use App\Http\Controllers\HistorialDocumentosController;


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
Route::middleware('auth')
    ->prefix('historial-documentos')
    ->name('historial-documentos.')
    ->group(function () {
        Route::get(
            '/',
            [
                HistorialDocumentosController::class,
                'index',
            ]
        )->name('index');

        Route::get(
            '/{documento}/descargar',
            [
                HistorialDocumentosController::class,
                'descargar',
            ]
        )->name('descargar');

        Route::delete(
            '/{documento}',
            [
                HistorialDocumentosController::class,
                'eliminar',
            ]
        )->name('eliminar');
    });
    
Route::middleware(['auth'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | RUTAS COMPRADOR
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:comprador')->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('/registros', [RegistroCompradorController::class, 'index'])
            ->name('comprador.registros.index');

        Route::get('/registros/personas/plantilla', [PersonaController::class, 'descargarPlantillaMasiva'])
            ->name('comprador.registros.personas.plantilla');

        Route::post('/registros/personas/importar', [PersonaController::class, 'importarMasivo'])
            ->name('comprador.registros.personas.importar');

        Route::prefix('registros/documentos-adjudicacion')
            ->name('comprador.registros.documentos.')
            ->group(function () {
                Route::get('/', [DocumentoAdjudicacionController::class, 'index'])->name('index');
                Route::get('/crear', [DocumentoAdjudicacionController::class, 'create'])->name('create');
                Route::post('/', [DocumentoAdjudicacionController::class, 'store'])->name('store');
                Route::get('/{documento}/editar', [DocumentoAdjudicacionController::class, 'edit'])->name('edit');
                Route::put('/{documento}', [DocumentoAdjudicacionController::class, 'update'])->name('update');
                Route::patch('/{documento}/estado', [DocumentoAdjudicacionController::class, 'cambiarEstado'])->name('estado');
                Route::delete('/{documento}', [DocumentoAdjudicacionController::class, 'destroy'])->name('destroy');
            });

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

        Route::get(
            '/buscar-procedimiento-publicacion/{valor}',
            [PublicacionController::class, 'buscarProcedimiento']
        )->name('publicacion.buscar');

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
        |
        | Se utilizan nombres de ruta exclusivos para evitar que esta sección
        | intercepte el formulario del Acta de Junta de Aclaraciones.
        |
        */

        Route::get('/ac-pregunta', [AcPreguntaController::class, 'index'])
            ->name('acpregunta.index');

        Route::post('/ac-pregunta/generar', [AcPreguntaController::class, 'generar'])
            ->name('acpregunta.generar');

        Route::get(
            '/buscar-procedimiento-ac-pregunta/{valor}',
            [AcPreguntaController::class, 'buscarProcedimiento']
        )->name('acpregunta.buscar');

        /*
        |--------------------------------------------------------------------------
        | ACTA DE JUNTA DE ACLARACIONES
        |--------------------------------------------------------------------------
        |
        | Estas rutas coinciden con el formulario acta.blade.php:
        | route('ac.generar') y /buscar-procedimiento-ac/{valor}.
        |
        */

        Route::get('/acta', [AclaracionController::class, 'index'])
            ->name('ac.index');

        Route::post('/acta/generar', [AclaracionController::class, 'generar'])
            ->name('ac.generar');

        Route::get(
            '/buscar-procedimiento-ac/{valor}',
            [AclaracionController::class, 'buscarProcedimiento']
        )->name('ac.buscar');

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

        
        Route::get('/dictamen-fallo', [DictFalloController::class, 'index'])
            ->name('dictamen.fallo.index');

        Route::get('/dictamen-fallo/buscar/{valor}', [DictFalloController::class, 'buscarProcedimiento'])
            ->name('dictamen.fallo.buscar');

        Route::post('/dictamen-fallo/generar', [DictFalloController::class, 'generar'])
            ->name('dictamen.fallo.generar');

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
        Route::get(
            '/procedimientos/reporte/excel',
            [UserController::class, 'descargarReporteProcedimientos']
        )->name('procedimientos.reporte');
        
    });

    Route::prefix('personas')
    ->name('personas.')
    ->group(function () {
        Route::get('/', [PersonaController::class, 'index'])
            ->name('index');

        Route::get('/crear', [PersonaController::class, 'create'])
            ->name('create');

        Route::post('/', [PersonaController::class, 'store'])
            ->name('store');

        Route::get(
            '/plantilla-carga-masiva',
            [PersonaController::class, 'descargarPlantillaMasiva']
        )->name('plantilla-masiva');

        Route::post(
            '/carga-masiva',
            [PersonaController::class, 'importarMasivo']
        )->name('importar-masivo');

        Route::get('/{id}/editar', [PersonaController::class, 'edit'])
            ->name('edit');

        Route::put('/{id}', [PersonaController::class, 'update'])
            ->name('update');

        Route::delete('/{id}', [PersonaController::class, 'destroy'])
            ->name('destroy');
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