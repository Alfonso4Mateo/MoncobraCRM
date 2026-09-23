<?php

namespace App\Models;

use App\Models\Concerns\Auditable;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class Epi extends Model
{
    use Auditable;
    // Campos que permitimos rellenar masivamente (desde el formulario)
    protected $fillable = [
        'nombre',
        'descripcion',
        'categoria',
        'activo',
    ];

    /**
     * Relación: Un EPI puede pertenecer a varios Puestos.
     */
    public function puestos()
    {
        return $this->belongsToMany(Puesto::class, 'epi_puesto')
                    ->withPivot('cantidad')
                    ->withTimestamps();
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