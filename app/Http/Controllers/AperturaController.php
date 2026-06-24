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
    public function index()
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

        $areasExcluidas = array_filter([
            $areaContratanteId,
            $encargadoContratoId,
            $juridicoId,
            $oicId,
        ]);

        $areasRequirentes = Persona::whereNotIn('area_id', $areasExcluidas)
            ->orderBy('nombre')
            ->get();

        return view('comprador.Apertura.apertura', compact(
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
            'fecha_apertura'       => $proc->fecha_apertura
                ? Carbon::parse($proc->fecha_apertura)->format('Y-m-d')
                : '',
            'hora_apertura'        => $proc->hora_apertura
                ? Carbon::parse($proc->hora_apertura)->format('H:i')
                : '',
            'fecha_fallo'          => $proc->fecha_fallo
                ? Carbon::parse($proc->fecha_fallo)->format('Y-m-d')
                : '',
            'hora_fallo'           => $proc->hora_fallo
                ? Carbon::parse($proc->hora_fallo)->format('H:i')
                : '',
        ]);
    }

    public function generar(Request $request)
    {
        $request->validate([
            'numero_busqueda'     => 'required',
            'fecha_apertura'      => 'nullable|date',
            'hora_apertura'       => 'nullable',
            'fecha_fallo'         => 'nullable|date',
            'hora_fallo'          => 'nullable',
            'area_contratante'    => 'required|exists:personas,id',
            'encargado_contrato'  => 'required|exists:personas,id',
            'area_requirente'     => 'required|exists:personas,id',
            'persona_juridico'    => 'required|exists:personas,id',
            'persona_oic'         => 'required|exists:personas,id',
            'archivo_word'        => 'required|file|mimes:docx',
        ]);

        $proc = Procedimiento::where(
            'num_procedimiento',
            'LIKE',
            '%-N-' . $request->numero_busqueda . '-%'
        )->first();

        if (!$proc) {
            return back()->with('error', 'No se encontró el procedimiento');
        }

        $fechaApertura = $request->fecha_apertura ?: $proc->fecha_apertura;
        $horaApertura = $request->hora_apertura ?: $proc->hora_apertura;
        $fechaFallo = $request->fecha_fallo ?: $proc->fecha_fallo;
        $horaFallo = $request->hora_fallo ?: $proc->hora_fallo;

        $fechaFalloTexto = '';
        if ($fechaFallo && $horaFallo) {
            $fechaFalloTexto =
                Carbon::parse($fechaFallo)->locale('es')->translatedFormat('d \d\e F \d\e Y') .
                ' a las ' .
                Carbon::parse($horaFallo)->format('H:i') .
                ' horas';
        }

        $horaapCierre = '';
        if ($fechaApertura && $horaApertura) {
            $horaapCierre =
                Carbon::parse($horaApertura)->addHours(2)->format('H:i') .
                ' horas del día ' .
                Carbon::parse($fechaApertura)->locale('es')->translatedFormat('d \d\e F \d\e Y') .
                '.';
        }

        $template = new TemplateProcessor(
            $request->file('archivo_word')->getRealPath()
        );

        $template->setValue('num_procedimiento', $proc->num_procedimiento ?? '');
        $template->setValue('nombre_procedimiento', $proc->nombre_procedimiento ?? '');

        $template->setValue(
            'fecha_apertura',
            $fechaApertura
                ? Carbon::parse($fechaApertura)->locale('es')->translatedFormat('d \d\e F \d\e Y')
                : ''
        );

        $template->setValue(
            'hora_apertura',
            $horaApertura ? Carbon::parse($horaApertura)->format('H:i') . ' horas' : ''
        );

        $template->setValue('horaap_cierre', $horaapCierre);

        $template->setValue('fecha_fallo', $fechaFalloTexto);

        $this->setPersonaConNegritas($template, 'area_contratante', $request->area_contratante);
        $this->setPersonaConNegritas($template, 'encargado_contrato', $request->encargado_contrato);
        $this->setPersonaConNegritas($template, 'area_requirente', $request->area_requirente);
        $this->setPersonaConNegritas($template, 'persona_juridico', $request->persona_juridico);
        $this->setPersonaConNegritas($template, 'persona_oic', $request->persona_oic);

        $carpetaTemp = storage_path('app/temp');

        if (!file_exists($carpetaTemp)) {
            mkdir($carpetaTemp, 0777, true);
        }

        $nombreArchivo = 'Apertura_' . $proc->num_procedimiento . '.docx';
        $rutaSalida = $carpetaTemp . '/' . $nombreArchivo;

        $template->saveAs($rutaSalida);

        return response()->download($rutaSalida)->deleteFileAfterSend(true);
    }

    private function setPersonaConNegritas($template, $etiqueta, $personaId)
    {
        $persona = Persona::find($personaId);

        if (!$persona) {
            $template->setValue($etiqueta, '');
            return;
        }

        $textRun = new TextRun();

        $textRun->addText(
            $persona->nombre,
            [
                'name' => 'Noto Sans',
                'size' => 10.5,
                'bold' => true,
            ]
        );

        $textRun->addText(
            ', ' . $persona->cargo,
            [
                'name' => 'Noto Sans',
                'size' => 10.5,
                'bold' => false,
            ]
        );

        $template->setComplexValue($etiqueta, $textRun);
    }
}