<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Persona;
use App\Models\Procedimiento;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\TemplateProcessor;
use Throwable;

class AcPreguntaController extends Controller
{
    /**
     * Muestra el formulario para generar el acta de aclaraciones.
     */
    public function index()
    {
        $areasContrato = Area::with([
            'personas' => function ($query) {
                $query->orderBy('nombre');
            },
        ])
            ->whereIn('nombre', [
                'Gerencia',
                'Subgerencia de Operaciones',
                'Subgerencia de Abasto',
            ])
            ->orderBy('nombre')
            ->get();

        /*
         * Sólo se muestran personas pertenecientes a:
         * Coordinación General de Adquisiciones y Servicios.
         */
        $areaContratanteId = Area::where(
            'nombre',
            'Coordinación General de Adquisiciones y Servicios'
        )->value('id_area');

        $personasContratante = $areaContratanteId
            ? Persona::where('area_id', $areaContratanteId)
                ->orderBy('nombre')
                ->get()
            : collect();

        $personasOic = Persona::where('area_id', 14)
            ->orderBy('nombre')
            ->get();

        $personasJuridico = Persona::where('area_id', 15)
            ->orderBy('nombre')
            ->get();

        return view('comprador.aclaracion.ac_pregunta', compact(
            'areasContrato',
            'personasContratante',
            'personasOic',
            'personasJuridico'
        ));
    }

    /**
     * Busca un procedimiento y devuelve sus datos para autocompletar el formulario.
     */
    public function buscarProcedimiento($valor)
    {
        $valor = trim((string) $valor);

        if ($valor === '') {
            return response()->json(null);
        }

        $procedimiento = $this->consultarProcedimiento($valor);

        if (!$procedimiento) {
            return response()->json(null);
        }

        $personaRequirente = $procedimiento->id_persona
            ? Persona::find($procedimiento->id_persona)
            : null;

        $areaRequirente = $personaRequirente
            ? Area::find($personaRequirente->area_id)
            : null;

        return response()->json([
            'num_procedimiento' =>
                $procedimiento->num_procedimiento,

            'nombre_procedimiento' =>
                $procedimiento->nombre_procedimiento,

            'fecha_ac' =>
                $this->formatearFechaInput(
                    $procedimiento->fecha_ac
                ),

            'hora_ac' =>
                $this->formatearHoraInput(
                    $procedimiento->hora_ac
                ),

            'fecha_apertura' =>
                $this->formatearFechaInput(
                    $procedimiento->fecha_apertura
                ),

            'hora_apertura' =>
                $this->formatearHoraInput(
                    $procedimiento->hora_apertura
                ),

            'area_requirente_id' =>
                $personaRequirente?->id,

            'area_requirente_nombre' =>
                $personaRequirente
                    ? trim(
                        $personaRequirente->nombre
                        . (
                            $personaRequirente->cargo
                                ? ' - ' . $personaRequirente->cargo
                                : ''
                        )
                    )
                    : '',

            'plantilla_referencia_requirente' =>
                $personaRequirente?->plantilla_referencia ?? '',

            'area_requirente_area' =>
                $areaRequirente?->nombre ?? '',
        ]);
    }

    /**
     * Genera el documento Word a partir de la plantilla proporcionada.
     */
    public function generar(Request $request)
    {
        $datosValidados = $request->validate(
            $this->reglasValidacion(),
            $this->mensajesValidacion(),
            $this->atributosValidacion()
        );

        $numeroBusqueda = trim(
            (string) $datosValidados['numero_busqueda']
        );

        $procedimiento = $this->consultarProcedimiento(
            $numeroBusqueda
        );

        if (!$procedimiento) {
            return back()
                ->withInput()
                ->withErrors([
                    'numero_busqueda' =>
                        'No existe un procedimiento registrado con ese número.',
                ]);
        }

        /*
         * El área requirente se obtiene automáticamente de
         * procedimientos.id_persona.
         */
        $personaRequirente = $procedimiento->id_persona
            ? Persona::find($procedimiento->id_persona)
            : null;

        if (!$personaRequirente) {
            return back()
                ->withInput()
                ->withErrors([
                    'numero_busqueda' =>
                        'El procedimiento no tiene una persona válida registrada en id_persona.',
                ]);
        }

        $request->merge([
            'area_requirente' => $personaRequirente->id,
            'area_requirente_nombre' => trim(
                $personaRequirente->nombre
                . (
                    $personaRequirente->cargo
                        ? ' - ' . $personaRequirente->cargo
                        : ''
                )
            ),
        ]);

        /*
         * Toda la información necesaria se prepara antes de mover
         * la plantilla. Así, los errores de captura regresan al
         * formulario sin dejar archivos temporales.
         */
        $datosDocumento = $this->prepararDatosDocumento(
            $request,
            $datosValidados,
            $procedimiento
        );

        $templatePath = null;
        $outputPath = null;

        try {
            Carbon::setLocale('es');

            $templatePath = $this->guardarPlantillaTemporal(
                $request
            );

            $templateProcessor = new TemplateProcessor(
                $templatePath
            );

            $this->llenarPlantilla(
                $templateProcessor,
                $datosDocumento
            );

            [
                'path' => $outputPath,
                'name' => $outputName,
            ] = $this->guardarDocumentoGenerado(
                $templateProcessor
            );

            $this->eliminarArchivo($templatePath);
            $templatePath = null;

            return response()
                ->download(
                    $outputPath,
                    $outputName
                )
                ->deleteFileAfterSend(true);
        } catch (Throwable $e) {
            $this->eliminarArchivo($outputPath);

            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'No fue posible generar el documento Word. Revisa la plantilla y vuelve a intentarlo.'
                );
        } finally {
            $this->eliminarArchivo($templatePath);
        }
    }

    /**
     * Prepara toda la información que será enviada a la plantilla.
     */
    private function prepararDatosDocumento(
        Request $request,
        array $datosValidados,
        Procedimiento $procedimiento
    ): array {
        $datosProcedimiento = $this->resolverDatosProcedimiento(
            $request,
            $procedimiento
        );

        $personas = $this->resolverPersonasDocumento(
            $request
        );

        $areaReq = $personas['area_requirente'];
        $areaCont = $personas['area_contratante'];
        $admiContrato = $personas['administrador'];
        $oic = $personas['oic'];
        $juridico = $personas['juridico'];

        $nombresAreas = $this->obtenerNombresAreas([
            $areaReq->area_id,
            $admiContrato->area_id,
        ]);

        $participantes = $this->prepararParticipantes(
            $request->input('participantes', [])
        );

        $licitantes = $this->prepararLicitantesConPreguntas(
            $participantes,
            $request->input('preguntas', [])
        );

        $totalPreguntas = array_sum(
            array_map(
                static fn (array $licitante): int =>
                    count($licitante['preguntas']),
                $licitantes
            )
        );

        return [
            'num_procedimiento' =>
                $datosProcedimiento['numero'],

            'nombre_procedimiento' =>
                $datosProcedimiento['nombre'],

            'fecha_ac' =>
                $datosProcedimiento['fecha_ac_texto'],

            'hora_inicio' =>
                $datosProcedimiento['hora_inicio'],

            'hora_cierre' =>
                $datosProcedimiento['hora_cierre'],

            'hora_reanudacion' =>
                $datosProcedimiento['hora_reanudacion'],

            'fecha_apertura' =>
                $this->formatearFechaHoraApertura(
                    $procedimiento
                ),

            'oficio_preguntas' =>
                trim((string) $datosValidados['oficio_preguntas']),

            'fecha_oficio_preguntas' =>
                $this->formatearFechaTexto(
                    Carbon::parse(
                        $datosValidados['fecha_oficio_preguntas']
                    )
                ),

            'oficio_respuestas' =>
                trim((string) $datosValidados['oficio_respuestas']),

            'fecha_oficio_respuestas' =>
                $this->formatearFechaTexto(
                    Carbon::parse(
                        $datosValidados['fecha_oficio_respuestas']
                    )
                ),

            'area_requirente' =>
                $areaReq,

            'area_contratante' =>
                $areaCont,

            'administrador' =>
                $admiContrato,

            'oic' =>
                $oic,

            'juridico' =>
                $juridico,

            'area_requirente_nombre' =>
                $nombresAreas->get(
                    $areaReq->area_id,
                    ''
                ),

            'area_administrador_nombre' =>
                $nombresAreas->get(
                    $admiContrato->area_id,
                    ''
                ),

            'ref_oic' =>
                $request->input('ref_oic', ''),

            'ref_juridico' =>
                $request->input('ref_juridico', ''),

            'participantes' =>
                $participantes,

            'licitantes' =>
                $licitantes,

            'texto_total_preguntas' =>
                $this->crearTextoTotalPreguntas(
                    $totalPreguntas
                ),

            'solicitudes' =>
                $this->crearTextoSolicitudes(
                    count($licitantes)
                ),

            'usuario' =>
                Auth::user(),
        ];
    }

    /**
     * Resuelve los datos editables del procedimiento.
     *
     * Los valores capturados en el formulario tienen prioridad.
     * Cuando llegan vacíos se usan los datos de la base como respaldo.
     */
    private function resolverDatosProcedimiento(
        Request $request,
        Procedimiento $procedimiento
    ): array {
        $numero = $request->filled('num_procedimiento')
            ? trim((string) $request->num_procedimiento)
            : trim((string) $procedimiento->num_procedimiento);

        $nombre = $request->filled('nombre_procedimiento')
            ? trim((string) $request->nombre_procedimiento)
            : trim((string) $procedimiento->nombre_procedimiento);

        $fechaAc = $request->filled('fecha_ac')
            ? $request->fecha_ac
            : $procedimiento->fecha_ac;

        $horaAc = $request->filled('hora_ac')
            ? $request->hora_ac
            : $procedimiento->hora_ac;

        $errores = [];

        if ($numero === '') {
            $errores['num_procedimiento'] =
                'Debe capturar el número completo del procedimiento.';
        }

        if ($nombre === '') {
            $errores['nombre_procedimiento'] =
                'Debe capturar el nombre del procedimiento.';
        }

        if (!$fechaAc) {
            $errores['fecha_ac'] =
                'Debe capturar la fecha de la junta de aclaraciones.';
        }

        if (!$horaAc) {
            $errores['hora_ac'] =
                'Debe capturar la hora de la junta de aclaraciones.';
        }

        if ($errores) {
            throw ValidationException::withMessages(
                $errores
            );
        }

        $fecha = Carbon::parse($fechaAc);
        $horaInicio = Carbon::parse($horaAc);
        $horaCierre = $horaInicio->copy()->addMinutes(30);
        $horaReanudacion = $horaCierre->copy()->addHours(6);
        $fechaTexto = $this->formatearFechaTexto($fecha);

        return [
            'numero' =>
                $numero,

            'nombre' =>
                $nombre,

            'fecha_ac_texto' =>
                $fechaTexto,

            'hora_inicio' =>
                $horaInicio->format('H:i'),

            'hora_cierre' =>
                $horaCierre->format('H:i') . ' horas',

            'hora_reanudacion' =>
                $horaReanudacion->format('H:i')
                . ' horas del día '
                . $fechaTexto,
        ];
    }

    /**
     * Obtiene y valida las personas seleccionadas con una sola consulta.
     */
    private function resolverPersonasDocumento(
        Request $request
    ): array {
        $personas = $this->obtenerPersonasSeleccionadas(
            $request
        );

        $areaReq = $personas->get(
            (int) $request->area_requirente
        );

        $areaCont = $personas->get(
            (int) $request->area_contratante
        );

        $administrador = $personas->get(
            (int) $request->admi_contrato
        );

        $errores = [];

        if (!$areaReq) {
            $errores['area_requirente'] =
                'No fue posible obtener la persona requirente registrada en el procedimiento.';
        }

        if (!$areaCont) {
            $errores['area_contratante'] =
                'No fue posible obtener la persona del área contratante.';
        }

        if (!$administrador) {
            $errores['admi_contrato'] =
                'No fue posible obtener al administrador del contrato.';
        }

        if ($errores) {
            throw ValidationException::withMessages($errores);
        }

        return [
            'area_requirente' =>
                $areaReq,

            'area_contratante' =>
                $areaCont,

            'administrador' =>
                $administrador,

            'oic' =>
                $request->filled('persona_oic')
                    ? $personas->get(
                        (int) $request->persona_oic
                    )
                    : null,

            'juridico' =>
                $request->filled('persona_juridico')
                    ? $personas->get(
                        (int) $request->persona_juridico
                    )
                    : null,
        ];
    }

    /**
     * Coloca todos los datos en la plantilla Word.
     */
    private function llenarPlantilla(
        TemplateProcessor $template,
        array $datos
    ): void {
        $this->llenarValoresSimples(
            $template,
            $datos
        );

        $this->llenarPersonasYFirmas(
            $template,
            $datos
        );

        $this->clonarTablaParticipantes(
            $template,
            $datos['participantes']
        );

        $this->clonarResumenPreguntas(
            $template,
            $datos['licitantes']
        );

        if ($datos['licitantes']) {
            $template->setComplexBlock(
                'bloque_preguntas',
                $this->crearTablaPreguntas(
                    $datos['licitantes']
                )
            );
        } else {
            $template->setValue(
                'bloque_preguntas',
                ''
            );
        }
    }

    /**
     * Coloca los valores de texto simple en la plantilla.
     */
    private function llenarValoresSimples(
        TemplateProcessor $template,
        array $datos
    ): void {
        $valores = [
            'num_procedimiento' =>
                $datos['num_procedimiento'],

            'nombre_procedimiento' =>
                $datos['nombre_procedimiento'],

            'hora_inicio' =>
                $datos['hora_inicio'],

            'fecha_ac' =>
                $datos['fecha_ac'],

            'hora_cierre' =>
                $datos['hora_cierre'],

            'hora_reanudacion' =>
                $datos['hora_reanudacion'],

            'fecha_apertura' =>
                $datos['fecha_apertura'],

            'oficio_preguntas' =>
                $datos['oficio_preguntas'],

            'fecha_oficio_preguntas' =>
                $datos['fecha_oficio_preguntas'],

            'oficio_respuestas' =>
                $datos['oficio_respuestas'],

            'fecha_oficio_respuestas' =>
                $datos['fecha_oficio_respuestas'],

            'texto_total_preguntas' =>
                $datos['texto_total_preguntas'],

            'area_area_requirente' =>
                $datos['area_requirente_nombre'],

            'area_admi_contrato' =>
                $datos['area_administrador_nombre'],

            'ref_oic' =>
                $datos['ref_oic'],

            'ref_juridico' =>
                $datos['ref_juridico'],

            'solicitudes' =>
                $datos['solicitudes'],
        ];

        foreach ($valores as $marcador => $valor) {
            $template->setValue(
                $marcador,
                $this->limpiarTexto($valor)
            );
        }
    }

    /**
     * Coloca personas y firmas en la plantilla.
     */
    private function llenarPersonasYFirmas(
        TemplateProcessor $template,
        array $datos
    ): void {
        $usuario = $datos['usuario'];

        $template->setComplexValue(
            'area_requirente',
            $this->crearTextoPersona(
                $datos['area_requirente']
            )
        );

        $template->setComplexValue(
            'area_contratante',
            $this->crearTextoPersona(
                $datos['area_contratante']
            )
        );

        $template->setComplexValue(
            'admi_contrato',
            $this->crearTextoPersona(
                $datos['administrador']
            )
        );

        $template->setComplexValue(
            'persona_oic',
            $this->crearTextoPersona(
                $datos['oic']
            )
        );

        $template->setComplexValue(
            'persona_juridico',
            $this->crearTextoPersona(
                $datos['juridico']
            )
        );

        $template->setComplexValue(
            'elaboro',
            $this->crearTextoElaboro(
                $usuario
            )
        );

        $template->setComplexValue(
            'comprador',
            $this->crearTextoComprador(
                $usuario
            )
        );

        $template->setComplexValue(
            'admi_contrato_tabla',
            $this->crearTextoPersona(
                $datos['administrador']
            )
        );

        $template->setComplexValue(
            'area_requirente_tabla',
            $this->crearTextoPersona(
                $datos['area_requirente']
            )
        );

        $template->setComplexValue(
            'comprador_tabla',
            $this->crearTextoComprador(
                $usuario
            )
        );

        $template->setComplexValue(
            'persona_oic_tabla',
            $this->crearTextoPersona(
                $datos['oic']
            )
        );

        $template->setComplexValue(
            'persona_juridico_tabla',
            $this->crearTextoPersona(
                $datos['juridico']
            )
        );
    }

    /**
     * Guarda el documento generado y devuelve su ruta y nombre.
     */
    private function guardarDocumentoGenerado(
        TemplateProcessor $template
    ): array {
        $directorio = storage_path(
            'app/public/documentos'
        );

        File::ensureDirectoryExists(
            $directorio
        );

        $nombre =
            'ac_pregunta_'
            . now()->format('Ymd_His_u')
            . '.docx';

        $ruta =
            $directorio
            . DIRECTORY_SEPARATOR
            . $nombre;

        $template->saveAs(
            $ruta
        );

        return [
            'path' => $ruta,
            'name' => $nombre,
        ];
    }

    /**
     * Elimina un archivo cuando existe.
     */
    private function eliminarArchivo(
        ?string $ruta
    ): void {
        if (
            $ruta &&
            File::exists($ruta)
        ) {
            File::delete($ruta);
        }
    }

    /**
     * Reglas de validación del formulario.
     */
    private function reglasValidacion(): array
    {
        return [
            'numero_busqueda'          => ['required', 'string', 'max:100'],
            'num_procedimiento'        => ['nullable', 'string', 'max:255'],
            'nombre_procedimiento'     => ['nullable', 'string', 'max:1000'],
            'fecha_ac'                 => ['nullable', 'date'],
            'hora_ac'                  => ['nullable', 'date_format:H:i'],

            'area_requirente'          => ['nullable', 'integer', 'exists:personas,id'],
            'area_contratante'         => ['required', 'integer', 'exists:personas,id'],
            'admi_contrato'            => ['required', 'integer', 'exists:personas,id'],

            'oficio_preguntas'         => ['required', 'string', 'max:255'],
            'oficio_respuestas'        => ['required', 'string', 'max:255'],
            'fecha_oficio_preguntas'   => ['required', 'date'],
            'fecha_oficio_respuestas'  => ['required', 'date'],

            'persona_oic'              => ['nullable', 'integer', 'exists:personas,id'],
            'persona_juridico'         => ['nullable', 'integer', 'exists:personas,id'],
            'ref_oic'                  => ['nullable', 'string', 'max:255'],
            'ref_juridico'             => ['nullable', 'string', 'max:255'],

            'participantes'                         => ['nullable', 'array'],
            'participantes.*.nombre'                => ['nullable', 'string', 'max:255'],
            'participantes.*.pregunta'              => ['nullable', 'string', 'max:100'],
            'participantes.*.preguntas'             => ['nullable', 'array'],
            'participantes.*.preguntas.*.pregunta'  => ['nullable', 'string'],
            'participantes.*.preguntas.*.respuesta' => ['nullable', 'string'],

            'preguntas'               => ['nullable', 'array'],
            'preguntas.*.pregunta'    => ['nullable', 'string'],
            'preguntas.*.respuesta'   => ['nullable', 'string'],

            'archivo_word'            => ['required', 'file', 'mimes:docx', 'max:10240'],
        ];
    }

    /**
     * Mensajes de validación en español.
     */
    private function mensajesValidacion(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe contener texto válido.',
            'integer' => 'El campo :attribute debe contener un número entero.',
            'min' => 'El campo :attribute debe ser igual o mayor que :min.',
            'max' => 'El campo :attribute no debe exceder el límite permitido.',
            'date' => 'El campo :attribute no contiene una fecha válida.',
            'date_format' => 'El campo :attribute debe tener el formato HH:MM.',
            'array' => 'El campo :attribute tiene un formato incorrecto.',
            'exists' => 'El valor seleccionado para :attribute no existe.',
            'file' => 'Debe seleccionar un archivo válido para :attribute.',
            'mimes' => 'La plantilla debe ser un archivo Word con extensión .docx.',

            'numero_busqueda.required' => 'Debe buscar primero un procedimiento.',

                        'area_contratante.required' => 'Seleccione a la persona representante del área contratante.',
            'admi_contrato.required' => 'Seleccione al administrador del contrato.',

            'oficio_preguntas.required' => 'Ingrese la referencia del oficio de preguntas.',
            'oficio_preguntas.string' => 'La referencia del oficio de preguntas debe contener texto válido.',
            'oficio_preguntas.max' => 'La referencia del oficio de preguntas no debe exceder 255 caracteres.',

            'oficio_respuestas.required' => 'Ingrese la referencia del oficio de respuestas.',
            'oficio_respuestas.string' => 'La referencia del oficio de respuestas debe contener texto válido.',
            'oficio_respuestas.max' => 'La referencia del oficio de respuestas no debe exceder 255 caracteres.',
            'fecha_oficio_preguntas.required' => 'Seleccione la fecha del oficio de preguntas.',
            'fecha_oficio_respuestas.required' => 'Seleccione la fecha del oficio de respuestas.',

            'persona_oic.exists' => 'El representante del OIC seleccionado no existe.',
            'persona_juridico.exists' => 'El representante jurídico seleccionado no existe.',

            'archivo_word.required' => 'Debe seleccionar una plantilla de Word.',
            'archivo_word.file' => 'La plantilla seleccionada no es un archivo válido.',
            'archivo_word.mimes' => 'La plantilla debe ser un archivo Word con extensión .docx.',
            'archivo_word.max' => 'La plantilla de Word no debe superar los 10 MB.',
        ];
    }

    /**
     * Nombres amigables para los campos de validación.
     */
    private function atributosValidacion(): array
    {
        return [
            'numero_busqueda' => 'número de búsqueda del procedimiento',
            'num_procedimiento' => 'número completo del procedimiento',
            'nombre_procedimiento' => 'nombre del procedimiento',
            'fecha_ac' => 'fecha de la junta de aclaraciones',
            'hora_ac' => 'hora de la junta de aclaraciones',
            'area_requirente' => 'área requirente',
            'area_contratante' => 'área contratante',
            'admi_contrato' => 'administrador del contrato',
            'oficio_preguntas' => 'referencia del oficio de preguntas',
            'oficio_respuestas' => 'referencia del oficio de respuestas',
            'fecha_oficio_preguntas' => 'fecha del oficio de preguntas',
            'fecha_oficio_respuestas' => 'fecha del oficio de respuestas',
            'persona_oic' => 'representante del OIC',
            'persona_juridico' => 'representante jurídico',
            'ref_oic' => 'referencia del OIC',
            'ref_juridico' => 'referencia jurídica',
            'participantes' => 'participantes',
            'preguntas' => 'preguntas',
            'archivo_word' => 'plantilla de Word',
        ];
    }

    /**
     * Consulta el procedimiento por la parte numérica utilizada en el formulario.
     */
    private function consultarProcedimiento(string $numero): ?Procedimiento
    {
        return Procedimiento::where(
            'num_procedimiento',
            'LIKE',
            '%-N-' . trim($numero) . '-%'
        )->first();
    }

    /**
     * Guarda la plantilla en una ubicación temporal controlada.
     */
    private function guardarPlantillaTemporal(Request $request): string
    {
        $templateDir = storage_path('app/plantillas');
        File::ensureDirectoryExists($templateDir);

        $filename = uniqid('plantilla_', true) . '.docx';
        $request->file('archivo_word')->move($templateDir, $filename);

        return $templateDir . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * Obtiene las personas seleccionadas mediante una sola consulta.
     */
    private function obtenerPersonasSeleccionadas(Request $request)
    {
        $ids = array_values(array_unique(array_filter([
            $request->area_requirente,
            $request->area_contratante,
            $request->admi_contrato,
            $request->persona_oic,
            $request->persona_juridico,
        ], static fn ($id): bool => $id !== null && $id !== '')));

        return Persona::whereIn('id', $ids)
            ->get()
            ->keyBy('id');
    }

    /**
     * Obtiene los nombres de las áreas mediante una sola consulta.
     */
    private function obtenerNombresAreas(array $ids)
    {
        $ids = array_values(array_unique(array_filter($ids)));

        return Area::whereIn('id_area', $ids)
            ->pluck('nombre', 'id_area');
    }

    /**
     * Crea el texto enriquecido para una persona.
     */
    private function crearTextoPersona($persona): TextRun
    {
        $textRun = new TextRun();

        if (!$persona) {
            return $textRun;
        }

        $nombre = trim((string) $persona->nombre);
        $cargo = trim((string) $persona->cargo);

        if ($nombre !== '') {
            $textRun->addText($nombre, [
                'name' => 'Noto Sans',
                'size' => 10,
                'bold' => true,
            ]);
        }

        if ($cargo !== '') {
            $textRun->addText(($nombre !== '' ? ', ' : '') . $cargo, [
                'name' => 'Noto Sans',
                'size' => 10,
                'bold' => false,
            ]);
        }

        return $textRun;
    }

    /**
     * Crea el texto enriquecido del comprador conservando el estilo previo.
     */
    private function crearTextoComprador($usuario): TextRun
    {
        $textRun = new TextRun();

        if (!$usuario) {
            return $textRun;
        }

        $nombre = trim((string) $usuario->name);
        $cargo = trim((string) $usuario->cargo);

        if ($nombre !== '') {
            $textRun->addText($nombre, [
                'name' => 'Noto Sans',
                'size' => 10,
                'bold' => true,
            ]);
        }

        if ($cargo !== '') {
            $textRun->addText(($nombre !== '' ? ' / ' : '') . $cargo, [
                'name' => 'Noto Sans',
                'size' => 10,
                'bold' => false,
            ]);
        }

        return $textRun;
    }

    /**
     * Genera ${elaboro} con el formato: NOMBRE.- CARGO.
     */
    private function crearTextoElaboro($usuario): TextRun
    {
        $textRun = new TextRun();

        if (!$usuario) {
            return $textRun;
        }

        $nombre = mb_strtoupper(trim((string) $usuario->name), 'UTF-8');
        $cargo = mb_strtoupper(trim((string) $usuario->cargo), 'UTF-8');

        if ($nombre === '') {
            return $textRun;
        }

        $texto = $nombre;

        if ($cargo !== '') {
            $texto .= '.- ' . $cargo;
        }

        $textRun->addText($texto, [
            'name' => 'Noto Sans',
            'size' => 10,
            'bold' => true,
        ]);

        return $textRun;
    }

    /**
     * Determina si el participante indicó que presentó preguntas.
     */
    private function normalizarSiPresento($valor): bool
    {
        $valor = mb_strtoupper(trim((string) ($valor ?? 'NO')), 'UTF-8');
        $valor = strtr($valor, [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
        ]);

        return in_array($valor, [
            'SI',
            'SI PRESENTO',
            'SI PRESENTO PREGUNTAS',
        ], true);
    }

    /**
     * Elimina participantes vacíos y normaliza sus datos.
     */
    private function prepararParticipantes(array $participantes): array
    {
        $resultado = [];

        foreach ($participantes as $participante) {
            if (!is_array($participante)) {
                continue;
            }

            $nombre = trim((string) ($participante['nombre'] ?? ''));

            if ($nombre === '') {
                continue;
            }

            $participante['nombre'] = $nombre;
            $resultado[] = $participante;
        }

        return $resultado;
    }

    /**
     * Construye la lista de licitantes que sí presentaron preguntas.
     */
    private function prepararLicitantesConPreguntas(
        array $participantes,
        array $preguntasGenerales
    ): array {
        $resultado = [];
        $preguntasGeneralesUsadas = false;

        $preguntasGenerales = $this->filtrarPreguntas($preguntasGenerales);

        foreach ($participantes as $participante) {
            if (!$this->normalizarSiPresento($participante['pregunta'] ?? 'NO')) {
                continue;
            }

            $preguntas = $this->filtrarPreguntas($participante['preguntas'] ?? []);

            if (!$preguntas && !$preguntasGeneralesUsadas && $preguntasGenerales) {
                $preguntas = $preguntasGenerales;
                $preguntasGeneralesUsadas = true;
            }

            if (!$preguntas) {
                continue;
            }

            $resultado[] = [
                'empresa' => trim((string) $participante['nombre']),
                'preguntas' => $preguntas,
            ];
        }

        return $resultado;
    }

    /**
     * Filtra preguntas vacías y normaliza sus textos.
     */
    private function filtrarPreguntas(array $preguntas): array
    {
        $resultado = [];

        foreach ($preguntas as $pregunta) {
            if (!is_array($pregunta)) {
                continue;
            }

            $textoPregunta = trim((string) ($pregunta['pregunta'] ?? ''));

            if ($textoPregunta === '') {
                continue;
            }

            $resultado[] = [
                'pregunta' => $textoPregunta,
                'respuesta' => trim((string) ($pregunta['respuesta'] ?? '')),
            ];
        }

        return $resultado;
    }

    /**
     * Clona la tabla de empresas interesadas.
     */
    private function clonarTablaParticipantes(
        TemplateProcessor $templateProcessor,
        array $participantes
    ): void {
        if (!$participantes) {
            $templateProcessor->setValue(
                'empresa_interes',
                ''
            );
            $templateProcessor->setValue(
                'presento_preguntas',
                ''
            );

            return;
        }

        $templateProcessor->cloneRow('empresa_interes', count($participantes));

        foreach ($participantes as $indice => $participante) {
            $fila = $indice + 1;
            $presento = $this->normalizarSiPresento($participante['pregunta'] ?? 'NO')
                ? 'SÍ PRESENTÓ'
                : 'NO PRESENTÓ';

            $templateProcessor->setValue(
                "empresa_interes#{$fila}",
                $this->limpiarTexto($participante['nombre'])
            );
            $templateProcessor->setValue("presento_preguntas#{$fila}", $presento);
        }
    }

    /**
     * Clona la tabla resumen de preguntas por empresa.
     */
    private function clonarResumenPreguntas(
        TemplateProcessor $templateProcessor,
        array $licitantes
    ): void {
        if (!$licitantes) {
            $templateProcessor->setValue(
                'empresa_resumen',
                ''
            );
            $templateProcessor->setValue(
                'numero_preguntas',
                ''
            );

            return;
        }

        $templateProcessor->cloneRow('empresa_resumen', count($licitantes));

        foreach ($licitantes as $indice => $licitante) {
            $fila = $indice + 1;

            $templateProcessor->setValue(
                "empresa_resumen#{$fila}",
                $this->limpiarTexto($licitante['empresa'])
            );
            $templateProcessor->setValue(
                "numero_preguntas#{$fila}",
                (string) count($licitante['preguntas'])
            );
        }
    }

    /**
     * Crea la tabla dinámica de preguntas y respuestas.
     */
    private function crearTablaPreguntas(array $licitantes): Table
    {
        $tabla = new Table([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
        ]);

        foreach ($licitantes as $licitante) {
            $tabla->addRow();
            $tabla->addCell(9400, [
                'gridSpan' => 3,
                'bgColor' => 'D9D9D9',
            ])->addText(
                mb_strtoupper($this->limpiarTexto($licitante['empresa']), 'UTF-8'),
                [
                    'name' => 'Noto Sans',
                    'size' => 10,
                    'bold' => true,
                ]
            );

            $tabla->addRow();
            $this->agregarEncabezadoTabla($tabla, 800, 'No.');
            $this->agregarEncabezadoTabla($tabla, 4300, 'Preguntas');
            $this->agregarEncabezadoTabla($tabla, 4300, 'Respuestas');

            foreach ($licitante['preguntas'] as $indice => $pregunta) {
                $tabla->addRow();

                $tabla->addCell(800)->addText((string) ($indice + 1), [
                    'name' => 'Noto Sans',
                    'size' => 10,
                ]);

                $tabla->addCell(4300)->addText(
                    $this->limpiarTexto($pregunta['pregunta']),
                    [
                        'name' => 'Noto Sans',
                        'size' => 10,
                    ]
                );

                $tabla->addCell(4300)->addText(
                    mb_strtoupper($this->limpiarTexto($pregunta['respuesta'] ?? ''), 'UTF-8'),
                    [
                        'name' => 'Noto Sans',
                        'size' => 10,
                    ]
                );
            }
        }

        return $tabla;
    }

    /**
     * Agrega una celda de encabezado a la tabla de preguntas.
     */
    private function agregarEncabezadoTabla(Table $tabla, int $ancho, string $texto): void
    {
        $tabla->addCell($ancho, ['bgColor' => 'D9D9D9'])
            ->addText($texto, [
                'name' => 'Noto Sans',
                'size' => 9,
                'bold' => true,
            ]);
    }

    /**
     * Genera el texto de cantidad total de preguntas.
     */
    private function crearTextoTotalPreguntas(int $total): string
    {
        if ($total === 1) {
            return 'La única pregunta recibida';
        }

        return sprintf(
            'Las %d (%s) preguntas recibidas',
            $total,
            $this->numeroEnTexto($total, false)
        );
    }

    /**
     * Genera el texto de solicitudes recibidas.
     */
    private function crearTextoSolicitudes(int $total): string
    {
        $numeroTexto = $this->numeroEnTexto($total, true);

        if ($total === 1) {
            return "se recibió {$total} ({$numeroTexto}) solicitud";
        }

        return "se recibieron {$total} ({$numeroTexto}) solicitudes";
    }

    /**
     * Convierte números frecuentes a texto.
     */
    private function numeroEnTexto(int $numero, bool $mayusculas): string
    {
        $numeros = [
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
            10 => 'diez',
        ];

        $texto = $numeros[$numero] ?? (string) $numero;

        return $mayusculas
            ? mb_strtoupper($texto, 'UTF-8')
            : $texto;
    }

    /**
     * Formatea una fecha en español para el documento.
     */
    private function formatearFechaTexto(Carbon $fecha): string
    {
        $meses = [
            1 => 'enero',
            2 => 'febrero',
            3 => 'marzo',
            4 => 'abril',
            5 => 'mayo',
            6 => 'junio',
            7 => 'julio',
            8 => 'agosto',
            9 => 'septiembre',
            10 => 'octubre',
            11 => 'noviembre',
            12 => 'diciembre',
        ];

        return sprintf(
            '%d de %s de %d',
            $fecha->day,
            $meses[$fecha->month],
            $fecha->year
        );
    }

    /**
     * Formatea la fecha y hora de apertura almacenadas en el procedimiento.
     */
    private function formatearFechaHoraApertura(Procedimiento $procedimiento): string
    {
        if (!$procedimiento->fecha_apertura || !$procedimiento->hora_apertura) {
            return '';
        }

        $fecha = Carbon::parse($procedimiento->fecha_apertura);
        $hora = Carbon::parse($procedimiento->hora_apertura)->format('H:i');

        return $this->formatearFechaTexto($fecha) . ", a las {$hora} horas.";
    }

    /**
     * Formatea fechas para controles HTML date.
     */
    private function formatearFechaInput($valor): string
    {
        return $valor ? Carbon::parse($valor)->format('Y-m-d') : '';
    }

    /**
     * Formatea horas para controles HTML time.
     */
    private function formatearHoraInput($valor): string
    {
        return $valor ? Carbon::parse($valor)->format('H:i') : '';
    }

    /**
     * Limpia caracteres que pueden romper el XML interno del archivo Word.
     */
    private function limpiarTexto($texto): string
    {
        $texto = trim((string) $texto);

        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $texto) ?? '';
    }
}