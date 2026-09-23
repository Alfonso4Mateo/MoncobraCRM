<?php

namespace App\Models;

use App\Models\Concerns\Auditable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class SalidaStock extends Model
{
    use Auditable;
    use HasFactory;

    protected $fillable = [
        'proyecto_id',
        'numero_salida',
        'fecha',
        'solicitante',
        'ot',
        'almacen_origen',
        'items',
        'documento_meta',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'items' => 'array',
        'documento_meta' => 'array',
    ];

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $numero = trim((string) $this->getAttribute('numero_salida'));
        $origen = trim((string) $this->getAttribute('almacen_origen'));
        $ot = trim((string) $this->getAttribute('ot'));
        $partes = array_values(array_filter([
            $numero !== '' ? 'Salida ' . $numero : null,
            $origen !== '' ? 'Almacén: ' . $origen : null,
            $ot !== '' ? 'OT: ' . $ot : null,
        ]));
        $referencia = implode(' - ', $partes);

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
