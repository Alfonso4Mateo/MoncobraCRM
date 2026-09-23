<?php

namespace App\Models;

use App\Models\Concerns\Auditable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Models\Activity;

class EquipoInformatico extends Model
{
    use Auditable;
    use HasFactory, SoftDeletes;

    protected $table = 'equipos_informaticos';

    protected $fillable = [
        'nombre', 'codigo', 'numero_serie', 'marca', 'modelo', 'categoria', 'estado',
        'ubicacion', 'responsable', 'proveedor', 'fecha_adquisicion', 'fecha_mantenimiento',
        'descripcion', 'imagen', 'activo', 'tipo_equipo', 'sistema_operativo', 'procesador',
        'memoria_ram', 'almacenamiento', 'mac_address', 'ip_address', 'garantia_hasta',
        'soporte_hasta', 'condicion_fisica', 'etiqueta_color', 'dias_estimados_baja', 'fecha_baja',
    ];

    protected $casts = [
        'fecha_adquisicion' => 'date', 'fecha_mantenimiento' => 'date',
        'garantia_hasta' => 'date', 'soporte_hasta' => 'date', 'activo' => 'boolean',
        'fecha_baja' => 'date', 'dias_estimados_baja' => 'integer',
    ];

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
        $referencia = $partes !== [] ? 'Equipo informático: ' . implode(' - ', $partes) : '';

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