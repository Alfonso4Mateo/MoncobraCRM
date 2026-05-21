<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AlbaranCliente extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'albaranes_clientes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'documento',
        'numero',
        'fecha',
        'cliente_id',
        'proyecto_id',
        'ot',
        'pedido_cliente',
        'titulo',
        'lista_articulos',
        'total',
        'estado',
        'archivo_pdf',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha' => 'date',
        'lista_articulos' => 'array',
        'total' => 'decimal:2',
    ];

    /**
     * Get the cliente that owns the albaran.
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /**
     * Get the proyecto that owns the albaran.
     */
    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }

    /**
     * Get the orders linked through the pivot table.
     */
    public function pedidosClientes(): BelongsToMany
    {
        return $this->belongsToMany(
            PedidoCliente::class,
            'pedido_cliente_albaran_cliente',
            'albaran_cliente_id',
            'pedido_cliente_id'
        )->withTimestamps();
    }
}
