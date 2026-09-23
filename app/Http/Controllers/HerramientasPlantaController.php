<?php

namespace App\Http\Controllers;

use App\Models\FamiliaHerramienta;
use App\Models\HerramientaPlanta;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class HerramientasPlantaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = HerramientaPlanta::query();
        
        // Llamamos a la función privada para mantener el index limpio
        $this->applyFilters($query, $request);

        $items = $query->latest()->paginate(12)->withQueryString();
        $base = HerramientaPlanta::query();
        $stats = [
            'total' => (clone $base)->count(), 
            'operativos' => (clone $base)->whereIn('estado', ['operativo', 'asignado'])->count(),
            'mantenimiento' => (clone $base)->whereIn('estado', ['mantenimiento', 'reparacion'])->count(),
            'alertas' => (clone $base)->whereIn('estado', ['mantenimiento', 'reparacion'])->count(),
        ];

        return view('herramientas.planta.index', [
            'items' => $items, 
            'stats' => $stats,
            'ubicaciones' => HerramientaPlanta::whereNotNull('ubicacion')->distinct()->orderBy('ubicacion')->pluck('ubicacion'),
            'estados' => HerramientaPlanta::distinct()->orderBy('estado')->pluck('estado'),
            'familias' => FamiliaHerramienta::where('activo', true)->orderBy('nombre')->get(),
        ]);
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('buscar')) {
            $search = trim((string) $request->string('buscar'));
            $query->where(function ($builder) use ($search) {
                $builder->where('id_interno', 'like', "%{$search}%")->orWhere('nombre', 'like', "%{$search}%")
                    ->orWhere('codigo', 'like', "%{$search}%")->orWhere('numero_serie', 'like', "%{$search}%")
                    ->orWhere('marca', 'like', "%{$search}%")->orWhere('modelo', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('estado')) {
            $query->where('estado', (string) $request->string('estado'));
        }
        
        if ($request->filled('ubicacion')) {
            $query->where('ubicacion', (string) $request->string('ubicacion'));
        }
        
        if ($request->filled('familia')) {
            $query->where('familia_id', $request->integer('familia'));
        }

        // Filtro inteligente de Alertas Legales y Mantenimiento
        if ($request->filled('alerta')) {
            if ($request->alerta === 'oca_vencida') {
                $query->whereDate('fecha_inspeccion', '<', now());
            } elseif ($request->alerta === 'oca_proxima') {
                $query->whereBetween('fecha_inspeccion', [now(), now()->addDays(30)]);
            } elseif ($request->alerta === 'mantenimiento') {
                $query->whereDate('fecha_mantenimiento', '<', now());
            }
        }
    }

    public function create()
    {
        return view('herramientas.planta.form', ['familias' => FamiliaHerramienta::where('activo', true)->orderBy('nombre')->get()]);
    }

    public function storeFamilia(Request $request)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:120', 'unique:familias_herramientas,nombre'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ]);

        $familia = FamiliaHerramienta::create($data);

        if ($request->expectsJson()) {
            return response()->json(['id' => $familia->id, 'nombre' => $familia->nombre]);
        }

        return back()->with('success', 'Familia creada correctamente.');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        if ($request->hasFile('imagen')) $data['imagen'] = $request->file('imagen')->store('herramientas-planta', 'public');
        $item = HerramientaPlanta::create($data);
        return redirect()->route('herramientas.planta.show', $item)->with('success', 'Herramienta registrada correctamente.');
    }

    public function show(HerramientaPlanta $herramientaPlanta)
    {
        return view('herramientas.planta.show', ['herramienta' => $herramientaPlanta->load('familia')]);
    }

    public function storeEvento(Request $request, HerramientaPlanta $herramientaPlanta)
    {
        $data = $this->eventValidated($request);
        $data['usuario_id'] = auth()->id();
        
        // Atrapamos el archivo si el operario ha subido uno
        if ($request->hasFile('archivo_adjunto')) {
            $data['archivo_adjunto'] = $request->file('archivo_adjunto')->store('eventos_planta', 'public');
        }

        $herramientaPlanta->eventos()->create($data);
        return back()->with('success', 'Evento documentado y añadido a la bitácora.');
    }

    public function edit(HerramientaPlanta $herramientaPlanta)
    {
        return view('herramientas.planta.form', ['item' => $herramientaPlanta, 'familias' => FamiliaHerramienta::where('activo', true)->orderBy('nombre')->get()]);
    }

    public function update(Request $request, HerramientaPlanta $herramientaPlanta)
    {
        $data = $this->validated($request, $herramientaPlanta);
        if ($request->hasFile('imagen')) {
            if ($herramientaPlanta->imagen) Storage::disk('public')->delete($herramientaPlanta->imagen);
            $data['imagen'] = $request->file('imagen')->store('herramientas-planta', 'public');
        }
        $herramientaPlanta->update($data);
        return redirect()->route('herramientas.planta.show', $herramientaPlanta)->with('success', 'Herramienta actualizada correctamente.');
    }

    public function destroy(HerramientaPlanta $herramientaPlanta)
    {
        $herramientaPlanta->delete();
        return redirect()->route('herramientas.index')->with('success', 'Herramienta eliminada correctamente.');
    }

    private function validated(Request $request, ?HerramientaPlanta $item = null): array
    {
        return $request->validate([
            'id_interno' => ['required', 'string', 'max:100', Rule::unique('herramientas_planta', 'id_interno')->ignore($item?->id)],
            'nombre' => ['required', 'string', 'max:255'], 'codigo' => ['nullable', 'string', 'max:100'],
            'numero_serie' => ['nullable', 'string', 'max:150'], 'marca' => ['nullable', 'string', 'max:100'],
            'modelo' => ['nullable', 'string', 'max:150'], 'categoria' => ['nullable', 'string', 'max:150'],
            'estado' => ['required', Rule::in(['operativo', 'asignado', 'mantenimiento', 'reparacion'])],
            'ubicacion' => ['nullable', 'string', 'max:150'], 'ubicacion_2' => ['nullable', 'string', 'max:150'],
            'responsable' => ['nullable', 'string', 'max:150'], 'proveedor' => ['nullable', 'string', 'max:150'],
            'id_proveedor' => ['nullable', 'string', 'max:100'], 'almacen' => ['nullable', 'string', 'max:150'],
            'stock' => ['required', 'numeric', 'min:0'], 'unidad_medida' => ['required', 'string', 'max:30'],
            'fecha_registro' => ['required', 'date'], 'fecha_adquisicion' => ['nullable', 'date'],
            'fecha_mantenimiento' => ['nullable', 'date'], 'descripcion' => ['nullable', 'string'],
            'imagen' => ['nullable', 'image', 'max:4096'], 'familia_id' => ['nullable', 'exists:familias_herramientas,id'],
            'familia_energetica' => ['nullable', 'string', 'max:80'], 'potencia' => ['nullable', 'string', 'max:80'],
            'tension' => ['nullable', 'string', 'max:80'], 'combustible' => ['nullable', 'string', 'max:80'],
            'criticidad' => ['nullable', 'string', 'max:30'], 'horas_uso' => ['nullable', 'integer', 'min:0'],
            'bloqueado' => ['required', 'boolean'], 'motivo_bloqueo' => ['nullable', 'string', 'max:255'],
            'garantia_hasta' => ['nullable', 'date'],'fecha_inspeccion' => ['nullable', 'date'],
        ]);
    }

    private function eventValidated(Request $request): array
    {
        return $request->validate([
            'tipo' => ['required', \Illuminate\Validation\Rule::in(['averia', 'reparacion', 'mantenimiento', 'responsable', 'otro'])],
            'titulo' => ['required', 'string', 'max:180'], 
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'fecha' => ['required', 'date'],
            // Le decimos al validador que acepte archivos
            'archivo_adjunto' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], 
        ]);
    }

    public function updateEstado(Request $request, HerramientaPlanta $herramientaPlanta)
    {
        $request->validate([
            'estado' => ['required', \Illuminate\Validation\Rule::in(['operativo', 'mantenimiento', 'reparacion'])],
            'titulo_evento' => ['required', 'string', 'max:180'],
            // NUEVO: Permitimos adjuntar fotos/PDFs del parte de avería
            'archivo_adjunto' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'dias_estimados_baja' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $dataToUpdate = ['estado' => $request->estado];
        
        // Si la herramienta vuelve al almacén (operativo), la desasignamos automáticamente y quitamos bloqueos.
        if ($request->estado === 'operativo') {
            $dataToUpdate['responsable'] = null;
            $dataToUpdate['bloqueado'] = false; 
            $dataToUpdate['motivo_bloqueo'] = null;
            $dataToUpdate['dias_estimados_baja'] = null;
            $dataToUpdate['fecha_baja'] = null;
        } elseif (in_array($request->estado, ['reparacion', 'mantenimiento'])) {
            // Si va a reparación o mantenimiento, la bloqueamos por seguridad para que no se pueda asignar/usar mientras tanto.
            $dataToUpdate['bloqueado'] = true;
            $dataToUpdate['motivo_bloqueo'] = $request->titulo_evento;
            $dataToUpdate['dias_estimados_baja'] = $request->dias_estimados_baja;
            $dataToUpdate['fecha_baja'] = now();
        }

        $herramientaPlanta->update($dataToUpdate);

        // Preparamos los datos de la bitácora
        $eventoData = [
            'tipo' => in_array($request->estado, ['mantenimiento', 'reparacion']) ? $request->estado : 'entrada',
            'titulo' => $request->titulo_evento,
            'descripcion' => 'Cambio de estado en planta a: ' . ucfirst($request->estado),
            'fecha' => now(),
            'usuario_id' => auth()->id(),
        ];

        // NUEVO: Procesamos el archivo si el operario adjuntó una foto de la máquina rota
        if ($request->hasFile('archivo_adjunto')) {
            $eventoData['archivo_adjunto'] = $request->file('archivo_adjunto')->store('eventos_planta', 'public');
        }

        // Guardamos en el historial
        $herramientaPlanta->eventos()->create($eventoData);

        return back()->with('success', 'Estado de la maquinaria actualizado y registrado correctamente.');
    }

    public function uploadManual(Request $request, HerramientaPlanta $herramientaPlanta)
    {
        $request->validate([
            'manual_pdf' => ['required', 'file', 'mimes:pdf', 'max:10240'], // Máx 10MB
        ]);

        // Si ya había un manual antiguo, lo borramos del servidor para no acumular basura
        if ($herramientaPlanta->manual_pdf) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($herramientaPlanta->manual_pdf);
        }

        $path = $request->file('manual_pdf')->store('manuales_planta', 'public');
        $herramientaPlanta->update(['manual_pdf' => $path]);

        // Registramos silenciosamente en la bitácora que alguien actualizó el manual
        $herramientaPlanta->eventos()->create([
            'tipo' => 'otro',
            'titulo' => 'Actualización de Documentación',
            'descripcion' => 'Se ha subido o actualizado el manual técnico / normativa de la máquina.',
            'fecha' => now(),
            'usuario_id' => auth()->id(),
        ]);

        return back()->with('success', 'Manual técnico actualizado y disponible para los operarios.');
    }

    public function updateAsignacion(Request $request, HerramientaPlanta $herramientaPlanta)
    {
        $request->validate([
            'responsable' => ['required', 'string', 'max:150'],
            'ubicacion' => ['required', 'string', 'max:150'],
            'titulo_evento' => ['required', 'string', 'max:180'],
            'archivo_adjunto' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        // 1. Asignamos la máquina y la marcamos operativa en obra
        $herramientaPlanta->update([
            'estado' => 'asignado',
            'responsable' => $request->responsable,
            'ubicacion' => $request->ubicacion,
            'bloqueado' => false,
        ]);

        // 2. Procesamos el archivo adjunto si existe (ej. albarán de entrega firmado)
        $rutaArchivo = null;
        if ($request->hasFile('archivo_adjunto')) {
            $rutaArchivo = $request->file('archivo_adjunto')->store('eventos_planta', 'public');
        }

        // 3. Registramos el movimiento físico en la bitácora
        $herramientaPlanta->eventos()->create([
            'tipo' => 'responsable',
            'titulo' => $request->titulo_evento,
            'descripcion' => "Entregado a: {$request->responsable} | Obra: {$request->ubicacion}",
            'fecha' => now(),
            'archivo_adjunto' => $rutaArchivo,
            'usuario_id' => auth()->id(),
        ]);

        return back()->with('success', 'Maquinaria asignada a la obra correctamente.');
    }
}