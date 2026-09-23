<?php

namespace App\Models;

use App\Models\Concerns\Auditable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Models\Activity;

class Curso extends Model
{
    use Auditable;
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

    public function personal()
    {
        return $this->belongsToMany(Personal::class, 'curso_personal')
                    ->using(PersonalCurso::class)
                    ->withPivot('fecha_realizacion', 'apto', 'descripcion_aptitud', 'archivo_diploma')
                    ->withTimestamps();
    }

    public function puestos()
    {
        return $this->belongsToMany(Puesto::class, 'curso_puesto')
                    ->withPivot('es_obligatorio')
                    ->withTimestamps();
    }
    public function proyectos()
    {
        return $this->belongsToMany(Proyecto::class, 'curso_proyecto');
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $nombre = trim((string) $this->getAttribute('nombre'));
        $categoria = trim((string) $this->getAttribute('categoria'));
        $referencia = $nombre !== '' ? 'Curso: ' . $nombre : '';

        if ($categoria !== '') {
            $referencia .= ($referencia !== '' ? ' - ' : '') . 'Categoría: ' . $categoria;
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