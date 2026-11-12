<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Activity;

class Personal extends Model
{
    use Auditable;
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
        'puesto',
        'puesto_trabajo_id',
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
        'bolsa_fod',
        'sin_tallas',
        'telefono',
        'correo',
        'descripcion',
        'ultima_revision_medica',
        'proxima_revision_medica',
        'revision_medica_manual',
        'ultima_graduacion',
        'proxima_graduacion',
        'reconocido_en',
        'graduado_en',
        'activo',
        'prl_revisado',
        'fecha_reactivacion',
        // CORRECCIÓN FASE 3: Permite guardar físicamente los documentos subidos
        'documento_riesgos',
        'documento_epi',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'ultima_revision_medica' => 'date',
        'proxima_revision_medica' => 'date',
        'revision_medica_manual' => 'boolean',
        'ultima_graduacion' => 'date',
        'proxima_graduacion' => 'date',
        'departamento' => 'array',
        'puesto' => 'string',
        'prl_revisado' => 'boolean',
        'fecha_reactivacion' => 'date',
    ];

    public function proyectos(): BelongsToMany
    {
        return $this->belongsToMany(Proyecto::class, 'personal_proyecto')->withTimestamps();
    }

    public function cursos(): BelongsToMany
    {
        return $this->belongsToMany(Curso::class, 'curso_personal')
                    ->using(PersonalCurso::class)
                    ->withPivot('fecha_realizacion', 'apto', 'descripcion_aptitud', 'archivo_diploma')
                    ->withTimestamps();
    }

    public function entregasEpis(): HasMany
    {
        return $this->hasMany(EntregaEpi::class, 'personal_id');
    }

    public function salidasStock(): HasMany
    {
        return $this->hasMany(SalidaStock::class, 'personal_id');
    }

    public function equiposInformaticos(): HasMany
    {
        return $this->hasMany(EquipoInformatico::class, 'personal_id');
    }

    public function herramientasPlanta(): HasMany
    {
        return $this->hasMany(HerramientaPlanta::class, 'personal_id');
    }

    public function aparatosCalibrables(): HasMany
    {
        return $this->hasMany(AparatoCalibrable::class, 'personal_id');
    }

    public function historialPrl(): HasMany
    {
        return $this->hasMany(HistorialPrl::class)->orderByDesc('created_at');
    }

    public function puestos(): BelongsToMany
    {
        return $this->belongsToMany(Puesto::class, 'personal_puesto', 'personal_id', 'puesto_id')
                    ->withTimestamps();
    }

    public function puestoTrabajo(): BelongsTo
    {
        return $this->belongsTo(PuestoTrabajo::class, 'puesto_trabajo_id');
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $nombre = trim(implode(' ', array_filter([
            $this->getAttribute('name'),
            $this->getAttribute('apellido'),
        ])));
        $idRrhh = trim((string) $this->getAttribute('id_rrhh'));
        $referencia = $nombre !== '' ? 'Personal: ' . $nombre : '';

        if ($idRrhh !== '') {
            $referencia .= ($referencia !== '' ? ' - ' : '') . 'RRHH: ' . $idRrhh;
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