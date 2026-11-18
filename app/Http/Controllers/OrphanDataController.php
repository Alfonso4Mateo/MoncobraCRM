<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrphanDataController extends Controller
{
    private const CATALOGO_MODELOS = [
        'Personal' => ['class' => \App\Models\Personal::class, 'area' => 'Personal (RRHH)', 'type' => 'pivot', 'pivot_table' => 'personal_proyecto'],
        'Curso' => ['class' => \App\Models\Curso::class, 'area' => 'Cursos / PRL', 'type' => 'pivot', 'pivot_table' => 'curso_proyecto'],
        'User' => ['class' => \App\Models\User::class, 'area' => 'Usuarios', 'type' => 'pivot', 'pivot_table' => 'proyecto_user'],

        'Puesto' => ['class' => \App\Models\Puesto::class, 'area' => 'PRL / Formación', 'type' => 'column'],
        'PuestoTrabajo' => ['class' => \App\Models\PuestoTrabajo::class, 'area' => 'PRL / Formación', 'type' => 'column'],
        'Epi' => ['class' => \App\Models\Epi::class, 'area' => 'PRL / Formación', 'type' => 'column'],
        'EntregaEpi' => ['class' => \App\Models\EntregaEpi::class, 'area' => 'PRL / Formación', 'type' => 'column'],

        'Cliente' => ['class' => \App\Models\Cliente::class, 'area' => 'Comercial / Facturación', 'type' => 'column'],
        'Presupuesto' => ['class' => \App\Models\Presupuesto::class, 'area' => 'Comercial / Facturación', 'type' => 'column'],
        'PedidoCliente' => ['class' => \App\Models\PedidoCliente::class, 'area' => 'Comercial / Facturación', 'type' => 'column'],
        'AlbaranCliente' => ['class' => \App\Models\AlbaranCliente::class, 'area' => 'Comercial / Facturación', 'type' => 'column'],
        'Bolsa' => ['class' => \App\Models\Bolsa::class, 'area' => 'Comercial / Facturación', 'type' => 'column'],
        'FacturacionManual' => ['class' => \App\Models\FacturacionManual::class, 'area' => 'Comercial / Facturación', 'type' => 'column'],

        'Almacen' => ['class' => \App\Models\Almacen::class, 'area' => 'Inventario / Almacén', 'type' => 'column'],
        'Inventario' => ['class' => \App\Models\Inventario::class, 'area' => 'Inventario / Almacén', 'type' => 'column'],
        'InventarioVariante' => ['class' => \App\Models\InventarioVariante::class, 'area' => 'Inventario / Almacén', 'type' => 'column'],
        'InventarioArticulo' => ['class' => \App\Models\InventarioArticulo::class, 'area' => 'Inventario / Almacén', 'type' => 'column'],
        'InventarioMovimiento' => ['class' => \App\Models\InventarioMovimiento::class, 'area' => 'Inventario / Almacén', 'type' => 'column'],
        'Articulo' => ['class' => \App\Models\Articulo::class, 'area' => 'Inventario / Almacén', 'type' => 'column'],
        'EntradaStock' => ['class' => \App\Models\EntradaStock::class, 'area' => 'Inventario / Almacén', 'type' => 'column'],
        'SalidaStock' => ['class' => \App\Models\SalidaStock::class, 'area' => 'Inventario / Almacén', 'type' => 'column'],
        'TrasladoStock' => ['class' => \App\Models\TrasladoStock::class, 'area' => 'Inventario / Almacén', 'type' => 'column'],

        'EquipoInformatico' => ['class' => \App\Models\EquipoInformatico::class, 'area' => 'Activos y Mantenimiento', 'type' => 'column'],
        'HerramientaPlanta' => ['class' => \App\Models\HerramientaPlanta::class, 'area' => 'Activos y Mantenimiento', 'type' => 'column'],
        'AparatoCalibrable' => ['class' => \App\Models\AparatoCalibrable::class, 'area' => 'Activos y Mantenimiento', 'type' => 'column'],
        'FamiliaHerramienta' => ['class' => \App\Models\FamiliaHerramienta::class, 'area' => 'Activos y Mantenimiento', 'type' => 'column'],
        'ClaseHerramienta' => ['class' => \App\Models\ClaseHerramienta::class, 'area' => 'Activos y Mantenimiento', 'type' => 'column'],
        'Ubicacion' => ['class' => \App\Models\Ubicacion::class, 'area' => 'Activos y Mantenimiento', 'type' => 'column'],

        'Documento' => ['class' => \App\Models\Documento::class, 'area' => 'Otros / Documentación', 'type' => 'column'],
        'EtiquetaQr' => ['class' => \App\Models\EtiquetaQr::class, 'area' => 'Otros / Documentación', 'type' => 'column'],
        'QrCarpeta' => ['class' => \App\Models\QrCarpeta::class, 'area' => 'Otros / Documentación', 'type' => 'column'],
        'CentroCoste' => ['class' => \App\Models\CentroCoste::class, 'area' => 'Otros / Documentación', 'type' => 'column'],
    ];

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        $this->authorizeSuperAdmin();
        $allRecords = $this->scanCatalog();
        $moduleFilter = (string) $request->query('modulo', 'all');
        $query = trim((string) $request->query('q', ''));

        $records = $allRecords
            ->when($moduleFilter !== 'all', fn (Collection $items) => $items->where('model_key', $moduleFilter))
            ->when($query !== '', fn (Collection $items) => $items->filter(
                fn (object $item) => str_contains(mb_strtolower($item->reference), mb_strtolower($query))
                    || str_contains(mb_strtolower($item->model_key), mb_strtolower($query))
                    || str_contains((string) $item->id, $query)
            ))
            ->values();

        $perPage = 50;
        $page = max(1, (int) $request->query('page', 1));
        $registros = new LengthAwarePaginator(
            $records->forPage($page, $perPage)->values(),
            $records->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $resumenModulos = $allRecords->countBy('model_key');

        return view('admin.orphans.index', [
            'resumenModulos' => $resumenModulos,
            'registros' => $registros,
            'proyectos' => Proyecto::query()->orderBy('nombre')->get(),
            'proyectoActivoId' => $this->resolveProyectoForCorrelativo($request),
            'moduloFiltro' => $moduleFilter,
            'query' => $query,
            'catalogoModelos' => self::CATALOGO_MODELOS,
            'totalHuerfanos' => $allRecords->count(),
        ]);
    }

    public function assign(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin();
        $validated = $request->validate([
            'proyecto_id' => ['required', 'exists:proyectos,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['required', 'string', 'regex:/^[A-Za-z0-9_]+:\d+$/'],
        ]);

        $assigned = DB::transaction(function () use ($validated, $request): int {
            $count = 0;
            foreach ($this->resolveItems($validated['items']) as [$key, $record, $definition]) {
                if ($definition['type'] === 'pivot') {
                    $alreadyAssigned = $record->proyectos()->whereKey($validated['proyecto_id'])->exists();
                    $record->proyectos()->syncWithoutDetaching([$validated['proyecto_id']]);
                    if (!$alreadyAssigned) {
                        $this->logPivotChange($record, 'rescatado', $validated['proyecto_id'], $request);
                    }
                } else {
                    $record->forceFill(['proyecto_id' => $validated['proyecto_id']])->save();
                    if (!class_uses_recursive($record, \App\Models\Concerns\Auditable::class)) {
                        $this->logManualChange($record, 'rescatado', $validated['proyecto_id'], $request);
                    }
                }
                $count++;
            }

            return $count;
        });

        return back()->with('success', "{$assigned} registro(s) rescatado(s) y asignado(s) al proyecto destino.");
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin();
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['required', 'string', 'regex:/^[A-Za-z0-9_]+:\d+$/'],
        ]);

        [$deleted, $failed] = DB::transaction(function () use ($validated, $request): array {
            $deleted = 0;
            $failed = [];

            foreach ($this->resolveItems($validated['items']) as [$key, $record]) {
                try {
                    $reference = $this->referenceFor($record);
                    $record->delete();
                    if (!class_uses_recursive($record, \App\Models\Concerns\Auditable::class)) {
                        $this->logManualChange($record, 'eliminado', null, $request, $reference);
                    }
                    $deleted++;
                } catch (QueryException $exception) {
                    $failed[] = "{$key}:{$record->getKey()} ({$this->referenceFor($record)})";
                }
            }

            return [$deleted, $failed];
        });

        $message = "{$deleted} registro(s) eliminado(s).";
        if ($failed !== []) {
            $message .= ' No se pudieron eliminar por dependencias o historial: ' . implode(', ', $failed) . '.';
        }

        return back()->with($failed === [] ? 'success' : 'warning', $message);
    }

    private function authorizeSuperAdmin(): void
    {
        abort_unless(auth()->user()?->role === 'superadmin', 403);
    }

    private function scanCatalog(): Collection
    {
        $records = collect();
        foreach (self::CATALOGO_MODELOS as $key => $definition) {
            $class = $definition['class'];
            if (!class_exists($class)) {
                continue;
            }

            $model = new $class();
            if (!Schema::hasTable($model->getTable())) {
                continue;
            }

            if ($definition['type'] === 'pivot') {
                if (!method_exists($model, 'proyectos')) {
                    continue;
                }
                if (!Schema::hasTable($definition['pivot_table'])) {
                    continue;
                }
                $query = $class::query()->doesntHave('proyectos');
                if ($key === 'User') {
                    $query->where('role', '!=', 'superadmin');
                }
            } elseif (!Schema::hasColumn($model->getTable(), 'proyecto_id')) {
                continue;
            } else {
                $table = $model->getTable();
                $query = $class::query()->where(function ($builder) use ($table) {
                    $builder->whereNull('proyecto_id')
                        ->orWhereNotExists(function ($subquery) use ($table) {
                            $subquery->select(DB::raw(1))
                                ->from('proyectos')
                                ->whereColumn('proyectos.id', $table . '.proyecto_id');
                        });
                });
            }

            foreach ($query->get() as $record) {
                $records->push((object) [
                    'model_key' => $key,
                    'area' => $definition['area'],
                    'id' => $record->getKey(),
                    'reference' => $this->referenceFor($record),
                    'reason' => $definition['type'] === 'pivot'
                        ? 'Sin sede asignada'
                        : ($record->proyecto_id === null ? 'Sin sede asignada' : 'Proyecto inexistente'),
                    'created_at' => $record->created_at ?? null,
                ]);
            }
        }

        return $records->sortBy([
            ['model_key', 'asc'],
            ['id', 'asc'],
        ])->values();
    }

    private function resolveItems(array $items): iterable
    {
        foreach ($items as $item) {
            [$key, $id] = explode(':', $item, 2);
            $definition = self::CATALOGO_MODELOS[$key] ?? null;
            if (!$definition || !class_exists($definition['class'])) {
                throw ValidationException::withMessages(['items' => "El modelo {$key} no está disponible."]);
            }

            $model = new $definition['class']();
            if (!Schema::hasTable($model->getTable())) {
                throw ValidationException::withMessages(['items' => "La tabla de {$key} no está disponible."]);
            }
            if ($definition['type'] === 'column' && !Schema::hasColumn($model->getTable(), 'proyecto_id')) {
                throw ValidationException::withMessages(['items' => "{$key} no admite asignación por proyecto."]);
            }
            if ($definition['type'] === 'pivot' && !method_exists($model, 'proyectos')) {
                throw ValidationException::withMessages(['items' => "{$key} no tiene relación de proyectos."]);
            }
            if ($definition['type'] === 'pivot' && !Schema::hasTable($definition['pivot_table'])) {
                throw ValidationException::withMessages(['items' => "La tabla de relación de {$key} no está disponible."]);
            }

            $record = $definition['class']::query()->find((int) $id);
            if (!$record) {
                throw ValidationException::withMessages(['items' => "No existe {$key}:{$id}."]);
            }

            yield [$key, $record, $definition];
        }
    }

    private function referenceFor(Model $model): string
    {
        $class = class_basename($model);
        if ($class === 'Personal') {
            $reference = trim(implode(' ', array_filter([
                $model->getAttribute('name'),
                $model->getAttribute('apellido'),
            ])));
            if ($model->getAttribute('id_rrhh')) {
                $reference .= ' (RRHH: ' . $model->getAttribute('id_rrhh') . ')';
            }
            return $reference !== '' ? $reference : 'Registro #' . $model->getKey();
        }

        if ($class === 'User') {
            $name = trim((string) $model->getAttribute('name'));
            $email = trim((string) $model->getAttribute('email'));
            return $name !== '' ? "{$name} ({$email})" : ($email !== '' ? $email : 'Registro #' . $model->getKey());
        }

        foreach ([
            'numero_pedido', 'numero_salida', 'numero_entrada', 'numero_traslado', 'numero',
            'empresa_nombre', 'nombre', 'name', 'titulo', 'referencia_manual', 'numero_referencia',
            'codigo', 'descripcion',
        ] as $field) {
            $value = $model->getAttribute($field);
            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return 'Registro #' . $model->getKey();
    }

    private function logPivotChange(Model $record, string $action, ?int $projectId, Request $request): void
    {
        $this->logManualChange($record, $action, $projectId, $request);
    }

    private function logManualChange(
        Model $record,
        string $action,
        ?int $projectId,
        Request $request,
        ?string $reference = null
    ): void {
        activity()
            ->causedBy($request->user())
            ->performedOn($record)
            ->withProperties([
                'reference_name' => $reference ?? $this->referenceFor($record),
                'proyecto_id' => $projectId,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ])
            ->log("Registro {$action} desde el centro de saneamiento");
    }
}
