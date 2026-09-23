<?php

namespace App\Models;

use App\Models\Concerns\Auditable;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class FacturacionManual extends Model
{
    use Auditable;
    // 1. Declaramos la tabla (opcional, pero buena práctica por la 's' del inglés)
    protected $table = 'facturacion_manuals';

    // 2. Permitimos la asignación masiva de estos campos
    protected $fillable = [
        'pedido_id',
        'importe',
        'concepto'
    ];

    // 3. Relación: Una facturación manual PERTENECE A un Pedido Cliente
    public function pedidoCliente()
    {
        return $this->belongsTo(PedidoCliente::class, 'pedido_id');
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $concepto = trim((string) $this->getAttribute('concepto'));
        $pedido = $this->relationLoaded('pedidoCliente') ? trim((string) $this->pedidoCliente?->numero_pedido) : '';
        $referencia = $concepto !== '' ? 'Facturación manual: ' . $concepto : '';

        if ($pedido !== '') {
            $referencia .= ($referencia !== '' ? ' - ' : '') . 'Pedido: ' . $pedido;
        }

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