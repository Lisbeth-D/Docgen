<?php

namespace App\Http\Controllers;

use App\Models\DocumentoGenerado;
use App\Services\HistorialDocumentosService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HistorialDocumentosController extends Controller
{
    /**
     * Muestra únicamente los documentos vigentes del usuario autenticado.
     */
    public function index(Request $request)
    {
        $buscar = trim(
            (string) $request->input('buscar', '')
        );

        $documentos = DocumentoGenerado::query()
            ->where('user_id', Auth::id())
            ->vigentes()
            ->when(
                $buscar !== '',
                function ($query) use ($buscar) {
                    $query->where(function ($subquery) use ($buscar) {
                        $subquery
                            ->where(
                                'tipo_documento',
                                'LIKE',
                                "%{$buscar}%"
                            )
                            ->orWhere(
                                'numero_procedimiento',
                                'LIKE',
                                "%{$buscar}%"
                            )
                            ->orWhere(
                                'nombre_archivo',
                                'LIKE',
                                "%{$buscar}%"
                            );
                    });
                }
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view(
            'comprador.historial.index',
            compact('documentos', 'buscar')
        );
    }

    /**
     * Permite volver a descargar un documento guardado en el historial.
     *
     * El archivo NO se elimina después de descargarlo, por lo que el usuario
     * puede recuperarlo nuevamente mientras siga vigente y exista en disco.
     */
    public function descargar(
        DocumentoGenerado $documento
    ): StreamedResponse {
        abort_unless(
            $documento->perteneceAlUsuario((int) Auth::id()),
            403,
            'No tiene autorización para descargar este documento.'
        );

        abort_if(
            $documento->fecha_expiracion
                && $documento->fecha_expiracion->isPast(),
            410,
            'Este documento ya venció.'
        );

        $disco = trim((string) ($documento->disco ?: 'local'));
        $rutaArchivo = trim((string) $documento->ruta_archivo);
        $nombreArchivo = trim((string) $documento->nombre_archivo);

        abort_if(
            $rutaArchivo === '',
            404,
            'El documento no tiene una ruta de archivo registrada.'
        );

        abort_unless(
            Storage::disk($disco)->exists($rutaArchivo),
            404,
            'El archivo ya no se encuentra disponible en el almacenamiento.'
        );

        if ($nombreArchivo === '') {
            $nombreArchivo = basename($rutaArchivo);
        }

        return Storage::disk($disco)->download(
            $rutaArchivo,
            $nombreArchivo,
            [
                'Content-Type' =>
                    $documento->tipo_mime
                    ?: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]
        );
    }

    /**
     * Elimina manualmente un documento del historial y del almacenamiento.
     */
    public function eliminar(
        DocumentoGenerado $documento,
        HistorialDocumentosService $historial
    ) {
        abort_unless(
            $documento->perteneceAlUsuario((int) Auth::id()),
            403,
            'No tiene autorización para eliminar este documento.'
        );

        $historial->eliminar($documento);

        return redirect()
            ->route('historial-documentos.index')
            ->with(
                'success',
                'El documento fue eliminado correctamente.'
            );
    }
}