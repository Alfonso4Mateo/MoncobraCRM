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
        'id_rrhh',
        'tipo_personal',
        'camiseta',
        'chaqueta',
        'sudadera',
        'pantalon',
        'calzado',
        'casco',
        'guantes',
        'gafas',
        'telefono',
        'descripcion',
        'ultima_revision_medica',
        'proxima_revision_medica',
        'ultima_graduacion',
        'proxima_graduacion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'ultima_revision_medica' => 'date',
        'proxima_revision_medica' => 'date',
        'ultima_graduacion' => 'date',
        'proxima_graduacion' => 'date',
        'departamento' => 'array',
    ];

    public function proyectos(): BelongsToMany
    {
        return $this->belongsToMany(Proyecto::class, 'personal_proyecto')->withTimestamps();
    }

    public function cursos(): BelongsToMany
    {
        return $this->belongsToMany(Curso::class)
            ->using(PersonalCurso::class)
            ->withPivot(['fecha_realizacion', 'apto', 'descripcion_aptitud'])
            ->withTimestamps();
    }
}