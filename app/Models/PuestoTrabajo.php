<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Activity;

/**
 * Puesto de Trabajo (distinto del modelo Puesto, que es el "Perfil Formativo").
 * Se usa para la vigilancia de la salud: cada puesto define cada cuántos
 * meses corresponde repetir el reconocimiento médico.
 */
class PuestoTrabajo extends Model
{
    use Auditable;

    protected $table = 'puestos_trabajo';

    protected $fillable = [
        'proyecto_id',
        'nombre',
        'periodicidad_meses',
        'activo',
    ];

    protected $casts = [
        'proyecto_id' => 'integer',
        'periodicidad_meses' => 'integer',
        'activo' => 'boolean',
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }

    public function personal(): HasMany
    {
        return $this->hasMany(Personal::class, 'puesto_trabajo_id');
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $nombre = trim((string) $this->getAttribute('nombre'));
        $referencia = $nombre !== ''
            ? 'Puesto de trabajo: ' . $nombre
            : 'Sin referencia (ID: ' . $this->getKey() . ')';

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