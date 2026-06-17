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
        $personas = Persona::whereHas('area', function ($query) {
                $query->where('nombre', 'Coordinación de Adquisiciones y Servicios');
            })
            ->orderBy('nombre')
            ->get();

        return view('comprador.revision.revision', compact('personas'));
    }

    public function buscarProcedimiento($valor)
    {
        $valor = trim($valor);

        $procedimiento = Procedimiento::with('tipo')
            ->where('num_procedimiento', 'LIKE', '%-N-' . $valor . '-%')
            ->first();

        if (!$procedimiento) {
            return response()->json(null);
        }

        return response()->json([
            'num_procedimiento'    => $procedimiento->num_procedimiento,
            'nombre_procedimiento' => $procedimiento->nombre_procedimiento,
            'tipo'                 => optional($procedimiento->tipo)->nombre_tipo ?? '',
        ]);
    }

    public function generar(Request $request)
    {
        $request->validate([
            'numero_referencia'    => 'required',
            'fecha_oficio'         => 'required|date',
            'fecha_publicacion'    => 'nullable|date',
            'numero_busqueda'      => 'required',
            'num_procedimiento'    => 'required',
            'nombre_procedimiento' => 'required',
            'tipo_procedimiento'   => 'nullable',
            'reviso_id'            => 'nullable|exists:personas,id',
            'archivo_word'         => 'required|file|mimes:docx',
        ]);

        $procedimiento = Procedimiento::where(
            'num_procedimiento',
            'LIKE',
            '%-N-' . $request->numero_busqueda . '-%'
        )->first();

        if (!$procedimiento) {
            return back()->with('error', 'No se encontró el procedimiento.');
        }

        $textoReviso = '';

        if ($request->filled('reviso_id')) {
            $persona = Persona::find($request->reviso_id);

            if ($persona) {
                $textoReviso = $persona->nombre . '.- ' . $persona->cargo . ':';
            }
        }

        $user = Auth::user();
        $textoElaboro = $user ? $user->name : '';

        $fechaOficio = Carbon::parse($request->fecha_oficio)
            ->locale('es')
            ->translatedFormat('d \d\e F \d\e Y');

        $fechaPublicacion = '';

        if ($request->filled('fecha_publicacion')) {
            $fechaPublicacion = Carbon::parse($request->fecha_publicacion)
                ->locale('es')
                ->translatedFormat('d \d\e F') . ' del presente.';
        }

        if (!$request->hasFile('archivo_word')) {
            return back()->with('error', 'No se subió ningún archivo Word.');
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
        $templateProcessor->setValue('fecha_publicacion', $fechaPublicacion);

        $templateProcessor->setValue('num_procedimiento', $request->num_procedimiento);
        $templateProcessor->setValue('nombre_procedimiento', $request->nombre_procedimiento);
        $templateProcessor->setValue('tipo_procedimiento', $request->tipo_procedimiento);

        $templateProcessor->setValue('reviso', $textoReviso);
        $templateProcessor->setValue('elaboro', $textoElaboro);

        $outputDir = storage_path('app/public/documentos');

        if (!file_exists($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        $outputName = 'revision_' . time() . '.docx';
        $outputPath = $outputDir . '/' . $outputName;

        $templateProcessor->saveAs($outputPath);

        return response()->download($outputPath)->deleteFileAfterSend(true);
    }
}