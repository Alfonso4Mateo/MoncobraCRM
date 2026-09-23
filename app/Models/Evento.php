<?php

namespace App\Models;

use App\Models\Concerns\Auditable;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class Evento extends Model
{
    use Auditable;
    protected $table = 'eventos';

    protected $fillable = ['tipo', 'titulo', 'descripcion', 'fecha', 'usuario_id', 'archivo_adjunto'];

    protected $casts = ['fecha' => 'datetime'];

    public function eventable()
    {
        return $this->morphTo();
    }

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    public function getTipoLabelAttribute(): string
    {
        return match ($this->tipo) {
            'averia' => 'Avería reportada',
            'reparacion' => 'Reparación',
            'calibracion' => 'Calibración',
            'responsable' => 'Cambio de responsable',
            'entrada' => 'Entrada en almacén',
            default => ucfirst((string) $this->tipo),
        };
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $tipo = trim((string) $this->getAttribute('tipo'));
        $titulo = trim((string) $this->getAttribute('titulo'));
        $referencia = $titulo !== '' ? $titulo : ($tipo !== '' ? $this->getTipoLabelAttribute() : '');

        if ($this->relationLoaded('eventable') && $this->eventable) {
            $relacionado = $this->eventable;
            $nombreRelacionado = trim((string) ($relacionado->nombre ?? $relacionado->titulo ?? $relacionado->numero ?? ''));
            if ($nombreRelacionado !== '') {
                $referencia .= ($referencia !== '' ? ' - ' : '') . $nombreRelacionado;
            }
        }

        $referencia = $referencia !== '' ? 'Evento: ' . $referencia : 'Sin referencia (ID: ' . $this->getKey() . ')';

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
