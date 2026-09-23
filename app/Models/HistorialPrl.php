<?php

namespace App\Models;

use App\Models\Concerns\Auditable;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class HistorialPrl extends Model
{
    use Auditable;
    protected $fillable = ['personal_id', 'tipo', 'archivo_path'];

    public function personal()
    {
        return $this->belongsTo(Personal::class);
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $tipo = trim((string) $this->getAttribute('tipo'));
        $archivo = trim((string) $this->getAttribute('archivo_path'));
        $personal = $this->relationLoaded('personal') ? trim((string) $this->personal?->name . ' ' . (string) $this->personal?->apellido) : '';
        $personal = trim($personal);
        $referencia = $personal !== '' ? 'PRL de ' . $personal : '';

        if ($tipo !== '') {
            $referencia .= ($referencia !== '' ? ' - ' : '') . $tipo;
        }

        if ($archivo !== '') {
            $referencia .= ($referencia !== '' ? ' - ' : '') . basename($archivo);
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