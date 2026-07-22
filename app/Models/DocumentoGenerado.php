<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoGenerado extends Model
{
    protected $table =
        'documentos_generados';

    protected $fillable = [
        'user_id',
        'tipo_documento',
        'numero_procedimiento',
        'nombre_archivo',
        'ruta_archivo',
        'disco',
        'tipo_mime',
        'tamano_archivo',
        'fecha_expiracion',
    ];

    protected $casts = [
        'fecha_expiracion' => 'datetime',
        'tamano_archivo' => 'integer',
    ];

    /*
     * Usuario que generó el documento.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    /*
     * Documentos que todavía no han vencido.
     */
    public function scopeVigentes(
        Builder $query
    ): Builder {
        return $query->where(
            'fecha_expiracion',
            '>',
            now()
        );
    }

    /*
     * Documentos que ya deben eliminarse.
     */
    public function scopeVencidos(
        Builder $query
    ): Builder {
        return $query->where(
            'fecha_expiracion',
            '<=',
            now()
        );
    }

    /*
     * Confirma que el documento pertenece
     * al usuario indicado.
     */
    public function perteneceAlUsuario(
        int $userId
    ): bool {
        return (int) $this->user_id
            === $userId;
    }
}