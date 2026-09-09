<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CentroCoste extends Model
{
    protected $table = 'centros_costes';
    protected $fillable = ['codigo', 'descripcion'];

    // Este accessor nos permite llamar a $cc->etiqueta_completa
    public function getEtiquetaCompletaAttribute()
    {
        return "{$this->codigo} ({$this->descripcion})";
    }
}