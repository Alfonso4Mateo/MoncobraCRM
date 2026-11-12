<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventarioStockAlmacen extends Model
{
    use HasFactory;

    protected $table = 'inventario_stock_almacen';

    protected $fillable = ['variante_id', 'almacen_id', 'cantidad', 'ubicacion_estanteria'];

    protected $casts = ['cantidad' => 'integer'];

    public function variante(): BelongsTo
    {
        return $this->belongsTo(InventarioVariante::class, 'variante_id');
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }
}
