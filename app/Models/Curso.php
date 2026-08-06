<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Curso extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'categoria',
        'nombre',
        'descripcion',
        'meses_validez',
        'dias_aviso_previo',
    ];

    protected $casts = [
        'categoria' => 'string',
        'descripcion' => 'string',
        'meses_validez' => 'integer',
        'dias_aviso_previo' => 'integer',
    ];

    public function personal(): BelongsToMany
    {
        return $this->belongsToMany(Personal::class)
            ->using(PersonalCurso::class)
            ->withPivot(['fecha_realizacion', 'apto', 'descripcion_aptitud'])
            ->withTimestamps();
    }
}