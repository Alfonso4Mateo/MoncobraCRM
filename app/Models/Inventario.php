<?php

namespace App\Models;

use App\Models\Concerns\Auditable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Activity;

class Inventario extends Model
{
    use Auditable;
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'inventario';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'proyecto_id',
        'inventario_variante_id',
        'nombre',
        'codigo',
        'descripcion',
        'referencia_proveedor',
        'clase_id',
        'ubicacion',
        'almacen',
        'stock_actual',
        'stock_minimo',
        'nivel_critico',
        'atributos_variante',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'stock_actual' => 'integer',
        'stock_minimo' => 'integer',
        'nivel_critico' => 'integer',
        'atributos_variante' => 'array',
    ];

    /**
     * Get the proyecto that owns the inventario item.
     */
    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }

    /**
     * Get the clase relation for the inventario item.
     */
    public function claseRelacion(): BelongsTo
    {
        return $this->belongsTo(Clase::class, 'clase_id');
    }

    /**
     * Get the inventario variante for this item.
     */
    public function variante(): BelongsTo
    {
        return $this->belongsTo(InventarioVariante::class, 'inventario_variante_id');
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $nombre = trim((string) $this->getAttribute('nombre'));
        $codigo = trim((string) $this->getAttribute('codigo'));
        $referenciaProveedor = trim((string) $this->getAttribute('referencia_proveedor'));
        $almacen = trim((string) $this->getAttribute('almacen'));
        $partes = array_values(array_filter([
            $nombre !== '' ? $nombre : null,
            $codigo !== '' ? 'Código: ' . $codigo : null,
            $referenciaProveedor !== '' ? 'Ref. proveedor: ' . $referenciaProveedor : null,
            $almacen !== '' ? 'Almacén: ' . $almacen : null,
        ]));
        $referencia = $partes !== [] ? 'Inventario: ' . implode(' - ', $partes) : '';

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
