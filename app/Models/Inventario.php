<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventario extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'inventario';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'proyecto_id',
        'codigo',
        'descripcion',
        'referencia_proveedor',
        'clase_id',
        'ubicacion',
        'almacen',
        'stock_actual',
        'stock_minimo',
        'nivel_critico',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'stock_actual' => 'integer',
        'stock_minimo' => 'integer',
        'nivel_critico' => 'integer',
    ];

    /**
     * Get the proyecto that owns the inventario item.
     */
    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }

    /**
     * Get the clase relation for the inventario item.
     */
    public function claseRelacion(): BelongsTo
    {
        return $this->belongsTo(Clase::class, 'clase_id');
    }
}
