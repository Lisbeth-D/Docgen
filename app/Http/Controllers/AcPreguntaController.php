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
        $areasRequirentes = Area::with(['personas' => function ($query) {
                $query->orderBy('nombre');
            }])
            ->whereNotIn('id_area', [4, 14, 15])
            ->orderBy('nombre')
            ->get();

        $areasContrato = Area::with(['personas' => function ($query) {
                $query->orderBy('nombre');
            }])
            ->whereIn('nombre', [
                'Gerencia',
                'Subgerencia de Operaciones',
                'Subgerencia de Abasto'
            ])
            ->orderBy('nombre')
            ->get();

        $personasContratante = Persona::where('area_id', 4)
            ->orderBy('nombre')
            ->get();

        $personasOic = Persona::where('area_id', 14)
            ->orderBy('nombre')
            ->get();

        $personasJuridico = Persona::where('area_id', 15)
            ->orderBy('nombre')
            ->get();

        return view('comprador.aclaracion.ac_pregunta', compact(
            'areasRequirentes',
            'areasContrato',
            'personasContratante',
            'personasOic',
            'personasJuridico'
        ));
    }

    private function crearTextoPersona($persona)
    {
        $textRun = new \PhpOffice\PhpWord\Element\TextRun();

        if (!$persona) {
            return $textRun;
        }

        $textRun->addText(trim($persona->nombre), [
            'name' => 'Noto Sans',
            'size' => 10,
            'bold' => true,
        ]);

        $textRun->addText(', ' . trim($persona->cargo), [
            'name' => 'Noto Sans',
            'size' => 10,
            'bold' => false,
        ]);

        return $textRun;
    }

    private function crearTextoComprador($usuario)
    {
        $textRun = new \PhpOffice\PhpWord\Element\TextRun();

        if (!$usuario) {
            return $textRun;
        }

        $textRun->addText(trim($usuario->name), [
            'name' => 'Noto Sans',
            'size' => 10,
            'bold' => true,
        ]);

        if (!empty($usuario->cargo)) {
            $textRun->addText(' / ' . trim($usuario->cargo), [
                'name' => 'Noto Sans',
                'size' => 10,
                'bold' => false,
            ]);
        }

        return $textRun;
    }

    private function normalizarSiPresento($valor)
    {
        $valor = strtoupper(trim($valor ?? 'NO'));

        $valor = str_replace(
            ['Á', 'É', 'Í', 'Ó', 'Ú'],
            ['A', 'E', 'I', 'O', 'U'],
            $valor
        );

        return in_array($valor, [
            'SI',
            'SI PRESENTO',
            'SI PRESENTO PREGUNTAS',
            'SÍ',
            'SÍ PRESENTÓ',
            'SÍ PRESENTÓ PREGUNTAS'
        ]);
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
            'numero_busqueda'             => 'required',
            'num_procedimiento'           => 'nullable',
            'nombre_procedimiento'        => 'nullable',
            'fecha_ac'                    => 'nullable|date',
            'hora_ac'                     => 'nullable',

            'area_requirente'             => 'required|exists:personas,id',
            'area_contratante'            => 'required|exists:personas,id',
            'admi_contrato'               => 'required|exists:personas,id',

            'numero_oficio_preguntas'     => 'required|numeric',
            'numero_oficio_respuestas'    => 'required|numeric',
            'fecha_oficio_preguntas'      => 'required|date',
            'fecha_oficio_respuestas'     => 'required|date',

            'persona_oic'                 => 'nullable|exists:personas,id',
            'persona_juridico'            => 'nullable|exists:personas,id',
            'ref_oic'                     => 'nullable',
            'ref_juridico'                => 'nullable',

            'participantes'               => 'nullable|array',
            'preguntas'                   => 'nullable|array',
            'archivo_word'                => 'required|file|mimes:docx'
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
        $admiContrato = Persona::find($request->admi_contrato);
        $oic = Persona::find($request->persona_oic);
        $juridico = Persona::find($request->persona_juridico);

        $areaRequirenteNombre = Area::find($areaReq->area_id)?->nombre ?? '';
        $areaAdmiContratoNombre = Area::find($admiContrato->area_id)?->nombre ?? '';

        $oficioPreguntas = str_replace(
            '{NUMERO}',
            $request->numero_oficio_preguntas,
            $areaCont->plantilla_referencia
        );

        $oficioRespuestas = str_replace(
            '{NUMERO}',
            $request->numero_oficio_respuestas,
            $areaReq->plantilla_referencia
        );

        $fechaOficioPreguntas = Carbon::parse($request->fecha_oficio_preguntas);
        $fechaOficioPreguntasTexto = $fechaOficioPreguntas->day . ' de ' . $fechaOficioPreguntas->translatedFormat('F') . ' de ' . $fechaOficioPreguntas->year;

        $fechaOficioRespuestas = Carbon::parse($request->fecha_oficio_respuestas);
        $fechaOficioRespuestasTexto = $fechaOficioRespuestas->day . ' de ' . $fechaOficioRespuestas->translatedFormat('F') . ' de ' . $fechaOficioRespuestas->year;

        $areaReqTexto = $this->crearTextoPersona($areaReq);
        $areaContTexto = $this->crearTextoPersona($areaCont);
        $admiContratoTexto = $this->crearTextoPersona($admiContrato);
        $oicTexto = $this->crearTextoPersona($oic);
        $juridicoTexto = $this->crearTextoPersona($juridico);

        $participantes = array_values(array_filter(
            $request->participantes ?? [],
            fn ($p) => !empty($p['nombre'])
        ));

        $licitantesConPreguntas = [];

        foreach ($participantes as $participante) {
            $siPresento = $this->normalizarSiPresento($participante['pregunta'] ?? 'NO');

            $preguntasParticipante = array_values(array_filter(
                $participante['preguntas'] ?? [],
                fn ($p) => !empty($p['pregunta'])
            ));

            if (
                empty($preguntasParticipante)
                && $siPresento
                && empty($licitantesConPreguntas)
                && !empty($request->preguntas)
            ) {
                $preguntasParticipante = array_values(array_filter(
                    $request->preguntas ?? [],
                    fn ($p) => !empty($p['pregunta'])
                ));
            }

            if ($siPresento && count($preguntasParticipante) > 0) {
                $licitantesConPreguntas[] = [
                    'empresa'   => trim($participante['nombre']),
                    'preguntas' => $preguntasParticipante,
                ];
            }
        }

        $totalPreguntas = 0;

        foreach ($licitantesConPreguntas as $licitante) {
            $totalPreguntas += count($licitante['preguntas']);
        }

        $numerosTextoPreguntas = [
            0 => 'cero',
            1 => 'una',
            2 => 'dos',
            3 => 'tres',
            4 => 'cuatro',
            5 => 'cinco',
            6 => 'seis',
            7 => 'siete',
            8 => 'ocho',
            9 => 'nueve',
            10 => 'diez'
        ];

        $textoNumeroPreguntas = $numerosTextoPreguntas[$totalPreguntas] ?? $totalPreguntas;

        if ($totalPreguntas == 1) {
            $textoTotalPreguntas = 'La única pregunta recibida';
        } else {
            $textoTotalPreguntas = "Las {$totalPreguntas} ({$textoNumeroPreguntas}) preguntas recibidas";
        }

        $totalEmpresas = count($licitantesConPreguntas);

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

        $textoNumero = $numerosTexto[$totalEmpresas] ?? $totalEmpresas;

        if ($totalEmpresas == 1) {
            $textoSolicitudes = "se recibió {$totalEmpresas} ({$textoNumero}) solicitud";
        } else {
            $textoSolicitudes = "se recibieron {$totalEmpresas} ({$textoNumero}) solicitudes";
        }

        $templateProcessor->setValue('num_procedimiento', $request->num_procedimiento ?: $proc->num_procedimiento);
        $templateProcessor->setValue('nombre_procedimiento', $request->nombre_procedimiento ?: $proc->nombre_procedimiento);

        $templateProcessor->setValue('hora_inicio', $horaInicioTexto);
        $templateProcessor->setValue('fecha_ac', $fechaACTexto);
        $templateProcessor->setValue('hora_cierre', $horaCierreTexto);
        $templateProcessor->setValue('hora_reanudacion', $horaReanudacionTexto);
        $templateProcessor->setValue('fecha_apertura', $fechaHoraApertura);

        $templateProcessor->setValue('oficio_preguntas', $oficioPreguntas);
        $templateProcessor->setValue('fecha_oficio_preguntas', $fechaOficioPreguntasTexto);
        $templateProcessor->setValue('oficio_respuestas', $oficioRespuestas);
        $templateProcessor->setValue('fecha_oficio_respuestas', $fechaOficioRespuestasTexto);

        $templateProcessor->setValue('texto_total_preguntas', $textoTotalPreguntas);

        $templateProcessor->setComplexValue('area_requirente', $areaReqTexto);
        $templateProcessor->setValue('area_area_requirente', $areaRequirenteNombre);

        $templateProcessor->setComplexValue('area_contratante', $areaContTexto);

        $templateProcessor->setComplexValue('admi_contrato', $admiContratoTexto);
        $templateProcessor->setValue('area_admi_contrato', $areaAdmiContratoNombre);

        $templateProcessor->setComplexValue('persona_oic', $oicTexto);
        $templateProcessor->setComplexValue('persona_juridico', $juridicoTexto);

        $templateProcessor->setValue('ref_oic', $request->ref_oic ?? '');
        $templateProcessor->setValue('ref_juridico', $request->ref_juridico ?? '');

        $templateProcessor->setValue('solicitudes', $textoSolicitudes);
        $templateProcessor->setComplexValue('comprador', $this->crearTextoComprador(Auth::user()));

        $templateProcessor->setComplexValue('admi_contrato_tabla', $this->crearTextoPersona($admiContrato));
        $templateProcessor->setComplexValue('area_requirente_tabla', $this->crearTextoPersona($areaReq));
        $templateProcessor->setComplexValue('comprador_tabla', $this->crearTextoComprador(Auth::user()));
        $templateProcessor->setComplexValue('persona_oic_tabla', $this->crearTextoPersona($oic));
        $templateProcessor->setComplexValue('persona_juridico_tabla', $this->crearTextoPersona($juridico));

        if (count($participantes) > 0) {
            $templateProcessor->cloneRow('empresa_interes', count($participantes));

            foreach ($participantes as $i => $participante) {
                $index = $i + 1;

                $presento = $this->normalizarSiPresento($participante['pregunta'] ?? 'NO')
                    ? 'SÍ PRESENTÓ'
                    : 'NO PRESENTÓ';

                $templateProcessor->setValue("empresa_interes#{$index}", trim($participante['nombre']));
                $templateProcessor->setValue("presento_preguntas#{$index}", $presento);
            }
        }

        if (count($licitantesConPreguntas) > 0) {
            $templateProcessor->cloneRow('empresa_resumen', count($licitantesConPreguntas));

            foreach ($licitantesConPreguntas as $i => $licitante) {
                $index = $i + 1;

                $templateProcessor->setValue("empresa_resumen#{$index}", $licitante['empresa']);
                $templateProcessor->setValue("numero_preguntas#{$index}", count($licitante['preguntas']));
            }
        }

        $tablaPreguntas = new \PhpOffice\PhpWord\Element\Table([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
        ]);

        foreach ($licitantesConPreguntas as $licitante) {
            $tablaPreguntas->addRow();

            $tablaPreguntas->addCell(9400, [
                'gridSpan' => 3,
                'bgColor' => 'D9D9D9',
            ])->addText(strtoupper($licitante['empresa']), [
                'name' => 'Noto Sans',
                'size' => 10,
                'bold' => true,
            ]);

            $tablaPreguntas->addRow();

            $tablaPreguntas->addCell(800, ['bgColor' => 'D9D9D9'])->addText('No.', [
                'name' => 'Noto Sans',
                'size' => 9,
                'bold' => true,
            ]);

            $tablaPreguntas->addCell(4300, ['bgColor' => 'D9D9D9'])->addText('Preguntas', [
                'name' => 'Noto Sans',
                'size' => 9,
                'bold' => true,
            ]);

            $tablaPreguntas->addCell(4300, ['bgColor' => 'D9D9D9'])->addText('Respuestas', [
                'name' => 'Noto Sans',
                'size' => 9,
                'bold' => true,
            ]);

            foreach ($licitante['preguntas'] as $i => $pregunta) {
                $tablaPreguntas->addRow();

                $tablaPreguntas->addCell(800)->addText($i + 1, [
                    'name' => 'Noto Sans',
                    'size' => 10,
                ]);

                $tablaPreguntas->addCell(4300)->addText(trim($pregunta['pregunta']), [
                    'name' => 'Noto Sans',
                    'size' => 10,
                ]);

                $tablaPreguntas->addCell(4300)->addText(strtoupper(trim($pregunta['respuesta'] ?? '')), [
                    'name' => 'Noto Sans',
                    'size' => 10,
                ]);
            }
        }

        $templateProcessor->setComplexBlock('bloque_preguntas', $tablaPreguntas);

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