<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Procedimiento;
use App\Models\Persona;
use PhpOffice\PhpWord\TemplateProcessor;

class ProcedimientoController extends Controller
{
    public function convocatoria()
    {
        $personas = Persona::orderBy('nombre')->get();
        return view('comprador.convo.convocatoria', compact('personas'));
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

        $numero = floatval($valor);

        return number_format($numero, 2, '.', ',');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre_procedimiento' => 'required',
            'num_procedimiento'    => 'required',
            'archivo_word'         => 'required|file|mimes:docx'
        ]);

        $procedimiento = Procedimiento::create([
            'nombre_procedimiento' => $request->nombre_procedimiento,
            'num_procedimiento'    => $request->num_procedimiento,
            'fecha_publicacion'    => $request->fecha_publicacion,
            'fecha_vm'             => $request->fecha_vm,
            'fecha_ac'             => $request->fecha_acl,
            'hora_ac'              => $request->hora_acl,
            'fecha_apertura'       => $request->fecha_apertura,
            'hora_apertura'        => $request->hora_apertura,
            'fecha_fallo'          => $request->fecha_fallo,
            'hora_fallo'           => $request->hora_fallo,
        ]);

        if ($request->hasFile('archivo_word')) {

            $file = $request->file('archivo_word');
            $filename = time() . '_' . $file->getClientOriginalName();

            $templateDir = storage_path('app/plantillas');

            if (!file_exists($templateDir)) {
                mkdir($templateDir, 0777, true);
            }

            $file->move($templateDir, $filename);

            $templatePath = $templateDir . DIRECTORY_SEPARATOR . $filename;

            $templateProcessor = new TemplateProcessor($templatePath);

            // =========================
            // FORMATEO HORAS (🔥 BONUS)
            // =========================
            $horaVM = $request->hora_vm ? strtoupper($request->hora_vm . ' HORAS') : '';
            $horaACL = $request->hora_acl ? strtoupper($request->hora_acl . ' HORAS') : '';
            $horaApertura = $request->hora_apertura ? strtoupper($request->hora_apertura . ' HORAS') : '';
            $horaFallo = $request->hora_fallo ? strtoupper($request->hora_fallo . ' HORAS') : '';

            // =========================
            // REEMPLAZOS PRINCIPALES
            // =========================
            $templateProcessor->setValue('nombre_procedimiento', $request->nombre_procedimiento);
            $templateProcessor->setValue('num_procedimiento', $request->num_procedimiento);
            $templateProcessor->setValue('fecha_publicacion', $request->fecha_publicacion);

            // =========================
            // V/M
            // =========================
            if ($request->aplica_vm == 'SI' && $request->fecha_vm && $request->hora_vm) {

                $meses = [
                    1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO',
                    4 => 'ABRIL', 5 => 'MAYO', 6 => 'JUNIO',
                    7 => 'JULIO', 8 => 'AGOSTO', 9 => 'SEPTIEMBRE',
                    10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE'
                ];

                $f = \Carbon\Carbon::parse($request->fecha_vm);

                $textoVM = "EL {$f->day} DE {$meses[$f->month]} DE {$f->year} A LAS $horaVM";

            } else {
                $textoVM = "NO APLICA";
            }

            $templateProcessor->setValue('fecha_vm', $textoVM);

            // =========================
            // ACL
            // =========================
            if ($request->aplica_acl == 'SI' && $request->fecha_acl && $request->hora_acl) {

                $meses = [
                    1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO',
                    4 => 'ABRIL', 5 => 'MAYO', 6 => 'JUNIO',
                    7 => 'JULIO', 8 => 'AGOSTO', 9 => 'SEPTIEMBRE',
                    10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE'
                ];

                $f = \Carbon\Carbon::parse($request->fecha_acl);

                $aclTexto = "EL {$f->day} DE {$meses[$f->month]} DE {$f->year}, A LAS $horaACL";
                $aclTabla = "{$f->day}-{$meses[$f->month]}-{$f->year}";

            } else {

                $aclTexto = "NO APLICA\nDE ACUERDO CON EL ARTÍCULO 56, FRACCIÓN V, SE ESTABLECE QUE A LAS DEMÁS DISPOSICIONES DE ESTA LEY QUE RESULTEN APLICABLES A LA LICITACIÓN PÚBLICA, SIENDO OPTATIVO PARA LA CONVOCANTE LA REALIZACIÓN DE LA JUNTA DE ACLARACIONES.";
                $aclTabla = "NO APLICA";
            }

            // =========================
            // APERTURA
            // =========================
            $fA = \Carbon\Carbon::parse($request->fecha_apertura);

            $meses = [
                1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO',
                4 => 'ABRIL', 5 => 'MAYO', 6 => 'JUNIO',
                7 => 'JULIO', 8 => 'AGOSTO', 9 => 'SEPTIEMBRE',
                10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE'
            ];

            $aperturaTexto = "EL DÍA {$fA->day} DE {$meses[$fA->month]} DE {$fA->year}, A LAS $horaApertura";
            $aperturaTabla = "{$fA->day}-{$meses[$fA->month]}-{$fA->year}";

            // =========================
            // FALLO
            // =========================
            $fF = \Carbon\Carbon::parse($request->fecha_fallo);

            $falloTexto = "EL DÍA {$fF->day} DE {$meses[$fF->month]} DE {$fF->year}, A LAS $horaFallo";
            $falloTabla = "{$fF->day}-{$meses[$fF->month]}-{$fF->year}";

            // =========================
            // ENVIAR A WORD
            // =========================
            $templateProcessor->setValue('acl_texto', $aclTexto);
            $templateProcessor->setValue('acl_tabla', $aclTabla);

            $templateProcessor->setValue('apertura_texto', $aperturaTexto);
            $templateProcessor->setValue('apertura_tabla', $aperturaTabla);
            $templateProcessor->setValue('hora_apertura', $horaApertura);

            $templateProcessor->setValue('fallo_texto', $falloTexto);
            $templateProcessor->setValue('fallo_tabla', $falloTabla);
            $templateProcessor->setValue('hora_fallo', $horaFallo);

            // =========================
            // PERSONAS
            // =========================
            $templateProcessor->setValue('resp_tecnico', $request->resp_tecnico);
            $templateProcessor->setValue('cargo_tecnico', $request->cargo_tecnico);

            // =========================
            // MONTOS
            // =========================
            $templateProcessor->setValue('monto_maximo', $this->formatearMonto($request->monto_maximo));
            $templateProcessor->setValue('monto_minimo', $this->formatearMonto($request->monto_minimo));

            // =========================
            // OTROS
            // =========================
            $templateProcessor->setValue('num_partida', $request->num_partida);
            $templateProcessor->setValue('partida_nombre', $request->partida_nombre);
            $templateProcessor->setValue('num_requisicion', $request->num_requisicion);
            $templateProcessor->setValue('plazo_contrato', $request->plazo_contrato);

            // =========================
            // GUARDAR
            // =========================
            $outputDir = storage_path('app/public/documentos');

            if (!file_exists($outputDir)) {
                mkdir($outputDir, 0777, true);
            }

            $outputName = 'procedimiento_' . $procedimiento->id . '.docx';
            $outputPath = $outputDir . DIRECTORY_SEPARATOR . $outputName;

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
        $path = storage_path('app/public/' . $procedimiento->ruta_documento);

        return response()->download($path);
    }
}