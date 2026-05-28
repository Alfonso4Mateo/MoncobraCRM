<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\AlbaranCliente;
use App\Models\Presupuesto;

class PedidoCliente extends Model
{
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
        'fecha_pedido',
        'ot',
        'presupuesto_id',
        'albaran_id',
        'estado',
        'bolsa',
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
        'total' => 'decimal:2',
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
        )->withTimestamps();
    }
}
