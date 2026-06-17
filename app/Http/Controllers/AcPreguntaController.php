<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Procedimiento;
use App\Models\Persona;
use App\Models\Area;
use PhpOffice\PhpWord\TemplateProcessor;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AcPreguntaController extends Controller
{
    public function index()
    {
        $areasRequirentes = Area::whereNotIn('nombre', [
                'OIC Ofi centrales',
                'Juridico Ofi centrales'
            ])
            ->with(['personas' => function ($query) {
                $query->orderBy('nombre');
            }])
            ->orderBy('nombre')
            ->get();

        $personasContratante = Persona::whereHas('area', function ($query) {
                $query->where('nombre', 'Coordinación de Adquisiciones y Servicios');
            })
            ->orderBy('nombre')
            ->get();

        $personasOic = Persona::whereHas('area', function ($query) {
                $query->where('nombre', 'OIC Ofi centrales');
            })
            ->orderBy('nombre')
            ->get();

        $personasJuridico = Persona::whereHas('area', function ($query) {
                $query->where('nombre', 'Juridico Ofi centrales');
            })
            ->orderBy('nombre')
            ->get();

        return view('comprador.aclaracion.ac_pregunta', compact(
            'areasRequirentes',
            'personasContratante',
            'personasOic',
            'personasJuridico'
        ));
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
            'fecha_ac'             => $proc->fecha_ac ? Carbon::parse($proc->fecha_ac)->format('Y-m-d') : '',
            'hora_ac'              => $proc->hora_ac ? Carbon::parse($proc->hora_ac)->format('H:i') : '',
            'fecha_apertura'       => $proc->fecha_apertura ? Carbon::parse($proc->fecha_apertura)->format('Y-m-d') : '',
            'hora_apertura'        => $proc->hora_apertura ? Carbon::parse($proc->hora_apertura)->format('H:i') : '',
        ]);
    }

    public function generar(Request $request)
    {
        $request->validate([
            'numero_busqueda'      => 'required',
            'num_procedimiento'    => 'nullable',
            'nombre_procedimiento' => 'nullable',
            'fecha_ac'             => 'nullable|date',
            'hora_ac'              => 'nullable',
            'area_requirente'      => 'required|exists:personas,id',
            'area_contratante'     => 'required|exists:personas,id',
            'persona_oic'          => 'nullable|exists:personas,id',
            'persona_juridico'     => 'nullable|exists:personas,id',
            'ref_oic'              => 'nullable',
            'ref_juridico'         => 'nullable',
            'participantes'        => 'nullable|array',
            'preguntas'            => 'nullable|array',
            'archivo_word'         => 'required|file|mimes:docx'
        ]);

        $proc = Procedimiento::where(
            'num_procedimiento',
            'LIKE',
            '%-N-' . $request->numero_busqueda . '-%'
        )->first();

        if (!$proc) {
            return back()->with('error', 'No se encontró el procedimiento');
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

        Carbon::setLocale('es');

        $fechaAC = Carbon::parse($request->fecha_ac ?: $proc->fecha_ac);
        $horaInicio = Carbon::parse($request->hora_ac ?: $proc->hora_ac);

        $horaCierre = $horaInicio->copy()->addMinutes(30);
        $horaReanudacion = $horaCierre->copy()->addHours(6);

        $fechaACTexto = $fechaAC->day . ' de ' . $fechaAC->translatedFormat('F') . ' de ' . $fechaAC->year;

        $fechaApertura = Carbon::parse($proc->fecha_apertura);
        $fechaAperturaTexto = $fechaApertura->day . ' de ' . $fechaApertura->translatedFormat('F') . ' de ' . $fechaApertura->year;

        $horaApertura = Carbon::parse($proc->hora_apertura)->format('H:i');
        $fechaHoraApertura = $fechaAperturaTexto . ', a las ' . $horaApertura . ' horas.';

        $horaInicioTexto = $horaInicio->format('H:i');
        $horaCierreTexto = $horaCierre->format('H:i') . ' horas';
        $horaReanudacionTexto = $horaReanudacion->format('H:i') . ' horas del día ' . $fechaACTexto;

        $areaReq = Persona::find($request->area_requirente);
        $areaCont = Persona::find($request->area_contratante);
        $oic = Persona::find($request->persona_oic);
        $juridico = Persona::find($request->persona_juridico);

        $areaReqTexto = $areaReq ? trim($areaReq->nombre . '.- ' . $areaReq->cargo) : '';
        $areaContTexto = $areaCont ? trim($areaCont->nombre . '.- ' . $areaCont->cargo) : '';
        $oicTexto = $oic ? trim($oic->nombre . '.- ' . $oic->cargo) : '';
        $juridicoTexto = $juridico ? trim($juridico->nombre . '.- ' . $juridico->cargo) : '';

        /*
        |--------------------------------------------------------------------------
        | PARTICIPANTES Y PREGUNTAS AGRUPADAS POR LICITANTE
        |--------------------------------------------------------------------------
        */

        $participantes = array_values(array_filter(
            $request->participantes ?? [],
            fn ($p) => !empty($p['nombre'])
        ));

        $licitantesConPreguntas = [];

        foreach ($participantes as $index => $participante) {
            $presento = strtoupper($participante['pregunta'] ?? 'NO');

            $preguntasParticipante = array_values(array_filter(
                $participante['preguntas'] ?? [],
                fn ($p) => !empty($p['pregunta'])
            ));

            /*
             * Compatibilidad con el formulario anterior:
             * Si todavía mandas preguntas generales, se asignan al primer participante que dijo SI.
             */
            if (
                empty($preguntasParticipante)
                && $presento === 'SI'
                && empty($licitantesConPreguntas)
                && !empty($request->preguntas)
            ) {
                $preguntasParticipante = array_values(array_filter(
                    $request->preguntas ?? [],
                    fn ($p) => !empty($p['pregunta'])
                ));
            }

            if ($presento === 'SI' && count($preguntasParticipante) > 0) {
                $licitantesConPreguntas[] = [
                    'empresa'   => trim($participante['nombre']),
                    'preguntas' => $preguntasParticipante,
                ];
            }
        }

        $numSolicitudes = 0;

        foreach ($licitantesConPreguntas as $licitante) {
            $numSolicitudes += count($licitante['preguntas']);
        }

        $numerosTexto = [
            0 => 'CERO',
            1 => 'UNA',
            2 => 'DOS',
            3 => 'TRES',
            4 => 'CUATRO',
            5 => 'CINCO',
            6 => 'SEIS',
            7 => 'SIETE',
            8 => 'OCHO',
            9 => 'NUEVE',
            10 => 'DIEZ'
        ];

        $textoNumero = $numerosTexto[$numSolicitudes] ?? $numSolicitudes;

        $textoSolicitudes = $numSolicitudes . ' (' . $textoNumero . ') ' .
            ($numSolicitudes == 1 ? 'solicitud' : 'solicitudes');

        /*
        |--------------------------------------------------------------------------
        | DATOS GENERALES
        |--------------------------------------------------------------------------
        */

        $templateProcessor->setValue('num_procedimiento', $request->num_procedimiento ?: $proc->num_procedimiento);
        $templateProcessor->setValue('nombre_procedimiento', $request->nombre_procedimiento ?: $proc->nombre_procedimiento);

        $templateProcessor->setValue('hora_inicio', $horaInicioTexto);
        $templateProcessor->setValue('fecha_ac', $fechaACTexto);

        $templateProcessor->setValue('hora_cierre', $horaCierreTexto);
        $templateProcessor->setValue('hora_reanudacion', $horaReanudacionTexto);
        $templateProcessor->setValue('fecha_apertura', $fechaHoraApertura);

        $templateProcessor->setValue('area_requirente', $areaReqTexto);
        $templateProcessor->setValue('area_contratante', $areaContTexto);

        $templateProcessor->setValue('persona_oic', $oicTexto);
        $templateProcessor->setValue('persona_juridico', $juridicoTexto);

        $templateProcessor->setValue('ref_oic', $request->ref_oic ?? '');
        $templateProcessor->setValue('ref_juridico', $request->ref_juridico ?? '');

        $templateProcessor->setValue('solicitudes', $textoSolicitudes);
        $templateProcessor->setValue('comprador', Auth::user()->name ?? '');

        /*
        |--------------------------------------------------------------------------
        | TABLA 1: TODOS LOS PARTICIPANTES
        | Etiquetas en Word:
        | ${empresa_interes}
        | ${presento_preguntas}
        |--------------------------------------------------------------------------
        */

        if (count($participantes) > 0) {
            $templateProcessor->cloneRow('empresa_interes', count($participantes));

            foreach ($participantes as $i => $participante) {
                $index = $i + 1;

                $presento = strtoupper($participante['pregunta'] ?? 'NO') === 'SI'
                    ? 'SÍ PRESENTÓ'
                    : 'NO PRESENTÓ';

                $templateProcessor->setValue("empresa_interes#{$index}", trim($participante['nombre']));
                $templateProcessor->setValue("presento_preguntas#{$index}", $presento);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | TABLA 2: SOLO LICITANTES QUE PRESENTARON PREGUNTAS
        | Etiquetas en Word:
        | ${empresa_resumen}
        | ${numero_preguntas}
        |--------------------------------------------------------------------------
        */

        if (count($licitantesConPreguntas) > 0) {
            $templateProcessor->cloneRow('empresa_resumen', count($licitantesConPreguntas));

            foreach ($licitantesConPreguntas as $i => $licitante) {
                $index = $i + 1;

                $templateProcessor->setValue("empresa_resumen#{$index}", $licitante['empresa']);
                $templateProcessor->setValue("numero_preguntas#{$index}", count($licitante['preguntas']));
            }
        } else {
            $templateProcessor->setValue('empresa_resumen', '');
            $templateProcessor->setValue('numero_preguntas', '');
        }

        /*
        |--------------------------------------------------------------------------
        | BLOQUE DE PREGUNTAS POR LICITANTE
        | Etiqueta en Word:
        | ${bloque_preguntas}
        |--------------------------------------------------------------------------
        */

        $bloquePreguntas = new \PhpOffice\PhpWord\Element\TextRun();

        foreach ($licitantesConPreguntas as $licitante) {

            $bloquePreguntas->addText(
                'Licitante: ' . $licitante['empresa'],
                [
                    'name' => 'Noto Sans',
                    'size' => 10,
                    'bold' => true,
                ]
            );

            $bloquePreguntas->addTextBreak(1);

            $tabla = new \PhpOffice\PhpWord\Element\Table([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => 80,
            ]);

            // ENCABEZADOS
            $tabla->addRow();

            $tabla->addCell(800, ['bgColor' => 'D9D9D9'])->addText(
                'No.',
                [
                    'name' => 'Noto Sans',
                    'size' => 9,
                    'bold' => true,
                ]
            );

            $tabla->addCell(4300, ['bgColor' => 'D9D9D9'])->addText(
                'Preguntas',
                [
                    'name' => 'Noto Sans',
                    'size' => 9,
                    'bold' => true,
                ]
            );

            $tabla->addCell(4300, ['bgColor' => 'D9D9D9'])->addText(
                'Respuestas',
                [
                    'name' => 'Noto Sans',
                    'size' => 9,
                    'bold' => true,
                ]
            );

            foreach ($licitante['preguntas'] as $i => $pregunta) {
                $numero = $i + 1;

                $tabla->addRow();

                $tabla->addCell(800)->addText(
                    $numero,
                    [
                        'name' => 'Noto Sans',
                        'size' => 10,
                    ]
                );

                $tabla->addCell(4300)->addText(
                    trim($pregunta['pregunta']),
                    [
                        'name' => 'Noto Sans',
                        'size' => 10,
                    ]
                );

                $tabla->addCell(4300)->addText(
                    strtoupper(trim($pregunta['respuesta'] ?? '')),
                    [
                        'name' => 'Noto Sans',
                        'size' => 10,
                    ]
                );
            }

            $bloquePreguntas->addElement($tabla);
            $bloquePreguntas->addTextBreak(2);
        }

        $templateProcessor->setComplexBlock('bloque_preguntas', $bloquePreguntas);

        /*
        |--------------------------------------------------------------------------
        | GUARDAR
        |--------------------------------------------------------------------------
        */

        $outputDir = storage_path('app/public/documentos');

        if (!file_exists($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        $outputName = 'ac_pregunta_' . time() . '.docx';
        $outputPath = $outputDir . '/' . $outputName;

        $templateProcessor->saveAs($outputPath);

        return response()->download($outputPath)->deleteFileAfterSend(true);
    }
}