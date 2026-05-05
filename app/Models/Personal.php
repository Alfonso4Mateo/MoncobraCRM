<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Personal extends Model
{
    use HasFactory;

    protected $table = 'personal';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'apellido',
        'dni_nie',
        'departamento',
        'tipo_personal',
        'camiseta',
        'chaqueta',
        'sudadera',
        'pantalon',
        'calzado',
        'casco',
        'guantes',
        'telefono',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function proyectos(): BelongsToMany
    {
        return $this->belongsToMany(Proyecto::class, 'personal_proyecto')->withTimestamps();
    }
}
