<?php

namespace App\Models;

use App\Models\Concerns\Auditable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Activity;

class Articulo extends Model
{
    use Auditable;
    use HasFactory;

    protected $attributes = [
        'facturado' => false,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'proyecto_id',
        'numero_referencia',
        'descripcion',
        'cantidad',
        'medida',
        'precio_unitario',
        'margen',
        'total',
        'facturado',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'cantidad' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'margen' => 'decimal:2',
        'total' => 'decimal:2',
        'facturado' => 'boolean',
    ];

    /**
     * Get the proyecto that owns the articulo.
     */
    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $numeroReferencia = trim((string) $this->getAttribute('numero_referencia'));
        $descripcion = trim((string) $this->getAttribute('descripcion'));
        $proyecto = $this->relationLoaded('proyecto') ? trim((string) $this->proyecto?->nombre) : '';
        $partes = array_values(array_filter([
            $numeroReferencia !== '' ? 'Referencia: ' . $numeroReferencia : null,
            $descripcion !== '' ? $descripcion : null,
            $proyecto !== '' ? 'Proyecto: ' . $proyecto : null,
        ]));
        $referencia = $partes !== [] ? 'Artículo: ' . implode(' - ', $partes) : '';

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
