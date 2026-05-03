<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
    protected $fillable = [
        'nombre',
        'cargo',
        'area_id',
        'plantilla_referencia', 
    ];

    /**
     * Relación con área
     */
    public function area()
    {
        return $this->belongsTo(Area::class);
    }
}