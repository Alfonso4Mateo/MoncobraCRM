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
        'sin_tallas',
        'telefono',
        'correo',
        'descripcion',
        'ultima_revision_medica',
        'proxima_revision_medica',
        'ultima_graduacion',
        'proxima_graduacion',
        'reconocido_en',
        'graduado_en',
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

    public function cursos()
    {
        return $this->belongsToMany(Curso::class, 'curso_personal')
                    ->withPivot('fecha_realizacion', 'apto', 'descripcion_aptitud', 'archivo_diploma')
                    ->withTimestamps();
    }

    public function puestos()
    {
        return $this->belongsToMany(Puesto::class, 'personal_puesto')
                    ->withTimestamps();
    }
}