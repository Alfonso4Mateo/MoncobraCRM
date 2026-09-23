<?php

namespace App\Models;

use App\Models\Concerns\Auditable;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class Puesto extends Model
{
    use Auditable;
    // AÑADIMOS 'meses_revision_medica'
    protected $fillable = ['nombre', 'activo', 'meses_revision_medica'];

    public function cursos()
    {
        return $this->belongsToMany(Curso::class, 'curso_puesto')
                    ->withPivot('es_obligatorio')
                    ->withTimestamps();
    }

    public function personal()
    {
        return $this->belongsToMany(Personal::class, 'personal_puesto')
                    ->withTimestamps();
    }

    public function epis() 
    {
        return $this->belongsToMany(Epi::class, 'epi_puesto')
                    ->withPivot('cantidad') // <-- CRUCIAL aquí también
                    ->withTimestamps();
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