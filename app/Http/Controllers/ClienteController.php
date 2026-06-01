<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\PedidoCliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $proyectoId = $this->resolveActiveProyectoId(request());

        $buscar = trim((string) request('buscar', ''));
        $estado = (string) request('estado', 'todos');

        $clientesQuery = Cliente::query()
            ->where('proyecto_id', $proyectoId)
            ->withCount(['albaranes', 'presupuestos', 'pedidosClientes']);

        if ($buscar !== '') {
            $clientesQuery->where(function ($query) use ($buscar) {
                $query->where('empresa_nombre', 'like', '%' . $buscar . '%')
                    ->orWhere('cif_nif', 'like', '%' . $buscar . '%')
                    ->orWhere('localidad', 'like', '%' . $buscar . '%')
                    ->orWhere('persona_contacto', 'like', '%' . $buscar . '%');
            });
        }

        if ($estado === 'favoritos') {
            $clientesQuery->where('favorito', true);
        }

        $clientes = $clientesQuery
            ->orderBy('empresa_nombre')
            ->paginate(10)
            ->withQueryString();

        return view('clientes.index', compact('clientes', 'buscar', 'estado'));
    }

    public function toggleFavorito(Request $request, Cliente $cliente)
    {
        $proyectoId = $this->resolveActiveProyectoId($request);

        if ((int) $cliente->proyecto_id !== $proyectoId) {
            abort(404);
        }

        $cliente->update([
            'favorito' => ! (bool) $cliente->favorito,
        ]);

        $estado = (string) $request->input('estado', 'todos');
        $buscar = trim((string) $request->input('buscar', ''));

        return redirect()->route('clientes.index', [
            'estado' => $estado,
            'buscar' => $buscar,
        ])->with('success', $cliente->favorito
            ? 'Cliente marcado como favorito'
            : 'Cliente eliminado de favoritos');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('clientes.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $proyectoId = $this->resolveActiveProyectoId($request);

        $validated = $request->validate([
            'empresa_nombre' => 'required|string|max:255',
            'cif_nif' => 'required|unique:clientes,cif_nif|max:20',
            'direccion' => 'required|string|max:255',
            'localidad' => 'required|string|max:100',
            'codigo_postal' => 'required|string|max:10',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'persona_contacto' => 'nullable|string|max:100',
        ]);

        $validated['proyecto_id'] = $proyectoId;

        Cliente::create($validated);

        return redirect()->route('clientes.index')->with('success', 'Cliente creado exitosamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(Cliente $cliente)
    {
        $proyectoId = $this->resolveActiveProyectoId(request());

        if ((int) $cliente->proyecto_id !== $proyectoId) {
            abort(404);
        }

        $historialActivo = (string) request('historial', 'presupuestos');

        if (!in_array($historialActivo, ['presupuestos', 'pedidos', 'albaranes'], true)) {
            $historialActivo = 'presupuestos';
        }

        $busqueda = trim((string) request('busqueda', ''));
        $fechaDesde = trim((string) request('fecha_desde', ''));
        $fechaHasta = trim((string) request('fecha_hasta', ''));
        $estadoFiltro = (string) request('estado', 'todos');
        $hoy = now();
        $limitePendiente = $hoy->copy()->subDays(15)->toDateString();
        $limiteEntregado = $hoy->copy()->subDays(45)->toDateString();

        $pedidosPorNumero = $cliente->pedidosClientes()
            ->whereNotNull('numero_pedido')
            ->get()
            ->keyBy(fn (PedidoCliente $pedido) => trim((string) $pedido->numero_pedido));

        $pedidosPorOt = PedidoCliente::query()
            ->where('id_cliente', $cliente->id)
            ->whereNotNull('ot')
            ->get()
            ->keyBy(fn (PedidoCliente $pedido) => trim((string) $pedido->ot));

        $resolverTotalDesdeArticulos = static function (?array $listaArticulos): ?float {
            if (!is_array($listaArticulos) || $listaArticulos === []) {
                return null;
            }

            $total = 0.0;
            $hayValores = false;

            foreach ($listaArticulos as $articulo) {
                if (!is_array($articulo)) {
                    continue;
                }

                if (isset($articulo['total']) && is_numeric($articulo['total'])) {
                    $total += (float) $articulo['total'];
                    $hayValores = true;
                    continue;
                }

                $cantidad = (float) ($articulo['cantidad'] ?? $articulo['qty'] ?? 0);
                $precio = (float) ($articulo['precio_unitario'] ?? $articulo['precio'] ?? $articulo['price'] ?? 0);

                if ($cantidad > 0 && $precio > 0) {
                    $total += $cantidad * $precio;
                    $hayValores = true;
                }
            }

            return $hayValores ? round($total, 2) : null;
        };

        $presupuestos = null;
        $pedidos = null;
        $albaranes = null;

        if ($historialActivo === 'presupuestos') {
            $presupuestosQuery = $cliente->presupuestos()
                ->orderByDesc('fecha')
                ->orderByDesc('id');

            if ($busqueda !== '') {
                $presupuestosQuery->where(function ($query) use ($busqueda) {
                    $query->where('numero', 'like', '%' . $busqueda . '%')
                        ->orWhere('ot', 'like', '%' . $busqueda . '%')
                        ->orWhere('titulo', 'like', '%' . $busqueda . '%')
                        ->orWhere('documento', 'like', '%' . $busqueda . '%')
                        ->orWhere('id', 'like', '%' . $busqueda . '%');
                });
            }

            if ($fechaDesde !== '') {
                $presupuestosQuery->whereDate('fecha', '>=', $fechaDesde);
            }

            if ($fechaHasta !== '') {
                $presupuestosQuery->whereDate('fecha', '<=', $fechaHasta);
            }

            if ($estadoFiltro === 'pendiente') {
                $presupuestosQuery->whereDate('fecha', '>=', $limitePendiente);
            } elseif ($estadoFiltro === 'recibido') {
                $presupuestosQuery->whereBetween('fecha', [$limiteEntregado, $hoy->copy()->subDays(16)->toDateString()]);
            } elseif ($estadoFiltro === 'entregado') {
                $presupuestosQuery->whereDate('fecha', '<', $limiteEntregado);
            }

            $presupuestos = $presupuestosQuery->paginate(5)->withQueryString();

            $presupuestos->getCollection()->transform(function ($presupuesto) use ($hoy, $pedidosPorOt, $resolverTotalDesdeArticulos) {
                $dias = $presupuesto->fecha ? $presupuesto->fecha->diffInDays($hoy) : 999;

                if ($dias <= 15) {
                    $presupuesto->ui_estado = 'pendiente';
                    $presupuesto->ui_estado_label = 'PENDIENTE';
                } elseif ($dias <= 45) {
                    $presupuesto->ui_estado = 'recibido';
                    $presupuesto->ui_estado_label = 'RECIBIDO';
                } else {
                    $presupuesto->ui_estado = 'entregado';
                    $presupuesto->ui_estado_label = 'ENTREGADO';
                }

                $total = null;
                $ot = trim((string) ($presupuesto->ot ?? ''));

                if ($ot !== '' && $pedidosPorOt->has($ot)) {
                    $total = $resolverTotalDesdeArticulos($pedidosPorOt->get($ot)?->lista_articulos);
                }

                $presupuesto->ui_total = $total;

                return $presupuesto;
            });
        } elseif ($historialActivo === 'pedidos') {
            $pedidos = $cliente->pedidosClientes()
                ->with('presupuesto')
                ->withCount('albaranes')
                ->orderByDesc('fecha_pedido')
                ->orderByDesc('id')
                ;

            if ($busqueda !== '') {
                $pedidos->where(function ($query) use ($busqueda) {
                    $query->where('numero_pedido', 'like', '%' . $busqueda . '%')
                        ->orWhere('ot', 'like', '%' . $busqueda . '%')
                        ->orWhere('estado', 'like', '%' . $busqueda . '%')
                        ->orWhere('lista_articulos', 'like', '%' . $busqueda . '%')
                        ->orWhere('id', 'like', '%' . $busqueda . '%')
                        ->orWhereHas('presupuesto', function ($query) use ($busqueda) {
                            $query->where('numero', 'like', '%' . $busqueda . '%')
                                ->orWhere('titulo', 'like', '%' . $busqueda . '%')
                                ->orWhere('documento', 'like', '%' . $busqueda . '%');
                        });
                });
            }

            if ($fechaDesde !== '') {
                $pedidos->whereDate('fecha_pedido', '>=', $fechaDesde);
            }

            if ($fechaHasta !== '') {
                $pedidos->whereDate('fecha_pedido', '<=', $fechaHasta);
            }

            if ($estadoFiltro !== 'todos') {
                $pedidos->where('estado', $estadoFiltro);
            }

            $pedidos = $pedidos->paginate(5)->withQueryString();

            $pedidos->getCollection()->transform(function ($pedido) use ($resolverTotalDesdeArticulos) {
                $estado = trim((string) ($pedido->estado ?? 'pendiente'));
                $estado = $estado !== '' ? $estado : 'pendiente';

                $pedido->ui_estado = $estado;
                $pedido->ui_estado_label = match ($estado) {
                    'facturado' => 'FACTURADO',
                    'facturado_parcial' => 'FACTURADO PARCIAL',
                    'pendiente' => 'PENDIENTE',
                    default => strtoupper(str_replace('_', ' ', $estado)),
                };
                $pedido->ui_presupuesto_numero = $pedido->presupuesto?->numero;
                $pedido->ui_albaranes_count = (int) ($pedido->albaranes_count ?? 0);
                $pedido->ui_total = $pedido->total !== null
                    ? (float) $pedido->total
                    : $resolverTotalDesdeArticulos(is_array($pedido->lista_articulos) ? $pedido->lista_articulos : null);

                return $pedido;
            });
        } else {
            $albaranes = $cliente->albaranes()
                ->orderByDesc('fecha')
                ->orderByDesc('id');

            if ($busqueda !== '') {
                $albaranes->where(function ($query) use ($busqueda) {
                    $query->where('numero', 'like', '%' . $busqueda . '%')
                        ->orWhere('documento', 'like', '%' . $busqueda . '%')
                        ->orWhere('ot', 'like', '%' . $busqueda . '%')
                        ->orWhere('pedido_cliente', 'like', '%' . $busqueda . '%')
                        ->orWhere('titulo', 'like', '%' . $busqueda . '%')
                        ->orWhere('estado', 'like', '%' . $busqueda . '%')
                        ->orWhere('lista_articulos', 'like', '%' . $busqueda . '%')
                        ->orWhere('id', 'like', '%' . $busqueda . '%');
                });
            }

            if ($fechaDesde !== '') {
                $albaranes->whereDate('fecha', '>=', $fechaDesde);
            }

            if ($fechaHasta !== '') {
                $albaranes->whereDate('fecha', '<=', $fechaHasta);
            }

            if ($estadoFiltro !== 'todos') {
                $albaranes->where('estado', $estadoFiltro);
            }

            $albaranes = $albaranes->paginate(5)->withQueryString();

            $albaranes->getCollection()->transform(function ($albaran) use ($pedidosPorNumero) {
                $pedidoNumero = trim((string) ($albaran->pedido_cliente ?? ''));
                $pedidoRelacionado = $pedidoNumero !== '' ? $pedidosPorNumero->get($pedidoNumero) : null;
                $estado = trim((string) ($albaran->estado ?? 'pendiente'));
                $estado = $estado !== '' ? $estado : 'pendiente';

                $albaran->ui_estado = $estado;
                $albaran->ui_estado_label = strtoupper(str_replace('_', ' ', $estado));
                $albaran->ui_pedido_id = $pedidoRelacionado?->id;
                $albaran->ui_pedido_numero = $pedidoRelacionado?->numero_pedido ?: ($pedidoNumero !== '' ? $pedidoNumero : null);
                $albaran->ui_total = $albaran->total !== null ? (float) $albaran->total : null;

                return $albaran;
            });
        }

        return view('clientes.show', compact(
            'cliente',
            'presupuestos',
            'pedidos',
            'albaranes',
            'estadoFiltro',
            'busqueda',
            'fechaDesde',
            'fechaHasta',
            'historialActivo'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Cliente $cliente)
    {
        $proyectoId = $this->resolveActiveProyectoId(request());

        if ((int) $cliente->proyecto_id !== $proyectoId) {
            abort(404);
        }

        return view('clientes.edit', compact('cliente'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Cliente $cliente)
    {
        $proyectoId = $this->resolveActiveProyectoId($request);

        if ((int) $cliente->proyecto_id !== $proyectoId) {
            abort(404);
        }

        $validated = $request->validate([
            'empresa_nombre' => 'required|string|max:255',
            'cif_nif' => 'required|unique:clientes,cif_nif,' . $cliente->id . '|max:20',
            'direccion' => 'required|string|max:255',
            'localidad' => 'required|string|max:100',
            'codigo_postal' => 'required|string|max:10',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'persona_contacto' => 'nullable|string|max:100',
        ]);

        $cliente->update($validated);

        return redirect()->route('clientes.index')->with('success', 'Cliente actualizado exitosamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cliente $cliente)
    {
        $proyectoId = $this->resolveActiveProyectoId(request());

        if ((int) $cliente->proyecto_id !== $proyectoId) {
            abort(404);
        }

        $albaranesCount = $cliente->albaranes()->count();
        $presupuestosCount = $cliente->presupuestos()->count();
        $pedidosCount = $cliente->pedidosClientes()->count();

        if ($albaranesCount > 0 || $presupuestosCount > 0 || $pedidosCount > 0) {
            return back()->with('error', 'Este cliente tiene documentos asociados y no puede ser borrado.');
        }

        $cliente->delete();

        return redirect()->route('clientes.index')->with('success', 'Cliente eliminado exitosamente');
    }
}
