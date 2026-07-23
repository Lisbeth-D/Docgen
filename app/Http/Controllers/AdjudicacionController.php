<?php

namespace App\Http\Controllers;

use App\Models\DocumentoAdjudicacion;
use App\Models\Persona;
use App\Models\Procedimiento;
use App\Services\HistorialDocumentosService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpWord\TemplateProcessor;
use Throwable;

class AdjudicacionController extends Controller
{
    /**
     * Muestra el formulario de adjudicación.
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

        $documentos = DocumentoAdjudicacion::query()
            ->where('activo', true)
            ->orderBy('orden')
            ->orderBy('id_documento')
            ->get();

        return view(
            'comprador.adjudicacion.adjudicacion',
            compact('personas', 'documentos')
        );
    }

    /**
     * Busca un procedimiento para autocompletar el formulario.
     *
     * Esta búsqueda es opcional. Si no se encuentra el procedimiento,
     * el usuario puede capturar todos los datos manualmente.
     */
    public function buscarProcedimiento($valor)
    {
        $valor = trim((string) $valor);

        if ($valor === '') {
            return response()->json(null);
        }

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
            'tipo' =>
                optional($procedimiento->tipo)->nombre_tipo ?? '',

            'num_procedimiento' =>
                $procedimiento->num_procedimiento,

            'nombre_procedimiento' =>
                $procedimiento->nombre_procedimiento,

            'monto_maximo' =>
                $procedimiento->monto_maximo !== null
                    ? number_format(
                        (float) $procedimiento->monto_maximo,
                        2,
                        '.',
                        ','
                    )
                    : '',

            'fecha_inicio_contrato' =>
                $procedimiento->fecha_inicio_contrato
                    ? Carbon::parse(
                        $procedimiento->fecha_inicio_contrato
                    )->format('Y-m-d')
                    : '',

            'fecha_fin_contrato' =>
                $procedimiento->fecha_fin_contrato
                    ? Carbon::parse(
                        $procedimiento->fecha_fin_contrato
                    )->format('Y-m-d')
                    : '',
        ]);
    }

    /**
     * Genera el oficio de adjudicación.
     */
    public function generar(
        Request $request,
        HistorialDocumentosService $historialDocumentos
    )
    {
        $request->validate(
            [
                'oficio_numero' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'fecha_oficio' => [
                    'required',
                    'date',
                ],

                /*
                 * La búsqueda es opcional.
                 * El formulario también puede capturarse manualmente.
                 */
                'numero_busqueda' => [
                    'nullable',
                    'string',
                    'max:50',
                ],

                'tipo_contrato_monto' => [
                    'required',
                    'in:abierto,cerrado',
                ],

                'procedimiento_tipo' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                /*
                 * Estos dos campos son necesarios para el documento,
                 * ya sea que provengan de la búsqueda o de captura manual.
                 */
                'num_procedimiento' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'nombre_procedimiento' => [
                    'required',
                    'string',
                    'max:1000',
                ],

                'contrato_numero' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                /*
                 * Se validan manualmente porque pueden contener
                 * comas, espacios o signo de pesos.
                 */
                'monto_minimo' => [
                    'nullable',
                ],

                'monto_maximo' => [
                    'nullable',
                ],

                'fecha_inicio' => [
                    'nullable',
                    'date',
                ],

                'fecha_fin' => [
                    'nullable',
                    'date',
                    'after_or_equal:fecha_inicio',
                ],

                'proveedor_razon_social' => [
                    'nullable',
                    'string',
                    'max:500',
                ],

                'proveedor_rfc' => [
                    'nullable',
                    'string',
                    'max:50',
                ],

                'proveedor_domicilio' => [
                    'nullable',
                    'string',
                    'max:1000',
                ],

                'proveedor_email' => [
                    'nullable',
                    'email',
                    'max:255',
                ],

                'proveedor_telefono' => [
                    'nullable',
                    'string',
                    'max:50',
                ],

                'reviso_id' => [
                    'nullable',
                    'integer',
                    'exists:personas,id',
                ],

                'documentos' => [
                    'nullable',
                    'array',
                ],

                'documentos.*' => [
                    'integer',
                    'distinct',
                    'exists:documentos_adjudicacion,id_documento',
                ],

                'archivo_word' => [
                    'required',
                    'file',
                    'mimes:docx',
                    'max:10240',
                ],
            ],
            [
                'oficio_numero.required' =>
                    'Debe ingresar el número de oficio.',

                'oficio_numero.string' =>
                    'El número de oficio debe ser texto.',

                'oficio_numero.max' =>
                    'El número de oficio no debe exceder los 255 caracteres.',

                'fecha_oficio.required' =>
                    'Debe ingresar la fecha del oficio.',

                'fecha_oficio.date' =>
                    'La fecha del oficio no es válida.',

                'numero_busqueda.string' =>
                    'El número de búsqueda debe ser texto.',

                'numero_busqueda.max' =>
                    'El número de búsqueda no debe exceder los 50 caracteres.',

                'tipo_contrato_monto.required' =>
                    'Debe seleccionar si el contrato es abierto o cerrado.',

                'tipo_contrato_monto.in' =>
                    'El tipo de contrato seleccionado no es válido.',

                'procedimiento_tipo.string' =>
                    'El tipo de procedimiento debe ser texto.',

                'procedimiento_tipo.max' =>
                    'El tipo de procedimiento no debe exceder los 255 caracteres.',

                'num_procedimiento.required' =>
                    'Debe ingresar el número del procedimiento.',

                'num_procedimiento.string' =>
                    'El número del procedimiento debe ser texto.',

                'num_procedimiento.max' =>
                    'El número del procedimiento no debe exceder los 255 caracteres.',

                'nombre_procedimiento.required' =>
                    'Debe ingresar el nombre del procedimiento.',

                'nombre_procedimiento.string' =>
                    'El nombre del procedimiento debe ser texto.',

                'nombre_procedimiento.max' =>
                    'El nombre del procedimiento no debe exceder los 1000 caracteres.',

                'contrato_numero.string' =>
                    'El número de contrato debe ser texto.',

                'contrato_numero.max' =>
                    'El número de contrato no debe exceder los 255 caracteres.',

                'fecha_inicio.date' =>
                    'La fecha inicial del contrato no es válida.',

                'fecha_fin.date' =>
                    'La fecha final del contrato no es válida.',

                'fecha_fin.after_or_equal' =>
                    'La fecha final no puede ser anterior a la fecha inicial.',

                'proveedor_razon_social.string' =>
                    'La razón social debe ser texto.',

                'proveedor_rfc.string' =>
                    'El RFC debe ser texto.',

                'proveedor_domicilio.string' =>
                    'El domicilio debe ser texto.',

                'proveedor_email.email' =>
                    'El correo electrónico del proveedor no es válido.',

                'proveedor_telefono.string' =>
                    'El teléfono debe ser texto.',

                'reviso_id.integer' =>
                    'La persona seleccionada para revisión no es válida.',

                'reviso_id.exists' =>
                    'La persona seleccionada para revisión no existe.',

                'documentos.array' =>
                    'La selección de documentos no es válida.',

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
         * La consulta es opcional.
         *
         * Se ejecuta solamente cuando el usuario capturó
         * un número de búsqueda.
         */
        $procedimiento = null;

        if ($request->filled('numero_busqueda')) {
            $procedimiento = Procedimiento::with('tipo')
                ->where(
                    'num_procedimiento',
                    'LIKE',
                    '%-N-' . trim((string) $request->numero_busqueda) . '-%'
                )
                ->first();
        }

        /*
         * Siempre se utilizan los valores finales del formulario.
         *
         * Pueden haber sido:
         * - capturados manualmente;
         * - autocompletados;
         * - o corregidos después de la búsqueda.
         *
         * No se actualiza la base de datos.
         */
        $numeroProcedimiento = trim(
            (string) $request->num_procedimiento
        );

        $nombreProcedimiento = trim(
            (string) $request->nombre_procedimiento
        );

        $tipoProcedimiento = trim(
            (string) $request->procedimiento_tipo
        );

        /*
         * Si se realizó una búsqueda y alguno de los campos opcionales
         * llegó vacío, se puede usar el dato de la base como respaldo.
         */
        if (
            $tipoProcedimiento === '' &&
            $procedimiento
        ) {
            $tipoProcedimiento =
                optional($procedimiento->tipo)->nombre_tipo ?? '';
        }

        /*
         * Limpiar y validar montos.
         */
        $montoMinimo = $this->limpiarMonto(
            $request->monto_minimo
        );

        $montoMaximo = $this->limpiarMonto(
            $request->monto_maximo
        );

        if (
            $request->filled('monto_minimo') &&
            $montoMinimo === null
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'monto_minimo' =>
                        'El monto mínimo ingresado no es válido.',
                ]);
        }

        if (
            $request->filled('monto_maximo') &&
            $montoMaximo === null
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'monto_maximo' =>
                        'El monto máximo ingresado no es válido.',
                ]);
        }

        if (
            $request->tipo_contrato_monto === 'abierto' &&
            $montoMinimo === null
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'monto_minimo' =>
                        'Debe ingresar el monto mínimo para un contrato abierto.',
                ]);
        }

        if ($montoMaximo === null) {
            return back()
                ->withInput()
                ->withErrors([
                    'monto_maximo' =>
                        'Debe ingresar el monto máximo.',
                ]);
        }

        if (
            $montoMinimo !== null &&
            $montoMinimo > $montoMaximo
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'monto_minimo' =>
                        'El monto mínimo no puede ser mayor que el monto máximo.',
                ]);
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
         * Documentos solicitados desde la base de datos.
         */
        $idsDocumentos = collect(
            $request->input('documentos', [])
        )
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $documentosSeleccionados = DocumentoAdjudicacion::query()
            ->where('activo', true)
            ->where(function ($query) use ($idsDocumentos) {
                $query->where('obligatorio', true);

                if ($idsDocumentos->isNotEmpty()) {
                    $query->orWhereIn(
                        'id_documento',
                        $idsDocumentos
                    );
                }
            })
            ->orderBy('orden')
            ->orderBy('id_documento')
            ->get();

        $documentosWord = [];

        foreach ($documentosSeleccionados->values() as $indice => $documento) {
            $documentosWord[] =
                $this->convertirIndiceAInciso($indice) .
                ') ' .
                trim((string) $documento->leyenda);
        }

        /*
         * Agrega dos saltos de línea entre cada inciso:
         * uno para terminar el renglón y otro para dejar
         * un espacio en blanco antes del siguiente inciso.
         */
        $textoDocumentos = $documentosWord
            ? implode(
                '</w:t><w:br/><w:br/><w:t>',
                $documentosWord
            )
            : '';

        /*
         * Rutas temporales.
         */
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

            $template = new TemplateProcessor(
                $templatePath
            );

            /*
             * Datos generales.
             */
            $template->setValue(
                'oficio_numero',
                $this->valorWord(
                    $request->oficio_numero
                )
            );

            $template->setValue(
                'fecha_oficio',
                $fechaOficio
            );

            /*
             * Datos del proveedor.
             */
            $template->setValue(
                'proveedor_razon_social',
                $this->valorWord(
                    $request->proveedor_razon_social
                )
            );

            $template->setValue(
                'proveedor_rfc',
                $this->valorWord(
                    $request->proveedor_rfc
                )
            );

            $template->setValue(
                'proveedor_domicilio',
                $this->valorWord(
                    $request->proveedor_domicilio
                )
            );

            $template->setValue(
                'proveedor_email',
                $this->valorWord(
                    $request->proveedor_email
                )
            );

            $template->setValue(
                'proveedor_telefono',
                $this->valorWord(
                    $request->proveedor_telefono
                )
            );

            /*
             * Información del procedimiento.
             *
             * Siempre se usan los valores finales del formulario.
             */
            $template->setValue(
                'procedimiento_numero',
                $this->valorWord(
                    $numeroProcedimiento
                )
            );

            $template->setValue(
                'num_procedimiento',
                $this->valorWord(
                    $numeroProcedimiento
                )
            );

            $template->setValue(
                'objeto_contrato',
                $this->valorWord(
                    $nombreProcedimiento
                )
            );

            $template->setValue(
                'nombre_procedimiento',
                $this->valorWord(
                    $nombreProcedimiento
                )
            );

            $template->setValue(
                'procedimiento_tipo',
                $this->valorWord(
                    $tipoProcedimiento
                )
            );

            $template->setValue(
                'contrato_numero',
                $this->valorWord(
                    $request->contrato_numero
                )
            );

            /*
             * Leyenda del monto según el tipo de contrato.
             *
             * La plantilla Word debe contener una sola etiqueta:
             * ${leyenda_monto}
             */
            $montoMaximoTexto = $this->numeroALetras(
                $montoMaximo
            );

            if (
                $request->tipo_contrato_monto === 'abierto' &&
                $montoMinimo !== null
            ) {
                $montoMinimoTexto = $this->numeroALetras(
                    $montoMinimo
                );

                $leyendaMonto =
                    'le informo que se le adjudica por un importe mínimo de ' .
                    $montoMinimoTexto .
                    ' antes de impuestos y un importe máximo de ' .
                    $montoMaximoTexto .
                    ' antes de impuestos,';
            } else {
                $leyendaMonto =
                    'le informo que se le adjudica por un importe de ' .
                    $montoMaximoTexto .
                    ' antes de impuestos,';
            }

            $template->setValue(
                'leyenda_monto',
                $leyendaMonto
            );

            /*
             * Compatibilidad con otras etiquetas de monto.
             */
            $template->setValue(
                'monto_minimo',
                $montoMinimo !== null
                    ? $this->numeroALetras($montoMinimo)
                    : ''
            );

            $template->setValue(
                'monto_maximo',
                $montoMaximoTexto
            );

            /*
             * Vigencia.
             */
            $vigencia = '';

            if (
                $request->filled('fecha_inicio') &&
                $request->filled('fecha_fin')
            ) {
                $vigencia = $this->formatearFechas(
                    $request->fecha_inicio,
                    $request->fecha_fin
                );
            }

            $template->setValue(
                'vigencia',
                $vigencia
            );

            /*
             * Documentos seleccionados.
             */
            $template->setValue(
                'documentos',
                $textoDocumentos
            );

            /*
             * Firmas.
             */
            $template->setValue(
                'reviso',
                $textoReviso
            );

            $template->setValue(
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
                'Adjudicacion_' .
                now()->format('Ymd_His') .
                '.docx';

            $outputPath =
                $directorioDocumentos .
                DIRECTORY_SEPARATOR .
                $nombreDocumento;

            $template->saveAs(
                $outputPath
            );

            /*
             * Verificar que el Word se haya generado correctamente
             * antes de registrarlo en el historial.
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
             * Esta copia permanecerá disponible durante 10 días
             * para visualizarse y descargarse nuevamente desde
             * el módulo de historial de documentos.
             */
            $historialDocumentos->registrar(
                $request->user(),
                $outputPath,
                $nombreDocumento,
                'Oficio de adjudicación',
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
             * El archivo temporal usado para la descarga inmediata
             * se elimina después de enviarse. La copia registrada
             * en el historial permanece guardada durante 10 días.
             */
            return response()
                ->download(
                    $outputPath,
                    $nombreDocumento
                )
                ->deleteFileAfterSend(true);
        } catch (Throwable $e) {
            /*
             * Eliminar archivos temporales si ocurre un error.
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
     * Limpia un monto recibido desde el formulario.
     */
    private function limpiarMonto($valor): ?float
    {
        if (
            $valor === null ||
            trim((string) $valor) === ''
        ) {
            return null;
        }

        $valor = trim((string) $valor);

        $valor = str_replace(
            [
                '$',
                ' ',
                "\xc2\xa0",
            ],
            '',
            $valor
        );

        /*
         * Formato europeo:
         * 1.234.567,89
         */
        if (
            preg_match(
                '/^-?\d{1,3}(\.\d{3})*,\d{1,2}$/',
                $valor
            )
        ) {
            $valor = str_replace(
                '.',
                '',
                $valor
            );

            $valor = str_replace(
                ',',
                '.',
                $valor
            );
        } else {
            /*
             * Formato habitual:
             * 1,234,567.89
             */
            $valor = str_replace(
                ',',
                '',
                $valor
            );
        }

        if (!is_numeric($valor)) {
            return null;
        }

        $numero = round(
            (float) $valor,
            2
        );

        return $numero >= 0
            ? $numero
            : null;
    }

    /**
     * Convierte una cantidad a número y letra.
     *
     * Ejemplo:
     * $ 293,200.00 (Doscientos noventa y tres mil
     * doscientos pesos 00/100 M.N.)
     */
    private function numeroALetras($numero): string
    {
        $numero = round(
            (float) $numero,
            2
        );

        $formatter = new \NumberFormatter(
            'es_MX',
            \NumberFormatter::SPELLOUT
        );

        $entero = (int) floor($numero);

        $decimal = (int) round(
            ($numero - $entero) * 100
        );

        if ($decimal === 100) {
            $entero++;
            $decimal = 0;
        }

        $letras = $formatter->format(
            $entero
        );

        $letras = str_replace(
            "\xC2\xAD",
            '',
            $letras
        );

        $letras = ucfirst(
            trim($letras)
        );

        return '$ ' .
            number_format(
                $numero,
                2,
                '.',
                ','
            ) .
            ' (' .
            $letras .
            ' pesos ' .
            str_pad(
                (string) $decimal,
                2,
                '0',
                STR_PAD_LEFT
            ) .
            '/100 M.N.)';
    }

    /**
     * Formatea la vigencia del contrato.
     */
    private function formatearFechas(
        $inicio,
        $fin
    ): string {
        $fechaInicio = Carbon::parse(
            $inicio
        )
            ->locale('es')
            ->translatedFormat(
                'j \d\e F \d\e Y'
            );

        $fechaFin = Carbon::parse(
            $fin
        )
            ->locale('es')
            ->translatedFormat(
                'j \d\e F \d\e Y'
            );

        return
            $fechaInicio .
            ' al ' .
            $fechaFin;
    }

    /**
     * Convierte un índice numérico en inciso alfabético.
     * Ejemplos: 0 = a, 25 = z, 26 = aa.
     */
    private function convertirIndiceAInciso(int $indice): string
    {
        $resultado = '';

        do {
            $residuo = $indice % 26;
            $resultado = chr(97 + $residuo) . $resultado;
            $indice = intdiv($indice, 26) - 1;
        } while ($indice >= 0);

        return $resultado;
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