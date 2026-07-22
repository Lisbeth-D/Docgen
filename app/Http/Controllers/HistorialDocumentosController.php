<?php

namespace App\Http\Controllers;

use App\Models\DocumentoGenerado;
use App\Services\HistorialDocumentosService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class HistorialDocumentosController extends Controller
{
    public function index(Request $request)
    {
        $buscar =
            trim(
                (string) $request->input(
                    'buscar',
                    ''
                )
            );

        $documentos =
            DocumentoGenerado::query()
                ->where(
                    'user_id',
                    Auth::id()
                )
                ->vigentes()
                ->when(
                    $buscar !== '',
                    function ($query) use ($buscar) {
                        $query->where(
                            function ($subquery) use (
                                $buscar
                            ) {
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
                            }
                        );
                    }
                )
                ->latest()
                ->paginate(10)
                ->withQueryString();

        return view(
            'comprador.historial.index',
            compact(
                'documentos',
                'buscar'
            )
        );
    }

    public function descargar(
        DocumentoGenerado $documento
    ): BinaryFileResponse {
        abort_unless(
            $documento->perteneceAlUsuario(
                (int) Auth::id()
            ),
            403,
            'No tiene autorización para descargar este documento.'
        );

        abort_if(
            $documento->fecha_expiracion
                ->isPast(),
            410,
            'Este documento ya venció.'
        );

        abort_unless(
            Storage::disk(
                $documento->disco
            )->exists(
                $documento->ruta_archivo
            ),
            404,
            'El archivo ya no se encuentra disponible.'
        );

        return Storage::disk(
            $documento->disco
        )->download(
            $documento->ruta_archivo,
            $documento->nombre_archivo,
            [
                'Content-Type' =>
                    $documento->tipo_mime
                    ?: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]
        );
    }

    public function eliminar(
        DocumentoGenerado $documento,
        HistorialDocumentosService $historial
    ) {
        abort_unless(
            $documento->perteneceAlUsuario(
                (int) Auth::id()
            ),
            403
        );

        $historial->eliminar(
            $documento
        );

        return redirect()
            ->route(
                'historial-documentos.index'
            )
            ->with(
                'success',
                'El documento fue eliminado correctamente.'
            );
    }
}