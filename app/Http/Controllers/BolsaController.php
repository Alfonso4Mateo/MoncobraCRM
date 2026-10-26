<?php

namespace App\Http\Controllers;

use App\Models\Bolsa;
use App\Models\Cliente;
use App\Models\LineaBolsa;
use App\Models\PedidoCliente;
use App\Services\LineaBolsaService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BolsaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $this->authorize('pedidos.view');

        $proyectoId = $this->resolveActiveProyectoId($request);
        $sessionKey = "bolsas_filter_pref_{$proyectoId}";

        // 1. GESTIÓN DE PERSISTENCIA DE FILTROS (Abiertas / Con Tope)
        if ($request->boolean('reset_filters')) {
            $request->session()->forget($sessionKey);
            return redirect()->route('bolsas.index');
        }

        if ($request->has('filter_applied')) {
            $verAbiertas = $request->boolean('ver_abiertas');
            $verCerradas = $request->boolean('ver_cerradas');

            // Si desmarca ambos por error, activamos ambos para no dejar una pantalla vacía confusa
            if (!$verAbiertas && !$verCerradas) {
                $verAbiertas = true;
                $verCerradas = true;
            }

            $request->session()->put($sessionKey, [
                'ver_abiertas' => $verAbiertas,
                'ver_cerradas' => $verCerradas,
            ]);
        } else {
            $prefs = $request->session()->get($sessionKey, [
                'ver_abiertas' => true,
                'ver_cerradas' => true,
            ]);
            $verAbiertas = (bool) ($prefs['ver_abiertas'] ?? true);
            $verCerradas = (bool) ($prefs['ver_cerradas'] ?? true);
        }

        $buscar = trim((string) $request->query('buscar', ''));

        $bolsasQuery = Bolsa::query()
            ->with(['cliente', 'partidas.albaranesPivot'])
            ->withCount('partidas')
            ->where('proyecto_id', $proyectoId);

        // 2. FILTRO POR MODALIDAD (Abiertas [total <= 0] vs Con Tope/Cerradas [total > 0])
        if ($verAbiertas && !$verCerradas) {
            $bolsasQuery->where('total', '<=', 0);
        } elseif (!$verAbiertas && $verCerradas) {
            $bolsasQuery->where('total', '>', 0);
        }

        // 3. BUSCADOR MULTI-CAMPO (Tipo, Cantidad, Número, Cliente y Fecha)
        if ($buscar !== '') {
            $like = '%' . $buscar . '%';
            // Normalizamos formato moneda español (ej. "1.200,50" -> "1200.50")
            $buscarNormalizado = str_replace(['.', ','], ['', '.'], $buscar);
            $buscarSimple = str_replace(',', '.', $buscar);

            $bolsasQuery->where(function ($query) use ($like, $buscar, $buscarNormalizado, $buscarSimple) {
                $query->where('numero', 'like', $like)
                    ->orWhere('tipo_bolsa', 'like', $like)
                    ->orWhere('fecha', 'like', $like)
                    ->orWhereHas('cliente', function ($qCliente) use ($like) {
                        $qCliente->where('empresa_nombre', 'like', $like);
                    });

                // Búsqueda por cantidad/importe
                if (is_numeric($buscarSimple)) {
                    $query->orWhere('total', '=', (float) $buscarSimple)
                          ->orWhere('total', 'like', '%' . $buscarSimple . '%');
                } elseif (is_numeric($buscarNormalizado)) {
                    $query->orWhere('total', '=', (float) $buscarNormalizado)
                          ->orWhere('total', 'like', '%' . $buscarNormalizado . '%');
                }

                // Búsqueda por fecha en formato español (dd/mm/yyyy o dd-mm-yyyy)
                if (preg_match('#^(\d{1,2})[/-](\d{1,2})[/-](\d{4})$#', $buscar, $matches)) {
                    $fechaSql = sprintf('%04d-%02d-%02d', (int) $matches[3], (int) $matches[2], (int) $matches[1]);
                    $query->orWhereDate('fecha', $fechaSql);
                } else {
                    try {
                        $fechaParsed = \Carbon\Carbon::parse($buscar)->toDateString();
                        $query->orWhereDate('fecha', $fechaParsed);
                    } catch (\Throwable $e) {
                        // No es una fecha válida, se ignora silenciosamente
                    }
                }
            });
        }

        $bolsas = $bolsasQuery
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $bolsas->getCollection()->transform(function (Bolsa $bolsa) {
            $bolsa->ui_saldo_disponible = $bolsa->saldoDisponible();
            return $bolsa;
        });

        return view('bolsas.index', [
            'bolsas' => $bolsas,
            'buscar' => $buscar,
            'verAbiertas' => $verAbiertas,
            'verCerradas' => $verCerradas,
            'titulo' => 'Bolsas',
            'breadcrumb' => 'Gestión de bolsas',
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('pedidos.manage');

        $proyectoId = $this->resolveActiveProyectoId($request);

        $clientes = Cliente::query()
            ->where('proyecto_id', $proyectoId)
            ->orderBy('empresa_nombre')
            ->get(['id', 'empresa_nombre']);

        return view('bolsas.create', [
            'clientes' => $clientes,
            'tipoBolsaDefault' => (string) date('Y'),
            'titulo' => 'Nueva bolsa',
            'breadcrumb' => 'Crear bolsa',
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('pedidos.manage');

        $proyectoId = $this->resolveActiveProyectoId($request);

        $validated = $request->validate([
            'id_cliente' => [
                'required',
                'integer',
                Rule::exists('clientes', 'id')->where(fn ($query) => $query->where('proyecto_id', $proyectoId)),
            ],
            'numero' => 'nullable|string|max:80',
            'tipo_bolsa' => 'nullable|string|max:60',
            'total' => 'required|numeric|min:0|max:1000000000',
            'fecha' => 'nullable|date',
        ]);

        $bolsa = Bolsa::create([
            'id_cliente' => $validated['id_cliente'],
            'proyecto_id' => $proyectoId,
            'numero' => $validated['numero'] ?? null,
            'tipo_bolsa' => mb_strtoupper(trim((string) ($validated['tipo_bolsa'] ?? '')), 'UTF-8') ?: (string) date('Y'),
            'total' => round((float) $validated['total'], 2),
            'fecha' => $validated['fecha'] ?? now()->toDateString(),
        ]);

        return redirect()->route('bolsas.show', $bolsa)->with('success', 'Bolsa creada correctamente');
    }

    public function edit(Request $request, Bolsa $bolsa)
    {
        $this->authorize('pedidos.manage');

        $proyectoId = $this->resolveActiveProyectoId($request);
        if ((int) $bolsa->proyecto_id !== $proyectoId) {
            abort(404);
        }

        $minimoPartidas = round((float) $bolsa->partidas()->sum('total'), 2);
        $clientes = Cliente::query()
            ->where('proyecto_id', $proyectoId)
            ->orderBy('empresa_nombre')
            ->get(['id', 'empresa_nombre']);

        return view('bolsas.edit', compact('bolsa', 'clientes', 'minimoPartidas') + [
            'titulo' => 'Editar bolsa',
            'breadcrumb' => 'Editar bolsa',
        ]);
    }

    public function update(Request $request, Bolsa $bolsa)
    {
        $this->authorize('pedidos.manage');

        $proyectoId = $this->resolveActiveProyectoId($request);
        if ((int) $bolsa->proyecto_id !== $proyectoId) {
            abort(404);
        }

        $validated = $request->validate([
            'id_cliente' => [
                'required',
                'integer',
                Rule::exists('clientes', 'id')->where(fn ($query) => $query->where('proyecto_id', $proyectoId)),
            ],
            'numero' => 'nullable|string|max:80',
            'tipo_bolsa' => 'nullable|string|max:60',
            'total' => 'required|numeric|min:0|max:1000000000',
            'fecha' => 'nullable|date',
        ]);

        $sumaPartidas = round((float) $bolsa->partidas()->sum('total'), 2);
        $nuevoTotal = (float) $validated['total'];
        if ($sumaPartidas > 0 && $nuevoTotal > 0 && $nuevoTotal < $sumaPartidas - 0.001) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'total' => 'El tope de la bolsa no puede ser inferior a la suma de las partidas que ya tiene dentro (' . number_format($sumaPartidas, 2, ',', '.') . ' €). Puedes aumentarlo sin límite, pero no bajarlo de ese importe.',
            ]);
        }

        if ($bolsa->partidas()->exists() && (int) $validated['id_cliente'] !== (int) $bolsa->id_cliente) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'id_cliente' => 'No puedes cambiar el cliente de una bolsa que ya tiene partidas vinculadas.',
            ]);
        }

        $bolsa->update([
            'id_cliente' => $validated['id_cliente'],
            'numero' => $validated['numero'] ?? null,
            'tipo_bolsa' => mb_strtoupper(trim((string) ($validated['tipo_bolsa'] ?? '')), 'UTF-8') ?: (string) date('Y'),
            'total' => round($nuevoTotal, 2),
            'fecha' => $validated['fecha'] ?? now()->toDateString(),
        ]);

        return redirect()->route('bolsas.show', $bolsa)->with('success', 'Bolsa global actualizada correctamente.');
    }

    public function show(Bolsa $bolsa)
    {
        $this->authorize('pedidos.view');

        $proyectoId = $this->resolveActiveProyectoId(request());
        if ((int) $bolsa->proyecto_id !== $proyectoId) {
            abort(404);
        }

        $bolsa->loadMissing(['cliente', 'partidas.albaranesPivot']);

        $fungibles = $bolsa->bolsasFungibles()->with(['partidas.albaranesPivot'])->get(['id', 'numero', 'tipo_bolsa', 'total']);
        $activities = \Spatie\Activitylog\Models\Activity::with('causer')
            ->forSubject($bolsa)
            ->latest()
            ->limit(8)
            ->get();

        return view('bolsas.show', [
            'bolsa' => $bolsa,
            'saldoDisponible' => $bolsa->saldoDisponible(),
            'bolsasFungibles' => $fungibles,
            'activities' => $activities,
            'titulo' => 'Bolsa ' . ($bolsa->numero ?: '#' . $bolsa->id),
            'breadcrumb' => 'Detalle de bolsa',
        ]);
    }

    // --- NUEVO: Función para borrar la bolsa entera si está vacía ---
    public function destroy(Request $request, Bolsa $bolsa)
    {
        $this->authorize('pedidos.delete');

        $proyectoId = $this->resolveActiveProyectoId($request);
        if ((int) $bolsa->proyecto_id !== $proyectoId) {
            abort(404);
        }

        if ($bolsa->partidas()->count() > 0) {
            return redirect()->back()->with('error', 'No se puede borrar la bolsa porque tiene partidas vinculadas. Desvincúlalas primero.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($bolsa) {
            $bolsa->lineasOrigen()->update(['bolsa_origen_id' => null]);
            $bolsa->delete();
        });

        return redirect()->route('bolsas.index')->with('success', 'Bolsa global eliminada correctamente.');
    }

    // --- NUEVO: Función para desvincular una partida específica ---
    public function quitarPartida(Request $request, Bolsa $bolsa, PedidoCliente $pedido)
    {
        $this->authorize('pedidos.manage');

        $proyectoId = $this->resolveActiveProyectoId($request);
        if ((int) $bolsa->proyecto_id !== $proyectoId || (int) $pedido->proyecto_id !== $proyectoId) {
            abort(404);
        }

        if ((int) $pedido->bolsa_id === (int) $bolsa->id) {
            $pedido->update(['bolsa_id' => null]);
            return redirect()->back()->with('success', 'Partida desvinculada. Ahora operará como una "Bolsa Suelta".');
        }

        return redirect()->back()->with('error', 'La partida no pertenece a esta bolsa.');
    }

    public function fungiblesDisponibles(Request $request)
    {
        $this->authorize('pedidos.view');
        $proyectoId = $this->resolveActiveProyectoId($request);
        $idCliente = (int) $request->query('id_cliente', 0);
        $tipoBolsa = trim((string) $request->query('tipo_bolsa', ''));
        $excluirBolsaId = (int) $request->query('excluir_bolsa_id', 0);

        if ($idCliente <= 0 || $tipoBolsa === '') {
            return response()->json(['bolsas' => []]);
        }

        $bolsas = Bolsa::query()
            ->where('proyecto_id', $proyectoId)
            ->where('id_cliente', $idCliente)
            ->whereRaw('UPPER(TRIM(tipo_bolsa)) = ?', [mb_strtoupper($tipoBolsa, 'UTF-8')])
            ->when($excluirBolsaId > 0, fn ($query) => $query->where('id', '!=', $excluirBolsaId))
            ->with(['partidas.albaranesPivot'])
            ->get(['id', 'numero', 'tipo_bolsa', 'total']);

        $bolsas->each(fn (Bolsa $bolsa) => $bolsa->setAttribute('saldo_disponible', $bolsa->saldoDisponible()));

        return response()->json(['bolsas' => $bolsas]);
    }

    public function lineaDesglose(Request $request, LineaBolsa $linea)
    {
        $this->authorize('pedidos.view');
        $proyectoId = $this->resolveActiveProyectoId($request);
        if ((int) $linea->proyecto_id !== $proyectoId) { abort(404); }

        return response()->json([
            'linea_id' => $linea->id,
            'modo_consumo' => $linea->modo_consumo,
            'importe_disponible' => $linea->importeDisponible(),
            'partidas' => (new LineaBolsaService())->desglosePorPartida($linea),
        ]);
    }
}