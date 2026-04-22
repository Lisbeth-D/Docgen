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

        Route::get('/procedimientos/{id}', [ProcedimientoController::class, 'show'])->name('procedimientos.show');
        Route::get('/procedimientos/{id}/descargar', [ProcedimientoController::class, 'descargar'])->name('procedimientos.descargar');

        Route::get('/revision', [RevisionController::class, 'index'])->name('revision.form');
        Route::post('/revision', [RevisionController::class, 'generar'])->name('revision.generar');

        Route::get('/publicacion', [PublicacionController::class, 'index'])->name('publicacion.index');
        Route::post('/publicacion/generar', [PublicacionController::class, 'generar'])->name('publicacion.generar');

        Route::get('/adjudicacion', [AdjudicacionController::class, 'index'])->name('adjudicacion.index');
        Route::post('/adjudicacion/generar', [AdjudicacionController::class, 'generar'])->name('adjudicacion.generar');


    });

    /*
    |--------------------------------------------------------------------------
    | RUTAS ADMIN
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth','role:admin'])->group(function () {

    Route::get('/admin/dashboard',
    [DashboardController::class,'adminDashboard']
    )->name('admin.dashboard');

    Route::get('/usuarios', [UserController::class,'index']);
    Route::get('/usuarios/crear', [UserController::class,'create']);
    Route::post('/usuarios', [UserController::class,'store']);
    Route::delete('/usuarios/{id}', [UserController::class,'destroy']);
    Route::put('/usuarios/{id}', [UserController::class,'update']);
    Route::get('/usuarios/{id}/editar',[UserController::class,'edit']);

    Route::post('/usuarios/reset/{id}',
    [UserController::class,'resetPassword']);

    Route::post('/usuarios/toggle/{id}',
    [UserController::class,'toggleActivo']);

    Route::get('/admin/reportes/actividad', [UserController::class, 'actividad'])
    ->middleware(['auth','role:admin']);

    Route::get('/personas', [PersonaController::class, 'index']);
    Route::get('/personas/crear', [PersonaController::class, 'create'])
    ->name('personas.create');
    Route::get('/personas/{id}/editar', [PersonaController::class, 'edit']);
    Route::put('/personas/{id}', [PersonaController::class, 'update']);
    Route::post('/personas', [PersonaController::class, 'store']);
    Route::delete('/personas/{id}', [PersonaController::class, 'destroy']);


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

require __DIR__.'/auth.php';