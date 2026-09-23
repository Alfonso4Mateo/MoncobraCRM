<?php

namespace App\Models;

use App\Models\Concerns\Auditable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Activity;

class AlbaranCliente extends Model
{
    use Auditable;
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
        'total' => 'decimal:4',
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
        )->withPivot('importe_imputado')->withTimestamps();
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $numero = trim((string) $this->getAttribute('numero'));
        $titulo = trim((string) $this->getAttribute('titulo'));
        $documento = trim((string) $this->getAttribute('documento'));
        $ot = trim((string) $this->getAttribute('ot'));
        $cliente = $this->relationLoaded('cliente') ? trim((string) $this->cliente?->empresa_nombre) : '';
        $partes = array_values(array_filter([
            $numero !== '' ? 'Albarán ' . $numero : null,
            $titulo !== '' ? $titulo : null,
            $documento !== '' ? 'Documento: ' . $documento : null,
            $cliente !== '' ? 'Cliente: ' . $cliente : null,
            $ot !== '' ? 'OT: ' . $ot : null,
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
