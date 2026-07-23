<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Persona;
use App\Models\Procedimiento;
use App\Services\HistorialDocumentosService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\TemplateProcessor;
use Throwable;

class AclaracionController extends Controller
{
    /**
     * Muestra el formulario para generar el acta.
     */
    public function index()
    {
        $areaContratanteId = Area::where(
            'nombre',
            'Coordinación General de Adquisiciones y Servicios'
        )->value('id_area');

        $personasContratante = $areaContratanteId
            ? Persona::with('area')->where('area_id', $areaContratanteId)
                ->orderBy('nombre')
                ->get()
            : collect();

        $personasOic = Persona::with('area')->where('area_id', 14)
            ->orderBy('nombre')
            ->get();

        $personasJuridico = Persona::with('area')->where('area_id', 15)
            ->orderBy('nombre')
            ->get();

        return view(
            'comprador.aclaracion.acta',
            compact(
                'personasContratante',
                'personasOic',
                'personasJuridico'
            )
        );
    }

    /**
     * Busca un procedimiento para autocompletar el formulario.
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
            ? Persona::with('area')->find($procedimiento->id_persona)
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
                $this->crearTextoPersonaPlano(
                    $personaRequirente
                ),
        ]);
    }

    /**
     * Genera el documento Word.
     */
    public function generar(
        Request $request,
        HistorialDocumentosService $historialDocumentos
    )
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

        $personaRequirente = $procedimiento->id_persona
            ? Persona::with('area')->find($procedimiento->id_persona)
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
            $datosValidados,
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

            /*
             * Verificar que el documento se haya generado
             * correctamente antes de registrarlo en el historial.
             */
            clearstatcache(
                true,
                $outputPath
            );

            if (
                !File::exists($outputPath) ||
                !File::isFile($outputPath)
            ) {
                throw new \RuntimeException(
                    'El documento generado no se encontró en el almacenamiento.'
                );
            }

            if ((int) File::size($outputPath) <= 0) {
                throw new \RuntimeException(
                    'El documento Word generado está vacío.'
                );
            }

            /*
             * Registrar una copia en el historial del usuario.
             *
             * La copia queda disponible durante 10 días para
             * consultarse y descargarse nuevamente.
             */
            $historialDocumentos->registrar(
                $request->user(),
                $outputPath,
                $outputName,
                'Acta de junta de aclaraciones',
                trim(
                    (string) $datosDocumento['num_procedimiento']
                ),
                10
            );

            /*
             * Eliminar únicamente la plantilla temporal.
             */
            $this->eliminarArchivo($templatePath);
            $templatePath = null;

            /*
             * El archivo temporal utilizado para la descarga
             * inmediata se elimina después de enviarse. La copia
             * registrada en el historial permanece disponible.
             */
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

            'fecha_ac' => [
                'nullable',
                'date',
            ],

            'hora_ac' => [
                'nullable',
                'date_format:H:i',
            ],

            'fecha_apertura' => [
                'nullable',
                'date',
            ],

            'hora_apertura' => [
                'nullable',
                'date_format:H:i',
            ],

            /*
             * Este valor se obtiene del procedimiento.
             */
            'area_requirente' => [
                'nullable',
                'integer',
                'exists:personas,id',
            ],

            'area_contratante' => [
                'required',
                'integer',
                'exists:personas,id',
            ],

            'persona_oic' => [
                'nullable',
                'integer',
                'exists:personas,id',
            ],

            'persona_juridico' => [
                'nullable',
                'integer',
                'exists:personas,id',
            ],

            'ref_oic' => [
                'nullable',
                'string',
                'max:255',
            ],

            'ref_juridico' => [
                'nullable',
                'string',
                'max:255',
            ],

            'participantes' => [
                'nullable',
                'array',
            ],

            'participantes.*.nombre' => [
                'nullable',
                'string',
                'max:255',
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

            'array' =>
                'El campo :attribute tiene un formato incorrecto.',

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

            'area_contratante.exists' =>
                'La persona seleccionada del área contratante no existe.',

            'persona_oic.exists' =>
                'La persona seleccionada del OIC no existe.',

            'persona_juridico.exists' =>
                'La persona seleccionada del área jurídica no existe.',

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

            'fecha_ac' =>
                'fecha de la junta de aclaraciones',

            'hora_ac' =>
                'hora de la junta de aclaraciones',

            'fecha_apertura' =>
                'fecha de apertura',

            'hora_apertura' =>
                'hora de apertura',

            'area_requirente' =>
                'área requirente',

            'area_contratante' =>
                'persona del área contratante',

            'persona_oic' =>
                'persona del OIC',

            'persona_juridico' =>
                'persona jurídica',

            'ref_oic' =>
                'referencia del OIC',

            'ref_juridico' =>
                'referencia jurídica',

            'participantes' =>
                'participantes',

            'archivo_word' =>
                'plantilla Word',
        ];
    }

    /**
     * Prepara la información que será enviada al Word.
     */
    private function prepararDatosDocumento(
        Request $request,
        array $datosValidados,
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

        $usuario = Auth::user();

        return [
            'num_procedimiento' =>
                $datosProcedimiento['numero'],

            'nombre_procedimiento' =>
                $datosProcedimiento['nombre'],

            'fecha_ac' =>
                $datosProcedimiento['fecha_ac_texto'],

            'hora_ac' =>
                $datosProcedimiento['hora_ac_texto'],

            'hora_cierre' =>
                $datosProcedimiento['hora_cierre_texto'],

            'fecha_apertura' =>
                $datosProcedimiento['fecha_apertura_texto'],

            /*
             * Etiquetas para texto normal:
             * nombre en negritas y cargo sin negritas.
             */
            'area_requirente' =>
                $this->crearTextoPersonaWord(
                    $personas['area_requirente'],
                    ', '
                ),

            'area_contratante' =>
                $this->crearTextoPersonaWord(
                    $personas['area_contratante'],
                    ', '
                ),

            /*
             * OIC y Jurídico conservan la misma etiqueta
             * tanto en párrafos como dentro de tablas.
             */
            'persona_oic' =>
                $this->crearTextoPersonaWord(
                    $personas['oic'],
                    ', '
                ),

            'persona_juridico' =>
                $this->crearTextoPersonaWord(
                    $personas['juridico'],
                    ', '
                ),

            'comprador' =>
                $this->crearTextoUsuarioWord(
                    $usuario,
                    ', '
                ),

            /*
             * Etiquetas específicas para la tabla de firmas.
             */
            'area_requirente_tabla' =>
                $this->crearTextoPersonaWord(
                    $personas['area_requirente'],
                    ' / '
                ),

            'area_requirente_area' =>
                $this->obtenerNombreAreaPersona(
                    $personas['area_requirente']
                ),

            'area_contratante_tabla' =>
                $this->crearTextoPersonaWord(
                    $personas['area_contratante'],
                    ' / '
                ),

            'area_contratante_area' =>
                $this->obtenerNombreAreaPersona(
                    $personas['area_contratante']
                ),

            'comprador_tabla' =>
                $this->crearTextoUsuarioWord(
                    $usuario,
                    ' / '
                ),

            'ref_oic' =>
                trim((string) $request->input(
                    'ref_oic',
                    ''
                )),

            'ref_juridico' =>
                trim((string) $request->input(
                    'ref_juridico',
                    ''
                )),

            'participantes' =>
                $this->prepararParticipantes(
                    $request->input(
                        'participantes',
                        []
                    )
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

        $fechaAc = $request->filled('fecha_ac')
            ? $request->fecha_ac
            : $procedimiento->fecha_ac;

        $horaAc = $request->filled('hora_ac')
            ? $request->hora_ac
            : $procedimiento->hora_ac;

        $fechaApertura = $request->filled('fecha_apertura')
            ? $request->fecha_apertura
            : $procedimiento->fecha_apertura;

        $horaApertura = $request->filled('hora_apertura')
            ? $request->hora_apertura
            : $procedimiento->hora_apertura;

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

        if (!$fechaApertura) {
            $errores['fecha_apertura'] =
                'Debe capturar la fecha de apertura.';
        }

        if (!$horaApertura) {
            $errores['hora_apertura'] =
                'Debe capturar la hora de apertura.';
        }

        if ($errores) {
            throw ValidationException::withMessages(
                $errores
            );
        }

        $fechaAcCarbon = Carbon::parse($fechaAc);
        $horaAcCarbon = Carbon::parse($horaAc);
        $horaCierreCarbon = $horaAcCarbon
            ->copy()
            ->addMinutes(30);

        $fechaAperturaCarbon = Carbon::parse(
            $fechaApertura
        );

        $horaAperturaCarbon = Carbon::parse(
            $horaApertura
        );

        return [
            'numero' =>
                $numero,

            'nombre' =>
                $nombre,

            'fecha_ac_texto' =>
                $this->formatearFechaTexto(
                    $fechaAcCarbon
                ),

            'hora_ac_texto' =>
                $horaAcCarbon->format('H:i')
                . ' horas',

            'hora_cierre_texto' =>
                $horaCierreCarbon->format('H:i')
                . ' horas',

            'fecha_apertura_texto' =>
                $this->formatearFechaTexto(
                    $fechaAperturaCarbon
                )
                . ', a las '
                . $horaAperturaCarbon->format('H:i')
                . ' horas',
        ];
    }

    /**
     * Obtiene las personas requeridas mediante una sola consulta.
     */
    private function resolverPersonasDocumento(
        Request $request,
        Persona $personaRequirente
    ): array {
        $ids = array_values(
            array_unique(
                array_filter([
                    $personaRequirente->id,
                    $request->area_contratante,
                    $request->persona_oic,
                    $request->persona_juridico,
                ], static fn ($id): bool =>
                    $id !== null &&
                    $id !== '')
            )
        );

        $personas = Persona::with('area')->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $areaRequirente = $personas->get(
            (int) $personaRequirente->id
        );

        $areaContratante = $personas->get(
            (int) $request->area_contratante
        );

        $errores = [];

        if (!$areaRequirente) {
            $errores['area_requirente'] =
                'No fue posible obtener la persona requirente registrada en el procedimiento.';
        }

        if (!$areaContratante) {
            $errores['area_contratante'] =
                'No fue posible obtener la persona del área contratante.';
        }

        if ($errores) {
            throw ValidationException::withMessages(
                $errores
            );
        }

        return [
            'area_requirente' =>
                $areaRequirente,

            'area_contratante' =>
                $areaContratante,

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
     * Prepara participantes no vacíos.
     */
    private function prepararParticipantes(
        array $participantes
    ): array {
        $resultado = [];

        foreach ($participantes as $participante) {
            if (!is_array($participante)) {
                continue;
            }

            $nombre = trim(
                (string) ($participante['nombre'] ?? '')
            );

            if ($nombre === '') {
                continue;
            }

            $resultado[] = [
                'nombre' => $nombre,
            ];
        }

        return $resultado;
    }

    /**
     * Coloca todos los datos en la plantilla.
     */
    private function llenarPlantilla(
        TemplateProcessor $template,
        array $datos
    ): void {
        $valoresSimples = [
            'num_procedimiento' =>
                $datos['num_procedimiento'],

            'nombre_procedimiento' =>
                $datos['nombre_procedimiento'],

            'fecha_ac' =>
                $datos['fecha_ac'],

            'hora_ac' =>
                $datos['hora_ac'],

            'hora_cierre' =>
                $datos['hora_cierre'],

            'fecha_apertura' =>
                $datos['fecha_apertura'],

            'area_requirente_area' =>
                $datos['area_requirente_area'],

            'area_contratante_area' =>
                $datos['area_contratante_area'],

            'ref_oic' =>
                $datos['ref_oic'],

            'ref_juridico' =>
                $datos['ref_juridico'],
        ];

        foreach ($valoresSimples as $marcador => $valor) {
            $template->setValue(
                $marcador,
                $this->limpiarTexto($valor)
            );
        }

        $this->colocarTextoComplejo(
            $template,
            'area_requirente',
            $datos['area_requirente']
        );

        $this->colocarTextoComplejo(
            $template,
            'area_contratante',
            $datos['area_contratante']
        );

        $this->colocarTextoComplejo(
            $template,
            'comprador',
            $datos['comprador']
        );

        $this->colocarTextoComplejo(
            $template,
            'area_requirente_tabla',
            $datos['area_requirente_tabla']
        );

        $this->colocarTextoComplejo(
            $template,
            'area_contratante_tabla',
            $datos['area_contratante_tabla']
        );

        $this->colocarTextoComplejo(
            $template,
            'comprador_tabla',
            $datos['comprador_tabla']
        );

        /*
         * Estas etiquetas pueden aparecer en el cuerpo y en una tabla.
         * setComplexValue reemplaza una sola aparición por llamada.
         */
        $this->colocarTextoComplejoRepetido(
            $template,
            'persona_oic',
            $datos['persona_oic'],
            2
        );

        $this->colocarTextoComplejoRepetido(
            $template,
            'persona_juridico',
            $datos['persona_juridico'],
            2
        );

        $this->clonarParticipantes(
            $template,
            $datos['participantes']
        );
    }

    /**
     * Coloca una etiqueta compleja o la elimina cuando está vacía.
     */
    private function colocarTextoComplejo(
        TemplateProcessor $template,
        string $marcador,
        TextRun $valor
    ): void {
        if (count($valor->getElements()) === 0) {
            $template->setValue(
                $marcador,
                ''
            );

            return;
        }

        $template->setComplexValue(
            $marcador,
            $valor
        );
    }

    /**
     * Reemplaza varias apariciones de una misma etiqueta compleja.
     */
    private function colocarTextoComplejoRepetido(
        TemplateProcessor $template,
        string $marcador,
        TextRun $valor,
        int $apariciones
    ): void {
        if (count($valor->getElements()) === 0) {
            $template->setValue(
                $marcador,
                ''
            );

            return;
        }

        for ($i = 0; $i < $apariciones; $i++) {
            $template->setComplexValue(
                $marcador,
                clone $valor
            );
        }
    }

    /**
     * Clona los participantes en la tabla Word.
     */
    private function clonarParticipantes(
        TemplateProcessor $template,
        array $participantes
    ): void {
        if (!$participantes) {
            $template->setValue('empresa', '');
            $template->setValue('pregunta', '');

            return;
        }

        $template->cloneRow(
            'empresa',
            count($participantes)
        );

        foreach ($participantes as $indice => $participante) {
            $fila = $indice + 1;

            $template->setValue(
                "empresa#{$fila}",
                $this->limpiarTexto(
                    $participante['nombre']
                )
            );

            $template->setValue(
                "pregunta#{$fila}",
                'NO'
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
                'plantilla_aclaracion_',
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
        TemplateProcessor $template
    ): array {
        $directorio = storage_path(
            'app/public/documentos'
        );

        File::ensureDirectoryExists(
            $directorio
        );

        $nombre =
            'acta_aclaracion_'
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
     * Formatea una fecha para controles date.
     */
    private function formatearFechaInput(
        $valor
    ): string {
        return $valor
            ? Carbon::parse($valor)->format('Y-m-d')
            : '';
    }

    /**
     * Formatea una hora para controles time.
     */
    private function formatearHoraInput(
        $valor
    ): string {
        return $valor
            ? Carbon::parse($valor)->format('H:i')
            : '';
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
     * Genera un texto enriquecido para una persona.
     * El nombre aparece en negritas y el cargo sin negritas.
     */
    private function crearTextoPersonaWord(
        $persona,
        string $separador = ', '
    ): TextRun {
        $texto = new TextRun();

        if (!$persona) {
            return $texto;
        }

        $nombre = $this->limpiarTexto(
            $persona->nombre ?? ''
        );

        $cargo = $this->limpiarTexto(
            $persona->cargo ?? ''
        );

        if ($nombre !== '') {
            $texto->addText(
                $nombre,
                [
                    'name' => 'Noto Sans',
                    'size' => 10,
                    'bold' => true,
                ]
            );
        }

        if ($cargo !== '') {
            $texto->addText(
                ($nombre !== '' ? $separador : '')
                . $cargo,
                [
                    'name' => 'Noto Sans',
                    'size' => 10,
                    'bold' => false,
                ]
            );
        }

        return $texto;
    }

    /**
     * Genera un texto enriquecido para el usuario comprador.
     */
    private function crearTextoUsuarioWord(
        $usuario,
        string $separador = ', '
    ): TextRun {
        $texto = new TextRun();

        if (!$usuario) {
            return $texto;
        }

        $nombre = $this->limpiarTexto(
            $usuario->name ?? ''
        );

        $cargo = $this->limpiarTexto(
            $usuario->cargo ?? ''
        );

        if ($nombre !== '') {
            $texto->addText(
                $nombre,
                [
                    'name' => 'Noto Sans',
                    'size' => 10,
                    'bold' => true,
                ]
            );
        }

        if ($cargo !== '') {
            $texto->addText(
                ($nombre !== '' ? $separador : '')
                . $cargo,
                [
                    'name' => 'Noto Sans',
                    'size' => 10,
                    'bold' => false,
                ]
            );
        }

        return $texto;
    }

    /**
     * Genera texto plano para mostrarlo en el formulario.
     */
    private function crearTextoPersonaPlano(
        $persona
    ): string {
        if (!$persona) {
            return '';
        }

        $nombre = $this->limpiarTexto(
            $persona->nombre ?? ''
        );

        $cargo = $this->limpiarTexto(
            $persona->cargo ?? ''
        );

        if ($nombre !== '' && $cargo !== '') {
            return $nombre . ' - ' . $cargo;
        }

        return $nombre !== ''
            ? $nombre
            : $cargo;
    }

    /**
     * Obtiene el nombre del área relacionada con una persona.
     */
    private function obtenerNombreAreaPersona(
        $persona
    ): string {
        if (!$persona) {
            return '';
        }

        if (
            method_exists($persona, 'relationLoaded')
            && $persona->relationLoaded('area')
            && $persona->area
        ) {
            return $this->limpiarTexto(
                $persona->area->nombre ?? ''
            );
        }

        $areaId = $persona->area_id ?? null;

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
     * Limpia caracteres inválidos para el XML del Word.
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
}