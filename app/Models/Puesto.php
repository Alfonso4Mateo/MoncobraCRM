<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Models\Activity;

class Puesto extends Model
{
    use Auditable;

    protected $fillable = [
        'nombre',
        'activo',
        'meses_revision_medica',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'meses_revision_medica' => 'integer',
    ];

    public function cursos(): BelongsToMany
    {
        return $this->belongsToMany(Curso::class, 'curso_puesto')
                    ->withPivot('es_obligatorio')
                    ->withTimestamps();
    }

    public function personal(): BelongsToMany
    {
        return $this->belongsToMany(Personal::class, 'personal_puesto')
                    ->withTimestamps();
    }

    /**
     * Relación con EPIs activos (por defecto solo devuelve dotación vigente).
     * Soluciona la inclusión de EPIs dados de baja en contadores y vistas.
     */
    public function epis(): BelongsToMany
    {
        return $this->belongsToMany(Epi::class, 'epi_puesto')
                    ->withPivot('cantidad')
                    ->where('epis.activo', true)
                    ->withTimestamps();
    }

    /**
     * Relación con todos los EPIs sin filtrar por estado activo.
     * Utilizada en sincronizaciones para no eliminar registros de forma destructiva.
     */
    public function todosLosEpis(): BelongsToMany
    {
        return $this->belongsToMany(Epi::class, 'epi_puesto')
                    ->withPivot('cantidad')
                    ->withTimestamps();
    }

    public function documentosEpis()
    {
        return $this->hasMany(PuestoEpiDocumento::class, 'puesto_id')
                    ->orderByDesc('es_vigente')
                    ->orderByDesc('id');
    }

    public function documentoEpiVigente()
    {
        return $this->hasOne(PuestoEpiDocumento::class, 'puesto_id')
                    ->where('es_vigente', true)
                    ->latestOfMany();
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $nombre = trim((string) $this->getAttribute('nombre'));
        $referencia = $nombre !== ''
            ? 'Perfil formativo: ' . $nombre
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