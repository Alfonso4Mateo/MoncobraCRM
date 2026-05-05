<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use App\Models\Proyecto;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PersonalController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:manage-users');
    }

    public function index(Request $request)
    {
        $query = (string) $request->input('q', '');
        $export = $request->input('export');

        $buildQuery = function () use ($query) {
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
        $personalTotal = Personal::count();
        $personalActivos = Personal::where('activo', true)->count();

        return view('personal.index', [
            'personals' => $personals,
            'query' => $query,
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

        return redirect()->route('personal.show', $personal->id)->with('success', 'Trabajador creado correctamente.');
    }

    public function show(Personal $personal, Request $request)
    {
        $personal->load('proyectos');

        $datosCompletos = [
            ['fecha' => now()->subDays(3), 'estado' => 'Entregado', 'estado_clase' => 'profile-chip profile-chip--ok', 'ot' => 'OT-7732', 'cantidad' => 1, 'articulo' => 'Casco de Seguridad'],
            ['fecha' => now()->subDays(6), 'estado' => 'Entregado', 'estado_clase' => 'profile-chip profile-chip--ok', 'ot' => 'OT-7650', 'cantidad' => 2, 'articulo' => 'Guantes de Protección'],
            ['fecha' => now()->subDays(10), 'estado' => 'Entregado', 'estado_clase' => 'profile-chip profile-chip--ok', 'ot' => 'REPOSICIÓN', 'cantidad' => 5, 'articulo' => 'Arnés de Seguridad'],
            ['fecha' => now()->subDays(14), 'estado' => 'Pendiente', 'estado_clase' => 'profile-chip profile-chip--pending', 'ot' => 'OT-7621', 'cantidad' => 1, 'articulo' => 'Cinturón de Trabajo'],
            ['fecha' => now()->subDays(18), 'estado' => 'Entregado', 'estado_clase' => 'profile-chip profile-chip--ok', 'ot' => 'OT-7589', 'cantidad' => 3, 'articulo' => 'Zapatos de Seguridad'],
            ['fecha' => now()->subDays(22), 'estado' => 'Entregado', 'estado_clase' => 'profile-chip profile-chip--ok', 'ot' => 'OT-7512', 'cantidad' => 2, 'articulo' => 'Gafas de Protección'],
            ['fecha' => now()->subDays(26), 'estado' => 'Entregado', 'estado_clase' => 'profile-chip profile-chip--ok', 'ot' => 'OT-7445', 'cantidad' => 1, 'articulo' => 'Mascarilla FFP2'],
            ['fecha' => now()->subDays(30), 'estado' => 'Entregado', 'estado_clase' => 'profile-chip profile-chip--ok', 'ot' => 'OT-7321', 'cantidad' => 4, 'articulo' => 'Chaleco Reflectante'],
        ];

        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');
        $articuloBuscar = $request->input('articulo');

        $datosFiltrados = array_filter($datosCompletos, function ($item) use ($fechaDesde, $fechaHasta, $articuloBuscar) {
            $fecha = $item['fecha'];

            if ($fechaDesde && $fecha < \Carbon\Carbon::parse($fechaDesde)->startOfDay()) {
                return false;
            }
            if ($fechaHasta && $fecha > \Carbon\Carbon::parse($fechaHasta)->endOfDay()) {
                return false;
            }
            if ($articuloBuscar && stripos($item['articulo'], $articuloBuscar) === false) {
                return false;
            }

            return true;
        });

        $historicoSalidas = array_map(function ($item) {
            return (object) [
                'fecha' => $item['fecha']->format('d M Y'),
                'articulo' => $item['articulo'],
                'cantidad' => $item['cantidad'],
                'ot' => $item['ot'],
                'estado' => $item['estado'],
                'estado_clase' => $item['estado_clase'],
            ];
        }, array_slice($datosFiltrados, 0, 8));

        $tallas = [
            ['label' => 'Camiseta', 'value' => $personal->camiseta ?: '—', 'icon' => 'fa-shirt'],
            ['label' => 'Chaqueta', 'value' => $personal->chaqueta ?: '—', 'icon' => 'fa-jacket'],
            ['label' => 'Sudadera', 'value' => $personal->sudadera ?: '—', 'icon' => 'fa-hoodie'],
            ['label' => 'Pantalón', 'value' => $personal->pantalon ?: '—', 'icon' => 'fa-person'],
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
            'proyecto_ids' => 'nullable|array',
            'proyecto_ids.*' => 'exists:proyectos,id',
            'activo' => 'boolean',
        ];

        $validated = $request->validate($rules);

        return $validated;
    }
}
