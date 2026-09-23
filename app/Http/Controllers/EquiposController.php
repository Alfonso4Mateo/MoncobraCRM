<?php

namespace App\Http\Controllers;

use App\Models\EquipoInformatico;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class EquiposController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = EquipoInformatico::where('activo', true);
        $this->applyFilters($query, $request);
        $items = $query->latest()->paginate(12)->withQueryString();
        $base = EquipoInformatico::where('activo', true);
        $stats = [
            'total' => (clone $base)->count(),
            'operativos' => (clone $base)->whereIn('estado', ['operativo', 'asignado'])->count(),
            'mantenimiento' => (clone $base)->whereIn('estado', ['mantenimiento', 'reparacion'])->count(),
            'alertas' => (clone $base)->whereIn('estado', ['mantenimiento', 'reparacion'])->count(),
        ];

        return view('herramientas.equipos.index', [
            'items' => $items,
            'stats' => $stats,
            'ubicaciones' => EquipoInformatico::where('activo', true)->whereNotNull('ubicacion')->distinct()->orderBy('ubicacion')->pluck('ubicacion'),
            'estados' => EquipoInformatico::where('activo', true)->distinct()->orderBy('estado')->pluck('estado'),
        ]);
    }

    public function create()
    {
        return view('herramientas.equipos.form');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        if ($request->hasFile('imagen')) $data['imagen'] = $request->file('imagen')->store('equipos', 'public');
        $item = EquipoInformatico::create($data);
        return redirect()->route('equipos.show', $item)->with('success', 'Equipo registrado correctamente.');
    }

    public function show(EquipoInformatico $equipo)
    {
        return view('herramientas.equipos.show', ['equipo' => $equipo->load('eventos.usuario')]);
    }

    public function storeEvento(Request $request, EquipoInformatico $equipo)
    {
        $data = $this->eventValidated($request);
        $data['usuario_id'] = auth()->id();
        
        // Verificamos si se ha subido un archivo en el formulario
        if ($request->hasFile('archivo_adjunto')) {
            // Guardamos el archivo en la carpeta storage/app/public/eventos_it
            $data['archivo_adjunto'] = $request->file('archivo_adjunto')->store('eventos_it', 'public');
        }

        $equipo->eventos()->create($data);
        return back()->with('success', 'Evento documentado y añadido a la bitácora.');
    }

    public function edit(EquipoInformatico $equipo)
    {
        return view('herramientas.equipos.form', ['item' => $equipo]);
    }

    public function update(Request $request, EquipoInformatico $equipo)
    {
        $data = $this->validated($request, $equipo);
        if ($request->hasFile('imagen')) {
            if ($equipo->imagen) Storage::disk('public')->delete($equipo->imagen);
            $data['imagen'] = $request->file('imagen')->store('equipos', 'public');
        }
        $equipo->update($data);
        return redirect()->route('equipos.show', $equipo)->with('success', 'Equipo actualizado correctamente.');
    }

    public function destroy(EquipoInformatico $equipo)
    {
        $equipo->delete();
        return redirect()->route('equipos.index')->with('success', 'Equipo archivado correctamente.');
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('buscar')) {
            $search = trim((string) $request->string('buscar'));
            $query->where(function ($builder) use ($search) {
                $builder->where('nombre', 'like', "%{$search}%")->orWhere('codigo', 'like', "%{$search}%")
                    ->orWhere('numero_serie', 'like', "%{$search}%")->orWhere('marca', 'like', "%{$search}%")
                    ->orWhere('modelo', 'like', "%{$search}%")->orWhere('responsable', 'like', "%{$search}%");
            });
        }
        if ($request->filled('estado')) $query->where('estado', (string) $request->string('estado'));
        if ($request->filled('ubicacion')) $query->where('ubicacion', (string) $request->string('ubicacion'));
        if ($request->filled('alerta')) {
            if ($request->alerta === 'obsoleto') {
                $query->whereDate('soporte_hasta', '<', now());
            } elseif ($request->alerta === 'mantenimiento') {
                $query->whereDate('fecha_mantenimiento', '<', now());
            }
        }
    }

    private function validated(Request $request, ?EquipoInformatico $equipo = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:255'], 'codigo' => ['nullable', 'string', 'max:100'],
            'numero_serie' => ['nullable', 'string', 'max:150'], 'marca' => ['nullable', 'string', 'max:100'],
            'modelo' => ['nullable', 'string', 'max:150'], 'categoria' => ['nullable', 'string', 'max:150'],
            'estado' => ['required', Rule::in(['operativo', 'asignado', 'mantenimiento', 'reparacion'])],
            'ubicacion' => ['nullable', 'string', 'max:150'], 'responsable' => ['nullable', 'string', 'max:150'],
            'proveedor' => ['nullable', 'string', 'max:150'], 'fecha_adquisicion' => ['nullable', 'date'],
            'fecha_mantenimiento' => ['nullable', 'date'], 'descripcion' => ['nullable', 'string'],
            'imagen' => ['nullable', 'image', 'max:4096'], 'tipo_equipo' => ['nullable', 'string', 'max:100'],
            'sistema_operativo' => ['nullable', 'string', 'max:100'], 'procesador' => ['nullable', 'string', 'max:150'],
            'memoria_ram' => ['nullable', 'string', 'max:80'], 'almacenamiento' => ['nullable', 'string', 'max:100'],
            'mac_address' => ['nullable', 'string', 'max:50'], 'ip_address' => ['nullable', 'string', 'max:50'],
            'garantia_hasta' => ['nullable', 'date'], 'soporte_hasta' => ['nullable', 'date'],
            'condicion_fisica' => ['nullable', 'string', 'max:40'], 'etiqueta_color' => ['nullable', 'string', 'max:30'],
        ]);
    }

    private function eventValidated(Request $request): array
    {
        return $request->validate([
            'tipo' => ['required', \Illuminate\Validation\Rule::in(['averia', 'reparacion', 'mantenimiento', 'responsable', 'otro'])],
            'titulo' => ['required', 'string', 'max:180'], 
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'fecha' => ['required', 'date'],
            // Validamos que sea un documento o imagen con un tamaño razonable (ej. máx 5MB)
            'archivo_adjunto' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], 
        ]);
    }

    public function updateEstado(Request $request, EquipoInformatico $equipo)
    {
        $request->validate([
            'estado' => ['required', \Illuminate\Validation\Rule::in(['operativo', 'mantenimiento', 'reparacion'])],
            'titulo_evento' => ['required', 'string', 'max:180'],
            'archivo_adjunto' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'dias_estimados_baja' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $dataToUpdate = ['estado' => $request->estado];
        
        if ($request->estado === 'operativo') {
            $dataToUpdate['responsable'] = null;
            $dataToUpdate['dias_estimados_baja'] = null;
            $dataToUpdate['fecha_baja'] = null;
        } elseif (in_array($request->estado, ['reparacion', 'mantenimiento'])) {
            $dataToUpdate['dias_estimados_baja'] = $request->dias_estimados_baja;
            $dataToUpdate['fecha_baja'] = now();
        }

        $equipo->update($dataToUpdate);

        // Procesamos el archivo adjunto si existe
        $rutaArchivo = null;
        if ($request->hasFile('archivo_adjunto')) {
            $rutaArchivo = $request->file('archivo_adjunto')->store('eventos_it', 'public');
        }

        // Registramos en la bitácora
        $equipo->eventos()->create([
            'tipo' => in_array($request->estado, ['mantenimiento', 'reparacion']) ? $request->estado : 'entrada',
            'titulo' => $request->titulo_evento,
            'descripcion' => 'Cambio rápido de estado a: ' . ucfirst($request->estado) . ($request->estado === 'operativo' ? ' (Devuelto a stock)' : ''),
            'fecha' => now(),
            'archivo_adjunto' => $rutaArchivo,
            'usuario_id' => auth()->id(),
        ]);

        return back()->with('success', 'Estado actualizado y registrado en la bitácora correctamente.');
    }

    public function updateAsignacion(Request $request, EquipoInformatico $equipo)
    {
        $request->validate([
            'responsable' => ['required', 'string', 'max:150'],
            'ubicacion' => ['required', 'string', 'max:150'],
            'titulo_evento' => ['required', 'string', 'max:180'],
            'archivo_adjunto' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        // 1. Asignamos el equipo
        $equipo->update([
            'estado' => 'asignado',
            'responsable' => $request->responsable,
            'ubicacion' => $request->ubicacion,
        ]);

        // 2. Procesamos el archivo adjunto si existe (ej. albarán de entrega firmado)
        $rutaArchivo = null;
        if ($request->hasFile('archivo_adjunto')) {
            $rutaArchivo = $request->file('archivo_adjunto')->store('eventos_it', 'public');
        }

        // 3. Registramos el movimiento en la bitácora
        $equipo->eventos()->create([
            'tipo' => 'responsable',
            'titulo' => $request->titulo_evento,
            'descripcion' => "Equipo entregado a: {$request->responsable} | Puesto: {$request->ubicacion}",
            'fecha' => now(),
            'archivo_adjunto' => $rutaArchivo,
            'usuario_id' => auth()->id(),
        ]);

        return back()->with('success', 'Equipo informático asignado correctamente.');
    }
}