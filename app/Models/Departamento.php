<?php

namespace App\Models;

use App\Models\Concerns\Auditable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class Departamento extends Model
{
    use Auditable;
    use HasFactory;

    protected $fillable = [
        'nombre',
    ];

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $nombre = trim((string) $this->getAttribute('nombre'));
        $referencia = $nombre !== ''
            ? 'Departamento: ' . $nombre
            : 'Sin referencia (ID: ' . $this->getKey() . ')';

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