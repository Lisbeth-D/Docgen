<?php

namespace App\Services;

use App\Models\DocumentoGenerado;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class HistorialDocumentosService
{
    /**
     * Registra un documento ya generado
     * y lo conserva durante 10 días.
     */
    public function registrar(
        User $usuario,
        string $rutaTemporal,
        string $nombreArchivo,
        string $tipoDocumento,
        ?string $numeroProcedimiento = null,
        int $diasConservacion = 10
    ): DocumentoGenerado {

        $this->limpiarVencidos();

        if (!File::exists($rutaTemporal)) {
            throw new RuntimeException(
                'El documento generado no existe.'
            );
        }

        $nombreSeguro =
            $this->crearNombreSeguro(
                $nombreArchivo
            );

        /*
         * Cada usuario tendrá su propia carpeta.
         */
        $rutaDestino =
            'documentos/historial/'
            . $usuario->id
            . '/'
            . now()->format('Y/m/d')
            . '/'
            . Str::uuid()
            . '_'
            . $nombreSeguro;

        $contenido =
            File::get($rutaTemporal);

        $guardado = Storage::disk('local')
            ->put(
                $rutaDestino,
                $contenido
            );

        if (!$guardado) {
            throw new RuntimeException(
                'No fue posible guardar el documento en el historial.'
            );
        }

        return DocumentoGenerado::create([
            'user_id' =>
                $usuario->id,

            'tipo_documento' =>
                trim($tipoDocumento),

            'numero_procedimiento' =>
                $numeroProcedimiento
                    ? trim($numeroProcedimiento)
                    : null,

            'nombre_archivo' =>
                $nombreSeguro,

            'ruta_archivo' =>
                $rutaDestino,

            'disco' =>
                'local',

            'tipo_mime' =>
                File::mimeType($rutaTemporal)
                ?: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',

            'tamano_archivo' =>
                File::size($rutaTemporal),

            'fecha_expiracion' =>
                now()->addDays(
                    $diasConservacion
                ),
        ]);
    }

    /**
     * Elimina el archivo físico y su registro.
     */
    public function eliminar(
        DocumentoGenerado $documento
    ): void {
        Storage::disk(
            $documento->disco
        )->delete(
            $documento->ruta_archivo
        );

        $documento->delete();
    }

    /**
     * Comprueba si el archivo todavía existe.
     */
    public function existe(
        DocumentoGenerado $documento
    ): bool {
        return Storage::disk(
            $documento->disco
        )->exists(
            $documento->ruta_archivo
        );
    }

    /**
     * Evita caracteres problemáticos en el nombre.
     */
    private function crearNombreSeguro(
        string $nombreArchivo
    ): string {
        $nombreArchivo =
            basename(
                trim($nombreArchivo)
            );

        $extension =
            pathinfo(
                $nombreArchivo,
                PATHINFO_EXTENSION
            );

        $nombreSinExtension =
            pathinfo(
                $nombreArchivo,
                PATHINFO_FILENAME
            );

        $nombreSinExtension =
            Str::slug(
                $nombreSinExtension,
                '_'
            );

        if ($nombreSinExtension === '') {
            $nombreSinExtension =
                'documento_generado';
        }

        $extension =
            strtolower(
                preg_replace(
                    '/[^a-zA-Z0-9]/',
                    '',
                    $extension
                )
            );

        if ($extension === '') {
            $extension = 'docx';
        }

        return
            $nombreSinExtension
            . '.'
            . $extension;
    }

 /**
 * Elimina los documentos vencidos por bloques
 * para evitar cargar todos los registros en memoria.
 */
public function limpiarVencidos(): void
{
    DocumentoGenerado::vencidos()
        ->orderBy('id')
        ->chunkById(
            100,
            function ($documentos) {
                foreach ($documentos as $documento) {
                    try {
                        Storage::disk(
                            $documento->disco
                        )->delete(
                            $documento->ruta_archivo
                        );

                        $documento->delete();
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            }
        );
}
}