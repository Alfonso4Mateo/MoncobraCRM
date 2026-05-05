<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrasladoStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'proyecto_id',
        'numero_traslado',
        'fecha',
        'solicitante',
        'ot',
        'almacen_origen',
        'almacen_actual',
        'items',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'items' => 'array',
    ];
}
