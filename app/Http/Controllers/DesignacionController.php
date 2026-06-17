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
        $personas = Persona::whereHas('area', function ($query) {
                $query->where('nombre', 'Coordinación de Adquisiciones y Servicios');
            })
            ->orderBy('nombre')
            ->get();

        return view('comprador.Designacion.Designacion', compact('personas'));
    }

    public function buscarProcedimiento($valor)
    {
        $valor = trim($valor);

        $proc = Procedimiento::where(
            'num_procedimiento',
            'LIKE',
            '%-N-' . $valor . '-%'
        )->first();

        if (!$proc) {
            return response()->json(null);
        }

        return response()->json([
            'num_procedimiento'    => $proc->num_procedimiento,
            'nombre_procedimiento' => $proc->nombre_procedimiento,

            'fecha_vm'       => $proc->fecha_vm ? Carbon::parse($proc->fecha_vm)->format('Y-m-d') : '',
            'fecha_ac'       => $proc->fecha_ac ? Carbon::parse($proc->fecha_ac)->format('Y-m-d') : '',
            'fecha_apertura' => $proc->fecha_apertura ? Carbon::parse($proc->fecha_apertura)->format('Y-m-d') : '',
            'fecha_fallo'    => $proc->fecha_fallo ? Carbon::parse($proc->fecha_fallo)->format('Y-m-d') : '',

            'hora_vm'       => $proc->hora_vm ? Carbon::parse($proc->hora_vm)->format('H:i') : '',
            'hora_ac'       => $proc->hora_ac ? Carbon::parse($proc->hora_ac)->format('H:i') : '',
            'hora_apertura' => $proc->hora_apertura ? Carbon::parse($proc->hora_apertura)->format('H:i') : '',
            'hora_fallo'    => $proc->hora_fallo ? Carbon::parse($proc->hora_fallo)->format('H:i') : '',
        ]);
    }

    public function generar(Request $request)
    {
        $request->validate([
            'numero_referencia'     => 'required',
            'fecha_oficio'          => 'required|date',
            'numero_busqueda'       => 'required',

            'num_procedimiento'     => 'required',
            'nombre_procedimiento'  => 'required',

            'fecha_vm'              => 'nullable',
            'hora_vm'               => 'nullable',
            'fecha_ac'              => 'nullable|date',
            'hora_ac'               => 'nullable',
            'fecha_apertura'        => 'nullable|date',
            'hora_apertura'         => 'nullable',
            'fecha_fallo'           => 'nullable|date',
            'hora_fallo'            => 'nullable',

            'reviso_id'             => 'nullable|exists:personas,id',
            'archivo_word'          => 'required|file|mimes:docx'
        ]);

        $procedimiento = Procedimiento::where(
            'num_procedimiento',
            'LIKE',
            '%-N-' . $request->numero_busqueda . '-%'
        )->first();

        if (!$procedimiento) {
            return back()->with('error', 'No se encontró el procedimiento');
        }

        $textoReviso = '';

        if ($request->filled('reviso_id')) {
            $persona = Persona::find($request->reviso_id);

            if ($persona) {
                $textoReviso = $persona->nombre . '.- ' . $persona->cargo . ':';
            }
        }

        $textoElaboro = Auth::user()->name ?? '';

        $fechaOficio = Carbon::parse($request->fecha_oficio)
            ->locale('es')
            ->translatedFormat('d \d\e F \d\e Y');

        $fecha_vm = $request->filled('fecha_vm')
            ? ucfirst(Carbon::parse($request->fecha_vm)->locale('es')->translatedFormat('d-F-Y'))
            : 'N/A';

        $fecha_ac = $request->filled('fecha_ac')
            ? ucfirst(Carbon::parse($request->fecha_ac)->locale('es')->translatedFormat('d-F-Y'))
            : 'N/A';

        $fecha_apertura = $request->filled('fecha_apertura')
            ? ucfirst(Carbon::parse($request->fecha_apertura)->locale('es')->translatedFormat('d-F-Y'))
            : 'N/A';

        $fecha_fallo = $request->filled('fecha_fallo')
            ? ucfirst(Carbon::parse($request->fecha_fallo)->locale('es')->translatedFormat('d-F-Y'))
            : 'N/A';

        $hora_vm = $request->filled('hora_vm')
            ? Carbon::parse($request->hora_vm)->format('H:i') . ' horas'
            : 'N/A';

        $hora_ac = $request->filled('hora_ac')
            ? Carbon::parse($request->hora_ac)->format('H:i') . ' horas'
            : 'N/A';

        $hora_apertura = $request->filled('hora_apertura')
            ? Carbon::parse($request->hora_apertura)->format('H:i') . ' horas'
            : 'N/A';

        $hora_fallo = $request->filled('hora_fallo')
            ? Carbon::parse($request->hora_fallo)->format('H:i') . ' horas'
            : 'N/A';

        if (!$request->hasFile('archivo_word')) {
            return back()->with('error', 'No se subió archivo');
        }

        $file = $request->file('archivo_word');
        $filename = time() . '_' . $file->getClientOriginalName();

        $templateDir = storage_path('app/plantillas');

        if (!file_exists($templateDir)) {
            mkdir($templateDir, 0777, true);
        }

        $file->move($templateDir, $filename);

        $templatePath = $templateDir . '/' . $filename;

        $templateProcessor = new TemplateProcessor($templatePath);

        $templateProcessor->setValue('numero_referencia', $request->numero_referencia);
        $templateProcessor->setValue('fecha_oficio', $fechaOficio);

        $templateProcessor->setValue('num_procedimiento', $request->num_procedimiento);
        $templateProcessor->setValue('nombre_procedimiento', $request->nombre_procedimiento);

        $templateProcessor->setValue('fecha_vm', $fecha_vm);
        $templateProcessor->setValue('hora_vm', $hora_vm);

        $templateProcessor->setValue('fecha_ac', $fecha_ac);
        $templateProcessor->setValue('hora_ac', $hora_ac);

        $templateProcessor->setValue('fecha_apertura', $fecha_apertura);
        $templateProcessor->setValue('hora_apertura', $hora_apertura);

        $templateProcessor->setValue('fecha_fallo', $fecha_fallo);
        $templateProcessor->setValue('hora_fallo', $hora_fallo);

        $templateProcessor->setValue('reviso', $textoReviso);
        $templateProcessor->setValue('elaboro', $textoElaboro);

        $outputDir = storage_path('app/public/documentos');

        if (!file_exists($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        $outputName = 'designacion_' . time() . '.docx';
        $outputPath = $outputDir . '/' . $outputName;

        $templateProcessor->saveAs($outputPath);

        return response()->download($outputPath)->deleteFileAfterSend(true);
    }
}