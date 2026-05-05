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
        'estado',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'items' => 'array',
    ];
}
