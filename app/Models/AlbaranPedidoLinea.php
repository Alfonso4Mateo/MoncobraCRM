<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlbaranPedidoLinea extends Model
{
    use Auditable, HasFactory;

    protected $table = 'albaran_pedido_lineas';

    protected $fillable = [
        'albaran_id',
        'pedido_id',
        'linea_id',
        'linea_uid',
        'cantidad',
        'precio_unitario',
        'total',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function albaran(): BelongsTo
    {
        return $this->belongsTo(AlbaranCliente::class, 'albaran_id');
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoCliente::class, 'pedido_id');
    }

    public function linea(): BelongsTo
    {
        return $this->belongsTo(LineaBolsa::class, 'linea_id');
    }
}