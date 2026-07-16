<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Models\Procedimiento;
use App\Models\TipoProcedimiento;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\TemplateProcessor;
use Throwable;

class ProcedimientoController extends Controller
{
    /**
     * Muestra el formulario para generar la convocatoria.
     */
    public function convocatoria()
    {
        /*
         * No se muestran como responsables técnicos las personas
         * pertenecientes a estas áreas.
         */
        $areasExcluidas = [
            'Coordinación General de Adquisiciones y Servicios',
            'Órgano Interno de Control',
            'Jurídico Centrales',
        ];

        $personas = Persona::with('area')
            ->whereHas('area', function ($query) use ($areasExcluidas) {
                $query->whereNotIn('nombre', $areasExcluidas);
            })
            ->orderBy('nombre')
            ->get();

        $tipos = TipoProcedimiento::orderBy('nombre_tipo')->get();

        return view(
            'comprador.convo.convocatoria',
            compact('personas', 'tipos')
        );
    }

    /**
     * Guarda el procedimiento y genera el documento Word.
     */
    public function store(Request $request)
    {
        $datosValidados = $request->validate(
            [
                'id_tipo_procedimiento' => [
                    'required',
                    'integer',
                    'exists:tipo_procedimiento,id_tipo_procedimiento',
                ],

                'resp_tecnico' => [
                    'required',
                    'integer',
                    'exists:personas,id',
                ],

                'nombre_procedimiento' => [
                    'required',
                    'string',
                    'max:1000',
                ],

                'num_procedimiento' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'archivo_word' => [
                    'required',
                    'file',
                    'mimes:docx',
                    'max:10240',
                ],

                'monto_maximo' => [
                    'required',
                ],

                'monto_minimo' => [
                    'nullable',
                ],

                'fecha_publicacion' => [
                    'required',
                    'date',
                ],

                'aplica_vm' => [
                    'nullable',
                    'in:SI,NO',
                ],

                'fecha_vm' => [
                    'nullable',
                    'required_if:aplica_vm,SI',
                    'date',
                ],

                'hora_vm' => [
                    'nullable',
                    'required_if:aplica_vm,SI',
                    'date_format:H:i',
                ],

                'aplica_acl' => [
                    'nullable',
                    'in:SI,NO',
                ],

                'fecha_acl' => [
                    'nullable',
                    'required_if:aplica_acl,SI',
                    'date',
                ],

                'hora_acl' => [
                    'nullable',
                    'required_if:aplica_acl,SI',
                    'date_format:H:i',
                ],

                'fecha_apertura' => [
                    'required',
                    'date',
                ],

                'hora_apertura' => [
                    'required',
                    'date_format:H:i',
                ],

                'fecha_fallo' => [
                    'required',
                    'date',
                ],

                'hora_fallo' => [
                    'required',
                    'date_format:H:i',
                ],

                'fecha_inicio_contrato' => [
                    'required',
                    'date',
                ],

                'fecha_fin_contrato' => [
                    'required',
                    'date',
                    'after_or_equal:fecha_inicio_contrato',
                ],

                'num_partida' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'partida_nombre' => [
                    'nullable',
                    'string',
                    'max:1000',
                ],

                'num_requisicion' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
            ],
            [
                'id_tipo_procedimiento.required' =>
                    'Debe seleccionar un tipo de procedimiento.',

                'id_tipo_procedimiento.exists' =>
                    'El tipo de procedimiento seleccionado no existe.',

                'resp_tecnico.required' =>
                    'Debe seleccionar un responsable técnico.',

                'resp_tecnico.exists' =>
                    'El responsable técnico seleccionado no existe.',

                'nombre_procedimiento.required' =>
                    'Debe ingresar el nombre del procedimiento.',

                'num_procedimiento.required' =>
                    'Debe ingresar el número del procedimiento.',

                'archivo_word.required' =>
                    'Debe seleccionar una plantilla Word.',

                'archivo_word.mimes' =>
                    'La plantilla debe ser un archivo Word con extensión .docx.',

                'archivo_word.max' =>
                    'La plantilla Word no debe superar los 10 MB.',

                'monto_maximo.required' =>
                    'Debe ingresar el monto máximo.',

                'fecha_publicacion.required' =>
                    'Debe ingresar la fecha de publicación.',

                'fecha_vm.required_if' =>
                    'Debe ingresar la fecha de la visita.',

                'hora_vm.required_if' =>
                    'Debe ingresar la hora de la visita.',

                'fecha_acl.required_if' =>
                    'Debe ingresar la fecha de la junta de aclaraciones.',

                'hora_acl.required_if' =>
                    'Debe ingresar la hora de la junta de aclaraciones.',

                'fecha_apertura.required' =>
                    'Debe ingresar la fecha de apertura.',

                'hora_apertura.required' =>
                    'Debe ingresar la hora de apertura.',

                'fecha_fallo.required' =>
                    'Debe ingresar la fecha del fallo.',

                'hora_fallo.required' =>
                    'Debe ingresar la hora del fallo.',

                'fecha_inicio_contrato.required' =>
                    'Debe ingresar la fecha inicial del contrato.',

                'fecha_fin_contrato.required' =>
                    'Debe ingresar la fecha final del contrato.',

                'fecha_fin_contrato.after_or_equal' =>
                    'La fecha final del contrato no puede ser anterior a la fecha inicial.',
            ]
        );

        $persona = Persona::findOrFail($datosValidados['resp_tecnico']);

        $montoMaximo = $this->limpiarMonto(
            $datosValidados['monto_maximo']
        );

        $montoMinimo = $this->limpiarMonto(
            $datosValidados['monto_minimo'] ?? null
        );

        if ($montoMaximo === null || $montoMaximo < 0) {
            return back()
                ->withInput()
                ->withErrors([
                    'monto_maximo' => 'El monto máximo ingresado no es válido.',
                ]);
        }

        if ($montoMinimo !== null && $montoMinimo < 0) {
            return back()
                ->withInput()
                ->withErrors([
                    'monto_minimo' => 'El monto mínimo ingresado no es válido.',
                ]);
        }

        if (
            $montoMinimo !== null &&
            $montoMinimo > $montoMaximo
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'monto_minimo' =>
                        'El monto mínimo no puede ser mayor que el monto máximo.',
                ]);
        }

        /*
         * Cuando la visita o la junta no aplican, se guardan
         * sus campos de fecha y hora como NULL.
         */
        $fechaVisita = $request->aplica_vm === 'SI'
            ? $request->fecha_vm
            : null;

        $horaVisita = $request->aplica_vm === 'SI'
            ? $request->hora_vm
            : null;

        $fechaAclaraciones = $request->aplica_acl === 'SI'
            ? $request->fecha_acl
            : null;

        $horaAclaraciones = $request->aplica_acl === 'SI'
            ? $request->hora_acl
            : null;

        $rutaPlantilla = null;
        $rutaDocumento = null;

        DB::beginTransaction();

        try {
            /*
             * Guardar la plantilla cargada.
             */
            $archivo = $request->file('archivo_word');

            $directorioPlantillas = storage_path('app/plantillas');

            File::ensureDirectoryExists($directorioPlantillas);

            $nombreOriginal = pathinfo(
                $archivo->getClientOriginalName(),
                PATHINFO_FILENAME
            );

            $nombreSeguro = Str::slug($nombreOriginal);

            if (empty($nombreSeguro)) {
                $nombreSeguro = 'plantilla';
            }

            $nombrePlantilla =
                now()->format('Ymd_His') .
                '_' .
                $nombreSeguro .
                '_' .
                Str::random(6) .
                '.docx';

            $archivo->move(
                $directorioPlantillas,
                $nombrePlantilla
            );

            $rutaPlantilla =
                $directorioPlantillas .
                DIRECTORY_SEPARATOR .
                $nombrePlantilla;

            /*
             * Registrar el procedimiento.
             */
            $procedimiento = Procedimiento::create([
                'id_tipo_procedimiento' =>
                    $datosValidados['id_tipo_procedimiento'],

                'id_persona' =>
                    $persona->id,

                'user_id' =>
                    Auth::id(),

                'nombre_procedimiento' =>
                    trim($datosValidados['nombre_procedimiento']),

                'num_procedimiento' =>
                    trim($datosValidados['num_procedimiento']),

                'fecha_publicacion' =>
                    $datosValidados['fecha_publicacion'],

                'fecha_vm' =>
                    $fechaVisita,

                'hora_vm' =>
                    $horaVisita,

                'fecha_ac' =>
                    $fechaAclaraciones,

                'hora_ac' =>
                    $horaAclaraciones,

                'fecha_apertura' =>
                    $datosValidados['fecha_apertura'],

                'hora_apertura' =>
                    $datosValidados['hora_apertura'],

                'fecha_fallo' =>
                    $datosValidados['fecha_fallo'],

                'hora_fallo' =>
                    $datosValidados['hora_fallo'],

                'fecha_inicio_contrato' =>
                    $datosValidados['fecha_inicio_contrato'],

                'fecha_fin_contrato' =>
                    $datosValidados['fecha_fin_contrato'],

                'monto_maximo' =>
                    $montoMaximo,
            ]);

            /*
             * Generar el documento Word.
             */
            $templateProcessor = new TemplateProcessor(
                $rutaPlantilla
            );

            $meses = $this->obtenerMeses();

            $horaVM = $this->formatearHora(
                $horaVisita
            );

            $horaACL = $this->formatearHora(
                $horaAclaraciones
            );

            $horaApertura = $this->formatearHora(
                $datosValidados['hora_apertura']
            );

            $horaFallo = $this->formatearHora(
                $datosValidados['hora_fallo']
            );

            /*
             * Visita a instalaciones.
             */
            if (
                $request->aplica_vm === 'SI' &&
                $fechaVisita &&
                $horaVisita
            ) {
                $fecha = Carbon::parse($fechaVisita);

                $textoVM =
                    "{$fecha->day} de " .
                    "{$meses[$fecha->month]} de " .
                    "{$fecha->year} a las {$horaVM}";
            } else {
                $textoVM = 'NO APLICA';
            }

            /*
             * Junta de aclaraciones.
             */
            if (
                $request->aplica_acl === 'SI' &&
                $fechaAclaraciones &&
                $horaAclaraciones
            ) {
                $fecha = Carbon::parse($fechaAclaraciones);

                $aclTexto =
                    "{$fecha->day} de " .
                    "{$meses[$fecha->month]} de " .
                    "{$fecha->year}, a las {$horaACL}";

                $aclTabla =
                    "{$fecha->day}-" .
                    "{$meses[$fecha->month]}-" .
                    "{$fecha->year}";
            } else {
                $aclTexto = 'NO APLICA';
                $aclTabla = 'NO APLICA';
            }

            /*
             * Apertura.
             */
            $fechaApertura = Carbon::parse(
                $datosValidados['fecha_apertura']
            );

            $aperturaTexto =
                "{$fechaApertura->day} de " .
                "{$meses[$fechaApertura->month]} de " .
                "{$fechaApertura->year}, a las {$horaApertura}";

            $aperturaTabla =
                "{$fechaApertura->day}-" .
                "{$meses[$fechaApertura->month]}-" .
                "{$fechaApertura->year}";

            /*
             * Fallo.
             */
            $fechaFallo = Carbon::parse(
                $datosValidados['fecha_fallo']
            );

            $falloTexto =
                "{$fechaFallo->day} de " .
                "{$meses[$fechaFallo->month]} de " .
                "{$fechaFallo->year}, a las {$horaFallo}";

            $falloTabla =
                "{$fechaFallo->day}-" .
                "{$meses[$fechaFallo->month]}-" .
                "{$fechaFallo->year}";

            /*
             * Vigencia del contrato.
             */
            $fechaInicio = Carbon::parse(
                $datosValidados['fecha_inicio_contrato']
            );

            $fechaFin = Carbon::parse(
                $datosValidados['fecha_fin_contrato']
            );

            $vigenciaTexto =
                "{$fechaInicio->day} de " .
                "{$meses[$fechaInicio->month]} del " .
                "{$fechaInicio->year} y hasta el " .
                "{$fechaFin->day} de " .
                "{$meses[$fechaFin->month]} del " .
                "{$fechaFin->year}";

            /*
             * Sustituir etiquetas de la plantilla Word.
             */
            $templateProcessor->setValue(
                'nombre_procedimiento',
                $this->valorWord(
                    $datosValidados['nombre_procedimiento']
                )
            );

            $templateProcessor->setValue(
                'num_procedimiento',
                $this->valorWord(
                    $datosValidados['num_procedimiento']
                )
            );

            $templateProcessor->setValue(
                'fecha_publicacion',
                $this->formatearFecha(
                    $datosValidados['fecha_publicacion'],
                    $meses
                )
            );

            $templateProcessor->setValue(
                'fecha_vm',
                $textoVM
            );

            $templateProcessor->setValue(
                'acl_texto',
                $aclTexto
            );

            $templateProcessor->setValue(
                'acl_tabla',
                $aclTabla
            );

            $templateProcessor->setValue(
                'apertura_texto',
                $aperturaTexto
            );

            $templateProcessor->setValue(
                'apertura_tabla',
                $aperturaTabla
            );

            $templateProcessor->setValue(
                'fallo_texto',
                $falloTexto
            );

            $templateProcessor->setValue(
                'fallo_tabla',
                $falloTabla
            );

            $templateProcessor->setValue(
                'hora_apertura',
                $horaApertura
            );

            $templateProcessor->setValue(
                'hora_fallo',
                $horaFallo
            );

            $templateProcessor->setValue(
                'resp_tecnico',
                $this->valorWord($persona->nombre)
            );

            $templateProcessor->setValue(
                'cargo_tecnico',
                $this->valorWord($persona->cargo)
            );

            $templateProcessor->setValue(
                'monto_maximo',
                $this->formatearMonto($montoMaximo)
            );

            $templateProcessor->setValue(
                'monto_minimo',
                $montoMinimo !== null
                    ? $this->formatearMonto($montoMinimo)
                    : ''
            );

            $templateProcessor->setValue(
                'num_partida',
                $this->valorWord(
                    $datosValidados['num_partida'] ?? ''
                )
            );

            $templateProcessor->setValue(
                'partida_nombre',
                $this->valorWord(
                    $datosValidados['partida_nombre'] ?? ''
                )
            );

            $templateProcessor->setValue(
                'num_requisicion',
                $this->valorWord(
                    $datosValidados['num_requisicion'] ?? ''
                )
            );

            $templateProcessor->setValue(
                'vigencia_contrato',
                $vigenciaTexto
            );

            /*
             * También se puede utilizar esta etiqueta en Word
             * para imprimir el nombre del tipo de procedimiento:
             *
             * ${tipo_procedimiento}
             */
            $tipoProcedimiento = TipoProcedimiento::find(
                $datosValidados['id_tipo_procedimiento']
            );

            $templateProcessor->setValue(
                'tipo_procedimiento',
                $tipoProcedimiento
                    ? $this->valorWord(
                        $tipoProcedimiento->nombre_tipo
                    )
                    : ''
            );

            /*
             * Guardar el documento generado.
             */
            $directorioDocumentos = storage_path(
                'app/public/documentos'
            );

            File::ensureDirectoryExists(
                $directorioDocumentos
            );

            $idProcedimiento = $procedimiento->getKey();

            $nombreDocumento =
                'procedimiento_' .
                $idProcedimiento .
                '.docx';

            $rutaDocumento =
                $directorioDocumentos .
                DIRECTORY_SEPARATOR .
                $nombreDocumento;

            $templateProcessor->saveAs(
                $rutaDocumento
            );

            $procedimiento->update([
                'ruta_documento' =>
                    'documentos/' . $nombreDocumento,
            ]);

            DB::commit();

            /*
             * La plantilla temporal ya no es necesaria.
             */
            if (
                $rutaPlantilla &&
                File::exists($rutaPlantilla)
            ) {
                File::delete($rutaPlantilla);
            }

            return response()->download(
                $rutaDocumento,
                $nombreDocumento
            );
        } catch (Throwable $e) {
            DB::rollBack();

            if (
                $rutaPlantilla &&
                File::exists($rutaPlantilla)
            ) {
                File::delete($rutaPlantilla);
            }

            if (
                $rutaDocumento &&
                File::exists($rutaDocumento)
            ) {
                File::delete($rutaDocumento);
            }

            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'No fue posible registrar el procedimiento o generar el documento Word. Revisa la plantilla e inténtalo nuevamente.'
                );
        }
    }

    /**
     * Muestra el resultado de un procedimiento.
     */
    public function show($id)
    {
        $procedimiento = Procedimiento::with([
            'tipo',
            'persona',
            'comprador',
        ])->findOrFail($id);

        return view(
            'comprador.convo.resultado',
            compact('procedimiento')
        );
    }

    /**
     * Descarga el documento de un procedimiento.
     */
    public function descargar($id)
    {
        $procedimiento = Procedimiento::findOrFail($id);

        if (empty($procedimiento->ruta_documento)) {
            return back()->with(
                'error',
                'Este procedimiento no tiene un documento generado.'
            );
        }

        $rutaDocumento = storage_path(
            'app/public/' . $procedimiento->ruta_documento
        );

        if (!File::exists($rutaDocumento)) {
            return back()->with(
                'error',
                'El archivo solicitado no existe en el almacenamiento.'
            );
        }

        return response()->download($rutaDocumento);
    }

    /**
     * Muestra todos los procedimientos en el módulo administrativo.
     */
    public function procedi()
    {
        $procedimientos = Procedimiento::with([
            'tipo',
            'persona',
            'comprador',
        ])
            ->orderByDesc('created_at')
            ->get();

        return view(
            'admin.procedimientos.procedi',
            compact('procedimientos')
        );
    }

    /**
     * Convierte un monto recibido desde el formulario
     * en un valor decimal utilizable por MySQL.
     */
    private function limpiarMonto($valor): ?float
    {
        if ($valor === null || trim((string) $valor) === '') {
            return null;
        }

        $valor = trim((string) $valor);

        $valor = str_replace(
            ['$', ' ', "\xc2\xa0"],
            '',
            $valor
        );

        /*
         * Formato europeo:
         * 1.234.567,89
         */
        if (preg_match('/^-?\d{1,3}(\.\d{3})*,\d{1,2}$/', $valor)) {
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
        } else {
            /*
             * Formato habitual:
             * 1,234,567.89
             */
            $valor = str_replace(',', '', $valor);
        }

        if (!is_numeric($valor)) {
            return null;
        }

        return round((float) $valor, 2);
    }

    /**
     * Formatea un monto para colocarlo en la plantilla Word.
     */
    private function formatearMonto($valor): string
    {
        if ($valor === null || $valor === '') {
            return '0.00';
        }

        return number_format(
            (float) $valor,
            2,
            '.',
            ','
        );
    }

    /**
     * Formatea una hora como "10:00 HORAS".
     */
    private function formatearHora($hora): string
    {
        if (!$hora) {
            return '';
        }

        return strtoupper(
            Carbon::createFromFormat('H:i', substr($hora, 0, 5))
                ->format('H:i') .
            ' horas'
        );
    }

    /**
     * Formatea una fecha para mostrarla en Word.
     */
    private function formatearFecha(
        $fecha,
        array $meses
    ): string {
        if (!$fecha) {
            return '';
        }

        $fechaCarbon = Carbon::parse($fecha);

        return
            "{$fechaCarbon->day} de " .
            "{$meses[$fechaCarbon->month]} de " .
            "{$fechaCarbon->year}";
    }

    /**
     * Evita valores nulos en TemplateProcessor.
     */
    private function valorWord($valor): string
    {
        return $valor !== null
            ? trim((string) $valor)
            : '';
    }

    /**
     * Devuelve los meses utilizados en los documentos.
     */
    private function obtenerMeses(): array
    {
        return [
            1  => 'Enero',
            2  => 'Febrero',
            3  => 'Marzo',
            4  => 'Abril',
            5  => 'Mayo',
            6  => 'Junio',
            7  => 'Julio',
            8  => 'Agosto',
            9  => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];
    }
}