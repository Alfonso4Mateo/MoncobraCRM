<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventarioArticulo extends Model
{
    use HasFactory;

    protected $table = 'inventario_articulos';

    protected $fillable = [
        'proyecto_id', 'clase_id', 'codigo', 'nombre', 'descripcion',
        'referencia_proveedor', 'tiene_variantes', 'tipos_atributos',
        'stock_minimo_global', 'nivel_critico_global', 'activo','nombre_proveedor',
    ];

    protected $casts = [
        'tiene_variantes' => 'boolean',
        'tipos_atributos' => 'array',
        'stock_minimo_global' => 'integer',
        'nivel_critico_global' => 'integer',
        'activo' => 'boolean',
    ];

    public function clase(): BelongsTo
    {
        return $this->belongsTo(Clase::class);
    }

    public function variantes(): HasMany
    {
        return $this->hasMany(InventarioVariante::class, 'articulo_id');
    }

    public function getStockTotalAttribute(): int
    {
        return (int) $this->variantes()
            ->where('activo', true)
            ->withSum('stockAlmacenes', 'cantidad')
            ->get()
            ->sum('stock_almacenes_sum_cantidad');
    }
}
