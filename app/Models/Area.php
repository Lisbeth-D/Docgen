<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    public function personas()
{
    return $this->hasMany(Persona::class);
}

}

