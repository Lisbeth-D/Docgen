<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoProcedimiento extends Model
{
    protected $table = 'tipo_procedimiento';
    protected $primaryKey = 'id_tipo_procedimiento';

    protected $fillable = [
        'nombre_tipo'
    ];
}