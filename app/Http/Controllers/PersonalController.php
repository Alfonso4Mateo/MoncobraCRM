<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use App\Models\SalidaStock;
use App\Models\Proyecto;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PersonalController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
       
    }

    public function index(Request $request)
    {
        $query = (string) $request->input('q', '');
        $export = $request->input('export');
        $user = $request->user();
        $alertaDias = $request->has('alerta_dias')
            ? (int) $request->input('alerta_dias', 0)
            : (int) ($user?->personal_alerta_dias ?? 0);
        $alertaDias = in_array($alertaDias, [0, 30, 60, 90, 120, 180], true) ? $alertaDias : 0;
        if ($request->has('alerta_dias') && $user) {
            $user->update(['personal_alerta_dias' => $alertaDias]);
        }
        $alertaLimite = $alertaDias > 0 ? now()->addDays($alertaDias)->endOfDay() : null;

        $alertaNombre = (string) $request->input('alerta_nombre', 'any');
        $alertaNombre = in_array($alertaNombre, ['any', 'with', 'without'], true) ? $alertaNombre : 'any';

        $buildQuery = function () use ($query, $alertaNombre, $alertaLimite) {
            $personalQuery = Personal::query()->with('proyectos');

            if ($query !== '') {
                $personalQuery->where(function ($subQuery) use ($query) {
                    $subQuery
                        ->where('name', 'like', "%{$query}%")
                        ->orWhere('apellido', 'like', "%{$query}%")
                        ->orWhere('dni_nie', 'like', "%{$query}%")
                        ->orWhere('telefono', 'like', "%{$query}%")
                        ->orWhere('departamento', 'like', "%{$query}%");
                });
            }

            if ($alertaNombre === 'with') {
                if ($alertaLimite) {
                    $personalQuery->whereNotNull('proxima_revision_medica')
                        ->where('proxima_revision_medica', '<=', $alertaLimite);
                } else {
                    $personalQuery->whereNotNull('proxima_revision_medica');
                }
            }

            if ($alertaNombre === 'without') {
                if ($alertaLimite) {
                    $personalQuery->where(function ($q) use ($alertaLimite) {
                        $q->whereNull('proxima_revision_medica')
                          ->orWhere('proxima_revision_medica', '>', $alertaLimite);
                    });
                } else {
                    $personalQuery->whereNull('proxima_revision_medica');
                }
            }

            return $personalQuery->orderBy('name');
        };

        if ($export === 'csv') {
            $rows = $buildQuery()->get();

            return response()->streamDownload(function () use ($rows) {
                $handle = fopen('php://output', 'w');
                fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($handle, ['ID', 'Nombre', 'Apellido', 'DNI/NIE', 'Telefono', 'Departamento', 'Estado', 'Proyectos']);

                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->id,
                        $row->name,
                        $row->apellido,
                        $row->dni_nie,
                        $row->telefono,
                        $row->departamento,
                        $row->activo ? 'Activo' : 'Inactivo',
                        $row->proyectos->pluck('nombre')->join(' | '),
                    ]);
                }

                fclose($handle);
            }, 'personal.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        $personals = $buildQuery()->paginate(12)->withQueryString();
        $personals->getCollection()->transform(function (Personal $personal) use ($alertaLimite) {
            $personal->alerta_revision_medica = $alertaLimite
                ? ($personal->proxima_revision_medica && $personal->proxima_revision_medica->lte($alertaLimite))
                : false;

            return $personal;
        });
        $personalTotal = Personal::count();
        $personalActivos = Personal::where('activo', true)->count();

        // Conteo de avisos de revisión médica según el límite de alerta actual
        if ($alertaLimite) {
            $avisosCount = Personal::whereNotNull('proxima_revision_medica')
                ->where('proxima_revision_medica', '<=', $alertaLimite)
                ->count();
        } else {
            $avisosCount = 0;
        }

        return view('personal.index', [
            'personals' => $personals,
            'query' => $query,
            'alertaDias' => $alertaDias,
            'alertaNombre' => $alertaNombre,
            'avisosCount' => $avisosCount,
            'personalTotal' => $personalTotal,
            'personalActivos' => $personalActivos,
        ]);
    }

    public function create()
    {
        $personal = new Personal(['activo' => true]);

        return view('personal.edit', [
            'personal' => $personal,
            'isCreate' => true,
            'proyectos' => Proyecto::orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePersonal($request);

        $proyectoIds = $validated['proyecto_ids'] ?? null;
        unset($validated['proyecto_ids']);

        $validated['activo'] = $request->boolean('activo', true);

        $personal = Personal::create($validated);
        if (is_array($proyectoIds)) {
            $personal->proyectos()->sync($proyectoIds);
        }

        return redirect()->route('personal.index')->with('success', 'Trabajador creado correctamente.');
    }

    public function show(Personal $personal, Request $request)
    {
        $personal->load('proyectos');

        $nombreCompleto = trim(preg_replace('/\s+/', ' ', trim((string) $personal->name . ' ' . (string) $personal->apellido)));
        $nombreNormalizado = mb_strtolower($nombreCompleto);

        $salidasQuery = SalidaStock::query()
            ->orderByDesc('fecha')
            ->orderByDesc('id');

        $salidas = $salidasQuery
            ->get()
            ->filter(function (SalidaStock $salida) use ($nombreNormalizado) {
                $solicitante = trim((string) ($salida->solicitante ?? ''));

                if ($solicitante === '') {
                    return false;
                }

                return mb_strtolower(preg_replace('/\s+/', ' ', $solicitante)) === $nombreNormalizado;
            })
            ->filter(function (SalidaStock $salida) use ($request) {
                $fechaDesde = $request->input('fecha_desde');
                $fechaHasta = $request->input('fecha_hasta');

                if ($fechaDesde && $salida->fecha < \Carbon\Carbon::parse($fechaDesde)->startOfDay()) {
                    return false;
                }

                if ($fechaHasta && $salida->fecha > \Carbon\Carbon::parse($fechaHasta)->endOfDay()) {
                    return false;
                }

                $articuloBuscar = trim((string) $request->input('articulo'));
                if ($articuloBuscar !== '') {
                    $articulos = collect((array) $salida->items)
                        ->map(fn ($item) => trim((string) ($item['descripcion'] ?? $item['articulo'] ?? $item['nombre'] ?? '')))
                        ->filter()
                        ->implode(' ');

                    if (stripos($articulos, $articuloBuscar) === false) {
                        return false;
                    }
                }

                return true;
            })
            ->take(8)
            ->values();

        $historicoSalidas = $salidas->flatMap(function (SalidaStock $salida) {
            $items = collect((array) $salida->items)
                ->filter(fn ($item) => is_array($item))
                ->map(function (array $item) {
                    $descripcion = trim((string) ($item['descripcion'] ?? $item['articulo'] ?? $item['nombre'] ?? ''));
                    $cantidad = (float) ($item['cantidad'] ?? 0);

                    return [
                        'descripcion' => $descripcion,
                        'cantidad' => $cantidad,
                    ];
                })
                ->filter(fn (array $item) => $item['descripcion'] !== '')
                ->values();

            if ($items->isEmpty()) {
                $items = collect([['descripcion' => 'Salida de inventario', 'cantidad' => 1]]);
            }

            return $items->map(function (array $item) use ($salida) {
                $estadoRaw = (string) ($salida->estado ?: 'Pendiente');
                $estadoNormalizado = mb_strtolower($estadoRaw);

                return (object) [
                    'salida_id' => $salida->id,
                    'fecha' => optional($salida->fecha)->format('d M Y'),
                    'articulo' => $item['descripcion'],
                    'cantidad' => $item['cantidad'],
                    'ot' => $salida->ot ?: '—',
                    'estado' => ucfirst($estadoNormalizado === 'aceptado' ? 'entregado' : $estadoNormalizado),
                    'estado_clase' => $estadoNormalizado === 'aceptado' || $estadoNormalizado === 'entregado'
                        ? 'profile-chip profile-chip--ok'
                        : 'profile-chip profile-chip--pending',
                ];
            });
        })->take(8)->values()->all();

        $tallas = [
            ['label' => 'Camiseta', 'value' => $personal->camiseta ?: '—', 'icon' => 'fa-tshirt'],
            ['label' => 'Sudadera', 'value' => $personal->sudadera ?: '—', 'icon' => 'fa-tshirt'],
            ['label' => 'Chaquetón', 'value' => $personal->chaqueta ?: '—', 'icon' => 'fa-vest'],
            ['label' => 'Pantalón', 'value' => $personal->pantalon ?: '—', 'icon' => 'fa-bridge'],
            ['label' => 'Calzado', 'value' => $personal->calzado ?: '—', 'icon' => 'fa-shoe-prints'],
            ['label' => 'Casco', 'value' => $personal->casco ?: '—', 'icon' => 'fa-hard-hat'],
            ['label' => 'Guantes', 'value' => $personal->guantes ?: '—', 'icon' => 'fa-hand-paper'],
        ];

        return view('personal.show', compact('personal', 'historicoSalidas', 'tallas'));
    }

    public function edit(Personal $personal)
    {
        return view('personal.edit', [
            'personal' => $personal,
            'isCreate' => false,
            'proyectos' => Proyecto::orderBy('nombre')->get(),
        ]);
    }

    public function update(Request $request, Personal $personal)
    {
        $validated = $this->validatePersonal($request, $personal->id);

        $proyectoIds = $validated['proyecto_ids'] ?? null;
        unset($validated['proyecto_ids']);

        if ($request->has('activo')) {
            $validated['activo'] = $request->boolean('activo');
        }

        $personal->update($validated);
        if (is_array($proyectoIds)) {
            $personal->proyectos()->sync($proyectoIds);
        }

        return redirect()->route('personal.show', $personal->id)->with('success', 'Ficha actualizada correctamente.');
    }

    public function destroy(Personal $personal)
    {
        if (auth()->user()?->role !== 'superadmin') {
            abort(403);
        }

        $personal->delete();

        return redirect()->route('personal.index')->with('success', 'Trabajador eliminado correctamente.');
    }

    public function tallas(Request $request)
    {
        $query = (string) $request->input('q', '');

        $personals = Personal::query()
            ->when($query !== '', function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('apellido', 'like', "%{$query}%")
                  ->orWhere('dni_nie', 'like', "%{$query}%")
                  ->orWhere('departamento', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->get();

        $columns = ['camiseta', 'chaqueta', 'sudadera', 'pantalon', 'calzado', 'guantes', 'casco'];

        return view('personal.tallas', [
            'personals' => $personals,
            'query' => $query,
            'columns' => $columns,
        ]);
    }

    private function validatePersonal(Request $request, ?int $ignoreId = null): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'apellido' => 'nullable|string|max:255',
            'dni_nie' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('personal', 'dni_nie')->ignore($ignoreId),
            ],
            'departamento' => 'nullable|string|max:255',
            'tipo_personal' => 'nullable|in:indefinido,temporal',
            'camiseta' => 'nullable|string|max:20',
            'chaqueta' => 'nullable|string|max:20',
            'sudadera' => 'nullable|string|max:20',
            'pantalon' => 'nullable|string|max:20',
            'calzado' => 'nullable|string|max:20',
            'casco' => 'nullable|string|max:20',
            'guantes' => 'nullable|string|max:20',
            'telefono' => 'nullable|string|max:20',
            'descripcion' => 'nullable|string|max:500',
            'ultima_revision_medica' => 'nullable|date',
            'proxima_revision_medica' => 'nullable|date',
            'proyecto_ids' => 'nullable|array',
            'proyecto_ids.*' => 'exists:proyectos,id',
            'activo' => 'boolean',
        ];

        $validated = $request->validate($rules);

        return $validated;
    }
}
