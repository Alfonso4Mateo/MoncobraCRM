<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Activity;

class PuestoEpiDocumento extends Model
{
    use Auditable;

    protected $table = 'puesto_epi_documentos';

    protected $fillable = [
        'puesto_id',
        'codigo_iso',
        'revision',
        'fecha_emision',
        'marco_prl',
        'sello_prevencion',
        'sello_delegacion',
        'dpto_prevencion',
        'dpto_delegacion',
        'file_path',
        'epis_snapshot',
        'es_vigente',
        'user_id',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'epis_snapshot' => 'array',
        'es_vigente' => 'boolean',
    ];

    public function puesto(): BelongsTo
    {
        return $this->belongsTo(Puesto::class, 'puesto_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $puestoNombre = optional($this->puesto)->nombre ?: ('Puesto #' . $this->puesto_id);
        $referencia = "Ficha EPIs ({$this->codigo_iso} {$this->revision}) - {$puestoNombre}";

        $activity->properties = $activity->properties->merge([
            'reference_name' => $referencia,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}