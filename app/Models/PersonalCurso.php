<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class PersonalCurso extends Pivot
{
    use Auditable;

    protected $table = 'curso_personal';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $casts = [
        'fecha_realizacion' => 'date',
        'apto' => 'boolean',
    ];

    public function personal(): BelongsTo
    {
        return $this->belongsTo(Personal::class, 'personal_id');
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class, 'curso_id');
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $persona = $this->relationLoaded('personal') ? trim((string) $this->personal?->name . ' ' . (string) $this->personal?->apellido) : '';
        $curso = $this->relationLoaded('curso') ? trim((string) $this->curso?->nombre) : '';
        $persona = trim($persona);
        $cursoId = $this->getAttribute('curso_id');
        $personalId = $this->getAttribute('personal_id');
        $referencia = $curso !== '' ? 'Curso: ' . $curso : ($cursoId ? 'Curso ID: ' . $cursoId : '');

        if ($persona !== '') {
            $referencia .= ($referencia !== '' ? ' - ' : '') . 'Personal: ' . $persona;
        } elseif ($personalId) {
            $referencia .= ($referencia !== '' ? ' - ' : '') . 'Personal ID: ' . $personalId;
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

    public function getFechaCaducidadAttribute(): ?Carbon
    {
        if (!$this->fecha_realizacion || !$this->curso || $this->curso->meses_validez === null) {
            return null;
        }

        return $this->fecha_realizacion->copy()->addMonths((int) $this->curso->meses_validez);
    }

    public function getEstaEnAvisoAttribute(): bool
    {
        $fechaCaducidad = $this->fecha_caducidad;

        if (!$fechaCaducidad) {
            return false;
        }

        $diasAviso = (int) ($this->curso->dias_aviso_previo ?? 30);

        return now()->greaterThanOrEqualTo($fechaCaducidad->copy()->subDays($diasAviso));
    }

    public function getEstaCaducadoAttribute(): bool
    {
        $fechaCaducidad = $this->fecha_caducidad;

        return $fechaCaducidad ? now()->greaterThan($fechaCaducidad) : false;
    }
}