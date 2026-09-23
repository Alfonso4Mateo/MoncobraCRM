<?php

namespace App\Models;

use App\Models\Concerns\Auditable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class EntradaStock extends Model
{
    use Auditable;
    use HasFactory;

    protected $fillable = [
        'proyecto_id',
        'numero_entrada',
        'fecha',
        'solicitante',
        'ot',
        'almacen_origen',
        'items',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'items' => 'array',
    ];

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $numero = trim((string) $this->getAttribute('numero_entrada'));
        $almacen = trim((string) $this->getAttribute('almacen_origen'));
        $ot = trim((string) $this->getAttribute('ot'));
        $solicitante = trim((string) $this->getAttribute('solicitante'));
        $partes = array_values(array_filter([
            $numero !== '' ? 'Entrada ' . $numero : null,
            $almacen !== '' ? 'Almacén: ' . $almacen : null,
            $ot !== '' ? 'OT: ' . $ot : null,
            $solicitante !== '' ? 'Solicitante: ' . $solicitante : null,
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
