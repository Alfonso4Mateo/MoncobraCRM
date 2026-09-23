<?php

namespace App\Models;

use App\Models\Concerns\Auditable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class AlertaConfiguracion extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'alertas_configuraciones';

    protected $fillable = [
        'modulo',
        'destinatarios',
        'dia_semana',
        'hora_ejecucion',
        'tipos_reporte',
    ];

    // Esto es magia pura: Laravel transformará los JSON en Arrays automáticamente
    protected $casts = [
        'destinatarios' => 'array',
        'tipos_reporte' => 'array',
    ];

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $modulo = trim((string) $this->getAttribute('modulo'));
        $dia = trim((string) $this->getAttribute('dia_semana'));
        $hora = trim((string) $this->getAttribute('hora_ejecucion'));
        $referencia = $modulo !== '' ? 'Alerta: ' . $modulo : '';

        if ($dia !== '' || $hora !== '') {
            $referencia .= ($referencia !== '' ? ' - ' : '') . trim($dia . ($dia !== '' && $hora !== '' ? ' a las ' : '') . $hora);
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