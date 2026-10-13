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

        $bolsas = Bolsa::query()
            ->with('cliente')
            ->withCount('partidas') // <-- NUEVO: Contamos las partidas para permitir borrar
            ->where('proyecto_id', $proyectoId)
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
            'tipo_bolsa' => trim((string) ($validated['tipo_bolsa'] ?? '')) ?: (string) date('Y'),
            'total' => round((float) $validated['total'], 4),
            'fecha' => $validated['fecha'] ?? now()->toDateString(),
        ]);

        return redirect()->route('bolsas.show', $bolsa)->with('success', 'Bolsa creada correctamente');
    }

    public function show(Bolsa $bolsa)
    {
        $this->authorize('pedidos.view');

        $proyectoId = $this->resolveActiveProyectoId(request());
        if ((int) $bolsa->proyecto_id !== $proyectoId) {
            abort(404);
        }

        $bolsa->loadMissing(['cliente', 'partidas']);

        $fungibles = $bolsa->bolsasFungibles()->get(['id', 'numero', 'tipo_bolsa', 'total']);

        return view('bolsas.show', [
            'bolsa' => $bolsa,
            'saldoDisponible' => $bolsa->saldoDisponible(),
            'bolsasFungibles' => $fungibles,
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

        $bolsa->delete();

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

        if ($pedido->bolsa_id === $bolsa->id) {
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
            ->where('tipo_bolsa', $tipoBolsa)
            ->when($excluirBolsaId > 0, fn ($query) => $query->where('id', '!=', $excluirBolsaId))
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