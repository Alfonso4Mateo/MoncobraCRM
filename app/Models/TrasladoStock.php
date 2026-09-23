<?php

namespace App\Models;

use App\Models\Concerns\Auditable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class TrasladoStock extends Model
{
    use Auditable;
    use HasFactory;

    protected $fillable = [
        'proyecto_id',
        'numero_traslado',
        'fecha',
        'solicitante',
        'ot',
        'almacen_origen',
        'almacen_actual',
        'items',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'items' => 'array',
    ];

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $numero = trim((string) $this->getAttribute('numero_traslado'));
        $origen = trim((string) $this->getAttribute('almacen_origen'));
        $destino = trim((string) $this->getAttribute('almacen_actual'));
        $ruta = implode(' -> ', array_values(array_filter([$origen, $destino])));
        $referencia = $numero !== '' ? 'Traslado ' . $numero : '';

        if ($ruta !== '') {
            $referencia .= ($referencia !== '' ? ' - ' : '') . $ruta;
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
