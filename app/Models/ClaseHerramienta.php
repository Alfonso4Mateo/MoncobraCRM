<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClaseHerramienta extends Model
{
    protected $table = 'clases_herramientas';
    
    protected $fillable = [
        'nombre'
    ];
}