<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Procedimiento;
use App\Models\Persona;
use PhpOffice\PhpWord\TemplateProcessor;

class ProcedimientoController extends Controller
{
    /**
     * Mostrar formulario de convocatoria
     */
    public function convocatoria()
    {
        $personas = Persona::orderBy('nombre')->get();

        return view('comprador.convo.convocatoria', compact('personas'));
    }

    /**
     * 🔥 FUNCIÓN PARA LIMPIAR Y FORMATEAR MONTOS
     */
    private function formatearMonto($valor)
    {
        if (!$valor) return '0.00';

        $valor = trim($valor);

        // Detectar formato europeo (coma decimal)
        if (preg_match('/,\d{1,2}$/', $valor)) {
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
        } else {
            $valor = str_replace(',', '', $valor);
        }

        $numero = floatval($valor);

        return number_format($numero, 2, '.', ',');
    }

    /**
     * Guardar + generar Word + descargar
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre_procedimiento' => 'required',
            'num_procedimiento'    => 'required',
            'archivo_word'         => 'required|file|mimes:docx'
        ]);

        // =========================
        // 1. GUARDAR EN BD
        // =========================
        $procedimiento = Procedimiento::create([
            'nombre_procedimiento' => $request->nombre_procedimiento,
            'num_procedimiento'    => $request->num_procedimiento,
            'fecha_publicacion'    => $request->fecha_publicacion,
            'fecha_vm'             => $request->fecha_vm,
            'fecha_ac'             => $request->fecha_acl,
            'hora_ac'              => $request->hora_acl,
            'fecha_apertura'       => $request->fecha_apertura,
            'hora_apertura'        => $request->hora_apertura,
            'fecha_fallo'          => $request->fecha_fallo,
            'hora_fallo'           => $request->hora_fallo,
        ]);

        // =========================
        // 2. PROCESAR WORD
        // =========================
        if ($request->hasFile('archivo_word')) {

            $file = $request->file('archivo_word');
            $filename = time() . '_' . $file->getClientOriginalName();

            $templateDir = storage_path('app/plantillas');

            if (!file_exists($templateDir)) {
                mkdir($templateDir, 0777, true);
            }

            $file->move($templateDir, $filename);

            $templatePath = $templateDir . DIRECTORY_SEPARATOR . $filename;

            if (!file_exists($templatePath)) {
                return back()->with('error', 'No se encontró el archivo Word');
            }

            $templateProcessor = new TemplateProcessor($templatePath);

            // =========================
            // 3. REEMPLAZOS PRINCIPALES
            // =========================
            $templateProcessor->setValue('nombre_procedimiento', $request->nombre_procedimiento);
            $templateProcessor->setValue('num_procedimiento', $request->num_procedimiento);
            $templateProcessor->setValue('fecha_publicacion', $request->fecha_publicacion);
            $templateProcessor->setValue('fecha_vm', $request->fecha_vm);
            $templateProcessor->setValue('fecha_acl', $request->fecha_acl);
            $templateProcessor->setValue('hora_acl', $request->hora_acl);
            $templateProcessor->setValue('fecha_apertura', $request->fecha_apertura);
            $templateProcessor->setValue('hora_apertura', $request->hora_apertura);
            $templateProcessor->setValue('fecha_fallo', $request->fecha_fallo);
            $templateProcessor->setValue('hora_fallo', $request->hora_fallo);

            // =========================
            // 4. PERSONAS
            // =========================
            $templateProcessor->setValue('resp_tecnico', $request->resp_tecnico);
            $templateProcessor->setValue('cargo_tecnico', $request->cargo_tecnico);
            $templateProcessor->setValue('resp_admin', $request->resp_admin);

            // =========================
            // 5. FORMATEAR MONTOS 🔥
            // =========================
            $montoMaximo = $this->formatearMonto($request->monto_maximo);
            $montoMinimo = $this->formatearMonto($request->monto_minimo);

            // =========================
            // 6. SOLO WORD
            // =========================
            $templateProcessor->setValue('num_partida', $request->num_partida);
            $templateProcessor->setValue('partida_nombre', $request->partida_nombre);
            $templateProcessor->setValue('num_requisicion', $request->num_requisicion);
            $templateProcessor->setValue('monto_maximo', $montoMaximo);
            $templateProcessor->setValue('monto_minimo', $montoMinimo);
            $templateProcessor->setValue('plazo_contrato', $request->plazo_contrato);

            // =========================
            // 7. GUARDAR DOCUMENTO
            // =========================
            $outputDir = storage_path('app/public/documentos');

            if (!file_exists($outputDir)) {
                mkdir($outputDir, 0777, true);
            }

            $outputName = 'procedimiento_' . $procedimiento->id . '.docx';
            $outputPath = $outputDir . DIRECTORY_SEPARATOR . $outputName;

            $templateProcessor->saveAs($outputPath);

            // =========================
            // 8. GUARDAR RUTA EN BD
            // =========================
            $procedimiento->update([
                'ruta_documento' => 'documentos/' . $outputName
            ]);

            // =========================
            // 9. DESCARGA AUTOMÁTICA
            // =========================
            return response()->download($outputPath);
        }

        return back()->with('error', 'No se subió ningún archivo');
    }

    /**
     * Mostrar resultado (opcional)
     */
    public function show($id)
    {
        $procedimiento = Procedimiento::findOrFail($id);
        return view('comprador.convo.resultado', compact('procedimiento'));
    }

    /**
     * Descargar manual
     */
    public function descargar($id)
    {
        $procedimiento = Procedimiento::findOrFail($id);

        $path = storage_path('app/public/' . $procedimiento->ruta_documento);

        return response()->download($path);
    }
}