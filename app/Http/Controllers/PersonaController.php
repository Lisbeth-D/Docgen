<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class PersonaController extends Controller
{
    /**
     * Listado de personas con filtro por área.
     */
    public function index(Request $request)
    {
        $areas = Area::orderBy('nombre')->get();
        $areaId = $request->input('area_id');

        $personas = Persona::with('area')
            ->when($areaId, function ($query) use ($areaId) {
                $query->where('area_id', $areaId);
            })
            ->orderByDesc($this->obtenerLlavePersona())
            ->paginate(10)
            ->withQueryString();

        return view(
            'admin.personas.index',
            compact('personas', 'areas', 'areaId')
        );
    }

    /**
     * Formulario para crear una persona.
     */
    public function create()
    {
        $areas = Area::orderBy('nombre')->get();

        return view(
            'admin.personas.create',
            compact('areas')
        );
    }

    /**
     * Guarda una persona.
     */
    public function store(Request $request)
    {
        $datos = $request->validate(
            $this->reglasPersona(),
            $this->mensajesValidacion(),
            $this->atributosValidacion()
        );

        Persona::create($datos);

        return redirect()
            ->route('personas.index')
            ->with(
                'success',
                'Persona registrada correctamente.'
            );
    }

    /**
     * Formulario de edición.
     */
    public function edit($id)
    {
        $persona = Persona::findOrFail($id);
        $areas = Area::orderBy('nombre')->get();

        return view(
            'admin.personas.edit',
            compact('persona', 'areas')
        );
    }

    /**
     * Actualiza una persona.
     */
    public function update(Request $request, $id)
    {
        $datos = $request->validate(
            $this->reglasPersona(),
            $this->mensajesValidacion(),
            $this->atributosValidacion()
        );

        $persona = Persona::findOrFail($id);
        $persona->update($datos);

        return redirect()
            ->route('personas.index')
            ->with(
                'success',
                'Persona actualizada correctamente.'
            );
    }

    /**
     * Elimina una persona.
     */
    public function destroy($id)
    {
        $persona = Persona::findOrFail($id);
        $persona->delete();

        return back()->with(
            'success',
            'Persona eliminada correctamente.'
        );
    }

    /**
     * Descarga una plantilla de Excel con las personas actuales.
     *
     * Las filas con ID actualizarán registros existentes.
     * Las filas sin ID crearán nuevas personas.
     */
    public function descargarPlantillaMasiva()
    {
        $spreadsheet = new Spreadsheet();
        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setTitle('Personas');

        $catalogos = $spreadsheet->createSheet();
        $catalogos->setTitle('Catalogos');

        $areas = Area::orderBy('nombre')->get();
        $personas = Persona::with('area')
            ->orderBy('nombre')
            ->get();

        $hoja->mergeCells('A1:E1');
        $hoja->setCellValue(
            'A1',
            'PLANTILLA DE CARGA MASIVA DE PERSONAS'
        );

        $hoja->mergeCells('A2:E2');
        $hoja->setCellValue(
            'A2',
            'Conserve los encabezados. ID vacío = alta nueva; ID con valor = actualización.'
        );

        $encabezados = [
            'ID',
            'Nombre completo',
            'Cargo',
            'Área',
            'Plantilla de referencia',
        ];

        foreach ($encabezados as $indice => $encabezado) {
            $columna = Coordinate::stringFromColumnIndex(
                $indice + 1
            );

            $hoja->setCellValue(
                $columna . '4',
                $encabezado
            );
        }

        $fila = 5;

        foreach ($personas as $persona) {
            $hoja->setCellValue("A{$fila}", $persona->getKey());
            $hoja->setCellValue("B{$fila}", $persona->nombre);
            $hoja->setCellValue("C{$fila}", $persona->cargo);
            $hoja->setCellValue(
                "D{$fila}",
                $persona->area?->nombre ?? ''
            );
            $hoja->setCellValue(
                "E{$fila}",
                $persona->plantilla_referencia ?? ''
            );

            $fila++;
        }

        if ($personas->isEmpty()) {
            $hoja->setCellValue('B5', 'Ejemplo: Juan Pérez López');
            $hoja->setCellValue('C5', 'Director Jurídico');
            $hoja->setCellValue('D5', 'Jurídico Centrales');
            $hoja->setCellValue(
                'E5',
                'SABG/OIC/VSS/{NUMERO}/2026'
            );

            $fila = 6;
        }

        $ultimaFilaEditable = max($fila + 100, 200);

        $catalogos->setCellValue('A1', 'Áreas');

        foreach ($areas as $indice => $area) {
            $catalogos->setCellValue(
                'A' . ($indice + 2),
                $area->nombre
            );
        }

        $ultimaFilaCatalogo = max(
            2,
            $areas->count() + 1
        );

        for ($numeroFila = 5; $numeroFila <= $ultimaFilaEditable; $numeroFila++) {
            $validacion = new DataValidation();
            $validacion->setType(DataValidation::TYPE_LIST);
            $validacion->setErrorStyle(DataValidation::STYLE_STOP);
            $validacion->setAllowBlank(false);
            $validacion->setShowErrorMessage(true);
            $validacion->setShowDropDown(true);
            $validacion->setErrorTitle('Área no válida');
            $validacion->setError(
                'Seleccione un área del catálogo.'
            );
            $validacion->setPromptTitle('Área');
            $validacion->setPrompt(
                'Seleccione un área registrada.'
            );
            $validacion->setFormula1(
                "'Catalogos'!\$A\$2:\$A\${$ultimaFilaCatalogo}"
            );

            $hoja->getCell("D{$numeroFila}")
                ->setDataValidation($validacion);
        }

        $this->aplicarFormatoPlantilla(
            $hoja,
            $ultimaFilaEditable
        );

        $catalogos->setSheetState(
            \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN
        );

        $directorio = storage_path('app/temp');
        File::ensureDirectoryExists($directorio);

        $nombreArchivo =
            'plantilla_carga_masiva_personas_'
            . now()->format('Ymd_His')
            . '.xlsx';

        $ruta = $directorio
            . DIRECTORY_SEPARATOR
            . $nombreArchivo;

        (new Xlsx($spreadsheet))->save($ruta);
        $spreadsheet->disconnectWorksheets();

        return response()
            ->download($ruta, $nombreArchivo)
            ->deleteFileAfterSend(true);
    }

    /**
     * Procesa la carga masiva de personas.
     */
    public function importarMasivo(Request $request)
    {
        $request->validate(
            [
                'archivo_personas' => [
                    'required',
                    'file',
                    'mimes:xlsx,xls',
                    'max:10240',
                ],
            ],
            [
                'archivo_personas.required' =>
                    'Debe seleccionar un archivo de Excel.',

                'archivo_personas.file' =>
                    'El archivo seleccionado no es válido.',

                'archivo_personas.mimes' =>
                    'El archivo debe tener extensión .xlsx o .xls.',

                'archivo_personas.max' =>
                    'El archivo no debe superar los 10 MB.',
            ]
        );

        $rutaTemporal = null;

        try {
            $archivo = $request->file('archivo_personas');
            $rutaTemporal = $archivo->getRealPath();

            $spreadsheet = IOFactory::load($rutaTemporal);
            $hoja = $spreadsheet->getSheetByName('Personas')
                ?? $spreadsheet->getActiveSheet();

            $encabezados = $this->leerEncabezados($hoja);
            $this->validarEncabezados($encabezados);

            $areasPorNombre = Area::all()
                ->keyBy(function ($area) {
                    return $this->normalizarTexto($area->nombre);
                });

            $creadas = 0;
            $actualizadas = 0;
            $omitidas = 0;
            $errores = [];

            DB::beginTransaction();

            for (
                $fila = 5;
                $fila <= $hoja->getHighestDataRow();
                $fila++
            ) {
                $id = trim((string) $hoja->getCell("A{$fila}")->getFormattedValue());
                $nombre = trim((string) $hoja->getCell("B{$fila}")->getFormattedValue());
                $cargo = trim((string) $hoja->getCell("C{$fila}")->getFormattedValue());
                $nombreArea = trim((string) $hoja->getCell("D{$fila}")->getFormattedValue());
                $plantilla = trim((string) $hoja->getCell("E{$fila}")->getFormattedValue());

                if (
                    $id === ''
                    && $nombre === ''
                    && $cargo === ''
                    && $nombreArea === ''
                    && $plantilla === ''
                ) {
                    $omitidas++;
                    continue;
                }

                $erroresFila = [];

                if ($nombre === '') {
                    $erroresFila[] = 'El nombre es obligatorio.';
                }

                if ($cargo === '') {
                    $erroresFila[] = 'El cargo es obligatorio.';
                }

                if ($nombreArea === '') {
                    $erroresFila[] = 'El área es obligatoria.';
                }

                if ($plantilla === '') {
                    $erroresFila[] =
                        'La plantilla de referencia es obligatoria.';
                }

                $area = $areasPorNombre->get(
                    $this->normalizarTexto($nombreArea)
                );

                if ($nombreArea !== '' && !$area) {
                    $erroresFila[] =
                        "El área '{$nombreArea}' no existe en la base de datos.";
                }

                if ($id !== '' && !ctype_digit($id)) {
                    $erroresFila[] =
                        'El ID debe ser numérico o quedar vacío.';
                }

                if ($erroresFila) {
                    $errores[] =
                        'Fila '
                        . $fila
                        . ': '
                        . implode(' ', $erroresFila);
                    continue;
                }

                $datos = [
                    'nombre' => $nombre,
                    'cargo' => $cargo,
                    'area_id' => $area->id_area,
                    'plantilla_referencia' => $plantilla,
                ];

                if ($id !== '') {
                    $persona = Persona::find($id);

                    if (!$persona) {
                        $errores[] =
                            "Fila {$fila}: no existe una persona con ID {$id}.";
                        continue;
                    }

                    $persona->update($datos);
                    $actualizadas++;
                } else {
                    Persona::create($datos);
                    $creadas++;
                }
            }

            if ($errores) {
                DB::rollBack();

                throw ValidationException::withMessages([
                    'archivo_personas' => $errores,
                ]);
            }

            DB::commit();
            $spreadsheet->disconnectWorksheets();

            $rutaRetorno = $request->routeIs(
                'comprador.registros.personas.*'
            )
                ? 'comprador.registros.index'
                : 'personas.index';

            return redirect()
                ->route($rutaRetorno)
                ->with(
                    'success',
                    "Carga masiva concluida: {$creadas} personas creadas, {$actualizadas} actualizadas y {$omitidas} filas vacías omitidas."
                );
        } catch (ValidationException $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            throw $e;
        } catch (Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            report($e);

            return back()
                ->withInput()
                ->withErrors([
                    'archivo_personas' =>
                        'No fue posible procesar el archivo. Verifique que corresponda a la plantilla de carga masiva.',
                ]);
        }
    }

    private function reglasPersona(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:255',
            ],
            'cargo' => [
                'required',
                'string',
                'max:255',
            ],
            'area_id' => [
                'required',
                'integer',
                'exists:areas,id_area',
            ],
            'plantilla_referencia' => [
                'required',
                'string',
                'max:255',
            ],
        ];
    }

    private function mensajesValidacion(): array
    {
        return [
            'required' =>
                'El campo :attribute es obligatorio.',
            'string' =>
                'El campo :attribute debe contener texto válido.',
            'integer' =>
                'El campo :attribute debe contener un número entero.',
            'max' =>
                'El campo :attribute no debe exceder :max caracteres.',
            'exists' =>
                'El valor seleccionado para :attribute no existe.',
        ];
    }

    private function atributosValidacion(): array
    {
        return [
            'nombre' => 'nombre completo',
            'cargo' => 'cargo',
            'area_id' => 'área',
            'plantilla_referencia' =>
                'plantilla de referencia',
        ];
    }

    private function leerEncabezados($hoja): array
    {
        return [
            $this->normalizarTexto(
                $hoja->getCell('A4')->getFormattedValue()
            ),
            $this->normalizarTexto(
                $hoja->getCell('B4')->getFormattedValue()
            ),
            $this->normalizarTexto(
                $hoja->getCell('C4')->getFormattedValue()
            ),
            $this->normalizarTexto(
                $hoja->getCell('D4')->getFormattedValue()
            ),
            $this->normalizarTexto(
                $hoja->getCell('E4')->getFormattedValue()
            ),
        ];
    }

    private function validarEncabezados(array $encabezados): void
    {
        $esperados = [
            'id',
            'nombre completo',
            'cargo',
            'area',
            'plantilla de referencia',
        ];

        if ($encabezados !== $esperados) {
            throw ValidationException::withMessages([
                'archivo_personas' =>
                    'Los encabezados del archivo fueron modificados. Descargue nuevamente la plantilla oficial.',
            ]);
        }
    }

    private function normalizarTexto($texto): string
    {
        $texto = mb_strtolower(
            trim((string) $texto),
            'UTF-8'
        );

        return strtr($texto, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n',
        ]);
    }

    private function aplicarFormatoPlantilla(
        $hoja,
        int $ultimaFila
    ): void {
        $hoja->getStyle('A1:E1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 14,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '9F1239'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $hoja->getStyle('A2:E2')->applyFromArray([
            'font' => [
                'italic' => true,
                'color' => ['rgb' => '831843'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FCE7F3'],
            ],
            'alignment' => [
                'wrapText' => true,
            ],
        ]);

        $hoja->getStyle('A4:E4')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'BE123C'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '881337'],
                ],
            ],
        ]);

        $hoja->getStyle("A5:E{$ultimaFila}")
            ->applyFromArray([
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E5E7EB'],
                    ],
                ],
            ]);

        $hoja->getColumnDimension('A')->setWidth(12);
        $hoja->getColumnDimension('B')->setWidth(30);
        $hoja->getColumnDimension('C')->setWidth(38);
        $hoja->getColumnDimension('D')->setWidth(38);
        $hoja->getColumnDimension('E')->setWidth(44);

        $hoja->getRowDimension(1)->setRowHeight(28);
        $hoja->getRowDimension(2)->setRowHeight(32);
        $hoja->getRowDimension(4)->setRowHeight(24);
        $hoja->freezePane('A5');
        $hoja->setAutoFilter("A4:E{$ultimaFila}");
    }

    /**
     * Se adapta al primary key configurado en el modelo Persona.
     */
    private function obtenerLlavePersona(): string
    {
        return (new Persona())->getKeyName();
    }
}