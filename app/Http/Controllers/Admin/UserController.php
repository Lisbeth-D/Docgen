<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Persona;
use App\Models\Procedimiento;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $buscar = $request->buscar;

        $users = User::where('name', 'LIKE', "%$buscar%")
            ->orWhere('username', 'LIKE', "%$buscar%")
            ->orWhere('email', 'LIKE', "%$buscar%")
            ->paginate(5);

        return view(
            'admin.usuarios.index',
            compact('users', 'buscar')
        );
    }

    public function create()
    {
        return view('admin.usuarios.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'username' => 'required|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'required',
            'cargo' => 'nullable|string|max:255',
        ]);

        User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'cargo' => $request->cargo,
        ]);

        return redirect('/usuarios')
            ->with(
                'success',
                'Usuario creado correctamente'
            );
    }

    public function destroy($id)
    {
        User::findOrFail($id)->delete();

        return redirect('/usuarios')
            ->with(
                'success',
                'Usuario eliminado'
            );
    }

    public function resetPassword($id)
    {
        $user = User::findOrFail($id);

        $user->password = Hash::make('12345678');
        $user->save();

        return back()->with(
            'success',
            'Contraseña reseteada'
        );
    }

    public function toggleActivo($id)
    {
        $user = User::findOrFail($id);

        $user->activo = !$user->activo;
        $user->save();

        return back();
    }

    public function actividad()
    {
        $totalUsuarios = User::count();
        $totalPersonas = Persona::count();

        return view(
            'admin.reportes.actividad',
            compact(
                'totalUsuarios',
                'totalPersonas'
            )
        );
    }

    /**
     * Descarga en Excel el reporte de procedimientos registrados.
     */
    public function descargarReporteProcedimientos()
    {
        $procedimientos = Procedimiento::with([
            'tipo',
            'persona',
            'comprador',
        ])
            ->orderByDesc('created_at')
            ->get();

        $spreadsheet = new Spreadsheet();
        $hoja = $spreadsheet->getActiveSheet();

        $hoja->setTitle('Procedimientos');

        $hoja->mergeCells('A1:G1');
        $hoja->setCellValue(
            'A1',
            'REPORTE DE PROCEDIMIENTOS REGISTRADOS'
        );

        $hoja->mergeCells('A2:G2');
        $hoja->setCellValue(
            'A2',
            'Fecha de generación: ' .
            now()->format('d/m/Y H:i')
        );

        $encabezados = [
            'Nombre del procedimiento',
            'Número de procedimiento',
            'Comprador',
            'Área técnica',
            'Cargo técnico',
            'Monto máximo',
            'Vigencia del contrato',
        ];

        foreach ($encabezados as $indice => $encabezado) {
            $columna = chr(65 + $indice);

            $hoja->setCellValue(
                $columna . '4',
                $encabezado
            );
        }

        $fila = 5;

        foreach ($procedimientos as $procedimiento) {
            $nombreComprador =
                optional($procedimiento->comprador)->name
                ?: optional($procedimiento->comprador)->username
                ?: 'Sin asignar';

            $nombrePersona =
                optional($procedimiento->persona)->nombre
                ?: 'Sin asignar';

            $cargoPersona =
                optional($procedimiento->persona)->cargo
                ?: 'Sin asignar';

            $fechaInicio =
                $procedimiento->fecha_inicio_contrato
                    ? Carbon::parse(
                        $procedimiento->fecha_inicio_contrato
                    )->format('d/m/Y')
                    : 'Sin fecha';

            $fechaFin =
                $procedimiento->fecha_fin_contrato
                    ? Carbon::parse(
                        $procedimiento->fecha_fin_contrato
                    )->format('d/m/Y')
                    : 'Sin fecha';

            $vigencia =
                $fechaInicio .
                ' al ' .
                $fechaFin;

            $hoja->setCellValue(
                "A{$fila}",
                $procedimiento->nombre_procedimiento
            );

            $hoja->setCellValue(
                "B{$fila}",
                $procedimiento->num_procedimiento
            );

            $hoja->setCellValue(
                "C{$fila}",
                $nombreComprador
            );

            $hoja->setCellValue(
                "D{$fila}",
                $nombrePersona
            );

            $hoja->setCellValue(
                "E{$fila}",
                $cargoPersona
            );

            $hoja->setCellValue(
                "F{$fila}",
                (float) ($procedimiento->monto_maximo ?? 0)
            );

            $hoja->setCellValue(
                "G{$fila}",
                $vigencia
            );

            $fila++;
        }

        $ultimaFila = max(5, $fila - 1);

        $hoja->getStyle('A1:G1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '7A1623'],
            ],
            'alignment' => [
                'horizontal' =>
                    Alignment::HORIZONTAL_CENTER,
                'vertical' =>
                    Alignment::VERTICAL_CENTER,
            ],
        ]);

        $hoja->getStyle('A2:G2')->applyFromArray([
            'font' => [
                'italic' => true,
                'color' => ['rgb' => '666666'],
            ],
            'alignment' => [
                'horizontal' =>
                    Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $hoja->getStyle('A4:G4')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '9F1239'],
            ],
            'alignment' => [
                'horizontal' =>
                    Alignment::HORIZONTAL_CENTER,
                'vertical' =>
                    Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' =>
                        Border::BORDER_THIN,
                    'color' => ['rgb' => 'E5E7EB'],
                ],
            ],
        ]);

        $hoja->getStyle(
            "A5:G{$ultimaFila}"
        )->applyFromArray([
            'alignment' => [
                'vertical' =>
                    Alignment::VERTICAL_TOP,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' =>
                        Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB'],
                ],
            ],
        ]);

        $hoja->getStyle(
            "F5:F{$ultimaFila}"
        )
            ->getNumberFormat()
            ->setFormatCode('$#,##0.00');

        $hoja->getColumnDimension('A')->setWidth(45);
        $hoja->getColumnDimension('B')->setWidth(32);
        $hoja->getColumnDimension('C')->setWidth(28);
        $hoja->getColumnDimension('D')->setWidth(35);
        $hoja->getColumnDimension('E')->setWidth(38);
        $hoja->getColumnDimension('F')->setWidth(20);
        $hoja->getColumnDimension('G')->setWidth(28);

        $hoja->getRowDimension(1)->setRowHeight(28);
        $hoja->getRowDimension(4)->setRowHeight(32);

        $hoja->freezePane('A5');

        if ($procedimientos->isNotEmpty()) {
            $hoja->setAutoFilter(
                "A4:G{$ultimaFila}"
            );
        }

        $directorio = storage_path('app/temp');

        File::ensureDirectoryExists($directorio);

        $nombreArchivo =
            'Reporte_Procedimientos_' .
            now()->format('Ymd_His') .
            '.xlsx';

        $rutaArchivo =
            $directorio .
            DIRECTORY_SEPARATOR .
            $nombreArchivo;

        $writer = new Xlsx($spreadsheet);
        $writer->save($rutaArchivo);

        $spreadsheet->disconnectWorksheets();

        return response()
            ->download(
                $rutaArchivo,
                $nombreArchivo
            )
            ->deleteFileAfterSend(true);
    }

    /**
     * FORMULARIO EDITAR USUARIO
     */
    public function edit($id)
    {
        $user = User::findOrFail($id);

        return view(
            'admin.usuarios.edit',
            compact('user')
        );
    }

    /**
     * ACTUALIZAR USUARIO
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'username' =>
                'required|unique:users,username,' . $id,
            'email' =>
                'required|email|unique:users,email,' . $id,
            'role' => 'required',
        ]);

        $user = User::findOrFail($id);

        $user->update([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'role' => $request->role,
            'cargo' => $request->cargo,
        ]);

        return redirect('/usuarios')
            ->with(
                'success',
                'Usuario actualizado'
            );
    }
}