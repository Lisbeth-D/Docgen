<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Persona;
use App\Models\Procedimiento;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\TemplateProcessor;

class FalloController extends Controller
{
    /**
     * Muestra el formulario para generar el Acta de Fallo.
     */
    public function indexActa()
    {
        /*
        |--------------------------------------------------------------------------
        | OBTENER LOS ID DE LAS ÁREAS
        |--------------------------------------------------------------------------
        | La llave primaria de la tabla areas es id_area.
        */

        $areaContratanteId = Area::whereIn('nombre', [
            'Coordinación de Adquisiciones y Servicios',
            'Adquisiciones y Servicios',
        ])->value('id_area');

        $encargadoContratoId = Area::where(
            'nombre',
            'Subgerencia de Operaciones'
        )->value('id_area');

        $juridicoId = Area::whereIn('nombre', [
            'Juridico Ofi centrales',
            'Jurídico Ofi centrales',
        ])->value('id_area');

        $oicId = Area::where(
            'nombre',
            'OIC Ofi centrales'
        )->value('id_area');

        /*
        |--------------------------------------------------------------------------
        | PERSONAS DEL ÁREA CONTRATANTE
        |--------------------------------------------------------------------------
        */

        $areasContratantes = Persona::where(
            'area_id',
            $areaContratanteId
        )
            ->orderBy('nombre')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | ENCARGADOS DEL CONTRATO
        |--------------------------------------------------------------------------
        */

        $encargadosContrato = Persona::where(
            'area_id',
            $encargadoContratoId
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
        | PERSONAS DE ÁREAS REQUIRENTES
        |--------------------------------------------------------------------------
        */

        $areasExcluidas = array_values(array_filter([
            $areaContratanteId,
            $encargadoContratoId,
            $juridicoId,
            $oicId,
        ]));

        $areasRequirentes = Persona::whereNotIn(
            'area_id',
            $areasExcluidas
        )
            ->orderBy('nombre')
            ->get();

        return view('comprador.Fallo.actaFallo', compact(
            'areasContratantes',
            'encargadosContrato',
            'areasRequirentes',
            'personasJuridico',
            'personasOic'
        ));
    }

    /**
     * Busca un procedimiento por el número intermedio.
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

            'fecha_fallo' => $proc->fecha_fallo
                ? Carbon::parse($proc->fecha_fallo)->format('Y-m-d')
                : '',

            'hora_fallo' => $proc->hora_fallo
                ? Carbon::parse($proc->hora_fallo)->format('H:i')
                : '',
        ]);
    }

    /**
     * Genera el Acta de Fallo en Word.
     */
    public function generarActa(Request $request)
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

            'archivo_word' => [
                'required',
                'file',
                'mimes:docx',
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

            'persona_oic' => [
                'required',
                'exists:personas,id',
            ],

            'persona_juridico' => [
                'required',
                'exists:personas,id',
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
                    'No se encontró el procedimiento.'
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

        $encargadoContrato = Persona::find(
            $request->encargado_contrato
        );

        $areaRequirente = Persona::find(
            $request->area_requirente
        );

        $personaOic = Persona::find(
            $request->persona_oic
        );

        $personaJuridico = Persona::find(
            $request->persona_juridico
        );

        if (
            !$areaContratante
            || !$encargadoContrato
            || !$areaRequirente
            || !$personaOic
            || !$personaJuridico
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
        | OBTENER NOMBRE DE LAS ÁREAS
        |--------------------------------------------------------------------------
        | Estas variables se utilizan para la columna derecha de la tabla.
        */

        $nombreAreaContratante = Area::where(
            'id_area',
            $areaContratante->area_id
        )->value('nombre') ?? '';

        $nombreAreaEncargadoContrato = Area::where(
            'id_area',
            $encargadoContrato->area_id
        )->value('nombre') ?? '';

        $nombreAreaRequirente = Area::where(
            'id_area',
            $areaRequirente->area_id
        )->value('nombre') ?? '';

        /*
        |--------------------------------------------------------------------------
        | CARGAR PLANTILLA WORD
        |--------------------------------------------------------------------------
        */

        $template = new TemplateProcessor(
            $request->file('archivo_word')->getRealPath()
        );

        Carbon::setLocale('es');

        /*
        |--------------------------------------------------------------------------
        | ETIQUETAS DEL PROCEDIMIENTO
        |--------------------------------------------------------------------------
        */

        $template->setValue(
            'num_procedimiento',
            $proc->num_procedimiento ?? ''
        );

        $template->setValue(
            'nombre_procedimiento',
            $proc->nombre_procedimiento ?? ''
        );

        $template->setValue(
            'hora_fallo',
            $proc->hora_fallo
                ? Carbon::parse($proc->hora_fallo)->format('H:i') . ' horas'
                : ''
        );

        $template->setValue(
            'fecha_fallo',
            $proc->fecha_fallo
                ? Carbon::parse($proc->fecha_fallo)
                    ->locale('es')
                    ->translatedFormat('d \d\e F \d\e Y')
                : ''
        );

        /*
        |--------------------------------------------------------------------------
        | ETIQUETAS NORMALES DE PARTICIPANTES
        |--------------------------------------------------------------------------
        | Nombre en negritas y cargo sin negritas.
        */

        $template->setComplexValue(
            'area_contratante',
            $this->crearTextoPersona($areaContratante)
        );

        $template->setComplexValue(
            'encargado_contrato',
            $this->crearTextoPersona($encargadoContrato)
        );

        $template->setComplexValue(
            'area_requirente',
            $this->crearTextoPersona($areaRequirente)
        );

        $template->setComplexValue(
            'persona_oic',
            $this->crearTextoPersona($personaOic)
        );

        $template->setComplexValue(
            'persona_juridico',
            $this->crearTextoPersona($personaJuridico)
        );

        /*
        |--------------------------------------------------------------------------
        | ETIQUETAS DE PERSONAS PARA TABLA
        |--------------------------------------------------------------------------
        */

        $template->setComplexValue(
            'area_contratante_tabla',
            $this->crearTextoPersona($areaContratante)
        );

        $template->setComplexValue(
            'encargado_contrato_tabla',
            $this->crearTextoPersona($encargadoContrato)
        );

        $template->setComplexValue(
            'area_requirente_tabla',
            $this->crearTextoPersona($areaRequirente)
        );

        /*
        |--------------------------------------------------------------------------
        | ETIQUETAS DE ÁREAS PARA TABLA
        |--------------------------------------------------------------------------
        | Muestran únicamente el nombre del área.
        */

        $template->setValue(
            'area_area_contratante',
            $nombreAreaContratante
        );

        $template->setValue(
            'area_encargado_contrato',
            $nombreAreaEncargadoContrato
        );

        $template->setValue(
            'area_area_requirente',
            $nombreAreaRequirente
        );

        /*
        |--------------------------------------------------------------------------
        | CREAR CARPETA TEMPORAL
        |--------------------------------------------------------------------------
        */

        $carpeta = storage_path('app/temp');

        if (!file_exists($carpeta)) {
            mkdir(
                $carpeta,
                0777,
                true
            );
        }

        /*
        |--------------------------------------------------------------------------
        | GUARDAR DOCUMENTO
        |--------------------------------------------------------------------------
        */

        $nombreArchivo =
            'Acta_Fallo_'
            . $this->limpiarNombreArchivo(
                $proc->num_procedimiento
            )
            . '.docx';

        $ruta = $carpeta
            . DIRECTORY_SEPARATOR
            . $nombreArchivo;

        $template->saveAs($ruta);

        return response()
            ->download(
                $ruta,
                $nombreArchivo
            )
            ->deleteFileAfterSend(true);
    }

    /**
     * Crea un TextRun para insertar una persona en Word.
     *
     * Nombre: Noto Sans 10.5 en negritas.
     * Cargo: Noto Sans 10.5 sin negritas.
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
                    'bold' => true,
                    'name' => 'Noto Sans',
                    'size' => 10.5,
                ]
            );
        }

        if ($cargo !== '') {
            $textRun->addText(
                ', ' . $cargo,
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
     * Limpia caracteres inválidos para nombres de archivos.
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