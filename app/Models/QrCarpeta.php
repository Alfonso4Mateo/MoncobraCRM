<?php

namespace App\Models;

use App\Models\Concerns\Auditable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Activity;

class QrCarpeta extends Model
{
    use Auditable;
    use SoftDeletes;

    protected $table = 'qr_carpetas';
    protected $fillable = ['nombre', 'parent_id'];

    // Obtener la carpeta padre
    public function padre()
    {
        return $this->belongsTo(QrCarpeta::class, 'parent_id');
    }

    // Obtener el primer nivel de subcarpetas
    public function subcarpetas()
    {
        return $this->hasMany(QrCarpeta::class, 'parent_id');
    }

    // Obtener TODO el árbol de subcarpetas (recursivo)
    public function subcarpetasRecursivas()
    {
        return $this->subcarpetas()->with('subcarpetasRecursivas');
    }

    // Obtener los códigos QR que están dentro de esta carpeta
    public function qrs()
    {
        return $this->hasMany(EtiquetaQr::class, 'carpeta_id');
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $nombre = trim((string) $this->getAttribute('nombre'));
        $referencia = $nombre !== '' ? 'Carpeta QR: ' . $nombre : '';

        if ($this->relationLoaded('padre') && $this->padre) {
            $padre = trim((string) $this->padre->getAttribute('nombre'));
            if ($padre !== '') {
                $referencia .= ($referencia !== '' ? ' - ' : '') . 'Padre: ' . $padre;
            }
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