<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Procedimiento;

class ProcedimientoController extends Controller
{
    // Mostrar formulario
    public function convocatoria()
    {
        return view('comprador.convo.convocatoria');
    }

    // Guardar datos
    public function store(Request $request)
    {
        Procedimiento::create([
            'nombre_procedimiento' => $request->nombre_procedimiento,
            'num_procedimiento' => $request->num_procedimiento,
            'fecha_publicacion' => $request->fecha_publicacion,
            'fecha_vm' => $request->fecha_vm,
            'fecha_ac' => $request->fecha_acl,
            'hora_ac' => $request->hora_acl,
            'fecha_apertura' => $request->fecha_apertura,
            'hora_apertura' => $request->hora_apertura,
            'fecha_fallo' => $request->fecha_fallo,
            'hora_fallo' => $request->hora_fallo,
        ]);

        return back()->with('success', 'Guardado correctamente');
    }
}