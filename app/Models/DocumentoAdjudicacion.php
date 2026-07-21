<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentoAdjudicacion extends Model
{
    protected $table = 'documentos_adjudicacion';
    protected $primaryKey = 'id_documento';

    protected $fillable = [
        'nombre',
        'leyenda',
        'orden',
        'activo',
        'obligatorio',
    ];

    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'activo' => 'boolean',
            'obligatorio' => 'boolean',
        ];
    }
}
