<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\AlbaranLineaPartida;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Bolsa extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'bolsas';

    protected $fillable = [
        'id_cliente',
        'proyecto_id',
        'numero',
        'tipo_bolsa',
        'total',
        'fecha',
    ];

    protected $casts = [
        'fecha' => 'date',
        'total' => 'decimal:4',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }

    /**
     * Partidas (pedidos bolsa) liberadas dentro de esta bolsa global.
     */
    public function partidas(): HasMany
    {
        return $this->hasMany(PedidoCliente::class, 'bolsa_id');
    }

    /**
     * Líneas que nacieron originalmente en esta bolsa (solo informativo:
     * una línea puede seguir consumiéndose aunque esta bolsa esté agotada).
     */
    public function lineasOrigen(): HasMany
    {
        return $this->hasMany(LineaBolsa::class, 'bolsa_origen_id');
    }

    /**
     * Otras bolsas con las que esta puede compartir sobrante: mismo
     * cliente y mismo tipo_bolsa (grupo de fungibilidad).
     */
    public function bolsasFungibles()
    {
        return static::query()
            ->where('id_cliente', $this->id_cliente)
            ->where('tipo_bolsa', $this->tipo_bolsa)
            ->where('id', '!=', $this->id);
    }

    /**
     * Calcula cuánto se ha descontado REALMENTE de esta bolsa: solo lo que ya
     * se ha facturado de verdad (vía albaranes) contra las líneas de sus
     * partidas, no la mera suma de los "total" declarados al crearlas.
     *
     * Antes esto sumaba $this->partidas()->sum('total'), así que una partida
     * descontaba saldo de la bolsa en el momento de crearse, aunque no
     * tuviera ningún albarán todavía. Ahora una partida sin albaranes no
     * descuenta nada: el saldo solo baja cuando hay facturación real.
     */
    public function totalAcumulado(): float
    {
        $partidaIds = $this->partidas()->pluck('id');
        if ($partidaIds->isEmpty()) {
            return 0.0;
        }

        return round(
            (float) AlbaranLineaPartida::whereIn('pedido_id', $partidaIds)
                ->whereHas('albaran', fn ($query) => $query->where('estado', '!=', 'cancelado'))
                ->sum('importe'),
            4
        );
    }

    /**
     * Calcula el dinero libre (Solo si la bolsa tiene un tope mayor a 0)
     */
    public function saldoDisponible(): float
    {
        $tope = (float) $this->total;
        
        // Si el tope es 0, es una Bolsa Abierta (infinita), no hay concepto de "disponible"
        if ($tope <= 0) {
            return 0.0; 
        }

        return max(0, round($tope - $this->totalAcumulado(), 4));
    }
}