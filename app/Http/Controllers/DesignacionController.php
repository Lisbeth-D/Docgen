<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Procedimiento;
use Carbon\Carbon;
use App\Models\Persona;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpWord\TemplateProcessor;

class DesignacionController extends Controller
{
    public function index()
    {
        $personas = Persona::orderBy('nombre')->get();
        return view('comprador.Designacion.Designacion', compact('personas'));
    }

    public function buscarProcedimiento($valor)
    {
        $proc = Procedimiento::where('num_procedimiento', 'LIKE', "%{$valor}%")->first();

        if (!$proc) {
            return response()->json(null);
        }

        return response()->json([
            'num_procedimiento' => $proc->num_procedimiento,
            'nombre_procedimiento' => $proc->nombre_procedimiento,

            // FECHAS
            'fecha_vm' => $proc->fecha_vm ? Carbon::parse($proc->fecha_vm)->format('Y-m-d') : null,
            'fecha_ac' => $proc->fecha_ac ? Carbon::parse($proc->fecha_ac)->format('Y-m-d') : null,
            'fecha_apertura' => $proc->fecha_apertura ? Carbon::parse($proc->fecha_apertura)->format('Y-m-d') : null,
            'fecha_fallo' => $proc->fecha_fallo ? Carbon::parse($proc->fecha_fallo)->format('Y-m-d') : null,

            // HORAS
            'hora_vm' => $proc->hora_vm ? Carbon::parse($proc->hora_vm)->format('H:i') : null,
            'hora_ac' => $proc->hora_ac ? Carbon::parse($proc->hora_ac)->format('H:i') : null,
            'hora_apertura' => $proc->hora_apertura ? Carbon::parse($proc->hora_apertura)->format('H:i') : null,
            'hora_fallo' => $proc->hora_fallo ? Carbon::parse($proc->hora_fallo)->format('H:i') : null,
        ]);
    }

    public function generar(Request $request)
    {
        $request->validate([
            'numero_referencia' => 'required',
            'fecha_oficio'      => 'required|date',
            'numero_busqueda'   => 'required',
            'reviso_id'         => 'nullable|exists:personas,id',
            'archivo_word'      => 'required|file|mimes:docx'
        ]);

        // 🔍 BUSCAR PROCEDIMIENTO
        $procedimiento = Procedimiento::where('num_procedimiento', 'like', '%' . $request->numero_busqueda . '%')->first();

        if (!$procedimiento) {
            return back()->with('error', 'No se encontró el procedimiento');
        }

        // 👤 PERSONA
        $textoReviso = '';
        if ($request->reviso_id) {
            $persona = Persona::find($request->reviso_id);
            if ($persona) {
                $textoReviso = $persona->nombre . '.- ' . $persona->cargo . ':';
            }
        }

        // 👤 USUARIO
        $textoElaboro = Auth::user()->name ?? '';

        // 📅 FECHA OFICIO
        $fechaOficio = Carbon::parse($request->fecha_oficio)
            ->locale('es')
            ->translatedFormat('d \d\e F \d\e Y');

        // =========================
        // 📅 FECHAS FORMATEADAS
        // =========================
        $fecha_vm = $procedimiento->fecha_vm 
            ? ucfirst(Carbon::parse($procedimiento->fecha_vm)->locale('es')->translatedFormat('d-F-Y'))
            : 'N/A';

        $fecha_ac = $procedimiento->fecha_ac 
            ? ucfirst(Carbon::parse($procedimiento->fecha_ac)->locale('es')->translatedFormat('d-F-Y'))
            : 'N/A';

        $fecha_apertura = $procedimiento->fecha_apertura 
            ? ucfirst(Carbon::parse($procedimiento->fecha_apertura)->locale('es')->translatedFormat('d-F-Y'))
            : 'N/A';

        $fecha_fallo = $procedimiento->fecha_fallo 
            ? ucfirst(Carbon::parse($procedimiento->fecha_fallo)->locale('es')->translatedFormat('d-F-Y'))
            : 'N/A';


        // =========================
        // 🕐 HORAS FORMATEADAS
        // =========================
        $hora_vm = $procedimiento->hora_vm 
            ? Carbon::parse($procedimiento->hora_vm)->format('H:i') . ' horas'
            : 'N/A';

        $hora_ac = $procedimiento->hora_ac 
            ? Carbon::parse($procedimiento->hora_ac)->format('H:i') . ' horas'
            : 'N/A';

        $hora_apertura = $procedimiento->hora_apertura 
            ? Carbon::parse($procedimiento->hora_apertura)->format('H:i') . ' horas'
            : 'N/A';

        $hora_fallo = $procedimiento->hora_fallo 
            ? Carbon::parse($procedimiento->hora_fallo)->format('H:i') . ' horas'
            : 'N/A';

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
            // FORMULARIO
            // =========================
            $templateProcessor->setValue('numero_referencia', $request->numero_referencia);
            $templateProcessor->setValue('fecha_oficio', $fechaOficio);

            // =========================
            // BD
            // =========================
            $templateProcessor->setValue('num_procedimiento', $procedimiento->num_procedimiento);
            $templateProcessor->setValue('nombre_procedimiento', $procedimiento->nombre_procedimiento);

            // 🔥 AQUÍ ESTABA TU ERROR (faltaban)
            $templateProcessor->setValue('fecha_vm', $fecha_vm);
            $templateProcessor->setValue('hora_vm', $hora_vm);

            $templateProcessor->setValue('fecha_ac', $fecha_ac);
            $templateProcessor->setValue('hora_ac', $hora_ac);

            $templateProcessor->setValue('fecha_apertura', $fecha_apertura);
            $templateProcessor->setValue('hora_apertura', $hora_apertura);

            $templateProcessor->setValue('fecha_fallo', $fecha_fallo);
            $templateProcessor->setValue('hora_fallo', $hora_fallo);

            // =========================
            // PERSONAS
            // =========================
            $templateProcessor->setValue('reviso', $textoReviso);
            $templateProcessor->setValue('elaboro', $textoElaboro);

            // =========================
            // GUARDAR
            // =========================
            $outputDir = storage_path('app/public/documentos');

            if (!file_exists($outputDir)) {
                mkdir($outputDir, 0777, true);
            }

            $outputName = 'designacion_' . time() . '.docx';
            $outputPath = $outputDir . '/' . $outputName;

            $templateProcessor->saveAs($outputPath);

            return response()->download($outputPath);
        }

        return back()->with('error', 'No se subió archivo');
    }
}