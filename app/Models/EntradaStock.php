<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntradaStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'proyecto_id',
        'numero_entrada',
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
