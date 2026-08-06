<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PersonalCurso extends Pivot
{
    protected $table = 'curso_personal';

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