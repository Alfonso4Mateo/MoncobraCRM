<?php

namespace App\Models;

use App\Models\Concerns\Auditable;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class CentroCoste extends Model
{
    use Auditable;
    protected $table = 'centros_costes';
    protected $fillable = ['codigo', 'descripcion'];

    // Este accessor nos permite llamar a $cc->etiqueta_completa
    public function getEtiquetaCompletaAttribute()
    {
        return "{$this->codigo} ({$this->descripcion})";
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $codigo = trim((string) $this->getAttribute('codigo'));
        $descripcion = trim((string) $this->getAttribute('descripcion'));
        $referencia = $codigo !== '' ? 'Centro de coste: ' . $codigo : '';

        if ($descripcion !== '') {
            $referencia .= ($referencia !== '' ? ' - ' : '') . $descripcion;
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