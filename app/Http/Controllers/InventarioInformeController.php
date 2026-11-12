<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\Clase;
use App\Models\InventarioInforme;
use App\Models\InventarioMovimiento;
use App\Models\InventarioMovimientoLinea;
use App\Models\InventarioStockAlmacen;
use App\Models\InventarioVariante;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class InventarioInformeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:inventario.view');
    }

    public function index(Request $request)
    {
        $proyectoId = $this->resolveActiveProyectoId($request);

        $tab = (string) $request->query('tab', 'salidas');
        if (!in_array($tab, ['salidas', 'entradas', 'traslados', 'stock'], true)) {
            $tab = 'salidas';
        }

        // Filtros del configurador del informe
        $desde = $request->query('desde');
        $hasta = $request->query('hasta');
        $claseId = $request->query('clase_id');
        $almacenId = $request->query('almacen_id');
        $buscar = trim((string) $request->query('q', ''));

        // Buscador del Historial de Informes Generados (parte inferior)
        $buscarHistorial = trim((string) $request->query('historial_q', ''));
        $fechaHistorial = $request->query('historial_fecha');

        $clases = Clase::where('proyecto_id', $proyectoId)->orderBy('nombre')->pluck('nombre', 'id');
        $almacenes = Almacen::where('proyecto_id', $proyectoId)->where('activo', true)->orderBy('nombre')->get(['id', 'nombre']);

        // Obtener filas del informe actual para vista previa
        $filasInforme = $this->construirDatosInforme($proyectoId, $tab, [
            'desde' => $desde,
            'hasta' => $hasta,
            'clase_id' => $claseId,
            'almacen_id' => $almacenId,
            'q' => $buscar,
        ]);

        // Comprobar si ya existe hoy un informe idéntico con el mismo estado de stock
        $snapshotHash = $this->calcularSnapshotHash($proyectoId, $tab, [
            'desde' => $desde,
            'hasta' => $hasta,
            'clase_id' => $claseId,
            'almacen_id' => $almacenId,
            'q' => $buscar,
        ]);

        $informeExistenteHoy = InventarioInforme::where('proyecto_id', $proyectoId)
            ->where('snapshot_hash', $snapshotHash)
            ->whereDate('created_at', now()->toDateString())
            ->latest()
            ->first();

        // Listado histórico de informes generados por el equipo
        $historialInformes = InventarioInforme::where('proyecto_id', $proyectoId)
            ->when($buscarHistorial !== '', function ($q) use ($buscarHistorial) {
                $q->where(function ($sub) use ($buscarHistorial) {
                    $sub->where('solicitante_nombre', 'like', "%{$buscarHistorial}%")
                        ->orWhere('archivo_nombre', 'like', "%{$buscarHistorial}%")
                        ->orWhere('tipo_informe', 'like', "%{$buscarHistorial}%");
                });
            })
            ->when(!empty($fechaHistorial), fn ($q) => $q->whereDate('created_at', $fechaHistorial))
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'hist_page')
            ->withQueryString();

        return view('inventario.informes.index', compact(
            'tab',
            'clases',
            'almacenes',
            'filasInforme',
            'informeExistenteHoy',
            'historialInformes',
            'desde',
            'hasta',
            'claseId',
            'almacenId',
            'buscar',
            'buscarHistorial',
            'fechaHistorial'
        ));
    }

    /**
     * Genera el CSV o reutiliza el que ya generó otro compañero hoy si el stock/Kardex no ha variado.
     */
    public function exportar(Request $request)
    {
        $proyectoId = $this->resolveActiveProyectoId($request);
        $tab = (string) $request->input('tab', 'salidas');
        $forzarNuevo = $request->boolean('forzar_nuevo', false);

        $filtros = [
            'desde' => $request->input('desde'),
            'hasta' => $request->input('hasta'),
            'clase_id' => $request->input('clase_id'),
            'almacen_id' => $request->input('almacen_id'),
            'q' => trim((string) $request->input('q', '')),
        ];

        $snapshotHash = $this->calcularSnapshotHash($proyectoId, $tab, $filtros);

        // 1. Si ya existe hoy con idéntico estado de inventario, lo servimos directo
        if (!$forzarNuevo) {
            $existente = InventarioInforme::where('proyecto_id', $proyectoId)
                ->where('snapshot_hash', $snapshotHash)
                ->whereDate('created_at', now()->toDateString())
                ->latest()
                ->first();

            if ($existente && Storage::disk('local')->exists($existente->archivo_path)) {
                $contenidoExistente = Storage::disk('local')->get($existente->archivo_path);
                return $this->respuestaDescargaCsv($contenidoExistente, $existente->archivo_nombre);
            }
        }

        // 2. Construir datos y generar CSV en memoria
        $filas = $this->construirDatosInforme($proyectoId, $tab, $filtros);
        $cabeceras = $this->obtenerCabecerasCsv($tab);

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF"); // BOM UTF-8 para Excel
        fputcsv($handle, $cabeceras, ';');

        foreach ($filas as $fila) {
            fputcsv($handle, array_values((array) $fila), ';');
        }

        rewind($handle);
        $csvContent = (string) stream_get_contents($handle);
        fclose($handle);

        $fechaStr = now()->format('Ymd_His');
        $nombreArchivo = "informe_{$tab}_{$fechaStr}.csv";
        $rutaStorage = "informes_inventario/proyecto_{$proyectoId}/{$nombreArchivo}";

        // Guardar copia en disco para el historial del equipo
        Storage::disk('local')->put($rutaStorage, $csvContent);

        $user = $request->user();
        $solicitante = $user?->name ?? 'Usuario';

        InventarioInforme::create([
            'proyecto_id' => $proyectoId,
            'user_id' => $user?->id,
            'solicitante_nombre' => $solicitante,
            'tipo_informe' => $tab,
            'filtros' => array_filter($filtros, fn ($v) => $v !== null && $v !== ''),
            'snapshot_hash' => $snapshotHash,
            'archivo_path' => $rutaStorage,
            'archivo_nombre' => $nombreArchivo,
            'total_filas' => $filas->count(),
            'datos_preview' => $filas->take(250)->values()->all(),
        ]);

        return $this->respuestaDescargaCsv($csvContent, $nombreArchivo);
    }

    /**
     * Descarga un informe ya existente de la tabla de historial.
     */
    public function descargarHistorico(Request $request, int $id)
    {
        $proyectoId = $this->resolveActiveProyectoId($request);
        $informe = InventarioInforme::where('proyecto_id', $proyectoId)->findOrFail($id);

        if (Storage::disk('local')->exists($informe->archivo_path)) {
            $contenido = Storage::disk('local')->get($informe->archivo_path);
            return $this->respuestaDescargaCsv($contenido, $informe->archivo_nombre);
        }

        // Fallback de seguridad: si el archivo físico se borró pero tenemos los datos en BD, lo reconstruye al vuelo
        if (!empty($informe->datos_preview)) {
            $cabeceras = $this->obtenerCabecerasCsv($informe->tipo_informe);
            $handle = fopen('php://temp', 'r+');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $cabeceras, ';');
            foreach ($informe->datos_preview as $fila) {
                fputcsv($handle, array_values((array) $fila), ';');
            }
            rewind($handle);
            $csvContent = (string) stream_get_contents($handle);
            fclose($handle);

            return $this->respuestaDescargaCsv($csvContent, $informe->archivo_nombre);
        }

        return back()->with('error', 'El archivo físico de este informe ya no se encuentra en el servidor.');
    }

    /**
     * Envía el CSV con cabeceras limpias sin depender de Storage::download() ni cortar la conexión en Windows.
     */
    private function respuestaDescargaCsv(string $csvContent, string $nombreArchivo)
    {
        // Limpiar cualquier buffer de salida previo (evita que un espacio en blanco corrompa la descarga)
        if (ob_get_length()) {
            ob_end_clean();
        }

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $nombreArchivo . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }

    /**
     * Construye la colección de filas exactas según las columnas que definiste para cada uno de los 4 informes.
     */
    private function construirDatosInforme(int $proyectoId, string $tab, array $filtros): Collection
    {
        // INFORME 4: ESTADO ACTUAL DEL INVENTARIO (STOCK REAL)
        if ($tab === 'stock') {
            $query = InventarioVariante::query()
                ->where('proyecto_id', $proyectoId)
                ->where('activo', true)
                ->where(function ($q) {
                    $q->whereNull('descripcion')
                      ->orWhere('descripcion', 'not like', '%[OBSOLETO]%');
                })
                ->with(['articulo.clase', 'stockAlmacenes.almacen'])
                ->when(!empty($filtros['clase_id']), function ($q) use ($filtros) {
                    $q->where(function ($sub) use ($filtros) {
                        $sub->where('clase_id', $filtros['clase_id'])
                            ->orWhereHas('articulo', fn ($qa) => $qa->where('clase_id', $filtros['clase_id']));
                    });
                })
                ->when(!empty($filtros['almacen_id']), function ($q) use ($filtros) {
                    $q->whereHas('stockAlmacenes', fn ($qs) => $qs->where('almacen_id', $filtros['almacen_id']));
                })
                ->when(!empty($filtros['q']), function ($q) use ($filtros) {
                    $term = $filtros['q'];
                    $q->where(function ($sub) use ($term) {
                        $sub->where('codigo', 'like', "%{$term}%")
                            ->orWhere('nombre', 'like', "%{$term}%")
                            ->orWhere('referencia_proveedor', 'like', "%{$term}%")
                            ->orWhere('nombre_proveedor', 'like', "%{$term}%")
                            ->orWhereHas('articulo', fn ($qa) => $qa->where('nombre', 'like', "%{$term}%")
                                ->orWhere('referencia_proveedor', 'like', "%{$term}%")
                                ->orWhere('nombre_proveedor', 'like', "%{$term}%"));
                    });
                });

            return $query->orderBy('codigo')->get()->map(function (InventarioVariante $v) use ($filtros) {
                $articulo = $v->articulo;
                $stocks = $v->stockAlmacenes;
                if (!empty($filtros['almacen_id'])) {
                    $stocks = $stocks->where('almacen_id', (int) $filtros['almacen_id']);
                }

                $cantidad = (int) $stocks->sum('cantidad');
                $claseNombre = $articulo?->clase?->nombre ?? $v->claseRelacion?->nombre ?? 'Sin clase';
                $nombreBase = $articulo?->nombre ?: ($v->nombre ?: 'Artículo');

                return [
                    'referencia_proveedor' => $v->referencia_proveedor ?: ($articulo?->referencia_proveedor ?: 'S/R'),
                    'sku_interno'          => $v->sku ?? $v->codigo ?? 'S/C',
                    'tipo_articulo'        => "[{$claseNombre}] {$nombreBase}",
                    'variantes'            => $this->formatearVariantesOrdenSku($v),
                    'talla'                => $this->extraerTalla($v->atributos),
                    'cantidad'             => $cantidad,
                ];
            });
        }

        // INFORMES 1, 2 Y 3: MOVIMIENTOS DEL KARDEX (SALIDAS, ENTRADAS, TRASLADOS)
        $tipoMovimiento = match ($tab) {
            'salidas'   => 'SALIDA',
            'entradas'  => 'ENTRADA',
            'traslados' => 'TRASLADO',
            default     => 'SALIDA',
        };

        $lineasQuery = InventarioMovimientoLinea::query()
            ->with(['movimiento.personal', 'movimiento.usuario', 'movimiento.almacenOrigen', 'movimiento.almacenDestino', 'variante.articulo.clase'])
            ->whereHas('movimiento', function ($qm) use ($proyectoId, $tipoMovimiento, $filtros) {
                $qm->where('proyecto_id', $proyectoId)
                   ->where('tipo', $tipoMovimiento)
                   ->where('estado', 'REGISTRADO')
                   ->when(!empty($filtros['desde']), fn ($q) => $q->whereDate('created_at', '>=', $filtros['desde']))
                   ->when(!empty($filtros['hasta']), fn ($q) => $q->whereDate('created_at', '<=', $filtros['hasta']))
                   ->when(!empty($filtros['almacen_id']), function ($q) use ($filtros) {
                       $q->where(fn ($sub) => $sub->where('almacen_origen_id', $filtros['almacen_id'])
                           ->orWhere('almacen_destino_id', $filtros['almacen_id']));
                   });
            })
            ->when(!empty($filtros['clase_id']), function ($ql) use ($filtros) {
                $ql->whereHas('variante', function ($qv) use ($filtros) {
                    $qv->where('clase_id', $filtros['clase_id'])
                       ->orWhereHas('articulo', fn ($qa) => $qa->where('clase_id', $filtros['clase_id']));
                });
            })
            ->when(!empty($filtros['q']), function ($ql) use ($filtros) {
                $term = $filtros['q'];
                $ql->where(function ($sub) use ($term) {
                    $sub->whereHas('variante', fn ($qv) => $qv->where('codigo', 'like', "%{$term}%")
                            ->orWhere('nombre', 'like', "%{$term}%")
                            ->orWhere('referencia_proveedor', 'like', "%{$term}%")
                            ->orWhere('nombre_proveedor', 'like', "%{$term}%"))
                        ->orWhereHas('movimiento.personal', fn ($qp) => $qp->where('name', 'like', "%{$term}%")
                            ->orWhere('apellido', 'like', "%{$term}%"))
                        ->orWhereHas('movimiento', fn ($qm) => $qm->where('documento_referencia', 'like', "%{$term}%")
                            ->orWhere('numero_documento', 'like', "%{$term}%"));
                });
            });

        $lineas = $lineasQuery->orderByDesc('id')->get();

        $filasMapeadas = $lineas->map(function (InventarioMovimientoLinea $linea) use ($tab) {
            $mov = $linea->movimiento;
            $v = $linea->variante;
            $art = $v?->articulo;

            $claseNombre = $art?->clase?->nombre ?? 'General';
            $nombreArticulo = $art?->nombre ?: ($v?->nombre ?: 'Artículo');
            $tipoArticuloStr = "[{$claseNombre}] {$nombreArticulo}";
            $referenciaArticulo = $v?->sku ?: ($v?->codigo ?: ($v?->referencia_proveedor ?: 'S/C'));
            $tallaStr = $this->extraerTalla($v?->atributos);
            $fechaStr = $mov?->created_at ? $mov->created_at->format('d/m/Y H:i') : '—';
            $timestamp = $mov?->created_at ? $mov->created_at->getTimestamp() : 0;

            if ($tab === 'salidas') {
                $sujetoRaw = $mov?->personal
                    ? trim($mov->personal->name . ' ' . $mov->personal->apellido)
                    : ($mov?->observaciones ?: ($mov?->usuario?->name ?? 'Sin asignar'));

                // Limpiar espacios múltiples para que "Jan  Espatero" y "Jan Espatero" sean idénticos
                $sujeto = preg_replace('/\s+/', ' ', trim((string) $sujetoRaw));

                return [
                    'sujeto'              => $sujeto,
                    'fecha'               => $fechaStr,
                    'tipo_articulo'       => $tipoArticuloStr,
                    'referencia_articulo' => $referenciaArticulo,
                    'talla'               => $tallaStr,
                    'cantidad'            => (int) $linea->cantidad,
                    '_sort_timestamp'     => $timestamp,
                ];
            }

            if ($tab === 'entradas') {
                $sujeto = $v?->nombre_proveedor
                    ?: ($art?->nombre_proveedor ?: ($mov?->documento_referencia ?: 'Proveedor no especificado'));

                return [
                    'sujeto'              => trim((string) $sujeto),
                    'fecha'               => $fechaStr,
                    'almacen_destino'     => $mov?->almacenDestino?->nombre ?? '—',
                    'tipo_articulo'       => $tipoArticuloStr,
                    'referencia_articulo' => $referenciaArticulo,
                    'talla'               => $tallaStr,
                    'cantidad'            => (int) $linea->cantidad,
                ];
            }

            // TRASLADOS: Incluye Almacén que Sale y Almacén que Entra
            $sujeto = $mov?->personal
                ? trim($mov->personal->name . ' ' . $mov->personal->apellido)
                : ($mov?->usuario?->name ?? 'Sistema');

            return [
                'sujeto'              => trim((string) $sujeto),
                'fecha'               => $fechaStr,
                'almacen_origen'      => $mov?->almacenOrigen?->nombre ?? '—',
                'almacen_destino'     => $mov?->almacenDestino?->nombre ?? '—',
                'tipo_articulo'       => $tipoArticuloStr,
                'referencia_articulo' => $referenciaArticulo,
                'talla'               => $tallaStr,
                'cantidad'            => (int) $linea->cantidad,
            ];
        });

        // ORDENACIÓN ESTRICTA PARA SALIDAS:
        // 1º Por Nombre del Trabajador (A-Z, ignorando mayúsculas/minúsculas y tildes)
        // 2º Dentro del mismo trabajador, por Fecha de Salida (de más reciente a más antigua)
        if ($tab === 'salidas') {
            $filasArray = $filasMapeadas->all();

            usort($filasArray, function (array $a, array $b): int {
                $nombreA = $this->normalizarTextoOrden($a['sujeto'] ?? '');
                $nombreB = $this->normalizarTextoOrden($b['sujeto'] ?? '');

                // Primero comparamos el nombre del trabajador
                $cmpNombre = strcmp($nombreA, $nombreB);
                if ($cmpNombre !== 0) {
                    return $cmpNombre;
                }

                // Si es el mismo trabajador, ordenamos por fecha (más reciente primero)
                return ($b['_sort_timestamp'] ?? 0) <=> ($a['_sort_timestamp'] ?? 0);
            });

            // Quitamos la columna auxiliar _sort_timestamp para que no salga en el CSV ni en la tabla
            return collect($filasArray)->map(function (array $fila) {
                unset($fila['_sort_timestamp']);
                return $fila;
            })->values();
        }

        return $filasMapeadas->values();
    }

    private function obtenerCabecerasCsv(string $tab): array
    {
        return match ($tab) {
            'salidas'   => ['Nombre Trabajador', 'Fecha de Salida', 'Tipo de Artículo', 'Referencia del Artículo', 'Talla', 'Cantidad'],
            'entradas'  => ['Nombre de Proveedor', 'Fecha de Entrada', 'Almacén que Recibe', 'Tipo de Artículo', 'Referencia del Artículo', 'Talla', 'Cantidad'],
            'traslados' => ['Nombre Solicitante', 'Fecha de Traslado', 'Almacén que Sale', 'Almacén que Entra', 'Tipo de Artículo', 'Referencia del Artículo', 'Talla', 'Cantidad'],
            'stock'     => ['Número de Referencia Proveedor', 'SKU Interno', 'Tipo de Artículo', 'Variantes (Orden SKU)', 'Talla', 'Cantidad'],
            default     => [],
        };
    }

    private function formatearVariantesOrdenSku(InventarioVariante $variante): string
    {
        $atributos = $variante->atributos ?? [];
        if (empty($atributos)) {
            return 'Estándar (Sin variantes)';
        }

        // Orden de tipos definido en la variante o en el artículo padre
        $ordenTipos = $variante->tipos_atributos ?: ($variante->articulo?->tipos_atributos ?: array_keys($atributos));
        if (!is_array($ordenTipos)) {
            $ordenTipos = array_keys($atributos);
        }

        $partesOrdenadas = [];
        $clavesProcesadas = [];

        foreach ($ordenTipos as $tipoBuscado) {
            $tipoLimpio = mb_strtolower(trim((string) $tipoBuscado), 'UTF-8');
            foreach ($atributos as $k => $v) {
                if (mb_strtolower(trim((string) $k), 'UTF-8') === $tipoLimpio) {
                    $valStr = is_array($v) ? implode(', ', $v) : (string) $v;
                    $partesOrdenadas[] = ucfirst(trim((string) $k)) . ': ' . $valStr;
                    $clavesProcesadas[] = $k;
                    break;
                }
            }
        }

        // Por si algún atributo no estaba listado en tipos_atributos, lo añade al final manteniendo su orden en el JSON
        foreach ($atributos as $k => $v) {
            if (!in_array($k, $clavesProcesadas, true)) {
                $valStr = is_array($v) ? implode(', ', $v) : (string) $v;
                $partesOrdenadas[] = ucfirst(trim((string) $k)) . ': ' . $valStr;
            }
        }

        return implode(' / ', $partesOrdenadas);
    }

    /**
     * Extrae la talla desde el array JSON de atributos de la variante.
     */
    private function extraerTalla(?array $atributos): string
    {
        if (empty($atributos)) {
            return 'Única / N/A';
        }

        foreach ($atributos as $clave => $valores) {
            $claveLimpia = mb_strtolower(trim((string) $clave), 'UTF-8');
            if (in_array($claveLimpia, ['talla', 'tallas', 'size', 'medida', 'numero', 'número'], true)) {
                return is_array($valores) ? implode(', ', $valores) : (string) $valores;
            }
        }

        return 'Única / N/A';
    }

    /**
     * Normaliza el nombre del trabajador (minúsculas, sin acentos y sin espacios dobles)
     * para garantizar que todos los registros del mismo trabajador queden juntos.
     */
    private function normalizarTextoOrden(string $texto): string
    {
        $limpio = preg_replace('/\s+/', ' ', trim($texto)) ?? '';
        $limpio = mb_strtolower($limpio, 'UTF-8');

        return strtr($limpio, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
            'ñ' => 'n',
        ]);
    }

    /**
     * Calcula una huella única combinando los filtros + el estado exacto del Kardex y Stock actual.
     */
    private function calcularSnapshotHash(int $proyectoId, string $tab, array $filtros): string
    {
        $ultimoMovTimestamp = (string) InventarioMovimiento::where('proyecto_id', $proyectoId)->max('updated_at');
        $totalMovimientos = (int) InventarioMovimiento::where('proyecto_id', $proyectoId)->count();
        $sumaStock = (int) InventarioStockAlmacen::whereHas('almacen', fn ($q) => $q->where('proyecto_id', $proyectoId))->sum('cantidad');

        $payload = json_encode([
            'schema_v'    => 4, 
            'proyecto_id' => $proyectoId,
            'tab'         => $tab,
            'filtros'     => $filtros,
            'last_mov'    => $ultimoMovTimestamp,
            'count_mov'   => $totalMovimientos,
            'sum_stock'   => $sumaStock,
        ]);

        return hash('sha256', (string) $payload);
    }
}