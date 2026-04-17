<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Procedimiento;
use App\Models\Persona;
use App\Models\TipoProcedimiento;
use PhpOffice\PhpWord\TemplateProcessor;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ProcedimientoController extends Controller
{
    public function convocatoria()
    {
        $personas = Persona::orderBy('nombre')->get();
        $tipos = TipoProcedimiento::all();

        return view('comprador.convo.convocatoria', compact('personas', 'tipos'));
    }

    private function formatearMonto($valor)
    {
        if (!$valor) return '0.00';

        $valor = trim($valor);

        if (preg_match('/,\d{1,2}$/', $valor)) {
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
        } else {
            $valor = str_replace(',', '', $valor);
        }

        return number_format(floatval($valor), 2, '.', ',');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre_procedimiento' => 'required',
            'num_procedimiento'    => 'required',
            'archivo_word'         => 'required|file|mimes:docx'
        ]);

        $persona = Persona::find($request->resp_tecnico);

        // =========================
        // GUARDAR EN BD
        // =========================
        $procedimiento = Procedimiento::create([
            'id_tipo_procedimiento' => $request->id_tipo_procedimiento,
            'nombre_procedimiento'  => $request->nombre_procedimiento,
            'num_procedimiento'     => $request->num_procedimiento,
            'fecha_publicacion'     => $request->fecha_publicacion,

            'fecha_vm'              => $request->fecha_vm,
            'hora_vm'               => $request->hora_vm,

            'fecha_ac'              => $request->fecha_acl,
            'hora_ac'               => $request->hora_acl,

            'fecha_apertura'        => $request->fecha_apertura,
            'hora_apertura'         => $request->hora_apertura,

            'fecha_fallo'           => $request->fecha_fallo,
            'hora_fallo'            => $request->hora_fallo,

            'fecha_inicio_contrato' => $request->fecha_inicio_contrato,
            'fecha_fin_contrato'    => $request->fecha_fin_contrato,

            'user_id'               => Auth::id(),
            'id_persona'            => $persona ? $persona->id : null,
        ]);

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

            $templateProcessor = new TemplateProcessor($templateDir . '/' . $filename);

            // HORAS
            $horaVM       = $request->hora_vm ? strtoupper($request->hora_vm . ' HORAS') : '';
            $horaACL      = $request->hora_acl ? strtoupper($request->hora_acl . ' HORAS') : '';
            $horaApertura = $request->hora_apertura ? strtoupper($request->hora_apertura . ' HORAS') : '';
            $horaFallo    = $request->hora_fallo ? strtoupper($request->hora_fallo . ' HORAS') : '';

            // MESES
            $meses = [
                1=>'ENERO',2=>'FEBRERO',3=>'MARZO',4=>'ABRIL',
                5=>'MAYO',6=>'JUNIO',7=>'JULIO',8=>'AGOSTO',
                9=>'SEPTIEMBRE',10=>'OCTUBRE',11=>'NOVIEMBRE',12=>'DICIEMBRE'
            ];

            // VM
            if ($request->aplica_vm == 'SI' && $request->fecha_vm && $request->hora_vm) {
                $f = Carbon::parse($request->fecha_vm);
                $textoVM = "{$f->day} de {$meses[$f->month]} de {$f->year} a las $horaVM";
            } else {
                $textoVM = "NO APLICA";
            }

            // ACL
            if ($request->aplica_acl == 'SI' && $request->fecha_acl && $request->hora_acl) {
                $f = Carbon::parse($request->fecha_acl);

                $aclTexto = "{$f->day} de {$meses[$f->month]} de {$f->year}, a las $horaACL";
                $aclTabla = "{$f->day}-{$meses[$f->month]}-{$f->year}";
            } else {
                $aclTexto = "NO APLICA";
                $aclTabla = "NO APLICA";
            }

            // APERTURA
            $aperturaTexto = '';
            $aperturaTabla = '';

            if ($request->fecha_apertura) {
                $fA = Carbon::parse($request->fecha_apertura);

                $aperturaTexto = "{$fA->day} de {$meses[$fA->month]} de {$fA->year}, a las $horaApertura";
                $aperturaTabla = "{$fA->day}-{$meses[$fA->month]}-{$fA->year}";
            }

            // FALLO
            $falloTexto = '';
            $falloTabla = '';

            if ($request->fecha_fallo) {
                $fF = Carbon::parse($request->fecha_fallo);

                $falloTexto = "{$fF->day} de {$meses[$fF->month]} de {$fF->year}, a las $horaFallo";
                $falloTabla = "{$fF->day}-{$meses[$fF->month]}-{$fF->year}";
            }

            // VIGENCIA
            $vigenciaTexto = '';

            if ($request->fecha_inicio_contrato && $request->fecha_fin_contrato) {

                $inicio = Carbon::parse($request->fecha_inicio_contrato);
                $fin    = Carbon::parse($request->fecha_fin_contrato);

                $vigenciaTexto = "{$inicio->day} de {$meses[$inicio->month]} del {$inicio->year} y hasta el {$fin->day} de {$meses[$fin->month]} del {$fin->year}";
            }

            // REEMPLAZOS
            $templateProcessor->setValue('nombre_procedimiento', $request->nombre_procedimiento);
            $templateProcessor->setValue('num_procedimiento', $request->num_procedimiento);
            $templateProcessor->setValue('fecha_publicacion', $request->fecha_publicacion);

            $templateProcessor->setValue('fecha_vm', $textoVM);

            $templateProcessor->setValue('acl_texto', $aclTexto);
            $templateProcessor->setValue('acl_tabla', $aclTabla);

            $templateProcessor->setValue('apertura_texto', $aperturaTexto);
            $templateProcessor->setValue('apertura_tabla', $aperturaTabla);

            $templateProcessor->setValue('fallo_texto', $falloTexto);
            $templateProcessor->setValue('fallo_tabla', $falloTabla);

            $templateProcessor->setValue('hora_apertura', $horaApertura);
            $templateProcessor->setValue('hora_fallo', $horaFallo);

            $templateProcessor->setValue('resp_tecnico', $persona ? $persona->nombre : '');
            $templateProcessor->setValue('cargo_tecnico', $persona ? $persona->cargo : '');

            $templateProcessor->setValue('monto_maximo', $this->formatearMonto($request->monto_maximo));
            $templateProcessor->setValue('monto_minimo', $this->formatearMonto($request->monto_minimo));

            $templateProcessor->setValue('num_partida', $request->num_partida);
            $templateProcessor->setValue('partida_nombre', $request->partida_nombre);
            $templateProcessor->setValue('num_requisicion', $request->num_requisicion);

            $templateProcessor->setValue('vigencia_contrato', $vigenciaTexto);

            // GUARDAR DOC
            $outputDir = storage_path('app/public/documentos');

            if (!file_exists($outputDir)) {
                mkdir($outputDir, 0777, true);
            }

            $outputName = 'procedimiento_' . $procedimiento->id . '.docx';
            $outputPath = $outputDir . '/' . $outputName;

            $templateProcessor->saveAs($outputPath);

            $procedimiento->update([
                'ruta_documento' => 'documentos/' . $outputName
            ]);

            return response()->download($outputPath);
        }

        return back()->with('error', 'No se subió ningún archivo');
    }

    public function show($id)
    {
        $procedimiento = Procedimiento::findOrFail($id);
        return view('comprador.convo.resultado', compact('procedimiento'));
    }

    public function descargar($id)
    {
        $procedimiento = Procedimiento::findOrFail($id);
        return response()->download(storage_path('app/public/' . $procedimiento->ruta_documento));
    }
}