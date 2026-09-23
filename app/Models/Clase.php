<?php

namespace App\Models;

use App\Models\Concerns\Auditable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Activity;

class Clase extends Model
{
    use Auditable;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'proyecto_id',
        'nombre',
    ];

    /**
     * Get the proyecto that owns the clase.
     */
    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }

    /**
     * Get the inventario items that belong to this clase.
     */
    public function inventarios(): HasMany
    {
        return $this->hasMany(Inventario::class, 'clase_id');
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $nombre = trim((string) $this->getAttribute('nombre'));
        $proyecto = $this->relationLoaded('proyecto') ? trim((string) $this->proyecto?->nombre) : '';
        $referencia = $nombre !== '' ? 'Clase: ' . $nombre : '';

        if ($proyecto !== '') {
            $referencia .= ($referencia !== '' ? ' - ' : '') . 'Proyecto: ' . $proyecto;
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
