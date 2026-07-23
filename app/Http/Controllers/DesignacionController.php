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

class DesignacionController extends Controller
{
    public function index()
    {
        $nombreAreaCoordinacion =
            'Coordinación General de Adquisiciones y Servicios';

        /*
         * Personas disponibles para seleccionar quién revisa.
         */
        $personas = Persona::whereHas('area', function ($query) use (
            $nombreAreaCoordinacion
        ) {
                $query->where(
                    'nombre',
                    $nombreAreaCoordinacion
                );
            })
            ->orderBy('nombre')
            ->get();

        /*
         * Referencia predeterminada para OIC y Jurídico.
         *
         * Se toma la plantilla de referencia de la única persona
         * registrada en el área de Gerencia.
         */
        $personaGerencia = Persona::whereHas('area', function ($query) {
                $query->where(
                    'nombre',
                    'Gerencia'
                );
            })
            ->first();

        $referenciaGerencia = $personaGerencia
            ? trim((string) $personaGerencia->plantilla_referencia)
            : '';

        /*
         * Referencia predeterminada del área requirente.
         *
         * Se toma la plantilla de referencia de la primera persona
         * registrada en la Coordinación General de Adquisiciones
         * y Servicios que tenga una plantilla capturada.
         */
        $personaCoordinacion = Persona::whereHas('area', function ($query) use (
            $nombreAreaCoordinacion
        ) {
                $query->where(
                    'nombre',
                    $nombreAreaCoordinacion
                );
            })
            ->whereNotNull('plantilla_referencia')
            ->where('plantilla_referencia', '<>', '')
            ->first();

        $referenciaAreaRequirente = $personaCoordinacion
            ? trim((string) $personaCoordinacion->plantilla_referencia)
            : '';

        /*
         * Las tres referencias utilizarán inicialmente la misma fecha.
         * El campo permanece editable desde el formulario.
         */
        $fechaReferencias = now()->format('Y-m-d');

        return view(
            'comprador.Designacion.Designacion',
            compact(
                'personas',
                'referenciaGerencia',
                'referenciaAreaRequirente',
                'fechaReferencias'
            )
        );
    }

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

        /*
         * Persona del área requirente vinculada al procedimiento.
         *
         * El procedimiento guarda a la persona requirente en id_persona.
         * De ella se obtienen el nombre y el cargo para autocompletar
         * el formulario, conservando ambos campos editables.
         */
        $personaAreaRequirente = $procedimiento->id_persona
            ? Persona::find($procedimiento->id_persona)
            : null;

        return response()->json([
            'num_procedimiento' =>
                $procedimiento->num_procedimiento,

            'nombre_procedimiento' =>
                $procedimiento->nombre_procedimiento,

            'nombre_area_requirente' =>
                $personaAreaRequirente
                    ? trim((string) $personaAreaRequirente->nombre)
                    : '',

            'cargo_area_requirente' =>
                $personaAreaRequirente
                    ? trim((string) $personaAreaRequirente->cargo)
                    : '',

            'fecha_vm' =>
                $procedimiento->fecha_vm
                    ? Carbon::parse(
                        $procedimiento->fecha_vm
                    )->format('Y-m-d')
                    : '',

            'fecha_ac' =>
                $procedimiento->fecha_ac
                    ? Carbon::parse(
                        $procedimiento->fecha_ac
                    )->format('Y-m-d')
                    : '',

            'fecha_apertura' =>
                $procedimiento->fecha_apertura
                    ? Carbon::parse(
                        $procedimiento->fecha_apertura
                    )->format('Y-m-d')
                    : '',

            'fecha_fallo' =>
                $procedimiento->fecha_fallo
                    ? Carbon::parse(
                        $procedimiento->fecha_fallo
                    )->format('Y-m-d')
                    : '',

            'hora_vm' =>
                $procedimiento->hora_vm
                    ? Carbon::parse(
                        $procedimiento->hora_vm
                    )->format('H:i')
                    : '',

            'hora_ac' =>
                $procedimiento->hora_ac
                    ? Carbon::parse(
                        $procedimiento->hora_ac
                    )->format('H:i')
                    : '',

            'hora_apertura' =>
                $procedimiento->hora_apertura
                    ? Carbon::parse(
                        $procedimiento->hora_apertura
                    )->format('H:i')
                    : '',

            'hora_fallo' =>
                $procedimiento->hora_fallo
                    ? Carbon::parse(
                        $procedimiento->hora_fallo
                    )->format('H:i')
                    : '',
        ]);
    }

    public function generar(
        Request $request,
        HistorialDocumentosService $historialDocumentos
    )
    {
        $request->validate(
            [

                'numero_busqueda' => [
                    'required',
                    'string',
                    'max:50',
                ],

                /*
                 * Estos campos se autocompletan, pero también pueden
                 * ser modificados manualmente.
                 *
                 * Ya no son obligatorios porque numero_busqueda
                 * identifica el procedimiento.
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

                'nombre_area_requirente' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'cargo_area_requirente' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'fecha_vm' => [
                    'nullable',
                    'date',
                ],

                'hora_vm' => [
                    'nullable',
                    'date_format:H:i',
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

                'fecha_fallo' => [
                    'nullable',
                    'date',
                ],

                'hora_fallo' => [
                    'nullable',
                    'date_format:H:i',
                ],

                'referencia_oic' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'referencia_juridico' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'referencia_area_requirente' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'fecha_referencias' => [
                    'required',
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
                'numero_busqueda.required' =>
                    'Debe ingresar el número de búsqueda.',

                'nombre_area_requirente.max' =>
                    'El nombre del área requirente no debe exceder los 255 caracteres.',

                'cargo_area_requirente.max' =>
                    'El cargo del área requirente no debe exceder los 255 caracteres.',

                'referencia_oic.max' =>
                    'La referencia del OIC no debe exceder los 255 caracteres.',

                'referencia_juridico.max' =>
                    'La referencia de Jurídico no debe exceder los 255 caracteres.',

                'referencia_area_requirente.max' =>
                    'La referencia del área requirente no debe exceder los 255 caracteres.',

                'fecha_referencias.required' =>
                    'Debe ingresar la fecha de las referencias.',

                'fecha_referencias.date' =>
                    'La fecha de las referencias no es válida.',

                'reviso_id.exists' =>
                    'La persona seleccionada para revisión no existe.',

                'archivo_word.required' =>
                    'Debe seleccionar una plantilla Word.',

                'archivo_word.mimes' =>
                    'La plantilla debe ser un archivo con extensión .docx.',

                'archivo_word.max' =>
                    'La plantilla no debe superar los 10 MB.',
            ]
        );

        /*
         * Buscar el procedimiento por el número ingresado.
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
         * Usar los datos enviados desde el formulario.
         *
         * Si la persona modificó manualmente alguno de estos campos,
         * ese nuevo valor será el que aparezca en el Word.
         *
         * Si el campo llega vacío, se utiliza como respaldo el valor
         * registrado originalmente en la base de datos.
         */
        $numeroProcedimientoWord = $request->filled(
            'num_procedimiento'
        )
            ? trim((string) $request->num_procedimiento)
            : $procedimiento->num_procedimiento;

        $nombreProcedimientoWord = $request->filled(
            'nombre_procedimiento'
        )
            ? trim((string) $request->nombre_procedimiento)
            : $procedimiento->nombre_procedimiento;

        /*
         * Nombre y cargo del área requirente.
         *
         * Se autocompletan al buscar el procedimiento, pero se toman
         * del formulario para respetar cualquier edición manual.
         */
        $nombreAreaRequirente = trim(
            (string) $request->input(
                'nombre_area_requirente',
                ''
            )
        );

        $cargoAreaRequirente = trim(
            (string) $request->input(
                'cargo_area_requirente',
                ''
            )
        );

        /*
         * Referencias editables enviadas desde el formulario.
         */
        $referenciaOic = trim(
            (string) $request->input(
                'referencia_oic',
                ''
            )
        );

        $referenciaJuridico = trim(
            (string) $request->input(
                'referencia_juridico',
                ''
            )
        );

        $referenciaAreaRequirente = trim(
            (string) $request->input(
                'referencia_area_requirente',
                ''
            )
        );

        /*
         * Las tres referencias utilizan una sola fecha.
         *
         * Ejemplo:
         * 22 de julio de 2026
         */
        $fechaReferencias = Carbon::parse(
            $request->fecha_referencias
        )
            ->locale('es')
            ->translatedFormat(
                'd \d\e F \d\e Y'
            );

        /*
         * Persona que revisa.
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
         * Usuario que elabora.
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
         * Fechas de los eventos.
         */
        $fechaVm = $request->filled('fecha_vm')
            ? ucfirst(
                Carbon::parse(
                    $request->fecha_vm
                )
                    ->locale('es')
                    ->translatedFormat('d-F-Y')
            )
            : 'N/A';

        $fechaAc = $request->filled('fecha_ac')
            ? ucfirst(
                Carbon::parse(
                    $request->fecha_ac
                )
                    ->locale('es')
                    ->translatedFormat('d-F-Y')
            )
            : 'N/A';

        $fechaApertura = $request->filled(
            'fecha_apertura'
        )
            ? ucfirst(
                Carbon::parse(
                    $request->fecha_apertura
                )
                    ->locale('es')
                    ->translatedFormat('d-F-Y')
            )
            : 'N/A';

        $fechaFallo = $request->filled('fecha_fallo')
            ? ucfirst(
                Carbon::parse(
                    $request->fecha_fallo
                )
                    ->locale('es')
                    ->translatedFormat('d-F-Y')
            )
            : 'N/A';

        /*
         * Horas de los eventos.
         */
        $horaVm = $request->filled('hora_vm')
            ? Carbon::createFromFormat(
                'H:i',
                $request->hora_vm
            )->format('H:i') . ' horas'
            : 'N/A';

        $horaAc = $request->filled('hora_ac')
            ? Carbon::createFromFormat(
                'H:i',
                $request->hora_ac
            )->format('H:i') . ' horas'
            : 'N/A';

        $horaApertura = $request->filled(
            'hora_apertura'
        )
            ? Carbon::createFromFormat(
                'H:i',
                $request->hora_apertura
            )->format('H:i') . ' horas'
            : 'N/A';

        $horaFallo = $request->filled('hora_fallo')
            ? Carbon::createFromFormat(
                'H:i',
                $request->hora_fallo
            )->format('H:i') . ' horas'
            : 'N/A';

        $templatePath = null;
        $outputPath = null;

        try {
            /*
             * Guardar temporalmente la plantilla.
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

            $templateProcessor =
                new TemplateProcessor(
                    $templatePath
                );

            /*
             * Referencias de las áreas.
             *
             * OIC y Jurídico se autocompletan inicialmente con
             * la plantilla de Gerencia, pero permanecen editables.
             *
             * El área requirente se autocompleta con la plantilla
             * de la Coordinación General de Adquisiciones y Servicios.
             */
            $templateProcessor->setValue(
                'referencia_oic',
                $this->valorWord(
                    $referenciaOic
                )
            );

            $templateProcessor->setValue(
                'referencia_juridico',
                $this->valorWord(
                    $referenciaJuridico
                )
            );

            $templateProcessor->setValue(
                'referencia_area_requirente',
                $this->valorWord(
                    $referenciaAreaRequirente
                )
            );

            /*
             * Una sola etiqueta de fecha puede colocarse todas
             * las veces necesarias dentro de la plantilla Word.
             */
            $templateProcessor->setValue(
                'fecha_referencias',
                $fechaReferencias
            );

            /*
             * Datos editables de la persona del área requirente.
             */
            $templateProcessor->setValue(
                'nombre_area_requirente',
                $this->valorWord(
                    $nombreAreaRequirente
                )
            );

            $templateProcessor->setValue(
                'cargo_area_requirente',
                $this->valorWord(
                    $cargoAreaRequirente
                )
            );

            /*
             * Se utilizan los datos del formulario.
             *
             * Si fueron modificados manualmente,
             * el Word mostrará esos cambios.
             */
            $templateProcessor->setValue(
                'num_procedimiento',
                $this->valorWord(
                    $numeroProcedimientoWord
                )
            );

            $templateProcessor->setValue(
                'nombre_procedimiento',
                $this->valorWord(
                    $nombreProcedimientoWord
                )
            );

            /*
             * Fechas y horas.
             */
            $templateProcessor->setValue(
                'fecha_vm',
                $fechaVm
            );

            $templateProcessor->setValue(
                'hora_vm',
                $horaVm
            );

            $templateProcessor->setValue(
                'fecha_ac',
                $fechaAc
            );

            $templateProcessor->setValue(
                'hora_ac',
                $horaAc
            );

            $templateProcessor->setValue(
                'fecha_apertura',
                $fechaApertura
            );

            $templateProcessor->setValue(
                'hora_apertura',
                $horaApertura
            );

            $templateProcessor->setValue(
                'fecha_fallo',
                $fechaFallo
            );

            $templateProcessor->setValue(
                'hora_fallo',
                $horaFallo
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
             * Guardar documento generado.
             */
            $directorioDocumentos = storage_path(
                'app/public/documentos'
            );

            File::ensureDirectoryExists(
                $directorioDocumentos
            );

            $nombreDocumento =
                'designacion_' .
                now()->format('Ymd_His') .
                '.docx';

            $outputPath =
                $directorioDocumentos .
                DIRECTORY_SEPARATOR .
                $nombreDocumento;

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
             * La copia queda disponible durante 10 días.
             */
            $historialDocumentos->registrar(
                $request->user(),
                $outputPath,
                $nombreDocumento,
                'Oficio de designación',
                trim((string) $numeroProcedimientoWord),
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
             * de enviarse. La copia registrada en el historial
             * permanece disponible.
             */
            return response()
                ->download(
                    $outputPath,
                    $nombreDocumento
                )
                ->deleteFileAfterSend(true);
        } catch (Throwable $e) {
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

    private function valorWord($valor): string
    {
        return $valor !== null
            ? trim((string) $valor)
            : '';
    }
}