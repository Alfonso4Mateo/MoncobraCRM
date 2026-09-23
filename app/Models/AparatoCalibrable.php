<?php

namespace App\Models;

use App\Models\Concerns\Auditable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Models\Activity;

class AparatoCalibrable extends Model
{
    use Auditable;
    use HasFactory, SoftDeletes;

    protected $table = 'aparatos_calibrables';

    protected $fillable = [
        'nombre',
        'codigo',
        'numero_serie',
        'marca',
        'modelo',
        'categoria',
        'estado',
        'ubicacion',
        'responsable',
        'proveedor',
        'imagen',
        'instrumento',
        'rango_medida',
        'clase_exactitud',
        'tolerancia',
        'incertidumbre',
        'norma_calibracion',
        'laboratorio',
        'certificado',
        'fecha_certificado',
        'laboratorio_externo',
        'estado_certificado',
        'proxima_calibracion',
        'ultima_calibracion',
        'certificado_pdf',
        'dias_estimados_baja',
        'fecha_baja',
    ];

    protected $casts = [
        'fecha_certificado' => 'date',
        'proxima_calibracion' => 'date',
        'ultima_calibracion' => 'date',
        'laboratorio_externo' => 'boolean',
        'fecha_baja' => 'date',
        'dias_estimados_baja' => 'integer',
    ];

    public function eventos(): MorphMany
    {
        return $this->morphMany(Evento::class, 'eventable')->latest('fecha');
    }

    public function getEstadoLabelAttribute(): string
    {
        return match ($this->estado) {
            'operativo' => 'Calibracion valida',
            'asignado' => 'Asignado',
            'calibracion' => 'En calibracion',
            'vencido' => 'Calibracion vencida',
            'aislado' => 'Aislado',
            default => ucfirst((string) $this->estado),
        };
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $nombre = trim((string) $this->getAttribute('nombre'));
        $codigo = trim((string) $this->getAttribute('codigo'));
        $serie = trim((string) $this->getAttribute('numero_serie'));
        $marca = trim((string) $this->getAttribute('marca'));
        $modelo = trim((string) $this->getAttribute('modelo'));
        $partes = array_values(array_filter([
            $nombre !== '' ? $nombre : null,
            $codigo !== '' ? 'Código: ' . $codigo : null,
            $serie !== '' ? 'Serie: ' . $serie : null,
            ($marca . ' ' . $modelo) !== ' ' ? trim($marca . ' ' . $modelo) : null,
        ]));
        $referencia = $partes !== [] ? 'Aparato calibrable: ' . implode(' - ', $partes) : '';

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