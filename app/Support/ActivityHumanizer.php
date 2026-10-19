<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;
use Throwable;

class ActivityHumanizer
{
    private const RELACIONES = [
        'proyecto_id' => ['App\Models\Proyecto', 'nombre'],
        'id_cliente' => ['App\Models\Cliente', 'empresa_nombre'],
        'cliente_id' => ['App\Models\Cliente', 'empresa_nombre'],
        'presupuesto_id' => ['App\Models\Presupuesto', 'numero'],
        'pedido_id' => ['App\Models\PedidoCliente', 'numero_pedido'],
        'pedido_cliente_id' => ['App\Models\PedidoCliente', 'numero_pedido'],
        'albaran_id' => ['App\Models\AlbaranCliente', 'numero'],
        'albaran_cliente_id' => ['App\Models\AlbaranCliente', 'numero'],
        'bolsa_id' => ['App\Models\Bolsa', 'numero'],
        'bolsa_origen_id' => ['App\Models\Bolsa', 'numero'],
        'linea_id' => ['App\Models\LineaBolsa', 'descripcion'],
        'linea_bolsa_id' => ['App\Models\LineaBolsa', 'descripcion'],
        'articulo_id' => ['App\Models\Articulo', 'descripcion'],
        'user_id' => ['App\Models\User', 'name'],
        'usuario_id' => ['App\Models\User', 'name'],
        'created_by' => ['App\Models\User', 'name'],
        'updated_by' => ['App\Models\User', 'name'],
        'responsable_id' => ['App\Models\User', 'name'],
        'personal_id' => ['App\Models\Personal', ['nombre', 'apellidos']],
        'puesto_trabajo_id' => ['App\Models\PuestoTrabajo', 'nombre'],
        'puesto_id' => ['App\Models\PuestoTrabajo', 'nombre'],
        'departamento_id' => ['App\Models\Departamento', 'nombre'],
        'ubicacion_id' => ['App\Models\Ubicacion', 'nombre'],
        'familia_id' => ['App\Models\FamiliaHerramienta', 'nombre'],
        'almacen_id' => ['App\Models\Almacen', 'nombre'],
        'almacen_origen_id' => ['App\Models\Almacen', 'nombre'],
        'almacen_destino_id' => ['App\Models\Almacen', 'nombre'],
        'categoria_id' => ['App\Models\Categoria', 'nombre'],
        'qr_carpeta_id' => ['App\Models\QrCarpeta', 'nombre'],
        'carpeta_id' => ['App\Models\QrCarpeta', 'nombre'],
        'curso_id' => ['App\Models\Curso', 'nombre'],
    ];

    private const FIELD_LABELS = [
        'id_cliente' => 'Cliente',
        'cliente_id' => 'Cliente',
        'proyecto_id' => 'Proyecto / Delegación',
        'presupuesto_id' => 'Presupuesto origen',
        'pedido_id' => 'Pedido',
        'pedido_cliente_id' => 'Pedido',
        'numero_pedido' => 'Nº de pedido',
        'referencia_manual' => 'Ref. pedido cliente',
        'albaran_id' => 'Albarán',
        'albaran_cliente_id' => 'Albarán',
        'bolsa_id' => 'Bolsa global',
        'bolsa_origen_id' => 'Bolsa global',
        'bolsa' => 'Modo bolsa',
        'bolsa_texto' => 'Texto de bolsa',
        'linea_id' => 'Línea de bolsa',
        'linea_bolsa_id' => 'Línea de bolsa',
        'ot' => 'Centro de Coste (CC)',
        'fecha_pedido' => 'Fecha del pedido',
        'fecha' => 'Fecha',
        'estado' => 'Estado',
        'total' => 'Importe total',
        'importe' => 'Importe',
        'importe_asignado' => 'Importe asignado',
        'importe_objetivo' => 'Importe objetivo',
        'precio_unitario' => 'Precio unitario',
        'cantidad' => 'Cantidad',
        'medida' => 'Unidad de medida',
        'unidad' => 'Unidad de medida',
        'unidad_medida' => 'Unidad de medida',
        'margen' => 'Margen (%)',
        'lista_articulos' => 'Líneas / Artículos',
        'archivo_pdf' => 'Documento adjunto',
        'archivo_documento' => 'Documento adjunto',
        'ruta_archivo' => 'Documento adjunto',
        'empresa_nombre' => 'Empresa',
        'personal_id' => 'Trabajador',
        'user_id' => 'Usuario',
        'usuario_id' => 'Usuario',
        'created_by' => 'Creado por',
        'updated_by' => 'Modificado por',
        'responsable_id' => 'Responsable',
        'ubicacion_id' => 'Ubicación',
        'departamento_id' => 'Departamento',
        'familia_id' => 'Familia',
        'almacen_id' => 'Almacén',
        'qr_carpeta_id' => 'Carpeta',
        'carpeta_id' => 'Carpeta',
        'permissions' => 'Permisos',
        'facturado' => 'Facturado',
        'bloqueado' => 'Bloqueado',
        'activo' => 'Activo',
        'tipo_bolsa' => 'Tipo de bolsa',
        'numero_referencia' => 'Nº de referencia',
        'titulo' => 'Título',
        'descripcion' => 'Descripción',
        'email' => 'Correo electrónico',
    ];

    private const BOOLEAN_FIELDS = ['bolsa', 'facturado', 'bloqueado', 'activo', 'es_abierta'];
    private const MONEY_FIELDS = ['total', 'importe', 'precio_unitario', 'importe_asignado', 'importe_objetivo'];

    public static function fieldLabel(string $field): string
    {
        if (isset(self::FIELD_LABELS[$field])) {
            return self::FIELD_LABELS[$field];
        }

        $label = preg_replace('/(^id_|_id$)/', '', $field) ?? $field;

        return ucfirst(str_replace('_', ' ', $label));
    }

    public static function resolveRelationsForActivities(iterable $activities): array
    {
        $ids = [];

        foreach ($activities as $activity) {
            $properties = $activity->properties?->toArray() ?? [];
            foreach (['attributes', 'old'] as $block) {
                foreach (($properties[$block] ?? []) as $field => $value) {
                    if (isset(self::RELACIONES[$field]) && is_scalar($value) && $value !== '') {
                        $ids[$field][] = $value;
                    }
                }
            }
        }

        $resolved = [];
        foreach ($ids as $field => $values) {
            [$class, $columns] = self::RELACIONES[$field];
            if (!class_exists($class)) {
                continue;
            }

            try {
                $model = new $class();
                $availableColumns = array_values(array_filter(
                    (array) $columns,
                    fn (string $column): bool => Schema::hasColumn($model->getTable(), $column)
                        || array_key_exists($column, $model->getAttributes())
                        || in_array($column, $model->getFillable(), true)
                ));
                if ($availableColumns === [] && $class === 'App\Models\Personal') {
                    $availableColumns = array_values(array_filter(
                        ['name', 'apellido'],
                        fn (string $column): bool => Schema::hasColumn($model->getTable(), $column)
                            || array_key_exists($column, $model->getAttributes())
                            || in_array($column, $model->getFillable(), true)
                    ));
                }
                if ($availableColumns === []) {
                    continue;
                }

                $query = in_array(SoftDeletes::class, class_uses_recursive($class), true)
                    ? $class::withTrashed()
                    : $class::query();
                $records = $query->whereIn($model->getKeyName(), array_unique($values))->get();

                foreach ($records as $record) {
                    $name = self::modelDisplayName($record, $availableColumns);
                    if ($name !== '') {
                        $resolved[$field][(string) $record->getKey()] = $name;
                    }
                }
            } catch (Throwable) {
                continue;
            }
        }

        return $resolved;
    }

        public static function formatValue(string $field, mixed $val, array $relaciones = []): string
    {
        if ($val === null || $val === '') {
            return 'Vacío';
        }
        
        // INTERCEPTOR DE FECHAS ISO (Como 2026-10-18T22:00:00.000000Z)
        if (is_string($val) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $val)) {
            try {
                return \Illuminate\Support\Carbon::parse($val)
                    ->timezone('Europe/Madrid')
                    ->format('d/m/Y H:i');
            } catch (\Exception $e) {
                // Si falla el parseo por lo que sea, continúa el flujo normal
            }
        }

        if (in_array($field, self::BOOLEAN_FIELDS, true) || is_bool($val)) {
            return (bool) $val ? 'Sí' : 'No';
        }
        if (is_scalar($val) && isset($relaciones[$field][(string) $val])) {
            return (string) $relaciones[$field][(string) $val];
        }
        if (in_array($field, ['archivo_pdf', 'archivo_documento', 'ruta_archivo'], true)) {
            return basename((string) $val);
        }
        if (in_array($field, self::MONEY_FIELDS, true) && is_numeric($val)) {
            return number_format((float) $val, 2, ',', '.') . ' €';
        }
        if ($field === 'estado') {
            return ucfirst(str_replace('_', ' ', (string) $val));
        }
        if (is_array($val)) {
            return self::formatLineItem($val);
        }

        return (string) $val;
    }


    public static function formatLineItem(mixed $item): string
    {
        if (!is_array($item)) {
            return self::formatValue('', $item);
        }

        $description = $item['descripcion'] ?? $item['nombre'] ?? 'Artículo';
        $quantity = $item['cantidad'] ?? 1;
        $unit = $item['medida'] ?? $item['unidad'] ?? 'und';
        $price = number_format((float) ($item['precio_unitario'] ?? $item['precio'] ?? 0), 2, ',', '.');
        $total = number_format((float) ($item['total'] ?? 0), 2, ',', '.');

        return '• ' . $description . ' — Cant: ' . $quantity . ' ' . $unit . ' × ' . $price . ' € = ' . $total . ' €';
    }

    public static function summarizeLineChanges(mixed $oldLines, mixed $newLines): string
    {
        $decodeLines = static function (mixed $lines): array {
            if (is_string($lines)) {
                $lines = json_decode($lines, true);
            }

            return is_array($lines) ? $lines : [];
        };
        $indexLines = static function (array $lines): array {
            $indexed = [];
            foreach ($lines as $index => $line) {
                if (!is_array($line)) {
                    continue;
                }

                $key = $line['uid'] ?? $line['descripcion'] ?? $index;
                $indexed[(string) $key] = $line;
            }

            return $indexed;
        };

        $old = $indexLines($decodeLines($oldLines));
        $new = $indexLines($decodeLines($newLines));
        $cambios = [];

        foreach (array_diff_key($new, $old) as $item) {
            $cambios[] = '➕ Añadido: ' . ($item['descripcion'] ?? 'Artículo');
        }
        foreach (array_diff_key($old, $new) as $item) {
            $cambios[] = '❌ Eliminado: ' . ($item['descripcion'] ?? 'Artículo');
        }
        foreach (array_intersect_key($new, $old) as $key => $newItem) {
            $oldItem = $old[$key];
            $hasChanged = (float) ($oldItem['total'] ?? 0) !== (float) ($newItem['total'] ?? 0)
                || (float) ($oldItem['cantidad'] ?? 0) !== (float) ($newItem['cantidad'] ?? 0)
                || (float) ($oldItem['precio_unitario'] ?? 0) !== (float) ($newItem['precio_unitario'] ?? 0);

            if ($hasChanged) {
                $cambios[] = '✏️ Modificado: ' . ($newItem['descripcion'] ?? '')
                    . ' (' . number_format((float) ($oldItem['total'] ?? 0), 2, ',', '.')
                    . ' € &rarr; ' . number_format((float) ($newItem['total'] ?? 0), 2, ',', '.') . ' €)';
            }
        }

        if (count($cambios) > 4) {
            $totalCambios = count($cambios);
            $cambios = array_slice($cambios, 0, 3);
            $cambios[] = '... y ' . ($totalCambios - 3) . ' cambios más en líneas';
        }

        return implode('<br>', $cambios);
    }

    public static function resolveSubjectReference(Activity $activity, array $relaciones = []): string
    {
        $properties = $activity->properties?->toArray() ?? [];
        $reference = trim((string) ($properties['reference_name'] ?? ''));
        if ($reference !== '' && !preg_match('/\bID\s*[:#]/i', $reference) && !str_contains($reference, 'Sin referencia')) {
            return $reference;
        }

        if ($activity->subject instanceof Model) {
            $reference = self::modelDisplayName($activity->subject, [
                'numero_pedido', 'numero', 'empresa_nombre', 'titulo', 'nombre',
                'descripcion', 'referencia_manual', 'name', 'email', 'apellido',
            ]);
            if ($reference !== '') {
                return $reference;
            }
        }

        foreach (['attributes', 'old'] as $block) {
            foreach (['numero_pedido', 'numero', 'empresa_nombre', 'titulo', 'nombre', 'descripcion', 'referencia_manual', 'name', 'email'] as $field) {
                $value = $properties[$block][$field] ?? null;
                if (is_scalar($value) && trim((string) $value) !== '') {
                    return trim((string) $value);
                }
            }
        }

        return 'Registro de ' . self::moduleLabel($activity->subject_type);
    }

    private static function modelDisplayName(Model $model, array $columns): string
    {
        $values = [];
        foreach ($columns as $column) {
            $value = $model->getAttribute($column);
            if (is_scalar($value) && trim((string) $value) !== '') {
                $values[] = trim((string) $value);
            }
        }

        return trim(implode(' ', array_unique($values)));
    }

    private static function moduleLabel(?string $subjectType): string
    {
        $name = class_basename((string) $subjectType);
        $labels = [
            'AlbaranCliente' => 'Albarán de cliente',
            'Articulo' => 'artículo',
            'Cliente' => 'cliente',
            'Documento' => 'documento',
            'PedidoCliente' => 'pedido',
            'Presupuesto' => 'presupuesto',
            'Proyecto' => 'proyecto',
        ];

        return $labels[$name] ?? strtolower(preg_replace('/(?<!^)[A-Z]/', ' $0', $name) ?: 'registro');
    }
}
