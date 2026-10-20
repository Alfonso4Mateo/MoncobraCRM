<?php

namespace App\Models;

use App\Models\Concerns\Auditable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
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
        'bolsa_id',
        'total',
        'lista_articulos',
        'archivo_pdf',
        'descripcion_general',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
     protected $casts = [
        'fecha_pedido' => 'datetime:Y-m-d H:i:s',
        'bolsa' => 'boolean',
        'total' => 'decimal:2',
        'lista_articulos' => 'array',
    ];

    protected ?Collection $albaranesEfectivosCache = null;

    public function clearAlbaranesCache(): void
    {
        $this->albaranesEfectivosCache = null;
    }

    public function refresh(array $with = []): static
    {
        $this->clearAlbaranesCache();

        return parent::refresh($with);
    }

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

    public function saldoConsumido(?int $excludeAlbaranId = null): float
    {
        return $this->importeConsumido($excludeAlbaranId);
    }

    /**
     * Get the manual billings (cuotas) associated with the order.
     */
    public function facturacionesManuales(): HasMany
    {
        return $this->hasMany(FacturacionManual::class, 'pedido_id');
    }

    /**
     * La bolsa global de la que esta partida forma parte (si aplica).
     */
    public function bolsaGlobal(): BelongsTo
    {
        return $this->belongsTo(Bolsa::class, 'bolsa_id');
    }

    /**
     * Líneas (entidad propia) que reciben presupuesto de esta partida.
     * Una línea puede aparecer aquí y también en otras partidas si su
     * saldo se reparte entre varias.
     */
    public function lineasBolsa(): BelongsToMany
    {
        return $this->belongsToMany(
            LineaBolsa::class,
            'linea_partida',
            'pedido_id',
            'linea_id'
        )->withPivot('importe_asignado')->withTimestamps();
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

    public function albaranesEfectivos(): Collection
    {
        if ($this->albaranesEfectivosCache !== null) {
            return $this->albaranesEfectivosCache;
        }

        $pivot = $this->relationLoaded('albaranesPivot')
            ? $this->albaranesPivot
            : $this->albaranesPivot()->get();

        $legacy = AlbaranCliente::query()
            ->where('proyecto_id', $this->proyecto_id)
            ->whereDoesntHave('pedidosClientes')
            ->where(function ($q) {
                $q->where('pedido_cliente', $this->numero_pedido);
                if ($this->albaran_id) {
                    $q->orWhere('id', $this->albaran_id);
                }
            })
            ->get();

        return $this->albaranesEfectivosCache = $pivot->concat($legacy)->unique('id')->values();
    }

    public function importeConsumido(?int $excluirAlbaranId = null): float
    {
        $contables = $this->albaranesEfectivos()
            ->reject(fn ($a) => in_array($a->estado, ['cancelado', 'rechazado'], true))
            ->when($excluirAlbaranId, fn ($c) => $c->reject(fn ($a) => (int) $a->id === $excluirAlbaranId))
            ->when(! $this->bolsa, fn ($c) => $c->where('estado', 'facturado'));

        return round($contables->sum(function ($a) {
            $imp = isset($a->pivot) ? (float) $a->pivot->importe_imputado : 0.0;
            return $imp > 0.001 ? $imp : (float) $a->total;   // escudo legacy
        }), 2);
    }

    public function pendienteFacturar(?int $excluirAlbaranId = null): float
    {
        return round(max(0, (float) $this->total - $this->importeConsumido($excluirAlbaranId)), 2);
    }

    public function lineasPendientes(?int $excluirAlbaranId = null): array
    {
        $pendientes = collect(is_array($this->lista_articulos) ? $this->lista_articulos : [])
            ->map(fn ($l) => (array) $l)->values()->all();

        $firma = fn (array $l) => mb_strtolower(trim((string) ($l['descripcion'] ?? '')))
            . '|' . mb_strtolower(trim((string) ($l['medida'] ?? $l['unidad'] ?? '')));

        $consumos = [];
        foreach ($this->albaranesEfectivos() as $alb) {
            if (in_array($alb->estado, ['cancelado', 'rechazado'], true) || (int) $alb->id === (int) $excluirAlbaranId) {
                continue;
            }
            foreach ((array) $alb->lista_articulos as $l) {
                $consumos[$firma((array) $l)] = ($consumos[$firma((array) $l)] ?? 0) + (float) ($l['cantidad'] ?? 0);
            }
        }

        foreach ($pendientes as &$l) {
            $k = $firma($l);
            $uso = min((float) ($l['cantidad'] ?? 0), $consumos[$k] ?? 0);
            $l['cantidad'] = round((float) $l['cantidad'] - $uso, 2);
            $consumos[$k] = ($consumos[$k] ?? 0) - $uso;
        }
        unset($l);

        return array_values(array_filter($pendientes, fn ($l) => $l['cantidad'] > 0));
    }
}