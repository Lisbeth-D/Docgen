<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Models\Procedimiento;
use App\Services\HistorialDocumentosService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpWord\TemplateProcessor;
use Throwable;

class PublicacionController extends Controller
{
    /**
     * Muestra el formulario de generación
     * del oficio de publicación.
     */
    public function index()
    {
        $personas = Persona::whereHas('area', function ($query) {
                $query->where(
                    'nombre',
                    'Coordinación General de Adquisiciones y Servicios'
                );
            })
            ->orderBy('nombre')
            ->get();

        return view(
            'comprador.publicacion.publicacion',
            compact('personas')
        );
    }

    /**
     * Busca un procedimiento para autocompletar el formulario.
     */
    public function buscarProcedimiento($valor)
    {
        $valor = trim($valor);

        $procedimiento = Procedimiento::where(
            'num_procedimiento',
            'LIKE',
            '%-N-' . $valor . '-%'
        )->first();

        if (!$procedimiento) {
            return response()->json(null);
        }

        return response()->json([
            'num_procedimiento' =>
                $procedimiento->num_procedimiento,

            'nombre_procedimiento' =>
                $procedimiento->nombre_procedimiento,

            'fecha_publicacion' =>
                $procedimiento->fecha_publicacion
                    ? Carbon::parse(
                        $procedimiento->fecha_publicacion
                    )->format('Y-m-d')
                    : '',
        ]);
    }

    /**
     * Genera el documento Word del oficio de publicación.
     */
    public function generar(
        Request $request,
        HistorialDocumentosService $historialDocumentos
    )
    {
        $request->validate(
            [
                'numero_referencia' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'fecha_oficio' => [
                    'required',
                    'date',
                ],

                'numero_busqueda' => [
                    'required',
                    'string',
                    'max:50',
                ],

                /*
                 * Se autocompletan al buscar, pero pueden
                 * modificarse manualmente.
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

                'fecha_publicacion' => [
                    'nullable',
                    'date',
                ],

                'reviso_id' => [
                    'nullable',
                    'integer',
                    'exists:personas,id',
                ],

                'archivo_word' => [
                    'required',
                    'file',
                    'mimes:docx',
                    'max:10240',
                ],
            ],
            [
                'numero_referencia.required' =>
                    'Debe ingresar el número de referencia.',

                'numero_referencia.string' =>
                    'El número de referencia debe ser texto.',

                'numero_referencia.max' =>
                    'El número de referencia no debe exceder los 255 caracteres.',

                'fecha_oficio.required' =>
                    'Debe ingresar la fecha del oficio.',

                'fecha_oficio.date' =>
                    'La fecha del oficio no es válida.',

                'numero_busqueda.required' =>
                    'Debe ingresar el número de búsqueda.',

                'numero_busqueda.string' =>
                    'El número de búsqueda debe ser texto.',

                'numero_busqueda.max' =>
                    'El número de búsqueda no debe exceder los 50 caracteres.',

                'num_procedimiento.string' =>
                    'El número del procedimiento debe ser texto.',

                'num_procedimiento.max' =>
                    'El número del procedimiento no debe exceder los 255 caracteres.',

                'nombre_procedimiento.string' =>
                    'El nombre del procedimiento debe ser texto.',

                'nombre_procedimiento.max' =>
                    'El nombre del procedimiento no debe exceder los 1000 caracteres.',

                'fecha_publicacion.date' =>
                    'La fecha de publicación no es válida.',

                'reviso_id.integer' =>
                    'La persona seleccionada para revisión no es válida.',

                'reviso_id.exists' =>
                    'La persona seleccionada para revisión no existe.',

                'archivo_word.required' =>
                    'Debe seleccionar una plantilla Word.',

                'archivo_word.file' =>
                    'Debe seleccionar un archivo válido.',

                'archivo_word.mimes' =>
                    'La plantilla debe ser un archivo Word con extensión .docx.',

                'archivo_word.max' =>
                    'La plantilla Word no debe superar los 10 MB.',
            ]
        );

        /*
         * Buscar el procedimiento registrado.
         */
        $procedimiento = Procedimiento::where(
            'num_procedimiento',
            'LIKE',
            '%-N-' . trim($request->numero_busqueda) . '-%'
        )->first();

        if (!$procedimiento) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'No se encontró el procedimiento.'
                );
        }

        /*
         * Se utilizan los valores enviados por el formulario.
         *
         * Si fueron corregidos manualmente, las correcciones
         * serán las que aparezcan en el documento Word.
         *
         * Si alguno llega vacío, se utiliza el valor original
         * guardado en la base de datos.
         */
        $numeroProcedimiento = $request->filled(
            'num_procedimiento'
        )
            ? trim((string) $request->num_procedimiento)
            : $procedimiento->num_procedimiento;

        $nombreProcedimiento = $request->filled(
            'nombre_procedimiento'
        )
            ? trim((string) $request->nombre_procedimiento)
            : $procedimiento->nombre_procedimiento;

        /*
         * Persona que revisa.
         *
         * Ejemplo:
         * Nombre.- Cargo:
         */
        $textoReviso = '';

        if ($request->filled('reviso_id')) {
            $persona = Persona::find(
                $request->reviso_id
            );

            if ($persona) {
                $nombreReviso = trim(
                    (string) $persona->nombre
                );

                $cargoReviso = trim(
                    (string) $persona->cargo
                );

                if (
                    $nombreReviso !== '' &&
                    $cargoReviso !== ''
                ) {
                    $textoReviso =
                        $nombreReviso .
                        '.- ' .
                        $cargoReviso .
                        ':';
                } elseif ($nombreReviso !== '') {
                    $textoReviso =
                        $nombreReviso .
                        ':';
                } elseif ($cargoReviso !== '') {
                    $textoReviso =
                        $cargoReviso .
                        ':';
                }
            }
        }

        /*
         * Usuario que elabora el documento.
         *
         * Ejemplo:
         * Comprador.- Auxiliar Administrativo
         */
        $usuario = Auth::user();

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

        /*
         * Formatear fecha del oficio.
         *
         * Ejemplo:
         * 15 de julio de 2026
         */
        $fechaOficio = Carbon::parse(
            $request->fecha_oficio
        )
            ->locale('es')
            ->translatedFormat(
                'd \d\e F \d\e Y'
            );

        /*
         * Formatear fecha de publicación.
         *
         * Ejemplo:
         * 15 de julio
         */
        $fechaPublicacion = '';

        if ($request->filled('fecha_publicacion')) {
            $fechaPublicacion = Carbon::parse(
                $request->fecha_publicacion
            )
                ->locale('es')
                ->translatedFormat(
                    'd \d\e F'
                );
        }

        $templatePath = null;
        $outputPath = null;

        try {
            /*
             * Guardar temporalmente la plantilla Word.
             */
            $archivo = $request->file(
                'archivo_word'
            );

            $nombrePlantilla =
                now()->format('Ymd_His') .
                '_' .
                $archivo->getClientOriginalName();

            $directorioPlantillas = storage_path(
                'app/plantillas'
            );

            File::ensureDirectoryExists(
                $directorioPlantillas
            );

            $archivo->move(
                $directorioPlantillas,
                $nombrePlantilla
            );

            $templatePath =
                $directorioPlantillas .
                DIRECTORY_SEPARATOR .
                $nombrePlantilla;

            /*
             * Abrir la plantilla Word.
             */
            $templateProcessor = new TemplateProcessor(
                $templatePath
            );

            /*
             * Etiquetas generales.
             */
            $templateProcessor->setValue(
                'numero_referencia',
                $this->valorWord(
                    $request->numero_referencia
                )
            );

            $templateProcessor->setValue(
                'fecha_oficio',
                $fechaOficio
            );

            $templateProcessor->setValue(
                'fecha_publicacion',
                $fechaPublicacion
            );

            /*
             * Información del procedimiento.
             *
             * Aquí se usan los datos enviados desde el formulario,
             * incluidos los cambios manuales.
             */
            $templateProcessor->setValue(
                'num_procedimiento',
                $this->valorWord(
                    $numeroProcedimiento
                )
            );

            $templateProcessor->setValue(
                'nombre_procedimiento',
                $this->valorWord(
                    $nombreProcedimiento
                )
            );

            /*
             * Firmas.
             */
            $templateProcessor->setValue(
                'reviso',
                $textoReviso
            );

            $templateProcessor->setValue(
                'elaboro',
                $textoElaboro
            );

            /*
             * Crear el directorio de documentos.
             */
            $directorioDocumentos = storage_path(
                'app/public/documentos'
            );

            File::ensureDirectoryExists(
                $directorioDocumentos
            );

            $nombreDocumento =
                'publicacion_' .
                now()->format('Ymd_His') .
                '.docx';

            $outputPath =
                $directorioDocumentos .
                DIRECTORY_SEPARATOR .
                $nombreDocumento;

            /*
             * Guardar el documento generado.
             */
            $templateProcessor->saveAs(
                $outputPath
            );

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
             * La copia permanece disponible durante 10 días.
             */
            $historialDocumentos->registrar(
                $request->user(),
                $outputPath,
                $nombreDocumento,
                'Oficio de publicación',
                trim((string) $numeroProcedimiento),
                10
            );

            /*
             * Eliminar únicamente la plantilla temporal.
             */
            if (
                $templatePath &&
                File::exists($templatePath)
            ) {
                File::delete($templatePath);
            }

            /*
             * La copia temporal de descarga se elimina después
             * de enviarse. La copia del historial se conserva.
             */
            return response()
                ->download(
                    $outputPath,
                    $nombreDocumento
                )
                ->deleteFileAfterSend(true);
        } catch (Throwable $e) {
            /*
             * Eliminar archivos temporales o incompletos.
             */
            if (
                $templatePath &&
                File::exists($templatePath)
            ) {
                File::delete($templatePath);
            }

            if (
                $outputPath &&
                File::exists($outputPath)
            ) {
                File::delete($outputPath);
            }

            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'No fue posible generar el documento Word. Revisa la plantilla y vuelve a intentarlo.'
                );
        }
    }

    /**
     * Convierte valores nulos en cadenas vacías.
     */
    private function valorWord($valor): string
    {
        return $valor !== null
            ? trim((string) $valor)
            : '';
    }
}