<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventarioMovimientoLinea extends Model
{
    use HasFactory;

    protected $table = 'inventario_movimiento_lineas';

    protected $fillable = [
        'movimiento_id', 'variante_id', 'cantidad',
        'saldo_anterior_origen', 'saldo_posterior_origen',
        'saldo_anterior_destino', 'saldo_posterior_destino',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'saldo_anterior_origen' => 'integer',
        'saldo_posterior_origen' => 'integer',
        'saldo_anterior_destino' => 'integer',
        'saldo_posterior_destino' => 'integer',
    ];

    public function movimiento(): BelongsTo
    {
        return $this->belongsTo(InventarioMovimiento::class, 'movimiento_id');
    }

    public function variante(): BelongsTo
    {
        return $this->belongsTo(InventarioVariante::class, 'variante_id');
    }
}
