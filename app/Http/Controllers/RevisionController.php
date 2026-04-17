<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Procedimiento;
use App\Models\Persona;
use PhpOffice\PhpWord\TemplateProcessor;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class RevisionController extends Controller
{
    public function index()
    {
        $personas = Persona::orderBy('nombre')->get();

        return view('comprador.revision.formulario', compact('personas'));
    }

    public function generar(Request $request)
    {
        $request->validate([
            'numero_referencia' => 'required',
            'fecha_oficio'      => 'required',
            'numero_busqueda'   => 'required',
            'archivo_word'      => 'required|file|mimes:docx'
        ]);

        // 🔍 BUSCAR PROCEDIMIENTO POR EL NÚMERO (ej: 25)
        $procedimiento = Procedimiento::where('num_procedimiento', 'like', '%' . $request->numero_busqueda . '%')->first();

        if (!$procedimiento) {
            return back()->with('error', 'No se encontró el procedimiento');
        }

        // 👤 PERSONA (REVISÓ)
        $persona = Persona::find($request->reviso);

        // =========================
        // WORD
        // =========================
        if ($request->hasFile('archivo_word')) {

            $file = $request->file('archivo_word');
            $filename = time() . '_' . $file->getClientOriginalName();

            $templateDir = storage_path('app/plantillas');

            if (!file_exists($templateDir)) {
                mkdir($templateDir, 0777, true);
            }

            $file->move($templateDir, $filename);

            $templatePath = $templateDir . '/' . $filename;

            $templateProcessor = new TemplateProcessor($templatePath);

            // =========================
            // DATOS DEL FORMULARIO
            // =========================
            $templateProcessor->setValue('numero_referencia', $request->numero_referencia);
            $templateProcessor->setValue('fecha_oficio', $request->fecha_oficio);

            // =========================
            // DATOS DESDE BD (AUTOMÁTICO 🔥)
            // =========================
            $templateProcessor->setValue('num_procedimiento', $procedimiento->num_procedimiento);
            $templateProcessor->setValue('nombre_procedimiento', $procedimiento->nombre_procedimiento);

            // =========================
            // TIPO (si ya lo tienes relacionado)
            // =========================
            $tipo = optional($procedimiento->tipo)->nombre_tipo ?? '';
            $templateProcessor->setValue('tipo_procedimiento', $tipo);

            // =========================
            // PERSONAS
            // =========================
            $templateProcessor->setValue('reviso', $persona ? $persona->nombre : '');
            $templateProcessor->setValue('elaboro', Auth::user()->name);

            // =========================
            // GUARDAR Y DESCARGAR
            // =========================
            $outputDir = storage_path('app/public/documentos');

            if (!file_exists($outputDir)) {
                mkdir($outputDir, 0777, true);
            }

            $outputName = 'revision_' . time() . '.docx';
            $outputPath = $outputDir . '/' . $outputName;

            $templateProcessor->saveAs($outputPath);

            return response()->download($outputPath);
        }

        return back()->with('error', 'No se subió archivo');
    }
}