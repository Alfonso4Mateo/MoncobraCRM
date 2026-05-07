<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalidaStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'proyecto_id',
        'numero_salida',
        'fecha',
        'solicitante',
        'ot',
        'almacen_origen',
        'items',
        'documento_meta',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'items' => 'array',
        'documento_meta' => 'array',
    ];
}
