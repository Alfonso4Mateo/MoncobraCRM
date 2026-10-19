<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LineaBolsa extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'lineas_bolsa';

    public const MODO_CANTIDAD = 'cantidad';
    public const MODO_IMPORTE = 'importe';

    public const ESTADO_ACTIVA = 'activa';
    public const ESTADO_AGOTADA = 'agotada';

    protected $fillable = [
        'uid',
        'proyecto_id',
        'id_cliente',
        'bolsa_origen_id',
        'articulo',
        'descripcion',
        'medida',
        'modo_consumo',
        'cantidad',
        'precio_unitario',
        'margen',
        'importe_objetivo',
        'estado',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'margen' => 'decimal:2',
        'importe_objetivo' => 'decimal:2',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }

    public function bolsaOrigen(): BelongsTo
    {
        return $this->belongsTo(Bolsa::class, 'bolsa_origen_id');
    }

    /**
     * Partidas que aportan presupuesto a esta línea (puede haber más de
     * una, incluso de bolsas distintas si comparten tipo_bolsa).
     */
    public function partidas(): BelongsToMany
    {
        return $this->belongsToMany(
            PedidoCliente::class,
            'linea_partida',
            'linea_id',
            'pedido_id'
        )->withPivot('importe_asignado')->withTimestamps();
    }

    /**
     * Traza de consumo de esta línea, por albarán (histórico técnico).
     */
    public function consumos(): HasMany
    {
        return $this->hasMany(AlbaranPedidoLinea::class, 'linea_id');
    }

    /**
     * Reparto manual: de cada albarán que consume esta línea, cuánto se
     * imputó a cada partida concreta.
     */
    public function repartos(): HasMany
    {
        return $this->hasMany(AlbaranLineaPartida::class, 'linea_id');
    }

    public function importeAsignadoTotal(): float
    {
        return (float) $this->partidas()->sum('linea_partida.importe_asignado');
    }

    public function importeConsumidoTotal(): float
    {
        return round((float) $this->repartos()
            ->whereHas('albaran', fn ($query) => $query->whereNotIn('estado', ['cancelado', 'rechazado']))
            ->sum('importe'), 2);
    }

    public function importeDisponible(): float
    {
        return max(0, round($this->importeAsignadoTotal() - $this->importeConsumidoTotal(), 2));
    }

    public function esPorImporte(): bool
    {
        return $this->modo_consumo === self::MODO_IMPORTE;
    }
}