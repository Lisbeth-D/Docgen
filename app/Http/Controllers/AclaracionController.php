<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Procedimiento;
use App\Models\Persona;
use PhpOffice\PhpWord\TemplateProcessor;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AclaracionController extends Controller
{
    public function index()
    {
        $personas = Persona::orderBy('nombre')->get();

        return view(
            'comprador.aclaracion.acta',
            compact('personas')
        );
    }

    public function buscarProcedimiento($valor)
    {
        $proc = Procedimiento::where(
            'num_procedimiento',
            'LIKE',
            "%{$valor}%"
        )->first();

        if (!$proc) {
            return response()->json(null);
        }

        return response()->json([
            'num_procedimiento'    => $proc->num_procedimiento,
            'nombre_procedimiento' => $proc->nombre_procedimiento,
            'fecha_ac'             => $proc->fecha_ac,
            'hora_ac'              => $proc->hora_ac,
            'fecha_apertura'       => $proc->fecha_apertura,
            'hora_apertura'        => $proc->hora_apertura,
        ]);
    }

    public function generar(Request $request)
    {
        $request->validate([

            'numero_busqueda'  => 'required',

            'archivo_word'     => 'required|file|mimes:docx',

            'area_requirente'  => 'required',

            'area_contratante' => 'required',

        ]);

        // =====================================================
        // PROCEDIMIENTO
        // =====================================================

        $proc = Procedimiento::where(
            'num_procedimiento',
            'LIKE',
            "%{$request->numero_busqueda}%"
        )->first();

        if (!$proc) {

            return back()->with(
                'error',
                'No se encontró el procedimiento'
            );
        }

        // =====================================================
        // WORD
        // =====================================================

        $file = $request->file('archivo_word');

        $filename =
            time() .
            '_' .
            $file->getClientOriginalName();

        $templateDir =
            storage_path('app/plantillas');

        if (!file_exists($templateDir)) {

            mkdir($templateDir, 0777, true);
        }

        $file->move($templateDir, $filename);

        $templatePath =
            $templateDir .
            '/' .
            $filename;

        $templateProcessor =
            new TemplateProcessor($templatePath);

        // =====================================================
        // FECHAS Y HORAS
        // =====================================================

        Carbon::setLocale('es');

        // =========================
        // ACLARACIONES
        // =========================

        $fechaAC = Carbon::parse($proc->fecha_ac);

        $horaAC = Carbon::parse($proc->hora_ac);

        // +30 minutos automático
        $horaCierre =
            $horaAC->copy()->addMinutes(30);

        $fechaACTexto =
            $fechaAC->day .
            ' de ' .
            $fechaAC->translatedFormat('F') .
            ' de ' .
            $fechaAC->year;

        $horaACTexto =
            $horaAC->format('H:i') .
            ' horas';

        $horaCierreTexto =
            $horaCierre->format('H:i') .
            ' horas';

        // =========================
        // APERTURA
        // =========================

        $fechaApertura =
            Carbon::parse($proc->fecha_apertura);

        $fechaAperturaTexto =
            $fechaApertura->day .
            ' de ' .
            $fechaApertura->translatedFormat('F') .
            ' de ' .
            $fechaApertura->year;

        $horaAperturaTexto =
            Carbon::parse($proc->hora_apertura)
                ->format('H:i') .
            ' horas';

        $fechaHoraApertura =
            $fechaAperturaTexto .
            ', a las ' .
            $horaAperturaTexto;

        // =====================================================
        // PERSONAS
        // =====================================================

        $areaReq =
            Persona::find(
                $request->area_requirente
            );

        $areaCont =
            Persona::find(
                $request->area_contratante
            );

        $oic =
            Persona::find(
                $request->persona_oic
            );

        $juridico =
            Persona::find(
                $request->persona_juridico
            );

        $areaReqTexto = $areaReq
            ? trim(
                $areaReq->nombre .
                '.- ' .
                $areaReq->cargo
            )
            : '';

        $areaContTexto = $areaCont
            ? trim(
                $areaCont->nombre .
                '.- ' .
                $areaCont->cargo
            )
            : '';

        $oicTexto = $oic
            ? trim($oic->nombre)
            : '';

        $juridicoTexto = $juridico
            ? trim($juridico->nombre)
            : '';

        // =====================================================
        // SET VALUES
        // =====================================================

        $templateProcessor->setValue(
            'num_procedimiento',
            $proc->num_procedimiento
        );

        $templateProcessor->setValue(
            'nombre_procedimiento',
            $proc->nombre_procedimiento
        );

        $templateProcessor->setValue(
            'fecha_ac',
            $fechaACTexto
        );

        $templateProcessor->setValue(
            'hora_ac',
            $horaACTexto
        );

        $templateProcessor->setValue(
            'hora_cierre',
            $horaCierreTexto
        );

        $templateProcessor->setValue(
            'fecha_apertura',
            $fechaHoraApertura
        );

        $templateProcessor->setValue(
            'area_requirente',
            $areaReqTexto
        );

        $templateProcessor->setValue(
            'area_contratante',
            $areaContTexto
        );

        $templateProcessor->setValue(
            'persona_oic',
            $oicTexto
        );

        $templateProcessor->setValue(
            'persona_juridico',
            $juridicoTexto
        );

        $templateProcessor->setValue(
            'ref_oic',
            $request->ref_oic ?? ''
        );

        $templateProcessor->setValue(
            'ref_juridico',
            $request->ref_juridico ?? ''
        );

        $templateProcessor->setValue(
            'comprador',
            Auth::user()->name
        );

        // =====================================================
        // PARTICIPANTES
        // =====================================================

        $participantes = array_filter(
            $request->participantes ?? [],
            fn($p) => !empty($p['nombre'])
        );

        if (count($participantes) > 0) {

            // EN WORD:
            // ${empresa}
            // ${pregunta}

            $templateProcessor->cloneRow(
                'empresa',
                count($participantes)
            );

            foreach (
                array_values($participantes)
                as $i => $empresa
            ) {

                $index = $i + 1;

                $templateProcessor->setValue(
                    "empresa#{$index}",
                    trim($empresa['nombre'])
                );

                // AUTOMÁTICO
                $templateProcessor->setValue(
                    "pregunta#{$index}",
                    'NO'
                );
            }
        }

        // =====================================================
        // GUARDAR
        // =====================================================

        $outputDir = storage_path(
            'app/public/documentos'
        );

        if (!file_exists($outputDir)) {

            mkdir($outputDir, 0777, true);
        }

        $outputName =
            'acta_aclaracion_' .
            time() .
            '.docx';

        $outputPath =
            $outputDir .
            '/' .
            $outputName;

        $templateProcessor->saveAs($outputPath);

        return response()->download($outputPath);
    }
}