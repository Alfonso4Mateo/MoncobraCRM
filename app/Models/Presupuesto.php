<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Presupuesto extends Model
{
    use HasFactory;

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
        'documento',
        'numero',
        'numero_correlativo',
        'fecha',
        'cliente_id',
        'proyecto_id',
        'titulo',
        'ot',
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
        'total' => 'decimal:2',
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
    public function pedidosClientes(): HasMany
    {
        return $this->hasMany(PedidoCliente::class, 'presupuesto_id');
    }
}
