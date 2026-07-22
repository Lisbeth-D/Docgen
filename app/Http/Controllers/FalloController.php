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
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\TemplateProcessor;
use Throwable;

class FalloController extends Controller
{
    /**
     * Muestra el formulario para generar el Acta de Fallo.
     */
    public function indexActa()
    {
        /*
         * Área contratante:
         * Coordinación de Adquisiciones y Servicios.
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
            'Juridico',
        ])->value('id_area');

        /*
         * OIC:
         * Órgano Interno de Control.
         */
        $areaOicId = Area::whereIn('nombre', [
            'Órgano interno de control',
            'OIC',
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

        $areasEncargado = Area::whereIn('nombre', [
            'Subgerencia de Operaciones',
            'Subgerencia de Abasto',
        ])->pluck('id_area');

        $encargadosContrato = Persona::whereIn(
                'area_id',
                $areasEncargado
            )
            ->orderBy('nombre')
            ->get();

        return view(
            'comprador.Fallo.actaFallo',
            compact(
                'personasContratante',
                'encargadosContrato',
                'personasJuridico',
                'personasOic'
            )
        );
    }

    /**
     * Busca un procedimiento por el número intermedio.
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
     * Genera el Acta de Fallo en Word.
     */
    public function generarActa(Request $request)
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
             * Se obtiene automáticamente desde procedimientos.id_persona.
             */
            'area_requirente' => [
                'nullable',
                'integer',
                'exists:personas,id',
            ],

            'persona_oic' => [
                'required',
                'integer',
                'exists:personas,id',
            ],

            'persona_juridico' => [
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
                'Debe seleccionar al encargado del contrato.',

            'persona_oic.required' =>
                'Debe seleccionar a la persona del Órgano Interno de Control.',

            'persona_juridico.required' =>
                'Debe seleccionar a la persona de Jurídico Centrales.',

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

            'fecha_fallo' =>
                'fecha del fallo',

            'hora_fallo' =>
                'hora del fallo',

            'area_contratante' =>
                'persona del área contratante',

            'encargado_contrato' =>
                'encargado del contrato',

            'area_requirente' =>
                'área requirente',

            'persona_oic' =>
                'persona del Órgano Interno de Control',

            'persona_juridico' =>
                'persona de Jurídico Centrales',

            'archivo_word' =>
                'plantilla Word',
        ];
    }

    /**
     * Prepara la información que será enviada a la plantilla.
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
            $personas['encargado']->area_id,
            $personas['area_requirente']->area_id,
        ]);

        $usuario = Auth::user();

        return [
            'num_procedimiento' =>
                $datosProcedimiento['numero'],

            'nombre_procedimiento' =>
                $datosProcedimiento['nombre'],

            'fecha_fallo' =>
                $datosProcedimiento['fecha_fallo_texto'],

            'hora_fallo' =>
                $datosProcedimiento['hora_fallo_texto'],

            'area_contratante' =>
                $personas['area_contratante'],

            'encargado' =>
                $personas['encargado'],

            'area_requirente' =>
                $personas['area_requirente'],

            'oic' =>
                $personas['oic'],

            'juridico' =>
                $personas['juridico'],

            /*
             * Usuario que genera el documento.
             */
            'comprador' =>
                $usuario,

            'comprador_area' =>
                $this->obtenerNombreAreaUsuario(
                    $usuario
                ),

            'area_contratante_nombre' =>
                $nombresAreas->get(
                    $personas['area_contratante']->area_id,
                    ''
                ),

            'area_encargado_nombre' =>
                $nombresAreas->get(
                    $personas['encargado']->area_id,
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

        $fechaFalloCarbon = Carbon::parse(
            $fechaFallo
        );

        $horaFalloCarbon = Carbon::parse(
            $horaFallo
        );

        return [
            'numero' =>
                $numero,

            'nombre' =>
                $nombre,

            'fecha_fallo_texto' =>
                $this->formatearFechaTexto(
                    $fechaFalloCarbon
                ),

            'hora_fallo_texto' =>
                $horaFalloCarbon->format('H:i')
                . ' horas',
        ];
    }

    /**
     * Obtiene todas las personas con una sola consulta.
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
                    $request->persona_oic,
                    $request->persona_juridico,
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

        $encargado = $personas->get(
            (int) $request->encargado_contrato
        );

        $areaRequirente = $personas->get(
            (int) $personaRequirente->id
        );

        $oic = $personas->get(
            (int) $request->persona_oic
        );

        $juridico = $personas->get(
            (int) $request->persona_juridico
        );

        $errores = [];

        if (!$areaContratante) {
            $errores['area_contratante'] =
                'No fue posible obtener la persona del área contratante.';
        }

        if (!$encargado) {
            $errores['encargado_contrato'] =
                'No fue posible obtener al encargado del contrato.';
        }

        if (!$areaRequirente) {
            $errores['area_requirente'] =
                'No fue posible obtener la persona requirente registrada en el procedimiento.';
        }

        if (!$oic) {
            $errores['persona_oic'] =
                'No fue posible obtener la persona del Órgano Interno de Control.';
        }

        if (!$juridico) {
            $errores['persona_juridico'] =
                'No fue posible obtener la persona de Jurídico Centrales.';
        }

        if ($errores) {
            throw ValidationException::withMessages(
                $errores
            );
        }

        return [
            'area_contratante' =>
                $areaContratante,

            'encargado' =>
                $encargado,

            'area_requirente' =>
                $areaRequirente,

            'oic' =>
                $oic,

            'juridico' =>
                $juridico,
        ];
    }

    /**
     * Coloca todos los valores en la plantilla Word.
     */
 /**
 * Coloca todos los valores en la plantilla Word.
 */
private function llenarPlantilla(
    TemplateProcessor $template,
    array $datos
): void {
    /*
     * Valores simples y etiquetas para la columna de área.
     */
    $valores = [
        'num_procedimiento' =>
            $datos['num_procedimiento'],

        'nombre_procedimiento' =>
            $datos['nombre_procedimiento'],

        'fecha_fallo' =>
            $datos['fecha_fallo'],

        'hora_fallo' =>
            $datos['hora_fallo'],

        /*
         * Etiquetas anteriores.
         */
        'area_area_contratante' =>
            $datos['area_contratante_nombre'],

        'area_encargado_contrato' =>
            $datos['area_encargado_nombre'],

        'area_area_requirente' =>
            $datos['area_requirente_nombre'],

        /*
         * Etiquetas para la columna "Área" de la tabla.
         */
        'area_contratante_area' =>
            $datos['area_contratante_nombre'],

        'encargado_contrato_area' =>
            $datos['area_encargado_nombre'],

        'area_requirente_area' =>
            $datos['area_requirente_nombre'],

        'comprador_area' =>
            $datos['comprador_area'],
    ];

    foreach ($valores as $marcador => $valor) {
        $template->setValue(
            $marcador,
            $this->limpiarTexto($valor)
        );
    }

    /*
     * Etiquetas de personas del cuerpo.
     */
    $this->colocarPersonaEnTodasLasApariciones(
        $template,
        'area_contratante',
        $datos['area_contratante']
    );

    $this->colocarPersonaEnTodasLasApariciones(
        $template,
        'encargado_contrato',
        $datos['encargado']
    );

    $this->colocarPersonaEnTodasLasApariciones(
        $template,
        'area_requirente',
        $datos['area_requirente']
    );

    /*
     * OIC y Jurídico:
     * La misma etiqueta puede colocarse varias veces,
     * tanto en el cuerpo como dentro de una tabla.
     */
    $this->colocarPersonaEnTodasLasApariciones(
        $template,
        'persona_oic',
        $datos['oic']
    );

    $this->colocarPersonaEnTodasLasApariciones(
        $template,
        'persona_juridico',
        $datos['juridico']
    );

    /*
     * Etiquetas independientes para las demás personas de la tabla.
     */
    $this->colocarPersonaEnTodasLasApariciones(
        $template,
        'area_contratante_tabla',
        $datos['area_contratante']
    );

    $this->colocarPersonaEnTodasLasApariciones(
        $template,
        'encargado_contrato_tabla',
        $datos['encargado']
    );

    $this->colocarPersonaEnTodasLasApariciones(
        $template,
        'area_requirente_tabla',
        $datos['area_requirente']
    );

    /*
     * Usuario autenticado dentro de la tabla.
     */
    $cantidadComprador =
        $template->getVariableCount()['comprador_tabla']
        ?? 0;

    if ($datos['comprador']) {
        for ($i = 0; $i < $cantidadComprador; $i++) {
            $template->setComplexValue(
                'comprador_tabla',
                $this->crearTextoUsuario(
                    $datos['comprador']
                )
            );
        }
    } else {
        $template->setValue(
            'comprador_tabla',
            ''
        );
    }
}

/**
 * Reemplaza todas las apariciones de una misma etiqueta
 * conservando el formato complejo:
 *
 * Nombre en negritas, cargo normal.
 */
private function colocarPersonaEnTodasLasApariciones(
    TemplateProcessor $template,
    string $marcador,
    ?Persona $persona
): void {
    $variables =
        $template->getVariableCount();

    $cantidad =
        $variables[$marcador]
        ?? 0;

    if ($cantidad === 0) {
        return;
    }

    if (!$persona) {
        $template->setValue(
            $marcador,
            ''
        );

        return;
    }

    for ($i = 0; $i < $cantidad; $i++) {
        $template->setComplexValue(
            $marcador,
            $this->crearTextoPersona(
                $persona
            )
        );
    }
}
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
                'plantilla_fallo_',
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
            'Acta_Fallo_'
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
     * Crea un TextRun para insertar una persona en Word.
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
                    'bold' => true,
                    'name' => 'Noto Sans',
                    'size' => 10.5,
                ]
            );
        }

        if ($cargo !== '') {
            $textRun->addText(
                ($nombre !== '' ? ', ' : '')
                . $cargo,
                [
                    'bold' => false,
                    'name' => 'Noto Sans',
                    'size' => 10.5,
                ]
            );
        }

        return $textRun;
    }

    /**
     * Crea un TextRun para el usuario autenticado.
     */
    private function crearTextoUsuario(
        $usuario
    ): TextRun {
        $textRun = new TextRun();

        if (!$usuario) {
            return $textRun;
        }

        $nombre = trim(
            (string) ($usuario->name ?? '')
        );

        $cargo = trim(
            (string) ($usuario->cargo ?? '')
        );

        if ($nombre !== '') {
            $textRun->addText(
                $nombre,
                [
                    'bold' => true,
                    'name' => 'Noto Sans',
                    'size' => 10.5,
                ]
            );
        }

        if ($cargo !== '') {
            $textRun->addText(
                ($nombre !== '' ? ', ' : '')
                . $cargo,
                [
                    'bold' => false,
                    'name' => 'Noto Sans',
                    'size' => 10.5,
                ]
            );
        }

        return $textRun;
    }

    /**
     * Obtiene el nombre del área del usuario autenticado.
     */
    private function obtenerNombreAreaUsuario(
        $usuario
    ): string {
        if (!$usuario) {
            return '';
        }

        $areaId = $usuario->area_id ?? null;

        if (!$areaId) {
            return '';
        }

        return $this->limpiarTexto(
            Area::where(
                'id_area',
                $areaId
            )->value('nombre')
        );
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
     * Limpia caracteres inválidos para nombres de archivo.
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