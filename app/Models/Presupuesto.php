<?php

namespace App\Models;

use App\Models\Concerns\Auditable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class Presupuesto extends Model
{
    use Auditable;
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'presupuestos';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'parent_id',
        'documento',
        'numero',
        'numero_correlativo',
        'fecha',
        'cliente_id',
        'proyecto_id',
        'titulo',
        'ot',
        'solicitante',
        'destinatario',
        'validez_oferta',
        'exclusiones',
        'total',
        'estado',
        'archivo_pdf',
        'lista_articulos',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha' => 'date',
        'total' => 'decimal:4',
        'lista_articulos' => 'array',
        'numero_correlativo' => 'integer',
    ];

    /**
     * Get the cliente that owns the presupuesto.
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /**
     * Get the proyecto that owns the presupuesto.
     */
    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }

    /**
     * Get the customer orders linked to this presupuesto.
     */
    public function pedidoCliente(): HasOne
    {
        return $this->hasOne(PedidoCliente::class, 'presupuesto_id');
    }

    /**
     * Compatibilidad con código anterior que esperaba el nombre plural.
     */
    public function pedidosClientes(): HasOne
    {
        return $this->pedidoCliente();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Presupuesto::class, 'parent_id');
    }

    /**
     * Obtiene todas las revisiones (hijos) creadas a partir de este presupuesto.
     */
    public function revisiones(): HasMany
    {
        return $this->hasMany(Presupuesto::class, 'parent_id');
    }

    /**
     * MEJORA 1: Configuración estricta de qué campos auditar y evitar "ruido".
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // O puedes usar logFillable() si prefieres
            ->logOnlyDirty() // Evita guardar un registro si los valores realmente no cambiaron
            ->dontLogIfAttributesChangedOnly(['updated_at', 'created_at']) // Elimina el "ruido" de fechas
            ->dontSubmitEmptyLogs(); // Si solo cambió el updated_at, cancela el registro
    }

    /**
     * MEJORA 2 y 3: Interceptar el evento antes de guardarlo en base de datos.
     * Aquí añadimos datos del cliente (IP) y limpiamos el JSON de artículos.
     */
    public function tapActivity(Activity $activity, string $eventName)
    {
        // A. Añadir IP y User-Agent al JSON de propiedades (Metadata forense)
        $activity->properties = $activity->properties->merge([
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'reference_name' => $this->activityReferenceName(),
        ]);

        // B. Limpieza inteligente del array de artículos
        $properties = $activity->properties->toArray();

        if ($eventName === 'deleted' && empty($properties['old'])) {
            $properties['old'] = $this->getAttributes();
        }

        if (isset($properties['attributes']['lista_articulos'])) {
            $properties['attributes']['lista_articulos'] = $this->limpiarArticulosLog($properties['attributes']['lista_articulos']);
        }
        
        if (isset($properties['old']['lista_articulos'])) {
            $properties['old']['lista_articulos'] = $this->limpiarArticulosLog($properties['old']['lista_articulos']);
        }

        // Reasignar las propiedades limpias al registro
        $activity->properties = collect($properties);
    }

    /**
     * Función auxiliar privada para extraer solo lo importante de los artículos.
     */
    private function limpiarArticulosLog($articulos)
    {
        // Prevenir errores si el campo viene como string en lugar de array
        if (is_string($articulos)) {
            $articulos = json_decode($articulos, true) ?? [];
        }

        if (!is_array($articulos)) {
            return [];
        }

        return collect($articulos)->map(function ($item) {
            // Extraemos solo los campos que le importan a un administrador al leer el registro
            return collect($item)->only([
                'articulo', 
                'descripcion', 
                'cantidad', 
                'precio_unitario', 
                'precio_con_margen'
            ])->toArray();
        })->toArray();
    }
}
