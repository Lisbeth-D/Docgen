<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Procedimiento extends Model
{
    protected $table = 'procedimientos';

    protected $fillable = [
        'id_tipo_procedimiento',
        'nombre_procedimiento',
        'num_procedimiento',
        'monto_maximo',
        'fecha_publicacion',
        'fecha_vm',
        'hora_vm',
        'fecha_ac',
        'hora_ac',
        'fecha_apertura',
        'hora_apertura',
        'fecha_fallo',
        'hora_fallo',
        'fecha_inicio_contrato',
        'fecha_fin_contrato',
        'user_id',
        'id_persona'
    ];

    public function tipo()
    {
        return $this->belongsTo(
            TipoProcedimiento::class,
            'id_tipo_procedimiento',
            'id_tipo_procedimiento'
        );
    }

    public function comprador()
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'id'
        );
    }

    public function persona()
    {
        return $this->belongsTo(
            Persona::class,
            'id_persona',
            'id'
        );
    }
}