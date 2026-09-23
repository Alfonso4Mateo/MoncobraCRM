<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Carbon\Carbon;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class EntregaEpi extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'entregas_epis';

    protected $fillable = [
        'personal_id',
        'fecha_entrega',
        'tipo_documento',
        'observaciones',
        'archivo_path',
    ];

    /**
     * Relación inversa: Una entrega de EPI pertenece a un único trabajador.
     */
    public function personal()
    {
        return $this->belongsTo(Personal::class, 'personal_id');
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $tipo = trim((string) $this->getAttribute('tipo_documento'));
        $fecha = $this->getAttribute('fecha_entrega');
        $archivo = trim((string) $this->getAttribute('archivo_path'));
        $personal = $this->relationLoaded('personal') ? trim((string) $this->personal?->name . ' ' . (string) $this->personal?->apellido) : '';
        $personal = trim($personal);
        $partes = array_values(array_filter([
            $personal !== '' ? 'Personal: ' . $personal : null,
            $tipo !== '' ? $tipo : null,
            $fecha ? 'Fecha: ' . Carbon::parse($fecha)->format('d/m/Y') : null,
            $archivo !== '' ? basename($archivo) : null,
        ]));
        $referencia = $partes !== [] ? 'Entrega EPI: ' . implode(' - ', $partes) : '';

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