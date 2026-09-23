<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

trait Auditable
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $activity->properties = $activity->properties->merge([
            'reference_name' => $this->activityReferenceName(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        if ($eventName === 'deleted' && !$activity->properties->get('old')) {
            $activity->properties = $activity->properties->put('old', $this->getAttributes());
        }
    }

    protected function activityReferenceName(): string
    {
        $modelReferences = [
            'User' => ['name', 'email'],
            'Personal' => ['name', 'apellido', 'id_rrhh'],
            'Cliente' => ['empresa_nombre', 'nombre', 'email'],
            'Presupuesto' => ['numero', 'titulo', 'documento'],
            'PedidoCliente' => ['numero_pedido', 'referencia_manual', 'ot'],
            'AlbaranCliente' => ['numero', 'titulo', 'documento'],
            'FacturacionManual' => ['concepto', 'pedido_id'],
            'Articulo' => ['numero_referencia', 'descripcion', 'articulo'],
            'Proyecto' => ['nombre', 'codigo'],
            'Curso' => ['nombre', 'categoria'],
            'Almacen' => ['nombre', 'codigo'],
            'Clase' => ['nombre', 'codigo'],
            'Departamento' => ['nombre'],
            'Puesto' => ['nombre'],
            'PuestoTrabajo' => ['nombre'],
            'FamiliaHerramienta' => ['nombre', 'descripcion'],
            'HerramientaPlanta' => ['nombre', 'referencia', 'descripcion'],
            'EquipoInformatico' => ['nombre', 'numero_serie', 'descripcion'],
            'AparatoCalibrable' => ['nombre', 'numero_serie', 'descripcion'],
            'Documento' => ['titulo', 'nombre', 'archivo'],
            'Evento' => ['titulo', 'tipo', 'descripcion'],
            'EtiquetaQr' => ['titulo', 'contenido_datos'],
            'QrCarpeta' => ['nombre'],
            'Epi' => ['nombre', 'descripcion'],
            'EntregaEpi' => ['nombre', 'descripcion'],
            'Inventario' => ['nombre', 'descripcion', 'codigo'],
            'InventarioVariante' => ['nombre', 'descripcion', 'codigo'],
            'EntradaStock' => ['numero', 'referencia', 'descripcion'],
            'SalidaStock' => ['numero', 'referencia', 'descripcion'],
            'TrasladoStock' => ['numero', 'referencia', 'descripcion'],
            'CentroCoste' => ['codigo', 'descripcion'],
            'HistorialPrl' => ['tipo', 'archivo_path'],
            'Setting' => ['key', 'value'],
        ];

        $referenceFields = $modelReferences[class_basename($this)] ?? [
            'name', 'nombre', 'titulo', 'descripcion', 'codigo', 'numero', 'referencia',
        ];

        if (class_basename($this) === 'Personal') {
            $name = trim(implode(' ', array_filter([
                $this->getAttribute('name'),
                $this->getAttribute('apellido'),
            ])));

            if ($name !== '') {
                return $name;
            }
        }

        foreach ($referenceFields as $field) {
            $value = $this->getAttribute($field);

            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return 'Sin referencia (ID: ' . $this->getKey() . ')';
    }
}
