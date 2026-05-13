<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventarioVariante extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'inventario_variantes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'proyecto_id',
        'codigo',
        'descripcion',
        'referencia_proveedor',
        'clase_id',
        'ubicacion',
        'almacen',
        'stock_minimo',
        'nivel_critico',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'stock_minimo' => 'integer',
        'nivel_critico' => 'integer',
        'tipos_atributos' => 'array',
    ];

    /**
     * Get the proyecto that owns the inventario variante.
     */
    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }

    /**
     * Get the clase that owns the inventario variante.
     */
    public function claseRelacion(): BelongsTo
    {
        return $this->belongsTo(Clase::class, 'clase_id');
    }

    /**
     * Get all the inventory items for this variant.
     */
    public function items(): HasMany
    {
        return $this->hasMany(Inventario::class, 'inventario_variante_id');
    }

    /**
     * Get the total stock for this variant across all items.
     */
    public function getStockTotalAttribute(): int
    {
        return $this->items()->sum('stock_actual') ?? 0;
    }

    /**
     * Check if stock is critical for any variant item.
     */
    public function isStockCritical(): bool
    {
        return $this->items()
            ->whereColumn('stock_actual', '<=', 'nivel_critico')
            ->exists();
    }

    /**
     * Check if stock is low for any variant item.
     */
    public function isStockLow(): bool
    {
        return $this->items()
            ->whereColumn('stock_actual', '<=', 'stock_minimo')
            ->exists();
    }
}
