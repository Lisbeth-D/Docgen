<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Models\Procedimiento;
use App\Services\HistorialDocumentosService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpWord\TemplateProcessor;

class RevisionController extends Controller
{
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
            'comprador.revision.revision',
            compact('personas')
        );
    }

    public function buscarProcedimiento($valor)
    {
        $valor = trim($valor);

        $procedimiento = Procedimiento::with('tipo')
            ->where(
                'num_procedimiento',
                'LIKE',
                '%-N-' . $valor . '-%'
            )
            ->first();

        if (!$procedimiento) {
            return response()->json(null);
        }

        return response()->json([
            'num_procedimiento' =>
                $procedimiento->num_procedimiento,

            'nombre_procedimiento' =>
                $procedimiento->nombre_procedimiento,

            'tipo' =>
                optional($procedimiento->tipo)->nombre_tipo ?? '',
        ]);
    }

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

                'fecha_publicacion' => [
                    'nullable',
                    'date',
                ],

                'numero_busqueda' => [
                    'required',
                    'string',
                    'max:50',
                ],

                /*
                 * Se autocompletan, pero pueden modificarse manualmente.
                 * No se dejan como required para evitar alertas innecesarias.
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

                'tipo_procedimiento' => [
                    'nullable',
                    'string',
                    'max:255',
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

                'fecha_publicacion.date' =>
                    'La fecha de publicación no es válida.',

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

                'tipo_procedimiento.string' =>
                    'El tipo de procedimiento debe ser texto.',

                'tipo_procedimiento.max' =>
                    'El tipo de procedimiento no debe exceder los 255 caracteres.',

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

        $procedimiento = Procedimiento::with('tipo')
            ->where(
                'num_procedimiento',
                'LIKE',
                '%-N-' . trim($request->numero_busqueda) . '-%'
            )
            ->first();

        if (!$procedimiento) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'No se encontró el procedimiento.'
                );
        }

        /*
         * Se usan los valores enviados desde el formulario.
         * Si fueron editados manualmente, esos serán los que
         * se coloquen en el documento Word.
         *
         * Si llegan vacíos, se usan los datos originales
         * almacenados en la base de datos.
         */
        $numeroProcedimiento = $request->filled('num_procedimiento')
            ? trim((string) $request->num_procedimiento)
            : $procedimiento->num_procedimiento;

        $nombreProcedimiento = $request->filled('nombre_procedimiento')
            ? trim((string) $request->nombre_procedimiento)
            : $procedimiento->nombre_procedimiento;

        $tipoProcedimiento = $request->filled('tipo_procedimiento')
            ? trim((string) $request->tipo_procedimiento)
            : optional($procedimiento->tipo)->nombre_tipo;

        /*
         * Persona que revisa el documento.
         */
        $textoReviso = '';

        if ($request->filled('reviso_id')) {
            $persona = Persona::find($request->reviso_id);

            if ($persona) {
                $nombreReviso = trim((string) $persona->nombre);
                $cargoReviso = trim((string) $persona->cargo);

                if ($nombreReviso !== '' && $cargoReviso !== '') {
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
        $user = Auth::user();

        $textoElaboro = '';

        if ($user) {
            $nombreElaboro = trim((string) $user->name);
            $cargoElaboro = trim((string) $user->cargo);

            if ($nombreElaboro !== '' && $cargoElaboro !== '') {
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
         * Fecha del oficio.
         */
        $fechaOficio = Carbon::parse(
            $request->fecha_oficio
        )
            ->locale('es')
            ->translatedFormat(
                'd \d\e F \d\e Y'
            );

        /*
         * Fecha de publicación.
         */
        $fechaPublicacion = '';

        if ($request->filled('fecha_publicacion')) {
            $fechaPublicacion = Carbon::parse(
                $request->fecha_publicacion
            )
                ->locale('es')
                ->translatedFormat(
                    'd \d\e F'
                ) .
                ' del presente.';
        }

        if (!$request->hasFile('archivo_word')) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'No se subió ningún archivo Word.'
                );
        }

        /*
         * Guardar temporalmente la plantilla.
         */
        $file = $request->file('archivo_word');

        $filename =
            time() .
            '_' .
            $file->getClientOriginalName();

        $templateDir = storage_path(
            'app/plantillas'
        );

        if (!file_exists($templateDir)) {
            mkdir(
                $templateDir,
                0777,
                true
            );
        }

        $file->move(
            $templateDir,
            $filename
        );

        $templatePath =
            $templateDir .
            DIRECTORY_SEPARATOR .
            $filename;

        $templateProcessor = new TemplateProcessor(
            $templatePath
        );

        /*
         * Etiquetas generales.
         */
        $templateProcessor->setValue(
            'numero_referencia',
            trim((string) $request->numero_referencia)
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
         * Aquí se usan los valores del formulario, incluidos
         * los cambios manuales realizados antes de generar.
         */
        $templateProcessor->setValue(
            'num_procedimiento',
            $numeroProcedimiento
        );

        $templateProcessor->setValue(
            'nombre_procedimiento',
            $nombreProcedimiento
        );

        $templateProcessor->setValue(
            'tipo_procedimiento',
            $tipoProcedimiento ?? ''
        );

        /*
         * Firmas o nombres.
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
         * Crear el documento.
         */
        $outputDir = storage_path(
            'app/public/documentos'
        );

        if (!file_exists($outputDir)) {
            mkdir(
                $outputDir,
                0777,
                true
            );
        }

        $outputName =
            'revision_' .
            time() .
            '.docx';

        $outputPath =
            $outputDir .
            DIRECTORY_SEPARATOR .
            $outputName;

        $templateProcessor->saveAs(
            $outputPath
        );

        clearstatcache(true, $outputPath);

        if (!file_exists($outputPath) || !is_file($outputPath)) {
            if (file_exists($templatePath)) {
                unlink($templatePath);
            }

            return back()
                ->withInput()
                ->with(
                    'error',
                    'El documento fue generado, pero no se encontró en el almacenamiento.'
                );
        }

        if ((int) filesize($outputPath) <= 0) {
            if (file_exists($templatePath)) {
                unlink($templatePath);
            }

            if (file_exists($outputPath)) {
                unlink($outputPath);
            }

            return back()
                ->withInput()
                ->with(
                    'error',
                    'El documento Word generado está vacío.'
                );
        }

        /*
         * Registrar una copia del documento en el historial del usuario.
         * El servicio elimina los documentos vencidos y conserva esta
         * copia durante 10 días.
         */
        $historialDocumentos->registrar(
            $request->user(),
            $outputPath,
            $outputName,
            'Revisión de convocatoria',
            trim((string) $numeroProcedimiento),
            10
        );

        /*
         * Eliminar únicamente la plantilla temporal.
         */
        if (file_exists($templatePath)) {
            unlink($templatePath);
        }

        /*
         * La descarga inmediata se elimina después de enviarse.
         * La copia registrada por HistorialDocumentosService queda
         * disponible en Historial de documentos durante 10 días.
         */
        return response()
            ->download(
                $outputPath,
                $outputName
            )
            ->deleteFileAfterSend(true);
    }
}