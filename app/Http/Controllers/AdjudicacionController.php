<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpWord\TemplateProcessor;
use App\Models\Persona;
use App\Models\Procedimiento;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AdjudicacionController extends Controller
{
    public function index()
    {
        $personas = Persona::whereHas('area', function ($query) {
                $query->where('nombre', 'Coordinación de Adquisiciones y Servicios');
            })
            ->orderBy('nombre')
            ->get();

        return view('comprador.adjudicacion.adjudicacion', compact('personas'));
    }

    public function buscarProcedimiento($valor)
    {
        $valor = trim($valor);

        $procedimiento = Procedimiento::with('tipo')
            ->where('num_procedimiento', 'LIKE', '%-N-' . $valor . '-%')
            ->first();

        if (!$procedimiento) {
            return response()->json(null);
        }

        return response()->json([
            'tipo'                  => optional($procedimiento->tipo)->nombre_tipo ?? '',
            'num_procedimiento'     => $procedimiento->num_procedimiento,
            'nombre_procedimiento'  => $procedimiento->nombre_procedimiento,
            'monto_maximo'          => $procedimiento->monto_maximo ?? '',
            'fecha_inicio_contrato' => $procedimiento->fecha_inicio_contrato
                ? Carbon::parse($procedimiento->fecha_inicio_contrato)->format('Y-m-d')
                : '',
            'fecha_fin_contrato'    => $procedimiento->fecha_fin_contrato
                ? Carbon::parse($procedimiento->fecha_fin_contrato)->format('Y-m-d')
                : '',
        ]);
    }

    public function generar(Request $request)
    {
        $request->validate([
            'oficio_numero'           => 'required',
            'fecha_oficio'            => 'required|date',
            'numero_busqueda'         => 'required',

            'tipo_contrato_monto'     => 'required|in:abierto,cerrado',

            'procedimiento_tipo'      => 'nullable',
            'num_procedimiento'       => 'required',
            'nombre_procedimiento'    => 'required',

            'contrato_numero'         => 'nullable',
            'monto_minimo'            => 'nullable|numeric',
            'monto_maximo'            => 'nullable|numeric',
            'fecha_inicio'            => 'nullable|date',
            'fecha_fin'               => 'nullable|date',

            'proveedor_razon_social'  => 'nullable',
            'proveedor_rfc'           => 'nullable',
            'proveedor_domicilio'     => 'nullable',
            'proveedor_email'         => 'nullable|email',
            'proveedor_telefono'      => 'nullable',

            'reviso_id'               => 'nullable|exists:personas,id',
            'documentos'              => 'nullable|array',
            'archivo_word'            => 'required|file|mimes:docx'
        ]);

        $procedimiento = Procedimiento::where(
            'num_procedimiento',
            'LIKE',
            '%-N-' . $request->numero_busqueda . '-%'
        )->first();

        if (!$procedimiento) {
            return back()->with('error', 'No se encontró el procedimiento');
        }

        $fechaOficio = Carbon::parse($request->fecha_oficio)
            ->locale('es')
            ->translatedFormat('d \d\e F \d\e Y');

        $template = new TemplateProcessor($request->file('archivo_word'));

        $template->setValue('oficio_numero', $request->oficio_numero);
        $template->setValue('fecha_oficio', $fechaOficio);

        $template->setValue('proveedor_razon_social', $request->proveedor_razon_social ?? '');
        $template->setValue('proveedor_rfc', $request->proveedor_rfc ?? '');
        $template->setValue('proveedor_domicilio', $request->proveedor_domicilio ?? '');
        $template->setValue('proveedor_email', $request->proveedor_email ?? '');
        $template->setValue('proveedor_telefono', $request->proveedor_telefono ?? '');

        $template->setValue('procedimiento_numero', $request->num_procedimiento);
        $template->setValue('num_procedimiento', $request->num_procedimiento);
        $template->setValue('objeto_contrato', $request->nombre_procedimiento);
        $template->setValue('nombre_procedimiento', $request->nombre_procedimiento);
        $template->setValue('procedimiento_tipo', $request->procedimiento_tipo ?? '');
        $template->setValue('contrato_numero', $request->contrato_numero ?? '');

        if ($request->tipo_contrato_monto === 'abierto') {
            $template->setValue(
                'monto_minimo',
                $request->filled('monto_minimo') ? $this->numeroALetras($request->monto_minimo) : ''
            );
        } else {
            $template->setValue('monto_minimo', '');
        }

        $template->setValue(
            'monto_maximo',
            $request->filled('monto_maximo') ? $this->numeroALetras($request->monto_maximo) : ''
        );

        $template->setValue(
            'vigencia',
            ($request->filled('fecha_inicio') && $request->filled('fecha_fin'))
                ? $this->formatearFechas($request->fecha_inicio, $request->fecha_fin)
                : ''
        );

        $mapaDocumentos = [
            "Acta Constitutiva y reformas" => "a) Acta Constitutiva y sus reformas, señalando los siguientes datos: número de escritura, lugar y fecha de constitución, nombre y número de la notaría y notario, nombre o razón social de la empresa, objeto social de la empresa, lugar, fecha y folio del registro público de la propiedad) y/o acta de nacimiento.",
            "Poder Notarial del Representante Legal" => "b) Poder Notarial de su Representante Legal señalando los siguientes datos: nombre del apoderado ó representante legal, numero de escritura, lugar, fecha y nombre de quien expide el instrumento notarial de constitución o poder y RFC del mismo.",
            "Constancia de situación fiscal" => "c) Constancia de situación fiscal, cuya Actividad esté relacionada con los bienes objeto del presente procedimiento.",
            "Identificación oficial vigente" => "d) Copia simple (frente y reverso) de identificación oficial vigente (INE, Pasaporte).",
            "Comprobante de domicilio" => "e) Comprobante de domicilio no mayor a 60 días.",
            "Opinión de cumplimiento fiscal SAT (32-D)" => "f) Opinión de cumplimiento fiscal SAT en sentido positivo.",
            "Opinión de cumplimiento IMSS" => "g) Opinión de cumplimiento IMSS en sentido positivo.",
            "Opinión de cumplimiento INFONAVIT (32-D)" => "h) Opinión INFONAVIT sin adeudo.",
            "Tarjeta patronal IMSS" => "i) Tarjeta Patronal IMSS.",
            "CLABE interbancaria" => "j) Clabe interbancaria.",
            "Registro Único de Proveedores (RUP)" => "k) Registro Único de Proveedores (RUP)"
        ];

        $documentos = $request->documentos ?? [];

        if (!empty($documentos)) {
            $textoDocs = implode('</w:t><w:br/><w:t>', array_map(function ($doc) use ($mapaDocumentos) {
                return $mapaDocumentos[$doc] ?? '';
            }, $documentos));

            $template->setValue('documentos', $textoDocs);
        } else {
            $template->setValue('documentos', '');
        }

        $textoReviso = '';

        if ($request->filled('reviso_id')) {
            $persona = Persona::find($request->reviso_id);

            if ($persona) {
                $textoReviso = $persona->nombre . '.- ' . $persona->cargo . ':';
            }
        }

        $template->setValue('reviso', $textoReviso);

        $user = Auth::user();
        $template->setValue('elaboro', $user ? $user->name : '');

        $fileName = 'Adjudicacion_' . time() . '.docx';
        $path = storage_path($fileName);

        $template->saveAs($path);

        return response()->download($path)->deleteFileAfterSend(true);
    }

    private function numeroALetras($numero)
    {
        $numero = (float) $numero;

        $formatter = new \NumberFormatter("es", \NumberFormatter::SPELLOUT);

        $entero = floor($numero);
        $decimal = round(($numero - $entero) * 100);

        $letras = ucfirst($formatter->format($entero));
        $letras = str_replace("\xC2\xAD", '', $letras);

        return number_format($numero, 2) . " (" . $letras . " pesos " . str_pad($decimal, 2, '0', STR_PAD_LEFT) . "/100 M.N.)";
    }

    private function formatearFechas($inicio, $fin)
    {
        $meses = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
        ];

        $i = date('j', strtotime($inicio)) . ' de ' . $meses[date('n', strtotime($inicio))] . ' de ' . date('Y', strtotime($inicio));
        $f = date('j', strtotime($fin)) . ' de ' . $meses[date('n', strtotime($fin))] . ' de ' . date('Y', strtotime($fin));

        return "$i al $f";
    }
}