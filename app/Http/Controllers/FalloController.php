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
    public function indexActa()
    {
        $areaContratanteId = Area::where('nombre', 'Coordinación de Adquisiciones y Servicios')->value('id');
        $encargadoContratoId = Area::where('nombre', 'Subgerencia de Operaciones')->value('id');
        $juridicoId = Area::where('nombre', 'Juridico Ofi centrales')->value('id');
        $oicId = Area::where('nombre', 'OIC Ofi centrales')->value('id');

        $areasContratantes = Persona::where('area_id', $areaContratanteId)
            ->orderBy('nombre')
            ->get();

        $encargadosContrato = Persona::where('area_id', $encargadoContratoId)
            ->orderBy('nombre')
            ->get();

        $personasJuridico = Persona::where('area_id', $juridicoId)
            ->orderBy('nombre')
            ->get();

        $personasOic = Persona::where('area_id', $oicId)
            ->orderBy('nombre')
            ->get();

        $areasExcluidas = [
            $areaContratanteId,
            $encargadoContratoId,
            $juridicoId,
            $oicId,
        ];

        $areasRequirentes = Persona::whereNotIn('area_id', $areasExcluidas)
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

    public function buscarProcedimiento($valor)
    {
        $valor = trim($valor);

        $proc = Procedimiento::where(
            'num_procedimiento',
            'LIKE',
            '%-N-' . $valor . '-%'
        )->first();

        if (!$proc) {
            return response()->json(null);
        }

        return response()->json([
            'num_procedimiento'    => $proc->num_procedimiento,
            'nombre_procedimiento' => $proc->nombre_procedimiento,
            'fecha_fallo'          => $proc->fecha_fallo
                ? Carbon::parse($proc->fecha_fallo)->format('Y-m-d')
                : '',
            'hora_fallo'           => $proc->hora_fallo
                ? Carbon::parse($proc->hora_fallo)->format('H:i')
                : '',
        ]);
    }

    public function generarActa(Request $request)
    {
        $request->validate([
            'numero_busqueda'    => 'required',
            'archivo_word'       => 'required|file|mimes:docx',

            'area_contratante'   => 'required|exists:personas,id',
            'encargado_contrato' => 'required|exists:personas,id',
            'area_requirente'    => 'required|exists:personas,id',
            'persona_oic'        => 'required|exists:personas,id',
            'persona_juridico'   => 'required|exists:personas,id',
        ]);

        $proc = Procedimiento::where(
            'num_procedimiento',
            'LIKE',
            '%-N-' . $request->numero_busqueda . '-%'
        )->first();

        if (!$proc) {
            return back()->with('error', 'No se encontró el procedimiento.');
        }

        $template = new TemplateProcessor(
            $request->file('archivo_word')->getRealPath()
        );

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
                    ->translatedFormat('d \\d\\e F \\d\\e Y')
                : ''
        );

        $this->setPersona($template, 'area_contratante', $request->area_contratante);
        $this->setPersona($template, 'encargado_contrato', $request->encargado_contrato);
        $this->setPersona($template, 'area_requirente', $request->area_requirente);
        $this->setPersona($template, 'persona_oic', $request->persona_oic);
        $this->setPersona($template, 'persona_juridico', $request->persona_juridico);

        $carpeta = storage_path('app/temp');

        if (!file_exists($carpeta)) {
            mkdir($carpeta, 0777, true);
        }

        $ruta = $carpeta . '/Acta_Fallo_' . time() . '.docx';

        $template->saveAs($ruta);

        return response()
            ->download($ruta)
            ->deleteFileAfterSend(true);
    }

    private function setPersona(
        TemplateProcessor $template,
        string $etiqueta,
        int $personaId
    ) {
        $persona = Persona::find($personaId);

        if (!$persona) {
            $template->setValue($etiqueta, '');
            return;
        }

        $textRun = new TextRun();

        $textRun->addText(
            $persona->nombre,
            [
                'bold' => true,
                'name' => 'Noto Sans',
                'size' => 10.5,
            ]
        );

        $textRun->addText(
            ', ' . $persona->cargo,
            [
                'bold' => false,
                'name' => 'Noto Sans',
                'size' => 10.5,
            ]
        );

        $template->setComplexValue($etiqueta, $textRun);
    }
}