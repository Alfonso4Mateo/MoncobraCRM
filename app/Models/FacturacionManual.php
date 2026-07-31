<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacturacionManual extends Model
{
    // 1. Declaramos la tabla (opcional, pero buena práctica por la 's' del inglés)
    protected $table = 'facturacion_manuals';

    // 2. Permitimos la asignación masiva de estos campos
    protected $fillable = [
        'pedido_id',
        'importe',
        'concepto'
    ];

    // 3. Relación: Una facturación manual PERTENECE A un Pedido Cliente
    public function pedidoCliente()
    {
        return $this->belongsTo(PedidoCliente::class, 'pedido_id');
    }
}