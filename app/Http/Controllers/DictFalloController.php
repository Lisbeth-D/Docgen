<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Persona;
use App\Models\Procedimiento;
use App\Services\HistorialDocumentosService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use NumberFormatter;
use PhpOffice\PhpWord\TemplateProcessor;
use Throwable;

class DictFalloController extends Controller
{
    public function index()
    {
        return view('comprador.Fallo.dictamenFallo');
    }

    public function buscarProcedimiento($valor)
    {
        $procedimiento = $this->consultarProcedimiento((string) $valor);

        if (!$procedimiento) {
            return response()->json(null);
        }

        $personaRequirente = $procedimiento->id_persona
            ? Persona::find($procedimiento->id_persona)
            : null;

        $areaRequirente = $personaRequirente
            ? Area::where('id_area', $personaRequirente->area_id)->first()
            : null;

        $coordinadora = $this->obtenerCoordinadoraGeneral();
        $montoMaximo = (float) ($procedimiento->monto_maximo ?? 0);

        return response()->json([
            'num_procedimiento' => $procedimiento->num_procedimiento,
            'nombre_procedimiento' => $procedimiento->nombre_procedimiento,
            'fecha_publicacion' => $this->fechaInput($procedimiento->fecha_publicacion),
            // La etiqueta ${fecha_acl} se alimenta desde procedimientos.fecha_ac.
            'fecha_acl' => $this->fechaInput($procedimiento->fecha_ac),
            'conv_dispo' => $procedimiento->fecha_ac
                ? Carbon::parse($procedimiento->fecha_ac)->subDay()->format('Y-m-d')
                : '',
            'fecha_apertura' => $this->fechaInput($procedimiento->fecha_apertura),
            'fecha_fallo' => $this->fechaInput($procedimiento->fecha_fallo),
            'hora_fallo' => $this->horaInput($procedimiento->hora_fallo),
            'fecha_inicio_contrato' => $this->fechaInput($procedimiento->fecha_inicio_contrato),
            'fecha_fin_contrato' => $this->fechaInput($procedimiento->fecha_fin_contrato),
            'monto_maximo' => number_format($montoMaximo, 2, '.', ''),
            'monto_minimo' => number_format($montoMaximo * .40, 2, '.', ''),
            'monto_fianza' => number_format($montoMaximo * .10, 2, '.', ''),
            'proposicion_tecnica' => trim((string) ($areaRequirente?->nombre ?? '')),
            'area_requirente_id' => $personaRequirente?->id,
            'area_requirente_nombre' => $this->textoPersona($personaRequirente),
            'oficio_solicitante' => trim((string) ($coordinadora?->plantilla_referencia ?? '')),
            'oficio_respuesta' => trim((string) ($personaRequirente?->plantilla_referencia ?? '')),
        ]);
    }

    public function generar(
        Request $request,
        HistorialDocumentosService $historialDocumentos
    )
    {
        $datos = $request->validate(
            $this->reglasValidacion(),
            $this->mensajesValidacion(),
            $this->atributosValidacion()
        );

        $procedimiento = $this->consultarProcedimiento($datos['numero_busqueda']);

        if (!$procedimiento) {
            return back()->withInput()->withErrors([
                'numero_busqueda' => 'No existe un procedimiento registrado con ese número.',
            ]);
        }

        $personaRequirente = $procedimiento->id_persona
            ? Persona::find($procedimiento->id_persona)
            : null;

        $areaRequirente = $personaRequirente
            ? Area::where('id_area', $personaRequirente->area_id)->first()
            : null;

        if (!$personaRequirente || !$areaRequirente) {
            return back()->withInput()->withErrors([
                'numero_busqueda' => 'El procedimiento no tiene una persona requirente y un área válidas.',
            ]);
        }

        $nombresLicitantes = collect($request->input('nombres_licitantes', []))
            ->map(fn ($nombre) => trim((string) $nombre))
            ->filter()
            ->values()
            ->all();

        if (count($nombresLicitantes) !== (int) $request->num_lici) {
            throw ValidationException::withMessages([
                'num_lici' => 'El número de licitantes no coincide con los nombres capturados.',
            ]);
        }

        $montoMaximo = (float) $request->monto_maximo;

        $valores = [
            'num_procedimiento' => trim((string) ($request->num_procedimiento ?: $procedimiento->num_procedimiento)),
            'nombre_procedimiento' => trim((string) ($request->nombre_procedimiento ?: $procedimiento->nombre_procedimiento)),
            /*
             * ${fecha_fallo} contiene la hora y la fecha completas.
             * Ejemplo: 11:00 horas del día 21 de julio de 2026.
             */
            'fecha_fallo' => Carbon::parse($request->hora_fallo)->format('H:i')
                . ' horas del día ' . $this->fechaTexto($request->fecha_fallo),

            /*
             * ${fecha_fallo_sola} contiene únicamente la fecha.
             * Ejemplo: 21 de julio de 2026.
             */
            'fecha_fallo_sola' => $this->fechaTexto($request->fecha_fallo),
            'fecha_publicacion' => $this->fechaTexto($request->fecha_publicacion),
            'fecha_acl' => $this->fechaTexto($request->fecha_acl),
            'conv_dispo' => $this->fechaTexto($request->conv_dispo),
            'fecha_apertura' => $this->fechaTexto($request->fecha_apertura),
            'num_lici' => $this->numeroLicitantesTexto(count($nombresLicitantes)),
            'proposicion_tecnica' => trim((string) $request->proposicion_tecnica),
            'oficio_solicitante' => trim((string) $request->oficio_solicitante),
            'fecha_oficio_solicitante' => $this->fechaTexto($request->fecha_oficio_solicitante),
            'oficio_respuesta' => trim((string) $request->oficio_respuesta),
            'fecha_oficio_respuesta' => $this->fechaTexto($request->fecha_oficio_respuesta),
            'proveedor_adjudicado' => trim((string) $request->proveedor_adjudicado),
            'monto_minimo' => $this->moneda($montoMaximo * .40),
            'monto_maximo' => $this->moneda($montoMaximo),
            'monto_fianza' => $this->moneda($montoMaximo * .10),
            'numero_contrato' => trim((string) $request->numero_contrato),
            'vigencia_contrato' => 'del ' . $this->fechaTexto($request->fecha_inicio_contrato)
                . ' al ' . $this->fechaTexto($request->fecha_fin_contrato),
            'area_requirente' => trim((string) $areaRequirente->nombre),
            'persona_requirente' => $this->textoPersona($personaRequirente),
        ];

        $plantillaTemporal = null;
        $salida = null;

        try {
            Carbon::setLocale('es');
            $plantillaTemporal = $this->guardarPlantillaTemporal($request);
            $template = new TemplateProcessor($plantillaTemporal);

            foreach ($valores as $marcador => $valor) {
                $template->setValue($marcador, $this->limpiarTexto($valor));
            }

            // La plantilla Word debe contener una fila de tabla con ${nombre_lici}.
            $template->cloneRow('nombre_lici', count($nombresLicitantes));

            foreach ($nombresLicitantes as $indice => $nombre) {
                $template->setValue(
                    'nombre_lici#' . ($indice + 1),
                    $this->limpiarTexto($nombre)
                );
            }

            [$salida, $nombreArchivo] = $this->guardarDocumento(
                $template,
                $procedimiento
            );

            /*
             * Registra una copia del documento en el historial del usuario
             * autenticado. El servicio también elimina los documentos
             * vencidos antes de guardar el nuevo archivo.
             */
            $historialDocumentos->registrar(
                $request->user(),
                $salida,
                $nombreArchivo,
                'Dictamen de fallo',
                trim((string) $procedimiento->num_procedimiento),
                10
            );

            $this->eliminarArchivo($plantillaTemporal);
            $plantillaTemporal = null;

            /*
             * El archivo temporal se elimina después de enviarse. La copia
             * registrada en el historial permanecerá disponible 10 días.
             */
            return response()
                ->download($salida, $nombreArchivo)
                ->deleteFileAfterSend(true);
        } catch (Throwable $e) {
            $this->eliminarArchivo($salida);
            report($e);

            return back()->withInput()->with(
                'error',
                'No fue posible generar el Dictamen de Fallo. Revisa la plantilla y vuelve a intentarlo.'
            );
        } finally {
            $this->eliminarArchivo($plantillaTemporal);
        }
    }

    private function reglasValidacion(): array
    {
        return [
            'numero_busqueda' => ['required', 'string', 'max:100'],
            'num_procedimiento' => ['nullable', 'string', 'max:255'],
            'nombre_procedimiento' => ['nullable', 'string', 'max:1000'],
            'fecha_publicacion' => ['required', 'date'],
            'fecha_acl' => ['required', 'date'],
            'conv_dispo' => ['required', 'date'],
            'fecha_apertura' => ['required', 'date'],
            'fecha_fallo' => ['required', 'date'],
            'hora_fallo' => ['required', 'date_format:H:i'],
            'num_lici' => ['required', 'integer', 'min:1', 'max:100'],
            'nombres_licitantes' => ['required', 'array', 'min:1'],
            'nombres_licitantes.*' => ['required', 'string', 'max:500'],
            'proposicion_tecnica' => ['required', 'string', 'max:500'],
            'oficio_solicitante' => ['required', 'string', 'max:500'],
            'fecha_oficio_solicitante' => ['required', 'date'],
            'oficio_respuesta' => ['required', 'string', 'max:500'],
            'fecha_oficio_respuesta' => ['required', 'date'],
            'proveedor_adjudicado' => ['required', 'string', 'max:500'],
            'monto_maximo' => ['required', 'numeric', 'min:0'],
            'monto_minimo' => ['required', 'numeric', 'min:0'],
            'monto_fianza' => ['required', 'numeric', 'min:0'],
            'numero_contrato' => ['required', 'string', 'max:255'],
            'fecha_inicio_contrato' => ['required', 'date'],
            'fecha_fin_contrato' => ['required', 'date', 'after_or_equal:fecha_inicio_contrato'],
            'archivo_word' => ['required', 'file', 'mimes:docx', 'max:10240'],
        ];
    }

    private function mensajesValidacion(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'date' => 'El campo :attribute no contiene una fecha válida.',
            'date_format' => 'El campo :attribute debe tener el formato HH:MM.',
            'numeric' => 'El campo :attribute debe contener un importe válido.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'array' => 'El campo :attribute debe contener una lista válida.',
            'after_or_equal' => 'La fecha final debe ser igual o posterior a la fecha inicial.',
            'archivo_word.mimes' => 'La plantilla debe ser un archivo Word .docx.',
            'archivo_word.max' => 'La plantilla Word no debe superar los 10 MB.',
            'nombres_licitantes.*.required' => 'Todos los nombres de los licitantes son obligatorios.',
        ];
    }

    private function atributosValidacion(): array
    {
        return [
            'numero_busqueda' => 'número de búsqueda',
            'fecha_publicacion' => 'fecha de publicación',
            'fecha_acl' => 'fecha de aclaraciones',
            'conv_dispo' => 'disponibilidad de la convocatoria',
            'fecha_apertura' => 'fecha de apertura',
            'fecha_fallo' => 'fecha del fallo',
            'hora_fallo' => 'hora del fallo',
            'num_lici' => 'número de licitantes',
            'nombres_licitantes' => 'licitantes',
            'nombres_licitantes.*' => 'nombre del licitante',
            'proposicion_tecnica' => 'proposición técnica',
            'oficio_solicitante' => 'oficio solicitante',
            'fecha_oficio_solicitante' => 'fecha del oficio solicitante',
            'oficio_respuesta' => 'oficio de respuesta',
            'fecha_oficio_respuesta' => 'fecha del oficio de respuesta',
            'proveedor_adjudicado' => 'proveedor adjudicado',
            'monto_maximo' => 'monto máximo',
            'monto_minimo' => 'monto mínimo',
            'monto_fianza' => 'monto de la fianza',
            'numero_contrato' => 'número de contrato',
            'fecha_inicio_contrato' => 'inicio de vigencia',
            'fecha_fin_contrato' => 'fin de vigencia',
            'archivo_word' => 'plantilla Word',
        ];
    }

    private function obtenerCoordinadoraGeneral(): ?Persona
    {
        $areas = Area::whereIn('nombre', [
            'Coordinación General de Adquisiciones y Servicios',
            'Coordinacion de Adquisiciones y Servicios',
            'Coordinación de adquisiciones y servicios',
            'adquisiciones y servicios',
        ])->pluck('id_area');

        if ($areas->isEmpty()) {
            return null;
        }

        return Persona::whereIn('area_id', $areas)
            ->where(function ($query) {
                $query->where('cargo', 'LIKE', '%Coordinadora General%')
                    ->orWhere('cargo', 'LIKE', '%Coordinador General%');
            })
            ->first()
            ?: Persona::whereIn('area_id', $areas)
                ->whereNotNull('plantilla_referencia')
                ->where('plantilla_referencia', '<>', '')
                ->first();
    }

    private function consultarProcedimiento(string $numero): ?Procedimiento
    {
        $numero = trim($numero);

        if ($numero === '') {
            return null;
        }

        return Procedimiento::where(
            'num_procedimiento',
            'LIKE',
            '%-N-' . $numero . '-%'
        )->first();
    }

    private function numeroLicitantesTexto(int $numero): string
    {
        /*
         * Para un solo licitante:
         * UNA (1) proposición
         */
        if ($numero === 1) {
            return 'UNA (1) proposición';
        }

        $palabra = '';

        if (class_exists(NumberFormatter::class)) {
            $formateador = new NumberFormatter(
                'es',
                NumberFormatter::SPELLOUT
            );

            $palabra = (string) $formateador->format(
                $numero
            );
        }

        $alternativas = [
            2 => 'dos',
            3 => 'tres',
            4 => 'cuatro',
            5 => 'cinco',
            6 => 'seis',
            7 => 'siete',
            8 => 'ocho',
            9 => 'nueve',
            10 => 'diez',
            11 => 'once',
            12 => 'doce',
            13 => 'trece',
            14 => 'catorce',
            15 => 'quince',
            16 => 'dieciséis',
            17 => 'diecisiete',
            18 => 'dieciocho',
            19 => 'diecinueve',
            20 => 'veinte',
        ];

        if ($palabra === '') {
            $palabra =
                $alternativas[$numero]
                ?? (string) $numero;
        }

        return mb_strtolower(
            $palabra,
            'UTF-8'
        )
            . ' ('
            . $numero
            . ') proposiciones';
    }

    private function fechaTexto($fecha): string
    {
        $valor = Carbon::parse($fecha)->locale('es');
        return $valor->day . ' de ' . $valor->translatedFormat('F') . ' de ' . $valor->year;
    }

    private function fechaInput($fecha): string
    {
        return $fecha ? Carbon::parse($fecha)->format('Y-m-d') : '';
    }

    private function horaInput($hora): string
    {
        return $hora ? Carbon::parse($hora)->format('H:i') : '';
    }

    private function moneda(float $monto): string
    {
        return '$' . number_format($monto, 2, '.', ',');
    }

    private function textoPersona(?Persona $persona): string
    {
        if (!$persona) {
            return '';
        }

        $nombre = trim((string) $persona->nombre);
        $cargo = trim((string) $persona->cargo);

        return $nombre && $cargo ? $nombre . ' - ' . $cargo : ($nombre ?: $cargo);
    }

    private function guardarPlantillaTemporal(Request $request): string
    {
        $directorio = storage_path('app/plantillas');
        File::ensureDirectoryExists($directorio);
        $nombre = uniqid('plantilla_dictamen_fallo_', true) . '.docx';
        $request->file('archivo_word')->move($directorio, $nombre);
        return $directorio . DIRECTORY_SEPARATOR . $nombre;
    }

    private function guardarDocumento(TemplateProcessor $template, Procedimiento $procedimiento): array
    {
        $directorio = storage_path('app/public/documentos');
        File::ensureDirectoryExists($directorio);
        $numero = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) $procedimiento->num_procedimiento);
        $nombre = 'Dictamen_Fallo_' . trim((string) $numero, '_') . '_' . now()->format('Ymd_His_u') . '.docx';
        $ruta = $directorio . DIRECTORY_SEPARATOR . $nombre;
        $template->saveAs($ruta);
        return [$ruta, $nombre];
    }

    private function limpiarTexto($texto): string
    {
        return preg_replace(
            '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u',
            '',
            trim((string) $texto)
        ) ?? '';
    }

    private function eliminarArchivo(?string $ruta): void
    {
        if ($ruta && File::exists($ruta)) {
            File::delete($ruta);
        }
    }
}