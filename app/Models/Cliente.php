<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'clientes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'proyecto_id',
        'empresa_nombre',
        'cif_nif',
        'direccion',
        'localidad',
        'provincia',
        'codigo_postal',
        'telefono',
        'email',
        'persona_contacto',
        'favorito',
    ];

    /**
     * Casts for model attributes.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'favorito' => 'boolean',
    ];

    /**
     * Get the proyecto that owns the cliente.
     */
    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }

    /**
     * Get all albaranes for this cliente.
     */
    public function albaranes(): HasMany
    {
        return $this->hasMany(AlbaranCliente::class, 'cliente_id');
    }

    /**
     * Get all presupuestos for this cliente.
     */
    public function presupuestos(): HasMany
    {
        return $this->hasMany(Presupuesto::class, 'cliente_id');
    }

    /**
     * Get all pedidos clientes for this cliente.
     */
    public function pedidosClientes(): HasMany
    {
        return $this->hasMany(PedidoCliente::class, 'id_cliente');
    }
}
