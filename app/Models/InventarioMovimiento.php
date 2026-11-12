<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class InventarioMovimiento extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $table = 'inventario_movimientos';

    protected $fillable = [
        'proyecto_id', 'numero_documento', 'documento_referencia', 'tipo', 'almacen_origen_id',
        'almacen_destino_id', 'personal_id', 'ot', 'motivo',
        'observaciones', 'estado', 'created_by',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'proyecto_id',
                'tipo',
                'numero_documento',
                'estado',
                'almacen_origen_id',
                'almacen_destino_id',
                'personal_id',
                'ot',
                'documento_referencia',
                'observaciones',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('inventario')
            ->setDescriptionForEvent(fn (string $eventName) => "Movimiento de inventario {$eventName}");
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        // Cargar las relaciones necesarias para el resumen
        $this->loadMissing(['lineas.variante.articulo.clase']);

        $totalUnidades = (int) $this->lineas->sum('cantidad');
        $clases = $this->obtenerClasesInvolucradasAuditoria();
        $articulos = $this->obtenerResumenLineasAuditoria();

        // Recuperar los atributos que Spatie va a registrar por defecto
        $attributes = $activity->properties->get('attributes', []);

        // Inyectar los campos personalizados del desglose de líneas
        $attributes['clases'] = $clases !== '' ? $clases : 'General';
        $attributes['total_unidades'] = $totalUnidades . ' uds.';
        $attributes['articulos'] = $articulos !== '' ? $articulos : 'Sin artículos';

        // Fusionar todo en las propiedades del log
        $activity->properties = $activity->properties->merge([
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'reference_name' => $this->getAttribute('numero_documento')
                ?: 'Movimiento de inventario (ID: ' . $this->getKey() . ')',
            'attributes' => $attributes,
        ]);
    }

    public function obtenerResumenLineasAuditoria(): string
    {
        $this->loadMissing(['lineas.variante.articulo.clase']);

        return $this->lineas->map(function ($linea) {
            $variante = $linea->variante;
            $articulo = $variante?->articulo;
            $clase = $articulo?->clase?->nombre ?? 'Sin clase';
            $sku = $variante?->sku ?? $variante?->codigo ?? 'S/C';
            $nombre = $variante?->nombre ?: ($articulo?->nombre ?: 'Artículo');

            return "[{$clase}] {$sku} - {$nombre} (x{$linea->cantidad} uds.)";
        })->implode(' | ');
    }

    public function obtenerClasesInvolucradasAuditoria(): string
    {
        $this->loadMissing(['lineas.variante.articulo.clase']);

        return $this->lineas->map(fn ($l) => $l->variante?->articulo?->clase?->nombre ?? 'General')
            ->unique()
            ->implode(', ');
    }

    public function lineas(): HasMany
    {
        return $this->hasMany(InventarioMovimientoLinea::class, 'movimiento_id');
    }

    public function almacenOrigen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_origen_id');
    }

    public function almacenDestino(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_destino_id');
    }

    public function personal(): BelongsTo
    {
        return $this->belongsTo(Personal::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}