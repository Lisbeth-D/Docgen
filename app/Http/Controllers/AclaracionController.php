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
use DOMDocument;
use DOMXPath;
use Throwable;
use ZipArchive;

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
        /*
         * Valores simples.
         */
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

        /*
         * Valores enriquecidos:
         * el nombre se inserta en negritas y el cargo sin negritas.
         */
        $valoresComplejos = [
            'area_requirente' =>
                $datos['area_requirente'],

            'area_contratante' =>
                $datos['area_contratante'],

            'persona_oic' =>
                $datos['persona_oic'],

            'persona_juridico' =>
                $datos['persona_juridico'],

            'comprador' =>
                $datos['comprador'],

            'area_requirente_tabla' =>
                $datos['area_requirente_tabla'],

            'area_contratante_tabla' =>
                $datos['area_contratante_tabla'],

            'comprador_tabla' =>
                $datos['comprador_tabla'],
        ];

        foreach ($valoresComplejos as $marcador => $valor) {
            /*
             * PhpWord no elimina correctamente un marcador complejo
             * cuando el TextRun está vacío. En ese caso lo sustituimos
             * como texto simple para evitar que aparezca literalmente
             * ${persona_juridico}, ${persona_oic}, etc.
             */
            if (
                $valor instanceof TextRun
                && count($valor->getElements()) === 0
            ) {
                $template->setValue(
                    $marcador,
                    ''
                );

                continue;
            }

            $template->setComplexValue(
                $marcador,
                $valor
            );
        }

        $this->clonarParticipantes(
            $template,
            $datos['participantes']
        );
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

        $ruta =
            $directorio
            . DIRECTORY_SEPARATOR
            . $nombre;

        /*
         * Word puede dividir visualmente una etiqueta en varios fragmentos XML.
         * Por ejemplo:
         * ${persona_ + juridico}
         *
         * Aunque en Word se vea completa, TemplateProcessor no la encuentra.
         * Esta normalización vuelve a unir las etiquetas antes de procesarlas.
         */
        $this->normalizarMarcadoresWord(
            $ruta
        );

        return $ruta;
    }

    /**
     * Repara marcadores que Microsoft Word dividió entre varios nodos <w:t>.
     *
     * TemplateProcessor sólo reconoce una etiqueta cuando está completa
     * dentro del XML. Esta rutina reconstruye las etiquetas sin alterar
     * el diseño de la plantilla.
     */
    private function normalizarMarcadoresWord(
        string $rutaDocumento
    ): void {
        $marcadores = [
            '${num_procedimiento}',
            '${nombre_procedimiento}',
            '${fecha_ac}',
            '${hora_ac}',
            '${hora_cierre}',
            '${fecha_apertura}',
            '${area_requirente}',
            '${area_contratante}',
            '${persona_oic}',
            '${persona_juridico}',
            '${ref_oic}',
            '${ref_juridico}',
            '${comprador}',
            '${area_requirente_tabla}',
            '${area_requirente_area}',
            '${area_contratante_tabla}',
            '${area_contratante_area}',
            '${comprador_tabla}',
            '${empresa}',
            '${pregunta}',
        ];

        $zip = new ZipArchive();

        if (
            $zip->open($rutaDocumento)
            !== true
        ) {
            throw new \RuntimeException(
                'No fue posible abrir la plantilla Word para normalizar sus etiquetas.'
            );
        }

        try {
            for (
                $indice = 0;
                $indice < $zip->numFiles;
                $indice++
            ) {
                $nombreEntrada = $zip->getNameIndex(
                    $indice
                );

                if (
                    !$nombreEntrada
                    || !str_starts_with(
                        $nombreEntrada,
                        'word/'
                    )
                    || !str_ends_with(
                        $nombreEntrada,
                        '.xml'
                    )
                ) {
                    continue;
                }

                $xml = $zip->getFromIndex(
                    $indice
                );

                if (
                    $xml === false
                    || !str_contains(
                        $xml,
                        '$'
                    )
                ) {
                    continue;
                }

                $xmlNormalizado =
                    $this->normalizarMarcadoresXml(
                        $xml,
                        $marcadores
                    );

                if ($xmlNormalizado !== $xml) {
                    $zip->addFromString(
                        $nombreEntrada,
                        $xmlNormalizado
                    );
                }
            }
        } finally {
            $zip->close();
        }
    }

    /**
     * Une un marcador dividido entre varios nodos de texto del XML.
     */
    private function normalizarMarcadoresXml(
        string $xml,
        array $marcadores
    ): string {
        $dom = new DOMDocument(
            '1.0',
            'UTF-8'
        );

        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;

        $estadoAnterior =
            libxml_use_internal_errors(true);

        try {
            if (!$dom->loadXML($xml)) {
                return $xml;
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors(
                $estadoAnterior
            );
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace(
            'w',
            'http://schemas.openxmlformats.org/wordprocessingml/2006/main'
        );

        $nodos = [];

        foreach (
            $xpath->query('//w:t') as $nodo
        ) {
            $nodos[] = $nodo;
        }

        if (!$nodos) {
            return $xml;
        }

        foreach ($marcadores as $marcador) {
            /*
             * Se repite porque una misma etiqueta puede existir
             * varias veces en el documento.
             */
            while (
                $this->unirPrimeraCoincidencia(
                    $nodos,
                    $marcador
                )
            ) {
                // Continúa hasta reparar todas las apariciones.
            }
        }

        return $dom->saveXML() ?: $xml;
    }

    /**
     * Une la primera aparición de un marcador fragmentado.
     */
    private function unirPrimeraCoincidencia(
        array $nodos,
        string $marcador
    ): bool {
        $textoCompleto = '';
        $rangos = [];

        foreach ($nodos as $indice => $nodo) {
            $inicio = mb_strlen(
                $textoCompleto
            );

            $contenido = (string) $nodo->nodeValue;
            $textoCompleto .= $contenido;

            $rangos[] = [
                'indice' => $indice,
                'inicio' => $inicio,
                'fin' =>
                    $inicio
                    + mb_strlen($contenido),
            ];
        }

        $posicion = mb_strpos(
            $textoCompleto,
            $marcador
        );

        if ($posicion === false) {
            return false;
        }

        $finMarcador =
            $posicion
            + mb_strlen($marcador);

        $primerRango = null;
        $ultimoRango = null;

        foreach ($rangos as $rango) {
            if (
                $primerRango === null
                && $posicion < $rango['fin']
            ) {
                $primerRango = $rango;
            }

            if (
                $finMarcador > $rango['inicio']
                && $finMarcador <= $rango['fin']
            ) {
                $ultimoRango = $rango;
                break;
            }
        }

        if (
            $primerRango === null
            || $ultimoRango === null
        ) {
            return false;
        }

        /*
         * Si ya está contenido en un solo nodo, no requiere reparación.
         */
        if (
            $primerRango['indice']
            === $ultimoRango['indice']
        ) {
            return false;
        }

        $primerNodo =
            $nodos[$primerRango['indice']];

        $ultimoNodo =
            $nodos[$ultimoRango['indice']];

        $offsetInicio =
            $posicion
            - $primerRango['inicio'];

        $offsetFin =
            $finMarcador
            - $ultimoRango['inicio'];

        $prefijo = mb_substr(
            (string) $primerNodo->nodeValue,
            0,
            $offsetInicio
        );

        $sufijo = mb_substr(
            (string) $ultimoNodo->nodeValue,
            $offsetFin
        );

        $primerNodo->nodeValue =
            $prefijo
            . $marcador;

        for (
            $indice = $primerRango['indice'] + 1;
            $indice < $ultimoRango['indice'];
            $indice++
        ) {
            $nodos[$indice]->nodeValue = '';
        }

        $ultimoNodo->nodeValue = $sufijo;

        return true;
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