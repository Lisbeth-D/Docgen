<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Models\Procedimiento;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpWord\TemplateProcessor;
use Throwable;

class ActaCierreController extends Controller
{
    /**
     * Muestra el formulario para generar el acta de cierre.
     */
    public function index()
    {
        /*
         * Área contratante:
         * únicamente personas adscritas a la
         * Coordinación General de Adquisiciones y Servicios.
         */
        $personasContratante = Persona::whereHas(
            'area',
            function ($query) {
                $query->where(
                    'nombre',
                    'Coordinación General de Adquisiciones y Servicios'
                );
            }
        )
            ->orderBy('nombre')
            ->get();

        /*
         * Estas listas se conservan para OIC y Jurídico.
         */
        $personasOic = Persona::where('area_id', 14)
            ->orderBy('nombre')
            ->get();

        $personasJuridico = Persona::where('area_id', 15)
            ->orderBy('nombre')
            ->get();

        return view(
            'comprador.aclaracion.acta_cierre',
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

        $procedimiento = $this->consultarProcedimiento($valor);

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
        ]);
    }

    /**
     * Genera el documento Word del acta de cierre.
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
         * La persona del área requirente se obtiene únicamente
         * de procedimientos.id_persona.
         */
        $personaRequirente = $procedimiento->id_persona
            ? Persona::find($procedimiento->id_persona)
            : null;

        if (!$personaRequirente) {
            return back()
                ->withInput()
                ->withErrors([
                    'numero_busqueda' =>
                        'El procedimiento no tiene una persona requirente válida registrada.',
                ]);
        }

        $request->merge([
            'area_requirente' => $personaRequirente->id,
        ]);

        /*
         * Los datos se preparan antes de mover la plantilla.
         * Así, cualquier error de captura regresa al formulario
         * sin dejar archivos temporales.
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

            /*
             * Estos campos pueden autocompletarse y editarse.
             * Si llegan vacíos se usan los datos de la base.
             */
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

            'archivo_word' => [
                'required',
                'file',
                'mimes:docx',
                'max:10240',
            ],

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

            'hora_suspendida' => [
                'required',
                'date_format:H:i',
            ],

            'hora_reanudacion' => [
                'required',
                'date_format:H:i',
            ],

            'hubo_repreguntas' => [
                'required',
                'in:si,no',
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

            'participantes.*.repregunta' => [
                'nullable',
                'string',
            ],

            'participantes.*.respuesta' => [
                'nullable',
                'string',
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

            'in' =>
                'El valor seleccionado para :attribute no es válido.',

            'file' =>
                'Debe seleccionar un archivo válido para :attribute.',

            'mimes' =>
                'La plantilla debe ser un archivo Word con extensión .docx.',

            'max' =>
                'El campo :attribute no debe exceder el límite permitido.',

            'numero_busqueda.required' =>
                'Debe ingresar el número de búsqueda.',

            'archivo_word.required' =>
                'Debe seleccionar una plantilla Word.',

            'archivo_word.file' =>
                'Debe seleccionar un archivo válido.',

            'archivo_word.mimes' =>
                'La plantilla debe ser un archivo Word con extensión .docx.',

            'archivo_word.max' =>
                'La plantilla Word no debe superar los 10 MB.',

            'area_requirente.exists' =>
                'La persona seleccionada del área requirente no existe.',

            'area_contratante.required' =>
                'Debe seleccionar a la persona del área contratante.',

            'area_contratante.exists' =>
                'La persona seleccionada del área contratante no existe.',

            'persona_oic.exists' =>
                'La persona seleccionada del OIC no existe.',

            'persona_juridico.exists' =>
                'La persona seleccionada del área jurídica no existe.',

            'hora_suspendida.required' =>
                'Debe ingresar la hora de suspensión.',

            'hora_suspendida.date_format' =>
                'La hora de suspensión no es válida.',

            'hora_reanudacion.required' =>
                'Debe ingresar la hora de reanudación.',

            'hora_reanudacion.date_format' =>
                'La hora de reanudación no es válida.',

            'hubo_repreguntas.required' =>
                'Debe indicar si se recibieron repreguntas.',

            'hubo_repreguntas.in' =>
                'La opción de repreguntas seleccionada no es válida.',
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
                'número del procedimiento',

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

            'archivo_word' =>
                'plantilla Word',

            'area_requirente' =>
                'persona del área requirente',

            'area_contratante' =>
                'persona del área contratante',

            'persona_oic' =>
                'persona del OIC',

            'persona_juridico' =>
                'persona jurídica',

            'hora_suspendida' =>
                'hora de suspensión',

            'hora_reanudacion' =>
                'hora de reanudación',

            'hubo_repreguntas' =>
                'recepción de repreguntas',

            'participantes' =>
                'participantes',
        ];
    }

    /**
     * Prepara todos los datos del documento.
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

        $huboRepreguntas =
            $datosValidados['hubo_repreguntas'];

        $participantes = $this->prepararParticipantes(
            $request->input('participantes', []),
            $huboRepreguntas
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
                $this->calcularHoraCierre(
                    $datosProcedimiento['hora_inicio_base'],
                    $huboRepreguntas
                ),

            'hora_suspendida' =>
                $this->formatearHoraDocumento(
                    $datosValidados['hora_suspendida']
                ),

            'hora_reanudacion' =>
                $this->formatearHoraDocumento(
                    $datosValidados['hora_reanudacion']
                ),

            'fecha_apertura' =>
                $datosProcedimiento['fecha_apertura_texto'],

            'area_requirente' =>
                $this->crearTextoPersona(
                    $personas['area_requirente']
                ),

            'area_contratante' =>
                $this->crearTextoPersona(
                    $personas['area_contratante']
                ),

            'persona_oic' =>
                $this->crearTextoNombre(
                    $personas['oic']
                ),

            'persona_juridico' =>
                $this->crearTextoNombre(
                    $personas['juridico']
                ),

            'comprador' =>
                $this->crearTextoComprador(
                    Auth::user()
                ),

            'texto_repreguntas' =>
                $huboRepreguntas === 'si'
                    ? 'SÍ SE RECIBIERON REPREGUNTAS'
                    : 'NO SE RECIBIERON REPREGUNTAS',

            'hubo_repreguntas' =>
                $huboRepreguntas,

            'participantes' =>
                $participantes,
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

            'hora_inicio' =>
                $horaAcCarbon->format('H:i')
                . ' horas',

            'hora_inicio_base' =>
                $horaAcCarbon,

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
     * Obtiene las personas seleccionadas mediante una sola consulta.
     */
    private function resolverPersonasDocumento(
        Request $request
    ): array {
        $ids = array_values(
            array_unique(
                array_filter([
                    $request->area_requirente,
                    $request->area_contratante,
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

        $areaRequirente = $personas->get(
            (int) $request->area_requirente
        );

        $areaContratante = $personas->get(
            (int) $request->area_contratante
        );

        if (
            !$areaRequirente ||
            !$areaContratante
        ) {
            throw ValidationException::withMessages([
                'area_requirente' =>
                    'No fue posible obtener la información de las personas seleccionadas.',
            ]);
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
     * Prepara y valida los participantes.
     */
    private function prepararParticipantes(
        array $participantes,
        string $huboRepreguntas
    ): array {
        $resultado = [];
        $errores = [];

        foreach ($participantes as $indice => $participante) {
            if (!is_array($participante)) {
                continue;
            }

            $nombre = trim(
                (string) ($participante['nombre'] ?? '')
            );

            if ($nombre === '') {
                continue;
            }

            $repregunta = trim(
                (string) ($participante['repregunta'] ?? '')
            );

            $respuesta = trim(
                (string) ($participante['respuesta'] ?? '')
            );

            if ($huboRepreguntas === 'si') {
                if ($repregunta === '') {
                    $errores[
                        "participantes.{$indice}.repregunta"
                    ] = 'Debe capturar la repregunta del participante '
                        . ($indice + 1)
                        . '.';
                }

                if ($respuesta === '') {
                    $errores[
                        "participantes.{$indice}.respuesta"
                    ] = 'Debe capturar la respuesta del participante '
                        . ($indice + 1)
                        . '.';
                }
            }

            $resultado[] = [
                'nombre' =>
                    $nombre,

                'repregunta' =>
                    $huboRepreguntas === 'si'
                        ? $repregunta
                        : 'NO PRESENTÓ',

                'respuesta' =>
                    $huboRepreguntas === 'si'
                        ? $respuesta
                        : '',
            ];
        }

        if ($errores) {
            throw ValidationException::withMessages(
                $errores
            );
        }

        return $resultado;
    }

    /**
     * Coloca los datos en la plantilla Word.
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

            'fecha_ac' =>
                $datos['fecha_ac'],

            'hora_inicio' =>
                $datos['hora_inicio'],

            'hora_cierre' =>
                $datos['hora_cierre'],

            'hora_suspendida' =>
                $datos['hora_suspendida'],

            'hora_reanudacion' =>
                $datos['hora_reanudacion'],

            'fecha_apertura' =>
                $datos['fecha_apertura'],

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

            'texto_repreguntas' =>
                $datos['texto_repreguntas'],
        ];

        foreach ($valores as $marcador => $valor) {
            $template->setValue(
                $marcador,
                $this->limpiarTexto($valor)
            );
        }

        $this->clonarParticipantes(
            $template,
            $datos['participantes']
        );
    }

    /**
     * Clona la tabla de participantes en Word.
     */
    private function clonarParticipantes(
        TemplateProcessor $template,
        array $participantes
    ): void {
        if (!$participantes) {
            $template->setValue('empresa', '');
            $template->setValue('repregunta', '');
            $template->setValue('respuesta', '');

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
                "repregunta#{$fila}",
                $this->limpiarTexto(
                    $participante['repregunta']
                )
            );

            $template->setValue(
                "respuesta#{$fila}",
                mb_strtoupper(
                    $this->limpiarTexto(
                        $participante['respuesta']
                    ),
                    'UTF-8'
                )
            );
        }
    }

    /**
     * Busca el procedimiento por el número capturado.
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
                'plantilla_acta_cierre_',
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
            'acta_cierre_'
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
            'path' =>
                $ruta,

            'name' =>
                $nombre,
        ];
    }

    /**
     * Calcula la hora de cierre.
     */
    private function calcularHoraCierre(
        Carbon $horaInicio,
        string $huboRepreguntas
    ): string {
        $horaCierre = $horaInicio->copy();

        if ($huboRepreguntas === 'si') {
            $horaCierre->addHour();
        } else {
            $horaCierre->addMinutes(30);
        }

        return
            $horaCierre->format('H:i')
            . ' horas';
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
     * Formatea una hora para el documento.
     */
    private function formatearHoraDocumento(
        $hora
    ): string {
        return
            Carbon::parse($hora)->format('H:i')
            . ' horas';
    }

    /**
     * Formatea una fecha para controles HTML date.
     */
    private function formatearFechaInput(
        $valor
    ): string {
        return $valor
            ? Carbon::parse($valor)->format('Y-m-d')
            : '';
    }

    /**
     * Formatea una hora para controles HTML time.
     */
    private function formatearHoraInput(
        $valor
    ): string {
        return $valor
            ? Carbon::parse($valor)->format('H:i')
            : '';
    }

    /**
     * Genera el texto Nombre.- Cargo.
     */
    private function crearTextoPersona(
        $persona
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
                . '.- '
                . $cargo;
        }

        return $nombre !== ''
            ? $nombre
            : $cargo;
    }

    /**
     * Devuelve únicamente el nombre de una persona.
     */
    private function crearTextoNombre(
        $persona
    ): string {
        return $persona
            ? trim((string) $persona->nombre)
            : '';
    }

    /**
     * Genera el texto del usuario que elabora.
     */
    private function crearTextoComprador(
        $usuario
    ): string {
        if (!$usuario) {
            return '';
        }

        $nombre = trim(
            (string) $usuario->name
        );

        $cargo = trim(
            (string) $usuario->cargo
        );

        if (
            $nombre !== '' &&
            $cargo !== ''
        ) {
            return
                $nombre
                . '.- '
                . $cargo;
        }

        return $nombre !== ''
            ? $nombre
            : $cargo;
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
     * Limpia caracteres no permitidos en el XML interno del Word.
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