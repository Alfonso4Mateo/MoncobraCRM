<?php

namespace App\Models;

use App\Models\Concerns\Auditable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Activity;

class EtiquetaQr extends Model
{
    use Auditable;
    use SoftDeletes;

    protected $table = 'etiquetas_qr';
    protected $fillable = ['titulo', 'contenido_datos', 'carpeta_id', 'ruta_archivo', 'activo'];

    // Obtener la carpeta a la que pertenece este QR
    public function carpeta()
    {
        return $this->belongsTo(QrCarpeta::class, 'carpeta_id');
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $titulo = trim((string) $this->getAttribute('titulo'));
        $carpeta = $this->relationLoaded('carpeta') ? trim((string) $this->carpeta?->nombre) : '';
        $referencia = $titulo !== '' ? 'Etiqueta QR: ' . $titulo : '';

        if ($carpeta !== '') {
            $referencia .= ($referencia !== '' ? ' - ' : '') . 'Carpeta: ' . $carpeta;
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