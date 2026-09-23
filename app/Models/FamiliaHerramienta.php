<?php

namespace App\Models;

use App\Models\Concerns\Auditable;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class FamiliaHerramienta extends Model
{
    use Auditable;
    protected $table = 'familias_herramientas';

    protected $fillable = ['nombre', 'descripcion', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function herramientas()
    {
        return $this->hasMany(HerramientaPlanta::class, 'familia_id');
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $nombre = trim((string) $this->getAttribute('nombre'));
        $descripcion = trim((string) $this->getAttribute('descripcion'));
        $referencia = $nombre !== '' ? 'Familia de herramientas: ' . $nombre : '';

        if ($referencia === '' && $descripcion !== '') {
            $referencia = 'Familia de herramientas: ' . $descripcion;
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
