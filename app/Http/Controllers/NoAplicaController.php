<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Procedimiento;
use App\Models\Persona;
use PhpOffice\PhpWord\TemplateProcessor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class NoAplicaController extends Controller
{
    public function index()
    {
        $revisores = Persona::where('area_id', 4)
            ->orderBy('nombre')
            ->get();

        return view('comprador.Aclaracion.noaplica', compact('revisores'));
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
            'fecha_apertura'       => $proc->fecha_apertura
                ? Carbon::parse($proc->fecha_apertura)->format('Y-m-d')
                : '',
        ]);
    }

    public function generar(Request $request)
    {
        $request->validate([
            'numero_busqueda'      => 'required',
            'num_procedimiento'    => 'nullable',
            'nombre_procedimiento' => 'nullable',
            'fecha_apertura'       => 'nullable|date',
            'correo_comprador'     => 'nullable|string|max:255',
            'reviso_id'            => 'required|exists:personas,id',
            'elaboro'              => 'required|string|max:255',
            'archivo_word'         => 'required|file|mimes:docx',
        ]);

        $proc = Procedimiento::where(
            'num_procedimiento',
            'LIKE',
            '%-N-' . $request->numero_busqueda . '-%'
        )->first();

        if (!$proc) {
            return back()->with('error', 'No se encontró el procedimiento');
        }

        $textoReviso = '';

        if ($request->filled('reviso_id')) {
            $persona = Persona::find($request->reviso_id);

            if ($persona) {
                $textoReviso = $persona->nombre . '.- ' . $persona->cargo . ':';
            }
        }

        $template = new TemplateProcessor(
            $request->file('archivo_word')->getRealPath()
        );

        $template->setValue('num_procedimiento', $proc->num_procedimiento ?? '');
        $template->setValue('nombre_procedimiento', $proc->nombre_procedimiento ?? '');

        $template->setValue(
            'fecha_apertura',
            $request->fecha_apertura
                ? Carbon::parse($request->fecha_apertura)->format('d/m/Y')
                : (
                    $proc->fecha_apertura
                        ? Carbon::parse($proc->fecha_apertura)->format('d/m/Y')
                        : ''
                )
        );

        $template->setValue(
            'correo_comprador',
            $request->correo_comprador ?: (Auth::user()->email ?? '')
        );

        $template->setValue('reviso', $textoReviso);
        $template->setValue('elaboro', $request->elaboro ?? '');

        $carpetaTemp = storage_path('app/temp');

        if (!file_exists($carpetaTemp)) {
            mkdir($carpetaTemp, 0777, true);
        }

        $nombreArchivo = 'No_Aplica_Junta_' . $proc->num_procedimiento . '.docx';
        $rutaSalida = $carpetaTemp . '/' . $nombreArchivo;

        $template->saveAs($rutaSalida);

        return response()->download($rutaSalida)->deleteFileAfterSend(true);
    }
}