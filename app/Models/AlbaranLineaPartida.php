<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlbaranLineaPartida extends Model
{
    use Auditable, HasFactory;

    protected $table = 'albaran_linea_partida';

    protected $fillable = [
        'albaran_id',
        'linea_id',
        'pedido_id',
        'importe',
    ];

    protected $casts = [
        'importe' => 'decimal:4',
    ];

    public function albaran(): BelongsTo
    {
        return $this->belongsTo(AlbaranCliente::class, 'albaran_id');
    }

    public function linea(): BelongsTo
    {
        return $this->belongsTo(LineaBolsa::class, 'linea_id');
    }

    public function partida(): BelongsTo
    {
        return $this->belongsTo(PedidoCliente::class, 'pedido_id');
    }
}