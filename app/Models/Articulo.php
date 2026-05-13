<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Articulo extends Model
{
    use HasFactory;

    protected $attributes = [
        'facturado' => false,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'proyecto_id',
        'numero_referencia',
        'descripcion',
        'cantidad',
        'medida',
        'precio_unitario',
        'margen',
        'total',
        'facturado',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'cantidad' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'margen' => 'decimal:2',
        'total' => 'decimal:2',
        'facturado' => 'boolean',
    ];

    /**
     * Get the proyecto that owns the articulo.
     */
    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }
}
