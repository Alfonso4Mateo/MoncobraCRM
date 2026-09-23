<?php

namespace App\Models;

use App\Models\Concerns\Auditable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class Setting extends Model
{
    use Auditable;
    use HasFactory;

    protected $fillable = ['key', 'value'];

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $clave = trim((string) $this->getAttribute('key'));
        $referencia = $clave !== ''
            ? 'Configuración: ' . $clave
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