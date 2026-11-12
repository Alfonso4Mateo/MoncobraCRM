<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Models\Activity;

class Epi extends Model
{
    use Auditable;

    /**
     * Campos asignables masivamente.
     * Incluye las claves foráneas para vincularlo a Inventario/Variantes.
     */
    protected $fillable = [
        'nombre',
        'descripcion',
        'categoria',
        'activo',
        'inventario_id',
        'inventario_variante_id',
        'inventario_articulo_id',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'inventario_id' => 'integer',
        'inventario_variante_id' => 'integer',
        'inventario_articulo_id' => 'integer',
    ];

    /**
     * Relación con Puestos / Perfiles Formativos.
     */
    public function puestos(): BelongsToMany
    {
        return $this->belongsToMany(Puesto::class, 'epi_puesto')
                    ->withPivot('cantidad')
                    ->withTimestamps();
    }

    /**
     * Relación directa con un ítem concreto de inventario (artículo simple).
     */
    public function inventario(): BelongsTo
    {
        return $this->belongsTo(Inventario::class, 'inventario_id');
    }

    /**
     * Relación con el grupo padre de inventario (artículo con variantes de tallas/colores).
     */
    public function inventarioVariante(): BelongsTo
    {
        return $this->belongsTo(InventarioVariante::class, 'inventario_variante_id');
    }

    public function inventarioArticulo(): BelongsTo
    {
        return $this->belongsTo(InventarioArticulo::class, 'inventario_articulo_id');
    }

    /**
     * Devuelve el total de existencias en almacén conectando con el módulo de Inventario.
     */
    public function getStockDisponibleAttribute(): int
    {
        if ($this->inventarioArticulo) {
            return (int) $this->inventarioArticulo->stock_total;
        }

        if ($this->inventarioVariante) {
            return (int) $this->inventarioVariante->stock_total;
        }

        if ($this->inventario) {
            return (int) $this->inventario->stock_actual;
        }

        return 0;
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $nombre = trim((string) $this->getAttribute('nombre'));
        $categoria = trim((string) $this->getAttribute('categoria'));
        $referencia = $nombre !== '' ? 'EPI: ' . $nombre : '';

        if ($categoria !== '') {
            $referencia .= ($referencia !== '' ? ' - ' : '') . 'Categoría: ' . $categoria;
        }

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