<?php

namespace App\Models;

use App\Models\Concerns\Auditable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Activity;

class Almacen extends Model
{
    use Auditable;
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'almacenes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'proyecto_id',
        'nombre',
        'descripcion',
    ];

    /**
     * Get the proyecto that owns the almacen.
     */
    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $nombre = trim((string) $this->getAttribute('nombre'));
        $descripcion = trim((string) $this->getAttribute('descripcion'));
        $proyecto = $this->relationLoaded('proyecto') ? trim((string) $this->proyecto?->nombre) : '';
        $referencia = $nombre !== '' ? 'Almacén: ' . $nombre : '';

        if ($descripcion !== '') {
            $referencia .= ($referencia !== '' ? ' - ' : '') . $descripcion;
        }

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