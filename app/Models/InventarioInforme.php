<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventarioInforme extends Model
{
    protected $table = 'inventario_informes';

    protected $fillable = [
        'proyecto_id',
        'user_id',
        'solicitante_nombre',
        'tipo_informe',
        'filtros',
        'snapshot_hash',
        'archivo_path',
        'archivo_nombre',
        'total_filas',
        'datos_preview',
    ];

    protected $casts = [
        'filtros' => 'array',
        'datos_preview' => 'array',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}