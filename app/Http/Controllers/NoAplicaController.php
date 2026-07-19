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

class NoAplicaController extends Controller
{
    /**
     * Muestra el formulario para generar el documento.
     */
    public function index()
    {
        $revisores = Persona::where('area_id', 4)
            ->orderBy('nombre')
            ->get();

        $usuario = Auth::user();

        $textoElaboro = $this->crearTextoElaboro(
            $usuario
        );

        return view(
            'comprador.Aclaracion.noaplica',
            compact(
                'revisores',
                'textoElaboro'
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

        return response()->json([
            'num_procedimiento' =>
                $procedimiento->num_procedimiento,

            'nombre_procedimiento' =>
                $procedimiento->nombre_procedimiento,

            'fecha_apertura' =>
                $this->formatearFechaInput(
                    $procedimiento->fecha_apertura
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

            'correo_comprador' => [
                'nullable',
                'email',
                'max:255',
            ],

            'reviso_id' => [
                'required',
                'integer',
                'exists:personas,id',
            ],

            /*
             * El campo elaboró ya no se captura manualmente.
             * Se genera con Auth::user().
             */
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

            'email' =>
                'El campo :attribute debe contener un correo electrónico válido.',

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

            'reviso_id.required' =>
                'Debe seleccionar a la persona que revisó el documento.',

            'reviso_id.exists' =>
                'La persona seleccionada para revisión no existe.',

            'correo_comprador.email' =>
                'El correo del comprador no tiene un formato válido.',

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

            'correo_comprador' =>
                'correo del comprador',

            'reviso_id' =>
                'persona que revisó',

            'archivo_word' =>
                'plantilla Word',
        ];
    }

    /**
     * Prepara los valores que serán enviados a la plantilla.
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

        $personaReviso = Persona::find(
            $datosValidados['reviso_id']
        );

        if (!$personaReviso) {
            throw ValidationException::withMessages([
                'reviso_id' =>
                    'No fue posible obtener la información de la persona que revisó.',
            ]);
        }

        $usuario = Auth::user();

        /*
         * Usuario que elabora.
         *
         * Ejemplo:
         * Comprador.- Auxiliar Administrativo
         */
        $textoElaboro = '';

        if ($usuario) {
            $nombreElaboro = trim(
                (string) $usuario->name
            );

            $cargoElaboro = trim(
                (string) $usuario->cargo
            );

            if (
                $nombreElaboro !== '' &&
                $cargoElaboro !== ''
            ) {
                $textoElaboro =
                    $nombreElaboro .
                    '.- ' .
                    $cargoElaboro;
            } elseif ($nombreElaboro !== '') {
                $textoElaboro =
                    $nombreElaboro;
            } elseif ($cargoElaboro !== '') {
                $textoElaboro =
                    $cargoElaboro;
            }
        }

        return [
            'num_procedimiento' =>
                $datosProcedimiento['numero'],

            'nombre_procedimiento' =>
                $datosProcedimiento['nombre'],

            'fecha_apertura' =>
                $datosProcedimiento['fecha_apertura'],

            'correo_comprador' =>
                $request->filled('correo_comprador')
                    ? trim(
                        (string) $request->correo_comprador
                    )
                    : trim(
                        (string) ($usuario?->email ?? '')
                    ),

            'reviso' =>
                $this->crearTextoReviso(
                    $personaReviso
                ),

            'elaboro' =>
                $textoElaboro,
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

        if ($errores) {
            throw ValidationException::withMessages(
                $errores
            );
        }

        return [
            'numero' =>
                $numero,

            'nombre' =>
                $nombre,

            'fecha_apertura' =>
                Carbon::parse(
                    $fechaApertura
                )->format('d/m/Y'),
        ];
    }

    /**
     * Coloca los valores en la plantilla Word.
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

            'correo_comprador' =>
                $datos['correo_comprador'],

            'reviso' =>
                $datos['reviso'],

            'elaboro' =>
                $datos['elaboro'],
        ];

        foreach ($valores as $marcador => $valor) {
            $template->setValue(
                $marcador,
                $this->limpiarTexto($valor)
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
     * Guarda temporalmente la plantilla.
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
                'plantilla_no_aplica_',
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

        $numeroSeguro = preg_replace(
            '/[^A-Za-z0-9_-]+/',
            '_',
            (string) $procedimiento->num_procedimiento
        );

        $nombre =
            'No_Aplica_Junta_'
            . trim((string) $numeroSeguro, '_')
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
     * Genera el texto de la persona que revisó.
     *
     * Ejemplo:
     * Nombre.- Cargo:
     */
    private function crearTextoReviso(
        Persona $persona
    ): string {
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
                . $cargo
                . ':';
        }

        if ($nombre !== '') {
            return $nombre . ':';
        }

        if ($cargo !== '') {
            return $cargo . ':';
        }

        return '';
    }

    /**
     * Genera el texto automático de quien elabora.
     */
    private function crearTextoElaboro(
        $usuario
    ): string {
        $textoElaboro = '';

        if ($usuario) {
            $nombreElaboro = trim(
                (string) $usuario->name
            );

            $cargoElaboro = trim(
                (string) $usuario->cargo
            );

            if (
                $nombreElaboro !== '' &&
                $cargoElaboro !== ''
            ) {
                $textoElaboro =
                    $nombreElaboro
                    . '.- '
                    . $cargoElaboro;
            } elseif ($nombreElaboro !== '') {
                $textoElaboro =
                    $nombreElaboro;
            } elseif ($cargoElaboro !== '') {
                $textoElaboro =
                    $cargoElaboro;
            }
        }

        return $textoElaboro;
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
     * Limpia caracteres inválidos para el XML de Word.
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