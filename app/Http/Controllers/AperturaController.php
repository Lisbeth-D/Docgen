<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Persona;
use App\Models\Procedimiento;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\TemplateProcessor;
use Throwable;

class AperturaController extends Controller
{
    /**
     * Muestra el formulario de apertura.
     */
    public function index()
    {
        /*
         * Área contratante:
         * Coordinación de Adquisiciones y Servicios.
         *
         * Se contemplan variantes con y sin acentos para evitar
         * problemas por diferencias de captura en la base de datos.
         */
        $areaContratanteId = Area::whereIn('nombre', [
            'Coordinación General de Adquisiciones y Servicios',
            'Adquisiciones',
            'Coordinación de adquisiciones y servicios',
        ])->value('id_area');

        /*
         * Jurídico:
         * Jurídico Centrales.
         */
        $areaJuridicoId = Area::whereIn('nombre', [
            'Jurídico Centrales',
            'Juridico Centrales',
        ])->value('id_area');

        /*
         * Órgano Interno de Control.
         */
        $areaOicId = Area::whereIn('nombre', [
            'Órgano interno de control',
            'Organo interno de control',
            'Órgano Interno de Control',
            'Organo Interno de Control',
        ])->value('id_area');

        $personasContratante = $areaContratanteId
            ? Persona::where('area_id', $areaContratanteId)
                ->orderBy('nombre')
                ->get()
            : collect();

        $personasJuridico = $areaJuridicoId
            ? Persona::where('area_id', $areaJuridicoId)
                ->orderBy('nombre')
                ->get()
            : collect();

        $personasOic = $areaOicId
            ? Persona::where('area_id', $areaOicId)
                ->orderBy('nombre')
                ->get()
            : collect();

        /*
         * Áreas permitidas para el administrador del contrato.
         */
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

        return view(
            'comprador.Apertura.apertura',
            compact(
                'personasContratante',
                'areasContrato',
                'personasJuridico',
                'personasOic'
            )
        );
    }

    /**
     * Busca un procedimiento mediante el número intermedio.
     */
    public function buscarProcedimiento($valor)
    {
        $valor = trim((string) $valor);

        if ($valor === '') {
            return response()->json(null);
        }

        $procedimiento = $this->consultarProcedimiento(
            $valor
        );

        if (!$procedimiento) {
            return response()->json(null);
        }

        $personaRequirente = $procedimiento->id_persona
            ? Persona::find($procedimiento->id_persona)
            : null;

        return response()->json([
            'num_procedimiento' =>
                $procedimiento->num_procedimiento,

            'nombre_procedimiento' =>
                $procedimiento->nombre_procedimiento,

            'fecha_apertura' =>
                $this->formatearFechaInput(
                    $procedimiento->fecha_apertura
                ),

            'hora_apertura' =>
                $this->formatearHoraInput(
                    $procedimiento->hora_apertura
                ),

            'fecha_fallo' =>
                $this->formatearFechaInput(
                    $procedimiento->fecha_fallo
                ),

            'hora_fallo' =>
                $this->formatearHoraInput(
                    $procedimiento->hora_fallo
                ),

            'area_requirente_id' =>
                $personaRequirente?->id,

            'area_requirente_nombre' =>
                $this->crearTextoPersonaPlano(
                    $personaRequirente
                ),
        ]);
    }

    /**
     * Genera el documento Word.
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
                        'El procedimiento no tiene una persona requirente válida registrada en id_persona.',
                ]);
        }

        $request->merge([
            'area_requirente' =>
                $personaRequirente->id,

            'area_requirente_nombre' =>
                $this->crearTextoPersonaPlano(
                    $personaRequirente
                ),
        ]);

        $datosDocumento = $this->prepararDatosDocumento(
            $request,
            $procedimiento,
            $personaRequirente
        );

        $templatePath = null;
        $outputPath = null;

        try {
            Carbon::setLocale('es');

            $templatePath = $this->guardarPlantillaTemporal(
                $request
            );

            $template = new TemplateProcessor(
                $templatePath
            );

            $this->llenarPlantilla(
                $template,
                $datosDocumento
            );

            [
                'path' => $outputPath,
                'name' => $outputName,
            ] = $this->guardarDocumentoGenerado(
                $template,
                $procedimiento
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
     * Reglas de validación.
     */
    private function reglasValidacion(): array
    {
        return [
            'numero_busqueda' => [
                'required',
                'string',
                'max:100',
            ],

            'num_procedimiento' => [
                'nullable',
                'string',
                'max:255',
            ],

            'nombre_procedimiento' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'fecha_apertura' => [
                'nullable',
                'date',
            ],

            'hora_apertura' => [
                'nullable',
                'date_format:H:i',
            ],

            'fecha_fallo' => [
                'nullable',
                'date',
            ],

            'hora_fallo' => [
                'nullable',
                'date_format:H:i',
            ],

            'area_contratante' => [
                'required',
                'integer',
                'exists:personas,id',
            ],

            'encargado_contrato' => [
                'required',
                'integer',
                'exists:personas,id',
            ],

            /*
             * Se llena automáticamente desde procedimientos.id_persona.
             */
            'area_requirente' => [
                'nullable',
                'integer',
                'exists:personas,id',
            ],

            'persona_juridico' => [
                'required',
                'integer',
                'exists:personas,id',
            ],

            'persona_oic' => [
                'required',
                'integer',
                'exists:personas,id',
            ],

            'archivo_word' => [
                'required',
                'file',
                'mimes:docx',
                'max:10240',
            ],
        ];
    }

    /**
     * Mensajes de validación en español.
     */
    private function mensajesValidacion(): array
    {
        return [
            'required' =>
                'El campo :attribute es obligatorio.',

            'string' =>
                'El campo :attribute debe contener texto válido.',

            'integer' =>
                'El campo :attribute debe contener un número entero.',

            'date' =>
                'El campo :attribute no contiene una fecha válida.',

            'date_format' =>
                'El campo :attribute debe tener el formato HH:MM.',

            'exists' =>
                'El valor seleccionado para :attribute no existe.',

            'file' =>
                'Debe seleccionar un archivo válido para :attribute.',

            'mimes' =>
                'La plantilla debe ser un archivo Word con extensión .docx.',

            'max' =>
                'El campo :attribute no debe exceder el límite permitido.',

            'numero_busqueda.required' =>
                'Debe ingresar el número de búsqueda.',

            'area_contratante.required' =>
                'Debe seleccionar a la persona del área contratante.',

            'encargado_contrato.required' =>
                'Debe seleccionar al administrador o encargado del contrato.',

            'persona_juridico.required' =>
                'Debe seleccionar a la persona de Jurídico Centrales.',

            'persona_oic.required' =>
                'Debe seleccionar a la persona del Órgano Interno de Control.',

            'archivo_word.required' =>
                'Debe seleccionar una plantilla Word.',

            'archivo_word.file' =>
                'Debe seleccionar un archivo válido.',

            'archivo_word.mimes' =>
                'La plantilla debe ser un archivo Word con extensión .docx.',

            'archivo_word.max' =>
                'La plantilla Word no debe superar los 10 MB.',
        ];
    }

    /**
     * Nombres amigables de los campos.
     */
    private function atributosValidacion(): array
    {
        return [
            'numero_busqueda' =>
                'número de búsqueda',

            'num_procedimiento' =>
                'número completo del procedimiento',

            'nombre_procedimiento' =>
                'nombre del procedimiento',

            'fecha_apertura' =>
                'fecha de apertura',

            'hora_apertura' =>
                'hora de apertura',

            'fecha_fallo' =>
                'fecha del fallo',

            'hora_fallo' =>
                'hora del fallo',

            'area_contratante' =>
                'persona del área contratante',

            'encargado_contrato' =>
                'administrador del contrato',

            'area_requirente' =>
                'área requirente',

            'persona_juridico' =>
                'persona de Jurídico Centrales',

            'persona_oic' =>
                'persona del Órgano Interno de Control',

            'archivo_word' =>
                'plantilla Word',
        ];
    }

    /**
     * Prepara la información que será enviada al documento.
     */
    private function prepararDatosDocumento(
        Request $request,
        Procedimiento $procedimiento,
        Persona $personaRequirente
    ): array {
        $datosProcedimiento = $this->resolverDatosProcedimiento(
            $request,
            $procedimiento
        );

        $personas = $this->resolverPersonasDocumento(
            $request,
            $personaRequirente
        );

        $nombresAreas = $this->obtenerNombresAreas([
            $personas['area_contratante']->area_id,
            $personas['administrador']->area_id,
            $personas['area_requirente']->area_id,
        ]);

        return [
            'num_procedimiento' =>
                $datosProcedimiento['numero'],

            'nombre_procedimiento' =>
                $datosProcedimiento['nombre'],

            'fecha_apertura' =>
                $datosProcedimiento['fecha_apertura_texto'],

            'hora_apertura' =>
                $datosProcedimiento['hora_apertura_texto'],

            'horaap_cierre' =>
                $datosProcedimiento['hora_cierre_texto'],

            'fecha_fallo' =>
                $datosProcedimiento['fecha_fallo_texto'],

            'area_contratante' =>
                $personas['area_contratante'],

            'administrador' =>
                $personas['administrador'],

            'area_requirente' =>
                $personas['area_requirente'],

            'juridico' =>
                $personas['juridico'],

            'oic' =>
                $personas['oic'],

            'area_contratante_nombre' =>
                $nombresAreas->get(
                    $personas['area_contratante']->area_id,
                    ''
                ),

            'area_administrador_nombre' =>
                $nombresAreas->get(
                    $personas['administrador']->area_id,
                    ''
                ),

            'area_requirente_nombre' =>
                $nombresAreas->get(
                    $personas['area_requirente']->area_id,
                    ''
                ),
        ];
    }

    /**
     * Resuelve los datos editables del procedimiento.
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

        $fechaApertura = $request->filled('fecha_apertura')
            ? $request->fecha_apertura
            : $procedimiento->fecha_apertura;

        $horaApertura = $request->filled('hora_apertura')
            ? $request->hora_apertura
            : $procedimiento->hora_apertura;

        $fechaFallo = $request->filled('fecha_fallo')
            ? $request->fecha_fallo
            : $procedimiento->fecha_fallo;

        $horaFallo = $request->filled('hora_fallo')
            ? $request->hora_fallo
            : $procedimiento->hora_fallo;

        $errores = [];

        if ($numero === '') {
            $errores['num_procedimiento'] =
                'Debe capturar el número completo del procedimiento.';
        }

        if ($nombre === '') {
            $errores['nombre_procedimiento'] =
                'Debe capturar el nombre del procedimiento.';
        }

        if (!$fechaApertura) {
            $errores['fecha_apertura'] =
                'Debe capturar la fecha de apertura.';
        }

        if (!$horaApertura) {
            $errores['hora_apertura'] =
                'Debe capturar la hora de apertura.';
        }

        if (!$fechaFallo) {
            $errores['fecha_fallo'] =
                'Debe capturar la fecha del fallo.';
        }

        if (!$horaFallo) {
            $errores['hora_fallo'] =
                'Debe capturar la hora del fallo.';
        }

        if ($errores) {
            throw ValidationException::withMessages(
                $errores
            );
        }

        $fechaAperturaCarbon = Carbon::parse(
            $fechaApertura
        );

        $horaAperturaCarbon = Carbon::parse(
            $horaApertura
        );

        $fechaFalloCarbon = Carbon::parse(
            $fechaFallo
        );

        $horaFalloCarbon = Carbon::parse(
            $horaFallo
        );

        $horaCierreCarbon = $horaAperturaCarbon
            ->copy()
            ->addHours(2);

        return [
            'numero' =>
                $numero,

            'nombre' =>
                $nombre,

            'fecha_apertura_texto' =>
                $this->formatearFechaTexto(
                    $fechaAperturaCarbon
                ),

            'hora_apertura_texto' =>
                $horaAperturaCarbon->format('H:i')
                . ' horas',

            'hora_cierre_texto' =>
                $horaCierreCarbon->format('H:i')
                . ' horas del día '
                . $this->formatearFechaTexto(
                    $fechaAperturaCarbon
                )
                . '.',

            'fecha_fallo_texto' =>
                $this->formatearFechaTexto(
                    $fechaFalloCarbon
                )
                . ' a las '
                . $horaFalloCarbon->format('H:i')
                . ' horas',
        ];
    }

    /**
     * Obtiene las personas seleccionadas con una sola consulta.
     */
    private function resolverPersonasDocumento(
        Request $request,
        Persona $personaRequirente
    ): array {
        $ids = array_values(
            array_unique(
                array_filter([
                    $request->area_contratante,
                    $request->encargado_contrato,
                    $personaRequirente->id,
                    $request->persona_juridico,
                    $request->persona_oic,
                ], static fn ($id): bool =>
                    $id !== null &&
                    $id !== '')
            )
        );

        $personas = Persona::whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $areaContratante = $personas->get(
            (int) $request->area_contratante
        );

        $administrador = $personas->get(
            (int) $request->encargado_contrato
        );

        $areaRequirente = $personas->get(
            (int) $personaRequirente->id
        );

        $juridico = $personas->get(
            (int) $request->persona_juridico
        );

        $oic = $personas->get(
            (int) $request->persona_oic
        );

        $errores = [];

        if (!$areaContratante) {
            $errores['area_contratante'] =
                'No fue posible obtener la persona del área contratante.';
        }

        if (!$administrador) {
            $errores['encargado_contrato'] =
                'No fue posible obtener al administrador del contrato.';
        }

        if (!$areaRequirente) {
            $errores['area_requirente'] =
                'No fue posible obtener la persona requirente registrada en el procedimiento.';
        }

        if (!$juridico) {
            $errores['persona_juridico'] =
                'No fue posible obtener la persona de Jurídico Centrales.';
        }

        if (!$oic) {
            $errores['persona_oic'] =
                'No fue posible obtener la persona del Órgano Interno de Control.';
        }

        if ($errores) {
            throw ValidationException::withMessages(
                $errores
            );
        }

        return [
            'area_contratante' =>
                $areaContratante,

            'administrador' =>
                $administrador,

            'area_requirente' =>
                $areaRequirente,

            'juridico' =>
                $juridico,

            'oic' =>
                $oic,
        ];
    }

    /**
     * Coloca todos los valores en la plantilla.
     */
    private function llenarPlantilla(
        TemplateProcessor $template,
        array $datos
    ): void {
        $valores = [
            'num_procedimiento' =>
                $datos['num_procedimiento'],

            'nombre_procedimiento' =>
                $datos['nombre_procedimiento'],

            'fecha_apertura' =>
                $datos['fecha_apertura'],

            'hora_apertura' =>
                $datos['hora_apertura'],

            'horaap_cierre' =>
                $datos['horaap_cierre'],

            'fecha_fallo' =>
                $datos['fecha_fallo'],

            'area_area_contratante' =>
                $datos['area_contratante_nombre'],

            'area_admi_contrato' =>
                $datos['area_administrador_nombre'],

            'area_area_requirente' =>
                $datos['area_requirente_nombre'],
        ];

        foreach ($valores as $marcador => $valor) {
            $template->setValue(
                $marcador,
                $this->limpiarTexto($valor)
            );
        }

        $personas = [
            'area_contratante' =>
                $datos['area_contratante'],

            'encargado_contrato' =>
                $datos['administrador'],

            'admi_contrato' =>
                $datos['administrador'],

            'area_requirente' =>
                $datos['area_requirente'],

            'persona_juridico' =>
                $datos['juridico'],

            'persona_oic' =>
                $datos['oic'],

            'area_contratante_tabla' =>
                $datos['area_contratante'],

            'encargado_contrato_tabla' =>
                $datos['administrador'],

            'admi_contrato_tabla' =>
                $datos['administrador'],

            'area_requirente_tabla' =>
                $datos['area_requirente'],
        ];

        foreach ($personas as $marcador => $persona) {
            $template->setComplexValue(
                $marcador,
                $this->crearTextoPersona(
                    $persona
                )
            );
        }
    }

    /**
     * Consulta el procedimiento por su parte numérica.
     */
    private function consultarProcedimiento(
        string $numero
    ): ?Procedimiento {
        return Procedimiento::where(
            'num_procedimiento',
            'LIKE',
            '%-N-' . trim($numero) . '-%'
        )->first();
    }

    /**
     * Obtiene nombres de áreas mediante una sola consulta.
     */
    private function obtenerNombresAreas(
        array $ids
    ) {
        $ids = array_values(
            array_unique(
                array_filter($ids)
            )
        );

        return Area::whereIn(
            'id_area',
            $ids
        )->pluck(
            'nombre',
            'id_area'
        );
    }

    /**
     * Guarda temporalmente la plantilla Word.
     */
    private function guardarPlantillaTemporal(
        Request $request
    ): string {
        $directorio = storage_path(
            'app/plantillas'
        );

        File::ensureDirectoryExists(
            $directorio
        );

        $nombre =
            uniqid(
                'plantilla_apertura_',
                true
            )
            . '.docx';

        $request->file('archivo_word')->move(
            $directorio,
            $nombre
        );

        return
            $directorio
            . DIRECTORY_SEPARATOR
            . $nombre;
    }

    /**
     * Guarda el documento generado.
     */
    private function guardarDocumentoGenerado(
        TemplateProcessor $template,
        Procedimiento $procedimiento
    ): array {
        $directorio = storage_path(
            'app/public/documentos'
        );

        File::ensureDirectoryExists(
            $directorio
        );

        $numeroSeguro = $this->limpiarNombreArchivo(
            (string) $procedimiento->num_procedimiento
        );

        $nombre =
            'Apertura_'
            . $numeroSeguro
            . '_'
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
     * Genera texto enriquecido para una persona.
     */
    private function crearTextoPersona(
        ?Persona $persona
    ): TextRun {
        $textRun = new TextRun();

        if (!$persona) {
            return $textRun;
        }

        $nombre = trim(
            (string) $persona->nombre
        );

        $cargo = trim(
            (string) $persona->cargo
        );

        if ($nombre !== '') {
            $textRun->addText(
                $nombre,
                [
                    'name' => 'Noto Sans',
                    'size' => 10,
                    'bold' => true,
                ]
            );
        }

        if ($cargo !== '') {
            $textRun->addText(
                ($nombre !== '' ? ', ' : '')
                . $cargo,
                [
                    'name' => 'Noto Sans',
                    'size' => 10,
                    'bold' => false,
                ]
            );
        }

        return $textRun;
    }

    /**
     * Genera texto plano para mostrar en el formulario.
     */
    private function crearTextoPersonaPlano(
        ?Persona $persona
    ): string {
        if (!$persona) {
            return '';
        }

        $nombre = trim(
            (string) $persona->nombre
        );

        $cargo = trim(
            (string) $persona->cargo
        );

        if (
            $nombre !== '' &&
            $cargo !== ''
        ) {
            return
                $nombre
                . ' - '
                . $cargo;
        }

        return $nombre !== ''
            ? $nombre
            : $cargo;
    }

    /**
     * Formatea una fecha en español.
     */
    private function formatearFechaTexto(
        Carbon $fecha
    ): string {
        return sprintf(
            '%d de %s de %d',
            $fecha->day,
            $fecha->translatedFormat('F'),
            $fecha->year
        );
    }

    /**
     * Formatea fechas para controles date.
     */
    private function formatearFechaInput(
        $valor
    ): string {
        return $valor
            ? Carbon::parse($valor)->format('Y-m-d')
            : '';
    }

    /**
     * Formatea horas para controles time.
     */
    private function formatearHoraInput(
        $valor
    ): string {
        return $valor
            ? Carbon::parse($valor)->format('H:i')
            : '';
    }

    /**
     * Limpia caracteres inválidos para XML.
     */
    private function limpiarTexto(
        $texto
    ): string {
        $texto = trim(
            (string) $texto
        );

        return preg_replace(
            '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u',
            '',
            $texto
        ) ?? '';
    }

    /**
     * Limpia caracteres no permitidos en nombres de archivo.
     */
    private function limpiarNombreArchivo(
        string $nombre
    ): string {
        $nombre = preg_replace(
            '/[\\\\\/:*?"<>|]/',
            '-',
            $nombre
        );

        return trim(
            (string) $nombre,
            '.-_ '
        );
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
}