<?php

namespace App\Models;

use App\Models\Concerns\Auditable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\AlbaranCliente;
use App\Models\Presupuesto;
use Spatie\Activitylog\Models\Activity;

class PedidoCliente extends Model
{
    use Auditable;
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pedidos_clientes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_cliente',
        'proyecto_id',
        'numero_pedido',
        'referencia_manual',
        'fecha_pedido',
        'ot',
        'presupuesto_id',
        'albaran_id',
        'estado',
        'bolsa',
        'bolsa_texto',
        'total',
        'lista_articulos',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_pedido' => 'date',
        'bolsa' => 'boolean',
        'total' => 'decimal:4',
        'lista_articulos' => 'array',
    ];

    /**
     * Get the cliente that owns the pedido.
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    /**
     * Get the proyecto that owns the pedido.
     */
    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }

    /**
     * Get the budget associated with the order.
     */
    public function presupuesto(): BelongsTo
    {
        return $this->belongsTo(Presupuesto::class, 'presupuesto_id');
    }

    /**
     * Get the delivery note associated with the order.
     */
    public function albaran(): BelongsTo
    {
        return $this->belongsTo(AlbaranCliente::class, 'albaran_id');
    }

    /**
     * Get all delivery notes linked to this order number.
     */
    public function albaranes(): HasMany
    {
        return $this->hasMany(AlbaranCliente::class, 'pedido_cliente', 'numero_pedido');
    }

    /**
     * Get the delivery notes linked through the pivot table.
     */
    public function albaranesPivot(): BelongsToMany
    {
        return $this->belongsToMany(
            AlbaranCliente::class,
            'pedido_cliente_albaran_cliente',
            'pedido_cliente_id',
            'albaran_cliente_id'
        )->withPivot('importe_imputado')->withTimestamps();
    }

    /**
     * Get the manual billings (cuotas) associated with the order.
     */
    public function facturacionesManuales(): HasMany
    {
        return $this->hasMany(FacturacionManual::class, 'pedido_id');
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $numero = trim((string) $this->getAttribute('numero_pedido'));
        $referenciaManual = trim((string) $this->getAttribute('referencia_manual'));
        $ot = trim((string) $this->getAttribute('ot'));
        $cliente = $this->relationLoaded('cliente') ? trim((string) $this->cliente?->empresa_nombre) : '';
        $partes = array_values(array_filter([
            $numero !== '' ? 'Pedido ' . $numero : null,
            $referenciaManual !== '' ? $referenciaManual : null,
            $ot !== '' ? 'OT: ' . $ot : null,
            $cliente !== '' ? 'Cliente: ' . $cliente : null,
        ]));
        $referencia = implode(' - ', $partes);

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