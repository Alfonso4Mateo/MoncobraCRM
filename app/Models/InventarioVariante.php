<?php

namespace App\Models;

use App\Models\Concerns\Auditable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Activity;

class InventarioVariante extends Model
{
    use Auditable;
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
        'nombre',
        'codigo',
        'descripcion',
        'referencia_proveedor',
        'clase_id',
        'ubicacion',
        'almacen',
        'stock_minimo',
        'nivel_critico',
        'tipos_atributos',
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

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $nombre = trim((string) $this->getAttribute('nombre'));
        $codigo = trim((string) $this->getAttribute('codigo'));
        $referenciaProveedor = trim((string) $this->getAttribute('referencia_proveedor'));
        $proyecto = $this->relationLoaded('proyecto') ? trim((string) $this->proyecto?->nombre) : '';
        $partes = array_values(array_filter([
            $nombre !== '' ? $nombre : null,
            $codigo !== '' ? 'Código: ' . $codigo : null,
            $referenciaProveedor !== '' ? 'Ref. proveedor: ' . $referenciaProveedor : null,
            $proyecto !== '' ? 'Proyecto: ' . $proyecto : null,
        ]));
        $referencia = $partes !== [] ? 'Variante: ' . implode(' - ', $partes) : '';

        if ($referencia === '') {
            $referencia = 'Sin referencia (ID: ' . $this->getKey() . ')';
        }

        $activity->properties = $activity->properties->merge([
            'reference_name' => $referencia,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        if ($eventName === 'deleted' && !$activity->properties->get('old')) {
            $activity->properties = $activity->properties->put('old', $this->getAttributes());
        }
    }
}
