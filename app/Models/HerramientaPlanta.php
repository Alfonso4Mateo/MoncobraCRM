<?php

namespace App\Models;

use App\Models\Concerns\Auditable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Models\Activity;

class HerramientaPlanta extends Model
{
    use Auditable;
    use HasFactory, SoftDeletes;

    protected $table = 'herramientas_planta';

    protected $fillable = [
        'id_interno', 'nombre', 'codigo', 'numero_serie', 'marca', 'modelo', 'categoria',
        'estado', 'ubicacion', 'ubicacion_2', 'responsable', 'proveedor', 'id_proveedor',
        'almacen', 'stock', 'unidad_medida', 'fecha_registro', 'fecha_adquisicion',
        'fecha_mantenimiento', 'descripcion', 'imagen', 'familia_id', 'familia_energetica',
        'potencia', 'tension', 'combustible', 'criticidad', 'horas_uso', 'bloqueado',
        'motivo_bloqueo', 'garantia_hasta','fecha_inspeccion' , 'dias_estimados_baja', 'fecha_baja',
    ];

    protected $casts = [
        'stock' => 'decimal:3', 'fecha_registro' => 'date', 'fecha_adquisicion' => 'date',
        'fecha_mantenimiento' => 'date', 'garantia_hasta' => 'date', 'horas_uso' => 'integer', 'bloqueado' => 'boolean','fecha_inspeccion' => 'date',
        'fecha_baja' => 'date', 'dias_estimados_baja' => 'integer',
    ];

    public function familia()
    {
        return $this->belongsTo(FamiliaHerramienta::class, 'familia_id');
    }

    public function eventos(): MorphMany
    {
        return $this->morphMany(Evento::class, 'eventable')->latest('fecha');
    }

    public function getEstadoLabelAttribute(): string
    {
        return match ($this->estado) {
            'operativo' => 'Operativo / Disponible',
            'asignado' => 'Asignado',
            'mantenimiento' => 'En mantenimiento',
            'reparacion' => 'En reparación',
            default => ucfirst((string) $this->estado),
        };
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $idInterno = trim((string) $this->getAttribute('id_interno'));
        $nombre = trim((string) $this->getAttribute('nombre'));
        $codigo = trim((string) $this->getAttribute('codigo'));
        $serie = trim((string) $this->getAttribute('numero_serie'));
        $familia = $this->relationLoaded('familia') ? trim((string) $this->familia?->nombre) : '';
        $partes = array_values(array_filter([
            $idInterno !== '' ? 'ID: ' . $idInterno : null,
            $nombre !== '' ? $nombre : null,
            $codigo !== '' ? 'Código: ' . $codigo : null,
            $serie !== '' ? 'Serie: ' . $serie : null,
            $familia !== '' ? 'Familia: ' . $familia : null,
        ]));
        $referencia = $partes !== [] ? 'Herramienta: ' . implode(' - ', $partes) : '';

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