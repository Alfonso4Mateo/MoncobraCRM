<?php

namespace App\Models;

use App\Models\Concerns\Auditable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Activity;

class Documento extends Model
{
    use Auditable;
    use HasFactory;
    use SoftDeletes;

    protected $table = 'documentos';

    protected $fillable = [
        'tipo',
        'numero_documento',
        'fecha_documento',
        'ot',
        'cliente',
        'original_name',
        'stored_name',
        'path',
        'mime_type',
        'size',
        'meta',
        'user_id',
        'proyecto_id',
    ];

    protected $casts = [
        'fecha_documento' => 'date',
        'size' => 'integer',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $nombreArchivo = trim((string) $this->getAttribute('original_name'));
        $numero = trim((string) $this->getAttribute('numero_documento'));
        $cliente = trim((string) $this->getAttribute('cliente'));
        $partes = array_values(array_filter([
            $nombreArchivo !== '' ? $nombreArchivo : null,
            $numero !== '' ? 'Documento: ' . $numero : null,
            $cliente !== '' ? 'Cliente: ' . $cliente : null,
        ]));
        $referencia = implode(' - ', $partes);

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