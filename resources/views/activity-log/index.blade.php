@extends('adminlte::page')

@section('title', 'Auditoría de Sistema')

@section('content_header')
    <div class="audit-head">
        <div>
            <div class="audit-kicker">Panel de Control <span>/</span> Seguridad</div>
            <h1>Auditoría de Cambios</h1>
            <p>Trazabilidad total: descubre quién modificó cada registro, a qué hora y qué datos exactos alteró.</p>
        </div>
        <div class="audit-stats">
            <strong>{{ $activities->total() }}</strong>
            <span>Eventos registrados</span>
        </div>
    </div>
@stop

@section('content')
<style>
    :root {
        --aud-navy: #002442; --aud-ink: #191c1e; --aud-bg: #f7f9fb;
        --aud-low: #eef2f5; --aud-muted: #687681;
        --aud-green: #0d9d7b; --aud-green-bg: #e2f7ee;
        --aud-red: #c92328; --aud-red-bg: #fee1df;
        --aud-blue: #17619a; --aud-blue-bg: #dcecf8;
        --aud-amber: #dca54a; --aud-amber-bg: #fbe6c4;
    }

    .content-wrapper { background: var(--aud-bg); }
    
    /* Cabecera */
    .audit-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 20px; margin-bottom: 24px; }
    .audit-kicker { font-size: 10px; text-transform: uppercase; letter-spacing: .12em; color: #73818b; font-weight: 800; }
    .audit-kicker span { margin: 0 6px; color: #a3adb4; }
    .audit-head h1 { font-size: 32px; font-weight: 800; color: var(--aud-ink); margin: 5px 0; letter-spacing: -0.02em; }
    .audit-head p { font-size: 14px; color: var(--aud-muted); margin: 0; }
    
    .audit-stats { background: #fff; padding: 14px 20px; border-radius: 8px; box-shadow: 0 4px 16px rgba(25,28,30,0.03); text-align: right; border-left: 4px solid var(--aud-navy); }
    .audit-stats strong { display: block; font-size: 24px; color: var(--aud-ink); line-height: 1; font-weight: 900; }
    .audit-stats span { font-size: 10px; text-transform: uppercase; letter-spacing: .05em; color: var(--aud-muted); font-weight: 700; }

    /* Filtros */
    .audit-filters { background: #fff; padding: 18px; border-radius: 8px; box-shadow: 0 4px 16px rgba(25,28,30,0.03); margin-bottom: 20px; }
    .audit-filters label { font-size: 10px; text-transform: uppercase; font-weight: 800; color: #7b8991; letter-spacing: 0.05em; margin-bottom: 6px; }
    .audit-filters .form-control { background: var(--aud-low); border: 0; font-size: 12px; height: 38px; color: var(--aud-ink); font-weight: 600; border-radius: 6px; }
    .audit-filters .form-control:focus { outline: 2px solid var(--aud-blue); background: #fff; }
    
    .btn-audit { border: 0; border-radius: 6px; height: 38px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-size: 12px; font-weight: 800; padding: 0 16px; cursor: pointer; transition: 0.2s; width: 100%; text-decoration: none; }
    .btn-audit-main { background: var(--aud-navy); color: #fff; box-shadow: 0 4px 12px rgba(0,36,66,0.15); }
    .btn-audit-main:hover { background: #155785; color: #fff; }
    .btn-audit-clear { background: transparent; color: var(--aud-muted); }
    .btn-audit-clear:hover { background: var(--aud-low); color: var(--aud-red); }

    /* Tabla Soft UI */
    .audit-card { background: #fff; border-radius: 8px; box-shadow: 0 4px 16px rgba(25,28,30,0.03); overflow: hidden; }
    .audit-table { width: 100%; margin: 0; border-collapse: separate; border-spacing: 0; }
    .audit-table th { background: #fafbfc; font-size: 10px; text-transform: uppercase; letter-spacing: 0.08em; color: #7b8991; font-weight: 800; padding: 14px 18px; border-bottom: 2px solid #edf0f2; }
    .audit-table td { padding: 16px 18px; border-bottom: 1px solid #edf0f2; vertical-align: top; }
    .audit-table tbody tr:hover { background: #fdfdfe; }
    
    /* Elementos de la tabla */
    .cell-date { white-space: nowrap; }
    .cell-date strong { display: block; font-size: 12px; color: var(--aud-ink); font-weight: 800; }
    .cell-date span { font-size: 11px; color: var(--aud-muted); font-weight: 600; }
    
    .cell-user { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 700; color: var(--aud-navy); }
    .cell-user i { font-size: 18px; color: #a3adb4; }
    
    .badge-event { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; }
    .event-created { background: var(--aud-green-bg); color: var(--aud-green); }
    .event-updated { background: var(--aud-blue-bg); color: var(--aud-blue); }
    .event-deleted { background: var(--aud-red-bg); color: var(--aud-red); }
    .event-restored { background: var(--aud-amber-bg); color: var(--aud-amber); }
    
    .cell-model strong { display: block; font-size: 12px; color: var(--aud-ink); font-weight: 800; }
    .cell-model span { display: inline-block; font-size: 10px; background: var(--aud-low); padding: 2px 6px; border-radius: 4px; color: var(--aud-muted); font-weight: 700; margin-top: 4px; }
    
    /* Visor de Cambios (Diff) */
    .diff-box { display: flex; flex-direction: column; gap: 6px; }
    .diff-row { font-size: 11px; background: var(--aud-bg); border: 1px solid #edf0f2; border-radius: 6px; padding: 6px 10px; display: flex; align-items: center; flex-wrap: wrap; gap: 8px; }
    .diff-field { font-weight: 800; color: #52636d; background: #fff; padding: 2px 6px; border-radius: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); text-transform: uppercase; font-size: 9px; letter-spacing: 0.05em; }
    .diff-old { color: var(--aud-red); text-decoration: line-through; max-width: 100%; min-width: 0; white-space: normal; overflow-wrap: anywhere; }
    .diff-icon { color: #a3adb4; font-size: 10px; }
    .diff-new { color: var(--aud-green); font-weight: 800; max-width: 100%; min-width: 0; white-space: normal; overflow-wrap: anywhere; }
    .diff-value { max-width: 100%; min-width: 120px; }
    .diff-value summary { cursor: pointer; list-style: none; max-width: 100%; overflow-wrap: anywhere; }
    .diff-value summary::-webkit-details-marker { display: none; }
    .diff-value summary::before { content: '\25B6'; display: inline-block; margin-right: 5px; font-size: 9px; text-decoration: none; }
    .diff-value[open] summary::before { content: '\25BC'; }
    .diff-value .full-value { margin-top: 5px; padding: 6px 8px; background: #fff; border: 1px solid #e1e8ed; border-radius: 4px; white-space: pre-wrap; overflow-wrap: anywhere; word-break: break-word; color: inherit; text-decoration: none; font-weight: 500; }
    
    /* Estilos para el visor JSON de artículos */
    .diff-json-container { width: 100%; font-family: monospace; font-size: 10px; background: #fff; border: 1px solid #e1e8ed; border-radius: 4px; margin-top: 4px; padding: 8px; color: #475569; max-height: 150px; overflow-y: auto; }
    .diff-json-item { border-bottom: 1px dashed #e2e8f0; padding-bottom: 4px; margin-bottom: 4px; }
    .diff-json-item:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
    
    .audit-empty { padding: 40px 20px; text-align: center; color: var(--aud-muted); }
    .audit-empty i { font-size: 40px; color: #dce4e9; margin-bottom: 12px; }
    .audit-empty h3 { font-size: 16px; font-weight: 800; color: var(--aud-ink); margin: 0 0 4px; }
    
    .audit-pagination { padding: 16px 20px; border-top: 1px solid #edf0f2; background: #fff; }

    @media(max-width: 900px) {
        .audit-head { flex-direction: column; align-items: flex-start; }
        .audit-table-wrap { overflow-x: auto; }
    }
</style>

@php
    // Diccionario visual centralizado de Módulos (para filtros y tabla)
    $nombresModulos = [
        'AlbaranCliente' => 'Albaranes de Clientes',
        'Articulo' => 'Catálogo de Artículos',
        'Cliente' => 'Directorio de Clientes',
        'Documento' => 'Gestor Documental',
        'EquipoInformatico' => 'Equipos Informáticos',
        'Evento' => 'Eventos Transaccionales',
        'HistorialPrl' => 'Registro PRL (Prevención)',
        'PedidoCliente' => 'Pedidos de Clientes',
        'Personal' => 'Expediente de Empleado',
        'PersonalCurso' => 'Formación / Cursos',
        'Presupuesto' => 'Presupuestos',
        'Proyecto' => 'Proyectos',
        'PuestoTrabajo' => 'Puestos de Trabajo',
        'QrCarpeta' => 'Carpetas QR',
        'Setting' => 'Configuración Global',
        'User' => 'Gestión de Usuarios'
    ];

    // Diccionario visual de Permisos
    $nombresPermisos = [
        'clientes.view' => 'Habilitar Módulo: Clientes',
        'clientes.manage' => 'Añadir y editar clientes',
        'clientes.historial' => 'Ver historial completo del cliente',
        'clientes.export' => 'Exportar datos y descargar PDFs del historial',
        'clientes.delete' => 'Eliminar clientes permanentemente',
        'presupuestos.view' => 'Habilitar Módulo: Presupuestos',
        'presupuestos.manage' => 'Crear y editar presupuestos',
        'presupuestos.download' => 'Generar y descargar presupuestos en PDF',
        'presupuestos.delete' => 'Eliminar presupuestos permanentemente',
        'pedidos.view' => 'Habilitar Módulo: Pedidos Clientes',
        'pedidos.manage' => 'Crear y gestionar pedidos',
        'pedidos.download' => 'Generar y descargar pedidos en PDF',
        'pedidos.delete' => 'Eliminar pedidos permanentemente',
        'albaranes.view' => 'Habilitar Módulo: Albaranes Clientes',
        'albaranes.manage' => 'Crear y editar albaranes',
        'albaranes.download' => 'Generar y descargar albaranes en PDF',
        'albaranes.delete' => 'Eliminar albaranes permanentemente',
        'documentos.view' => 'Habilitar Módulo: Documentos',
        'documentos.create' => 'Cargar archivos y generar nuevos documentos',
        'documentos.edit' => 'Editar metadata y líneas de facturación',
        'documentos.download' => 'Visualizar/Descargar los PDF y archivos físicos',
        'documentos.delete' => 'Eliminar documentos permanentemente',
        'inventario.view' => 'Habilitar Módulo: Inventario',
        'inventario.movimientos' => 'Registrar Entradas, Salidas (Vales) y Traslados',
        'inventario.admin' => 'Crear/Editar Almacenes, Categorías y Cancelar históricos',
        'personal.view' => 'Habilitar Módulo: Personal (RRHH)',
        'personal.create' => 'Permitir añadir trabajadores',
        'personal.edit' => 'Editar perfil y datos generales',
        'personal.acciones' => 'Ver y gestionar acciones del trabajador',
        'personal.bulk' => 'Realizar acciones de edición masiva (Bulk)',
        'personal.export' => 'Exportar listados de datos (CSV / PDF)',
        'personal.tallas' => 'Acceder a gestión de tallas y EPIs',
        'personal.medico' => 'Gestionar reconocimientos médicos',
        'personal.delete' => 'Eliminar datos del módulo permanentemente',
        'cursos.view' => 'Habilitar Módulo: Cursos (PRL)',
        'cursos.plantilla' => 'Directorio de plantilla (Ver tarjetas de trabajadores)',
        'cursos.create' => 'Crear nuevos cursos',
        'cursos.edit' => 'Gestionar/Editar cursos existentes',
        'cursos.normas' => 'Ver Panel de Normas',
        'cursos.alertas' => 'Configurar alertas de caducidad',
        'cursos.export' => 'Permitir exportar datos (Excel/CSV)',
        'cursos.delete' => 'Eliminar datos del módulo permanentemente',
        'users.view' => 'Habilitar Módulo: Seguridad',
        'users.manage' => 'Dar de alta, bloquear o resetear accesos',
        'users.permissions' => 'Modificar Matriz de Permisos de otros usuarios',
    ];
@endphp

<form class="audit-filters" method="GET" action="{{ route('activity-log.index') }}">
    <div class="row align-items-end">
        <div class="col-lg-2 col-md-6 mb-3 mb-lg-0">
            <label>Búsqueda libre</label>
            <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Ej. Carlos, avería...">
        </div>

        <div class="col-lg-2 col-md-3 mb-3 mb-lg-0">
            <label>Módulo / Modelo</label>
            <select class="form-control" name="model">
                <option value="">Todos los módulos</option>
                @foreach($models as $model)
                    @php
                        $rawModel = class_basename($model);
                        $moduloLegible = $nombresModulos[$rawModel] ?? $rawModel;
                    @endphp
                    <option value="{{ $model }}" @selected(request('model') === $model)>{{ $moduloLegible }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-lg-2 col-md-4 mb-3 mb-lg-0">
            <label>Acción</label>
            <select class="form-control" name="event">
                <option value="">Todas</option>
                <option value="created" @selected(request('event') === 'created')>Creación</option>
                <option value="updated" @selected(request('event') === 'updated')>Modificación</option>
                <option value="deleted" @selected(request('event') === 'deleted')>Eliminación</option>
            </select>
        </div>

        <div class="col-lg-2 col-md-4 mb-3 mb-lg-0">
            <label>Desde (Fecha y Hora)</label>
            <input class="form-control flatpickr-input" type="text" name="from" value="{{ request('from') }}" placeholder="Selecciona fecha...">
        </div>
        
        <div class="col-lg-2 col-md-4 mb-3 mb-lg-0">
            <label>Hasta (Fecha y Hora)</label>
            <input class="form-control flatpickr-input" type="text" name="to" value="{{ request('to') }}" placeholder="Selecciona fecha...">
        </div>

        <div class="col-lg-1 col-md-12 d-flex gap-2">
            <button class="btn-audit btn-audit-main w-100" type="submit" title="Filtrar"><i class="fas fa-search"></i></button>
            <a class="btn-audit btn-audit-clear w-100" href="{{ route('activity-log.index') }}" title="Limpiar"><i class="fas fa-times"></i></a>
        </div>
    </div>
</form>

<div class="audit-card">
    <div class="audit-table-wrap">
        @if($activities->isEmpty())
            <div class="audit-empty">
                <i class="fas fa-shield-alt"></i>
                <h3>Auditoría Inmaculada</h3>
                <p>No se han encontrado registros de cambios con los filtros actuales.</p>
            </div>
        @else
            <table class="audit-table">
                <thead style="table-layout: fixed; width: 100%;">
                    <tr>
                        <th style="width: 5%;">Registro Horario</th>
                        <th style="width: 10%;">Responsable</th>
                        <th style="width: 10%;">Acción</th>
                        <th style="width: 15%;">Módulo / ID</th>
                        <th style="width: 60%;">Detalle de Modificaciones</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($activities as $activity)
                    @php
                        $formatVal = function($val) {
                            if (is_bool($val)) return $val ? '1 (Sí)' : '0 (No)';
                            return $val === null || $val === '' ? 'Vacío' : (string) $val;
                        };

                        $event = $activity->event ?? $activity->description;
                        $properties = $activity->properties?->toArray() ?? [];
                        $attributes = $properties['attributes'] ?? [];
                        $old = $properties['old'] ?? [];
                        $displayFields = $event === 'deleted' && !empty($old) ? $old : $attributes;
                        $referenceName = $properties['reference_name'] ?? 'Sin referencia';
                        
                        $hiddenFields = ['password', 'remember_token', 'created_at', 'updated_at', 'deleted_at'];
                        
                        $eventMap = ['created' => ['Creación', 'plus-circle'], 'updated' => ['Modificación', 'edit'], 'deleted' => ['Eliminación', 'trash-alt'], 'restored' => ['Restauración', 'undo']];
                        $eventLabel = $eventMap[$event][0] ?? ucfirst($event);
                        $eventIcon = $eventMap[$event][1] ?? 'history';

                        $rawModel = class_basename($activity->subject_type ?? 'Registro');
                        $moduloLegible = $nombresModulos[$rawModel] ?? $rawModel;
                    @endphp
                    <tr>
                        <td class="cell-date">
                            <strong>{{ $activity->created_at->format('d/m/Y') }}</strong>
                            <span>{{ $activity->created_at->format('H:i:s') }}</span>
                        </td>
    
                        <td>
                            <div class="cell-user">
                                <i class="fas fa-user-circle"></i>
                                {{ $activity->causer?->name ?? 'Acción de Sistema' }}
                            </div>
                            
                            @if(isset($properties['ip']) && $properties['ip'] !== null)
                                <div style="font-size: 9px; color: #a3adb4; margin-top: 4px; font-weight: 600; text-transform: uppercase;">
                                    <i class="fas fa-network-wired" style="font-size: 9px; margin-right: 2px;"></i> IP: {{ $properties['ip'] }}
                                </div>
                            @endif
                        </td>
                        <td>
                            <span class="badge-event event-{{ $event }}">
                                <i class="fas fa-{{ $eventIcon }}"></i> {{ $eventLabel }}
                            </span>
                        </td>
                        <td class="cell-model">
                            <strong>{{ $moduloLegible }}</strong>
                            <small class="d-block text-muted" title="{{ $referenceName }}">{{ Str::limit($referenceName, 80) }}</small>
                            <span>ID: {{ $activity->subject_id ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <div class="diff-box">
                                @forelse($displayFields as $field => $value)
                                    @if(in_array($field, $hiddenFields)) @continue @endif
                                    
                                    <div class="diff-row">
                                        <span class="diff-field">{{ str_replace('_', ' ', $field) }}</span>
                                        @if($event === 'deleted')
                                            <span class="badge badge-danger mb-1">Valor eliminado</span>
                                            @php $displayValue = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : $formatVal($value); @endphp
                                            <details class="diff-value diff-old"><summary>{{ Str::limit($displayValue, 100) }}</summary><div class="full-value">{{ $displayValue }}</div></details>
                                        @else
                                        {{-- Renderizado del valor ANTIGUO --}}
                                        @if(is_array($old) && array_key_exists($field, $old))
                                            @if(is_array($old[$field]))
                                                <div class="diff-old w-100 mt-1">
                                                    <span class="badge badge-secondary mb-1">Versión Anterior:</span>
                                                    <div class="diff-json-container">
                                                        @foreach($old[$field] as $item)
                                                            <div class="diff-json-item">
                                                                @php
                                                                    $textoImprimir = $item;
                                                                    if ($field === 'permissions' && is_string($item)) {
                                                                        $textoImprimir = $nombresPermisos[$item] ?? $item;
                                                                    } else {
                                                                        $textoImprimir = is_array($item) ? collect($item)->except(['created_at', 'updated_at'])->toJson(JSON_UNESCAPED_UNICODE) : json_encode($item, JSON_UNESCAPED_UNICODE);
                                                                    }
                                                                @endphp
                                                                {{ $textoImprimir }}
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @else
                                                @php $oldValue = $formatVal($old[$field]); @endphp
                                                <details class="diff-value diff-old"><summary>{{ Str::limit($oldValue, 100) }}</summary><div class="full-value">{{ $oldValue }}</div></details>
                                                <i class="fas fa-long-arrow-alt-right diff-icon"></i>
                                            @endif
                                        @endif
                                        
                                        {{-- Renderizado del valor NUEVO --}}
                                        @if(is_array($value))
                                            <div class="diff-new w-100 mt-1">
                                                <span class="badge badge-success mb-1">Versión Nueva:</span>
                                                <div class="diff-json-container">
                                                    @foreach($value as $item)
                                                        <div class="diff-json-item">
                                                            @php
                                                                $textoImprimir = $item;
                                                                if ($field === 'permissions' && is_string($item)) {
                                                                    $textoImprimir = $nombresPermisos[$item] ?? $item;
                                                                } else {
                                                                    $textoImprimir = is_array($item) ? collect($item)->except(['created_at', 'updated_at'])->toJson(JSON_UNESCAPED_UNICODE) : json_encode($item, JSON_UNESCAPED_UNICODE);
                                                                }
                                                            @endphp
                                                            {{ $textoImprimir }}
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @else
                                            @php $newValue = $formatVal($value); @endphp
                                            <details class="diff-value diff-new"><summary>{{ Str::limit($newValue, 100) }}</summary><div class="full-value">{{ $newValue }}</div></details>
                                        @endif
                                        @endif
                                    </div>
                                @empty
                                    <span style="font-size: 11px; color: #87949b;"><i class="fas fa-info-circle"></i> Modificación estructural o sin cambios detectables.</span>
                                @endforelse
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
    @if($activities->isNotEmpty())
        <div class="audit-pagination">
            {{ $activities->links() }}
        </div>
    @endif
</div>
@stop

@section('js')
    <!-- CSS de Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <!-- Script de Flatpickr y el idioma Español -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar el calendario en los inputs que tengan la clase flatpickr-input
            flatpickr(".flatpickr-input", {
                enableTime: true,           // Habilitar selección de hora
                dateFormat: "Y-m-d H:i",    // Formato exacto que espera tu controlador Laravel
                time_24hr: true,            // Usar formato de 24 horas (sin AM/PM)
                locale: "es",               // Idioma español
                disableMobile: "true"       // Fuerza a que los móviles también usen este diseño premium
            });
        });
    </script>
@stop
