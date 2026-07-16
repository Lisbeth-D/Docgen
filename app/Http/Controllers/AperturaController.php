<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Procedimiento;
use App\Models\Persona;
use App\Models\Area;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\Element\TextRun;
use Carbon\Carbon;

class AperturaController extends Controller
{
    /**
     * Muestra el formulario de apertura.
     */
    public function index()
    {
        /*
        |--------------------------------------------------------------------------
        | ÁREA CONTRATANTE
        |--------------------------------------------------------------------------
        | Únicamente se mostrarán las personas pertenecientes a:
        |
        | - Coordinación de Adquisiciones y Servicios
        | - Adquisiciones y Servicios
        */

        $areaContratanteId = Area::whereIn('nombre', [
            'Coordinación de Adquisiciones y Servicios',
            'Adquisiciones y Servicios',
        ])->value('id_area');

        /*
        |--------------------------------------------------------------------------
        | JURÍDICO
        |--------------------------------------------------------------------------
        */

        $juridicoId = Area::whereIn('nombre', [
            'Juridico Ofi centrales',
            'Jurídico Ofi centrales',
        ])->value('id_area');

        /*
        |--------------------------------------------------------------------------
        | OIC
        |--------------------------------------------------------------------------
        */

        $oicId = Area::where(
            'nombre',
            'OIC Ofi centrales'
        )->value('id_area');

        /*
        |--------------------------------------------------------------------------
        | PERSONAS DEL ÁREA CONTRATANTE
        |--------------------------------------------------------------------------
        | El Blade muestra directamente estas personas.
        */

        $areasContratantes = Persona::where(
            'area_id',
            $areaContratanteId
        )
            ->orderBy('nombre')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | ÁREAS PARA EL ADMINISTRADOR DEL CONTRATO
        |--------------------------------------------------------------------------
        | El Blade primero solicita seleccionar el área y después la persona.
        |
        | Áreas permitidas:
        |
        | - Gerencia
        | - Subgerencia de Operaciones
        | - Subgerencia de Abasto
        */

        $areasContrato = Area::with([
            'personas' => function ($query) {
                $query->orderBy('nombre');
            }
        ])
            ->whereIn('nombre', [
                'Gerencia',
                'Subgerencia de Operaciones',
                'Subgerencia de Abasto',
            ])
            ->orderBy('nombre')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | ÁREAS REQUIRENTES
        |--------------------------------------------------------------------------
        | El Blade primero solicita seleccionar el área y después la persona.
        |
        | Se excluyen:
        |
        | - Coordinación de Adquisiciones y Servicios
        | - Adquisiciones y Servicios
        | - Jurídico Oficinas Centrales
        | - OIC Oficinas Centrales
        */

        $areasExcluidas = array_values(array_filter([
            $areaContratanteId,
            $juridicoId,
            $oicId,
        ]));

        $areasRequirentes = Area::with([
            'personas' => function ($query) {
                $query->orderBy('nombre');
            }
        ])
            ->whereNotIn(
                'id_area',
                $areasExcluidas
            )
            ->orderBy('nombre')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | PERSONAS DE JURÍDICO
        |--------------------------------------------------------------------------
        */

        $personasJuridico = Persona::where(
            'area_id',
            $juridicoId
        )
            ->orderBy('nombre')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | PERSONAS DEL OIC
        |--------------------------------------------------------------------------
        */

        $personasOic = Persona::where(
            'area_id',
            $oicId
        )
            ->orderBy('nombre')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | MOSTRAR LA VISTA
        |--------------------------------------------------------------------------
        */

        return view(
            'comprador.Apertura.apertura',
            compact(
                'areasContratantes',
                'areasContrato',
                'areasRequirentes',
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
        $valor = trim($valor);

        if ($valor === '') {
            return response()->json(null);
        }

        $proc = Procedimiento::where(
            'num_procedimiento',
            'LIKE',
            '%-N-' . $valor . '-%'
        )->first();

        if (!$proc) {
            return response()->json(null);
        }

        return response()->json([
            'num_procedimiento' => $proc->num_procedimiento,

            'nombre_procedimiento' => $proc->nombre_procedimiento,

            'fecha_apertura' => $proc->fecha_apertura
                ? Carbon::parse($proc->fecha_apertura)->format('Y-m-d')
                : '',

            'hora_apertura' => $proc->hora_apertura
                ? Carbon::parse($proc->hora_apertura)->format('H:i')
                : '',

            'fecha_fallo' => $proc->fecha_fallo
                ? Carbon::parse($proc->fecha_fallo)->format('Y-m-d')
                : '',

            'hora_fallo' => $proc->hora_fallo
                ? Carbon::parse($proc->hora_fallo)->format('H:i')
                : '',
        ]);
    }

    /**
     * Genera el documento Word.
     */
    public function generar(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | VALIDACIÓN
        |--------------------------------------------------------------------------
        */

        $request->validate([
            'numero_busqueda' => [
                'required',
                'string',
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
                'exists:personas,id',
            ],

            'encargado_contrato' => [
                'required',
                'exists:personas,id',
            ],

            'area_requirente' => [
                'required',
                'exists:personas,id',
            ],

            'persona_juridico' => [
                'required',
                'exists:personas,id',
            ],

            'persona_oic' => [
                'required',
                'exists:personas,id',
            ],

            'archivo_word' => [
                'required',
                'file',
                'mimes:docx',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | BUSCAR PROCEDIMIENTO
        |--------------------------------------------------------------------------
        */

        $numeroBusqueda = trim(
            $request->numero_busqueda
        );

        $proc = Procedimiento::where(
            'num_procedimiento',
            'LIKE',
            '%-N-' . $numeroBusqueda . '-%'
        )->first();

        if (!$proc) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'No se encontró el procedimiento indicado.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | OBTENER PERSONAS SELECCIONADAS
        |--------------------------------------------------------------------------
        */

        $areaContratante = Persona::find(
            $request->area_contratante
        );

        $admiContrato = Persona::find(
            $request->encargado_contrato
        );

        $areaRequirente = Persona::find(
            $request->area_requirente
        );

        $personaJuridico = Persona::find(
            $request->persona_juridico
        );

        $personaOic = Persona::find(
            $request->persona_oic
        );

        /*
        |--------------------------------------------------------------------------
        | VALIDAR PERSONAS
        |--------------------------------------------------------------------------
        */

        if (
            !$areaContratante
            || !$admiContrato
            || !$areaRequirente
            || !$personaJuridico
            || !$personaOic
        ) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'No fue posible obtener una o más personas seleccionadas.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | OBTENER NOMBRES DE LAS ÁREAS
        |--------------------------------------------------------------------------
        | Estas etiquetas se usan en la columna derecha de la tabla Word.
        */

        $nombreAreaContratante = Area::where(
            'id_area',
            $areaContratante->area_id
        )->value('nombre') ?? '';

        $nombreAreaAdmiContrato = Area::where(
            'id_area',
            $admiContrato->area_id
        )->value('nombre') ?? '';

        $nombreAreaRequirente = Area::where(
            'id_area',
            $areaRequirente->area_id
        )->value('nombre') ?? '';

        /*
        |--------------------------------------------------------------------------
        | FECHAS Y HORAS
        |--------------------------------------------------------------------------
        | Se utilizan primero los datos enviados desde el formulario.
        | Si vienen vacíos, se toman de la base de datos.
        */

        $fechaApertura = $request->fecha_apertura
            ?: $proc->fecha_apertura;

        $horaApertura = $request->hora_apertura
            ?: $proc->hora_apertura;

        $fechaFallo = $request->fecha_fallo
            ?: $proc->fecha_fallo;

        $horaFallo = $request->hora_fallo
            ?: $proc->hora_fallo;

        Carbon::setLocale('es');

        /*
        |--------------------------------------------------------------------------
        | FECHA DE APERTURA
        |--------------------------------------------------------------------------
        */

        $fechaAperturaTexto = '';

        if ($fechaApertura) {
            $fechaAperturaCarbon = Carbon::parse(
                $fechaApertura
            );

            $fechaAperturaTexto =
                $fechaAperturaCarbon->day
                . ' de '
                . $fechaAperturaCarbon->translatedFormat('F')
                . ' de '
                . $fechaAperturaCarbon->year;
        }

        /*
        |--------------------------------------------------------------------------
        | HORA DE APERTURA
        |--------------------------------------------------------------------------
        */

        $horaAperturaTexto = '';

        if ($horaApertura) {
            $horaAperturaTexto =
                Carbon::parse($horaApertura)->format('H:i')
                . ' horas';
        }

        /*
        |--------------------------------------------------------------------------
        | FECHA Y HORA DEL FALLO
        |--------------------------------------------------------------------------
        */

        $fechaFalloTexto = '';

        if ($fechaFallo) {
            $fechaFalloCarbon = Carbon::parse(
                $fechaFallo
            );

            $fechaFalloTexto =
                $fechaFalloCarbon->day
                . ' de '
                . $fechaFalloCarbon->translatedFormat('F')
                . ' de '
                . $fechaFalloCarbon->year;

            if ($horaFallo) {
                $fechaFalloTexto .=
                    ' a las '
                    . Carbon::parse($horaFallo)->format('H:i')
                    . ' horas';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | HORA DE CIERRE
        |--------------------------------------------------------------------------
        | Se agregan dos horas a la hora de apertura.
        */

        $horaapCierre = '';

        if ($fechaApertura && $horaApertura) {
            $horaCierre = Carbon::parse(
                $horaApertura
            )
                ->addHours(2)
                ->format('H:i');

            $fechaCierreCarbon = Carbon::parse(
                $fechaApertura
            );

            $fechaCierreTexto =
                $fechaCierreCarbon->day
                . ' de '
                . $fechaCierreCarbon->translatedFormat('F')
                . ' de '
                . $fechaCierreCarbon->year;

            $horaapCierre =
                $horaCierre
                . ' horas del día '
                . $fechaCierreTexto
                . '.';
        }

        /*
        |--------------------------------------------------------------------------
        | CARGAR PLANTILLA WORD
        |--------------------------------------------------------------------------
        */

        $templateProcessor = new TemplateProcessor(
            $request->file('archivo_word')->getRealPath()
        );

        /*
        |--------------------------------------------------------------------------
        | ETIQUETAS GENERALES
        |--------------------------------------------------------------------------
        */

        $templateProcessor->setValue(
            'num_procedimiento',
            $proc->num_procedimiento ?? ''
        );

        $templateProcessor->setValue(
            'nombre_procedimiento',
            $proc->nombre_procedimiento ?? ''
        );

        $templateProcessor->setValue(
            'fecha_apertura',
            $fechaAperturaTexto
        );

        $templateProcessor->setValue(
            'hora_apertura',
            $horaAperturaTexto
        );

        $templateProcessor->setValue(
            'horaap_cierre',
            $horaapCierre
        );

        $templateProcessor->setValue(
            'fecha_fallo',
            $fechaFalloTexto
        );

        /*
        |--------------------------------------------------------------------------
        | ETIQUETAS NORMALES DE PERSONAS
        |--------------------------------------------------------------------------
        */

        $templateProcessor->setComplexValue(
            'area_contratante',
            $this->crearTextoPersona($areaContratante)
        );

        $templateProcessor->setComplexValue(
            'encargado_contrato',
            $this->crearTextoPersona($admiContrato)
        );

        $templateProcessor->setComplexValue(
            'admi_contrato',
            $this->crearTextoPersona($admiContrato)
        );

        $templateProcessor->setComplexValue(
            'area_requirente',
            $this->crearTextoPersona($areaRequirente)
        );

        /*
         * Jurídico y OIC utilizan las mismas etiquetas tanto
         * en texto normal como dentro de una tabla.
         */

        $templateProcessor->setComplexValue(
            'persona_juridico',
            $this->crearTextoPersona($personaJuridico)
        );

        $templateProcessor->setComplexValue(
            'persona_oic',
            $this->crearTextoPersona($personaOic)
        );

        /*
        |--------------------------------------------------------------------------
        | ETIQUETAS PARA TABLA
        |--------------------------------------------------------------------------
        */

        $templateProcessor->setComplexValue(
            'area_contratante_tabla',
            $this->crearTextoPersona($areaContratante)
        );

        $templateProcessor->setComplexValue(
            'encargado_contrato_tabla',
            $this->crearTextoPersona($admiContrato)
        );

        $templateProcessor->setComplexValue(
            'admi_contrato_tabla',
            $this->crearTextoPersona($admiContrato)
        );

        $templateProcessor->setComplexValue(
            'area_requirente_tabla',
            $this->crearTextoPersona($areaRequirente)
        );

        /*
        |--------------------------------------------------------------------------
        | ETIQUETAS DE NOMBRE DE ÁREA
        |--------------------------------------------------------------------------
        */

        $templateProcessor->setValue(
            'area_area_contratante',
            $nombreAreaContratante
        );

        $templateProcessor->setValue(
            'area_admi_contrato',
            $nombreAreaAdmiContrato
        );

        $templateProcessor->setValue(
            'area_area_requirente',
            $nombreAreaRequirente
        );

        /*
        |--------------------------------------------------------------------------
        | CREAR CARPETA TEMPORAL
        |--------------------------------------------------------------------------
        */

        $carpetaTemp = storage_path(
            'app/temp'
        );

        if (!file_exists($carpetaTemp)) {
            mkdir(
                $carpetaTemp,
                0777,
                true
            );
        }

        /*
        |--------------------------------------------------------------------------
        | NOMBRE DEL ARCHIVO
        |--------------------------------------------------------------------------
        */

        $nombreArchivo =
            'Apertura_'
            . $this->limpiarNombreArchivo(
                $proc->num_procedimiento
            )
            . '.docx';

        $rutaSalida =
            $carpetaTemp
            . DIRECTORY_SEPARATOR
            . $nombreArchivo;

        /*
        |--------------------------------------------------------------------------
        | GUARDAR Y DESCARGAR
        |--------------------------------------------------------------------------
        */

        $templateProcessor->saveAs(
            $rutaSalida
        );

        return response()
            ->download(
                $rutaSalida,
                $nombreArchivo
            )
            ->deleteFileAfterSend(true);
    }

    /**
     * Genera el texto de una persona para Word.
     *
     * Nombre: Noto Sans 10 en negritas.
     * Cargo: Noto Sans 10 sin negritas.
     */
    private function crearTextoPersona(
        ?Persona $persona
    ): TextRun {
        $textRun = new TextRun();

        if (!$persona) {
            return $textRun;
        }

        $nombre = trim(
            $persona->nombre ?? ''
        );

        $cargo = trim(
            $persona->cargo ?? ''
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
                ', ' . $cargo,
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
     * Limpia caracteres no permitidos por Windows
     * en el nombre del archivo.
     */
    private function limpiarNombreArchivo(
        string $nombre
    ): string {
        return preg_replace(
            '/[\\\\\/:*?"<>|]/',
            '-',
            $nombre
        );
    }
}