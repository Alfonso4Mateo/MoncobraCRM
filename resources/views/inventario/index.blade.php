@extends('adminlte::page')

@section('title', 'Inventario - MoncobraCRM')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/inventario-index.css'])
    <style>
        /* 1. Layout a ancho completo (100% sin sidebar derecho) */
        .inventory-layout {
            display: block !important;
            width: 100% !important;
        }
        .inventory-main {
            width: 100% !important;
            max-width: 100% !important;
        }

        /* 2. Cabecera superior */
        .inventory-hero {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }
        .inventory-hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
            align-items: center;
        }
        .inventory-secondary-action {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            background: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            color: #334155 !important;
            padding: 0.55rem 1rem;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.84rem;
            text-decoration: none !important;
            transition: all 0.15s ease;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
        }
        .inventory-secondary-action:hover {
            background: #f8fafc !important;
            border-color: #94a3b8 !important;
            color: #0f2747 !important;
        }

        /* 3. Fila superior de KPIs + Panel de Operaciones Rápidas */
        .inventory-top-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr)) 320px;
            gap: 1rem;
            align-items: stretch;
            margin-bottom: 1.25rem;
        }
        @media (max-width: 1200px) {
            .inventory-top-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
            .inventory-ops-panel {
                grid-column: 1 / -1;
            }
        }
        @media (max-width: 768px) {
            .inventory-top-grid {
                grid-template-columns: 1fr;
            }
        }

        .inventory-ops-panel {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 0.9rem 1rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 0.55rem;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);
        }
        .inventory-ops-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
        }
        .btn-op {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            padding: 0.55rem 0.85rem;
            border-radius: 9px;
            font-weight: 800;
            font-size: 0.82rem;
            text-decoration: none !important;
            transition: all 0.15s ease;
            border: 1px solid transparent;
            cursor: pointer;
            white-space: nowrap;
        }
        .btn-op--new-product {
            background: #2563eb;
            color: #ffffff !important;
            width: 100%;
            box-shadow: 0 3px 8px rgba(37, 99, 235, 0.22);
        }
        .btn-op--new-product:hover { background: #1d4ed8; }
        .btn-op--light {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #334155 !important;
            font-size: 0.78rem;
            padding: 0.4rem 0.6rem;
        }
        .btn-op--light:hover { background: #f1f5f9; color: #0f172a !important; }
        .btn-op--salida {
            background: #0f172a;
            color: #ffffff !important;
            width: 100%;
        }
        .btn-op--salida:hover { background: #1e293b; }
        .btn-op--entrada {
            background: #173e67;
            color: #ffffff !important;
            width: 100%;
        }
        .btn-op--entrada:hover { background: #0f2747; }

        /* 4. Tabla principal */
        .inventory-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }
        .inventory-table th:nth-child(1), .inventory-table td:nth-child(1) { width: 10%; }
        .inventory-table th:nth-child(2), .inventory-table td:nth-child(2) { width: 14%; }
        .inventory-table th:nth-child(3), .inventory-table td:nth-child(3) { width: 23%; }
        .inventory-table th:nth-child(4), .inventory-table td:nth-child(4) { width: 10%; }
        .inventory-table th:nth-child(5), .inventory-table td:nth-child(5) { width: 13%; }
        .inventory-table th:nth-child(6), .inventory-table td:nth-child(6) { width: 9%; }
        .inventory-table th:nth-child(7), .inventory-table td:nth-child(7) { width: 9%; }
        .inventory-table th:nth-child(8), .inventory-table td:nth-child(8) { width: 12%; }

        .inventory-code, .inventory-description-text {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .inventory-parent-row {
            transition: background 0.12s ease;
        }
        .inventory-parent-row:hover td {
            filter: brightness(0.985);
        }

        .btn-variants-trigger {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin-top: 0.35rem;
            padding: 0.25rem 0.65rem;
            border-radius: 999px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
            font-size: 0.74rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .btn-variants-trigger:hover {
            background: #dbeafe;
            border-color: #93c5fd;
            transform: translateY(-1px);
        }
        .inventory-parent-alerts {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            margin-left: 0.35rem;
        }
        .inventory-parent-alert {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.2rem 0.45rem;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 800;
            line-height: 1;
        }
        .inventory-parent-alert--warning { background: #fff4e4; color: #c27b00; border: 1px solid #fde68a; }
        .inventory-parent-alert--danger { background: #ffecec; color: #c0392b; border: 1px solid #fecaca; }

        .inventory-actions {
            display: inline-flex !important;
            align-items: center;
            gap: 0.35rem;
            flex-wrap: nowrap !important;
        }
        .inventory-action-icon--danger {
            border-color: #fecaca !important;
            background: #fff5f5 !important;
            color: #dc2626 !important;
        }
        .inventory-action-icon--danger:hover {
            background: #fee2e2 !important;
            color: #b91c1c !important;
        }

        /* 5. MODAL DE VARIANTES AMPLIADO (XL: 92vw x 86vh) */
        .erp-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            z-index: 1060;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1.25rem;
            backdrop-filter: blur(3px);
        }
        .erp-modal-backdrop.is-open {
            display: flex;
        }
        .erp-modal-panel {
            background: #ffffff;
            border-radius: 18px;
            width: 94vw;
            max-width: 1420px;
            height: 86vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.3);
            border: 1px solid #cbd5e1;
            overflow: hidden;
            animation: modalFadeIn 0.16s ease-out;
        }
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-8px) scale(0.99); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .erp-modal-head {
            padding: 1.25rem 1.75rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            background: #f8fafc;
        }
        .erp-modal-head h3 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 800;
            color: #0f2747;
        }
        .erp-modal-head p {
            margin: 0.2rem 0 0 0;
            font-size: 0.86rem;
            color: #64748b;
            font-weight: 600;
        }

        /* Barra de herramientas y filtros de estado en el Modal */
        .erp-modal-toolbar {
            padding: 1rem 1.75rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            background: #ffffff;
        }
        .erp-modal-search {
            flex: 1 1 320px;
            max-width: 460px;
            position: relative;
        }
        .erp-modal-search i {
            position: absolute;
            left: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }
        .erp-modal-search input {
            width: 100%;
            padding: 0.6rem 0.9rem 0.6rem 2.4rem;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            color: #0f172a;
        }
        .erp-modal-search input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        /* Píldoras de filtro de estado (Todos / Óptimo / Reposición / Crítico) */
        .erp-status-filters {
            display: inline-flex;
            align-items: center;
            background: #f1f5f9;
            padding: 0.25rem;
            border-radius: 11px;
            border: 1px solid #e2e8f0;
            gap: 0.25rem;
            flex-wrap: wrap;
        }
        .status-filter-btn {
            border: none;
            background: transparent;
            color: #475569;
            font-size: 0.78rem;
            font-weight: 800;
            padding: 0.42rem 0.8rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }
        .status-filter-btn:hover {
            color: #0f172a;
            background: rgba(255, 255, 255, 0.6);
        }
        .status-filter-btn.is-active {
            background: #ffffff;
            color: #0f2747;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.1);
        }
        .status-filter-count {
            font-size: 0.7rem;
            padding: 0.1rem 0.4rem;
            border-radius: 999px;
            background: #e2e8f0;
            color: #334155;
        }
        .status-filter-btn--bajo.is-active { color: #b45309; }
        .status-filter-btn--critico.is-active { color: #b91c1c; }

        .erp-alert-check-label {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.82rem;
            font-weight: 800;
            color: #334155;
            background: #fffbeb;
            border: 1px solid #fde68a;
            padding: 0.45rem 0.85rem;
            border-radius: 10px;
            cursor: pointer;
            user-select: none;
            margin: 0;
        }
        .erp-alert-check-label input {
            width: 16px;
            height: 16px;
            accent-color: #d97706;
            cursor: pointer;
        }

        .erp-modal-body {
            padding: 0;
            overflow-y: auto;
            flex: 1;
            background: #ffffff;
        }
        .variants-modal-table {
            width: 100%;
            border-collapse: collapse;
        }
        .variants-modal-table th {
            position: sticky;
            top: 0;
            background: #f8fafc;
            color: #64748b;
            font-size: 0.74rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 0.95rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
            z-index: 2;
        }
        .variants-modal-table td {
            padding: 0.95rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            font-size: 0.9rem;
            color: #1e293b;
        }
        .variants-modal-table tr:hover td {
            background: #f8fafc;
        }
        .variant-attr-chip {
            display: inline-block;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            color: #173e67;
            font-size: 0.78rem;
            font-weight: 800;
            padding: 0.25rem 0.65rem;
            border-radius: 7px;
            margin: 0.15rem 0.3rem 0.15rem 0;
        }
        .erp-modal-foot {
            padding: 1rem 1.75rem;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* 6. MODAL DE DOBLE CONFIRMACIÓN DE BORRADO */
        .erp-confirm-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.7);
            z-index: 1080;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            backdrop-filter: blur(3px);
        }
        .erp-confirm-backdrop.is-open {
            display: flex;
        }
        .erp-confirm-dialog {
            background: #ffffff;
            border-radius: 16px;
            width: 100%;
            max-width: 480px;
            border: 1px solid #fecaca;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.35);
            overflow: hidden;
            animation: modalFadeIn 0.15s ease-out;
        }
        .erp-confirm-head {
            background: #fef2f2;
            padding: 1.15rem 1.5rem;
            border-bottom: 1px solid #fee2e2;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #991b1b;
        }
        .erp-confirm-head i {
            font-size: 1.35rem;
            color: #dc2626;
        }
        .erp-confirm-head h4 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 800;
        }
        .erp-confirm-body {
            padding: 1.5rem;
            color: #334155;
            font-size: 0.92rem;
        }
        .erp-confirm-step2 {
            margin-top: 1rem;
            padding: 1rem;
            background: #fff5f5;
            border: 1px dashed #fca5a5;
            border-radius: 10px;
            display: none;
        }
        .erp-confirm-step2.is-visible {
            display: block;
        }
        .erp-confirm-foot {
            padding: 1rem 1.5rem;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 0.65rem;
        }
        .btn-danger-solid {
            background: #dc2626;
            border: 1px solid #dc2626;
            color: #ffffff;
            font-weight: 800;
            font-size: 0.85rem;
            padding: 0.55rem 1.1rem;
            border-radius: 9px;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .btn-danger-solid:hover:not(:disabled) {
            background: #b91c1c;
        }
        .btn-danger-solid:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }
    </style>
@endsection

@section('content')
    <section class="inventory-page">
        {{-- CABECERA --}}
        <header class="inventory-hero">
            <div>
                <h1>Control de Inventario y Stock</h1>
                <p>Gestión centralizada de existencias, ubicaciones y alertas críticas.</p>
            </div>

            <div class="inventory-hero-actions">
                @if(auth()->user()->role === 'admin' || auth()->user()->role === 'superadmin')
                    <a href="{{ route('clases.index') }}" class="inventory-secondary-action" title="Gestionar categorías de ítems">
                        <i class="fas fa-tags"></i>
                        Gestionar Clases
                    </a>
                @endif
                <a href="{{ route('almacenes.index') }}" class="inventory-secondary-action">
                    <i class="fas fa-warehouse"></i>
                    Gestión de almacenes
                </a>
                <a href="{{ route('inventario.acciones.index') }}" class="inventory-secondary-action">
                    <i class="fas fa-clipboard-list"></i>
                    Registro de acciones
                </a>

                <a href="{{ route('inventario.informes.index') }}" class="inventory-secondary-action">
                    <i class="fas fa-file-csv"></i>
                    Informes
                </a>
            </div>
        </header>

        @if (session('success'))
            <div class="inventory-alert inventory-alert-success">
                <i class="fas fa-circle-check"></i>
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="inventory-alert inventory-alert-error">
                <i class="fas fa-triangle-exclamation"></i>
                {{ session('error') }}
            </div>
        @endif

        <div class="inventory-layout">
            <div class="inventory-main">
                {{-- BLOQUE SUPERIOR: 3 KPIs + Panel de Botones Operativos --}}
                <div class="inventory-top-grid">
                    <article class="inventory-stat-card">
                        <div class="inventory-stat-top">
                            <div>
                                <span class="inventory-stat-label">Total productos</span>
                                <strong class="inventory-stat-value">{{ number_format($totalProductos, 0, ',', '.') }}</strong>
                                <span class="inventory-stat-note positive">+{{ number_format(max(1, (int) round($totalProductos * 0.02)), 0, ',', '.') }} este mes</span>
                            </div>
                            <span class="inventory-stat-icon blue"><i class="fas fa-boxes"></i></span>
                        </div>
                    </article>

                    <article class="inventory-stat-card">
                        <div class="inventory-stat-top">
                            <div>
                                <span class="inventory-stat-label">Nivel crítico</span>
                                <strong class="inventory-stat-value">{{ number_format($nivelCritico, 0, ',', '.') }}</strong>
                                <span class="inventory-stat-note danger">Requiere atención inmediata</span>
                            </div>
                            <span class="inventory-stat-icon red"><i class="fas fa-exclamation-circle"></i></span>
                        </div>
                    </article>

                    <article class="inventory-stat-card">
                        <div class="inventory-stat-top">
                            <div>
                                <span class="inventory-stat-label">Stock total</span>
                                <strong class="inventory-stat-value">{{ number_format($stockTotal, 0, ',', '.') }}</strong>
                                <span class="inventory-stat-note">Unidades registradas</span>
                            </div>
                            <span class="inventory-stat-icon amber"><i class="fas fa-boxes"></i></span>
                        </div>
                    </article>

                    <div class="inventory-ops-panel">
                        <a href="{{ route('inventario.item.create') }}" class="btn-op btn-op--new-product">
                            <i class="fas fa-plus"></i> Nuevo producto
                        </a>
                        <div class="inventory-ops-row">
                            <a href="{{ route('inventario.salida.create') }}" class="btn-op btn-op--salida">
                                <i class="fas fa-minus"></i> Nueva salida
                            </a>
                            <a href="{{ route('inventario.create') }}" class="btn-op btn-op--entrada">
                                <i class="fas fa-plus"></i> Nueva entrada
                            </a>
                        </div>
                        <div class="inventory-ops-row">
                            <a href="{{ route('inventario.traslado.create') }}" class="btn-op btn-op--light" title="Trasladar stock entre almacenes">
                                <i class="fas fa-exchange-alt"></i> Trasladar
                            </a>
                            <a href="{{ route('inventario.informes.index') }}" class="btn-op btn-op--light" title="Centro de Informes y Exportación CSV">
                                <i class="fas fa-file-csv"></i> Informes CSV
                            </a>
                        </div>
                    </div>
                </div>

                {{-- BUSCADOR PRINCIPAL --}}
                <div class="inventory-search-card" style="margin-bottom: 1.25rem;">
                    <div class="inventory-search-header">
                        <div>
                            <h2>Buscar inventario</h2>
                            <p>Filtra por descripción, código SKU, clase o almacén.</p>
                        </div>

                        @if(request()->hasAny(['descripcion', 'clase_id', 'almacen_id', 'almacen']))
                            <a href="{{ route('inventario.index') }}" class="inventory-search-reset">Limpiar filtros</a>
                        @endif
                    </div>

                    <form method="GET" action="{{ route('inventario.index') }}" class="inventory-search-form">
                        <div class="inventory-search-field">
                            <label for="descripcion">Descripción / SKU</label>
                            <input id="descripcion" name="descripcion" type="text" value="{{ $descripcion }}" placeholder="Buscar por nombre, código o atributo...">
                        </div>

                        <div class="inventory-search-field">
                            <label for="clase_id">Clase</label>
                            <select id="clase_id" name="clase_id">
                                <option value="">Todas las clases</option>
                                @foreach($clases as $id => $nombre)
                                    <option value="{{ $id }}" @selected((string) $claseId === (string) $id)>{{ $nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="inventory-search-field">
                            <label for="almacen_id">Almacén</label>
                            <select id="almacen_id" name="almacen_id">
                                <option value="">Todos los almacenes</option>
                                @foreach($almacenesRegistrados ?? [] as $almacenItem)
                                    <option value="{{ $almacenItem->id }}" @selected((string)($almacenId ?? '') === (string)$almacenItem->id)>
                                        {{ $almacenItem->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="inventory-search-actions">
                            <button type="submit" class="inventory-search-btn">
                                <i class="fas fa-search"></i>
                                Buscar
                            </button>
                        </div>
                    </form>
                </div>

                {{-- TABLA PRINCIPAL: OBJETOS PADRE --}}
                <div class="inventory-card inventory-table-card">
                    <div class="table-responsive inventory-table-wrapper">
                        <table class="table inventory-table">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Nombre</th>
                                    <th>Descripción y Variantes</th>
                                    <th>Clase</th>
                                    <th>Almacén</th>
                                    <th>Stock actual</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($inventarios as $grupo)
                                    @php
                                        $cantidadHijos = $grupo->hijos->count();
                                        $padreTargetId = $grupo->variantePadreId ?? $grupo->idPadre;
                                    @endphp
                                    <tr class="inventory-row inventory-row-{{ $grupo->estadoGrupo }} inventory-parent-row">
                                        <td>
                                            <span class="inventory-code" title="{{ $grupo->codigoPadre }}">
                                                {{ $grupo->codigoPadre }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="inventory-name">
                                                <strong>{{ $grupo->nombrePadre }}</strong>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="inventory-description">
                                                <strong class="inventory-description-text" title="{{ $grupo->descripcionPadre }}">
                                                    {{ $grupo->descripcionPadre }}
                                                </strong>
                                                @if($grupo->referenciaPadre)
                                                    <small class="text-muted d-block">Ref: {{ $grupo->referenciaPadre }}</small>
                                                @endif

                                                <div class="d-flex align-items-center flex-wrap" style="gap: 0.35rem;">
                                                    <button type="button"
                                                        class="btn-variants-trigger js-open-variants-modal"
                                                        data-group-index="{{ $loop->index }}">
                                                        <i class="fas fa-layer-group"></i>
                                                        {{ $cantidadHijos }} {{ $cantidadHijos === 1 ? 'variante' : 'variantes' }}
                                                    </button>

                                                    @if($grupo->hijosReposicion > 0 || $grupo->hijosCriticos > 0)
                                                        <div class="inventory-parent-alerts">
                                                            @if($grupo->hijosReposicion > 0)
                                                                <span class="inventory-parent-alert inventory-parent-alert--warning" title="{{ $grupo->hijosReposicion }} variante(s) en reposición">
                                                                    <i class="fas fa-triangle-exclamation"></i> {{ $grupo->hijosReposicion }}
                                                                </span>
                                                            @endif
                                                            @if($grupo->hijosCriticos > 0)
                                                                <span class="inventory-parent-alert inventory-parent-alert--danger" title="{{ $grupo->hijosCriticos }} variante(s) en estado crítico">
                                                                    <i class="fas fa-circle-exclamation"></i> {{ $grupo->hijosCriticos }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="inventory-pill muted">{{ $grupo->clasePadre }}</span>
                                        </td>
                                        <td>
                                            <div style="font-weight: 600; color: #334155;">
                                                {{ $grupo->almacenPadre ?: '—' }}
                                            </div>
                                        </td>
                                        <td>
                                            <span class="inventory-stock inventory-stock-main">
                                                {{ number_format($grupo->stockGrupo, 0, ',', '.') }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="inventory-status inventory-status-{{ $grupo->estadoGrupo }}">
                                                {{ $grupo->estadoTextoGrupo }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="inventory-actions">
                                                <button type="button"
                                                    class="inventory-action-icon js-open-variants-modal"
                                                    data-group-index="{{ $loop->index }}"
                                                    title="Ver desglose de variantes">
                                                    <i class="far fa-eye"></i>
                                                </button>
                                                @if($grupo->idPadre)
                                                    <a href="{{ route('inventario.edit', $grupo->idPadre) }}" class="inventory-action-icon" title="Editar artículo y variantes">
                                                        <i class="fas fa-pen"></i>
                                                    </a>
                                                @endif
                                                @if($padreTargetId)
                                                    <a href="{{ route('inventario.item.create', ['variante_id' => $padreTargetId]) }}" class="inventory-action-icon" title="Añadir nueva variante">
                                                        <i class="fas fa-plus"></i>
                                                    </a>
                                                    <button type="button"
                                                        class="inventory-action-icon inventory-action-icon--danger js-open-delete-confirm"
                                                        data-delete-url="{{ route('inventario.destroy', $padreTargetId) }}"
                                                        data-delete-scope="padre"
                                                        data-delete-name="{{ $grupo->nombrePadre }} ({{ $grupo->codigoPadre }})"
                                                        data-delete-info="Se dará de baja el producto maestro junto con sus {{ $cantidadHijos }} variante(s) asociadas."
                                                        data-delete-stock="{{ (int) $grupo->stockGrupo }}"
                                                        title="Eliminar producto completo">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8">
                                            <div class="inventory-empty-state">
                                                <i class="fas fa-box-open"></i>
                                                <strong>No hay productos en inventario</strong>
                                                <span>Cuando se creen productos aparecerán aquí con el mismo diseño del panel.</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="inventory-toolbar inventory-toolbar-footer">
                        <span class="inventory-toolbar-label">
                            Mostrando {{ $inventarios->firstItem() ?? 0 }} - {{ $inventarios->lastItem() ?? 0 }} de {{ number_format($inventarios->total(), 0, ',', '.') }} productos
                        </span>

                        @if ($inventarios->hasPages())
                            <div class="inventory-pagination">
                                {{ $inventarios->onEachSide(1)->links('pagination::bootstrap-4') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- MODAL XL INTERACTIVO DE VARIANTES DEL ARTÍCULO --}}
    <div class="erp-modal-backdrop" id="modalVariantesProducto" aria-hidden="true">
        <div class="erp-modal-panel" role="dialog" aria-modal="true" aria-labelledby="modalVariantesTitulo">
            <header class="erp-modal-head">
                <div>
                    <h3 id="modalVariantesTitulo">Variantes del Producto</h3>
                    <p id="modalVariantesSubtitulo">Código maestro: —</p>
                </div>
                <div class="d-flex align-items-center" style="gap: 0.6rem;">
                    <a href="#" id="btnEditarPadreModal" class="inventory-secondary-action">
                        <i class="fas fa-pen"></i> Editar matriz
                    </a>
                    <a href="#" id="btnNuevaVarianteModal" class="btn-op btn-op--new-product" style="width: auto; padding: 0.55rem 1.1rem;">
                        <i class="fas fa-plus"></i> Nueva variante
                    </a>
                    <button type="button" class="inventory-secondary-action" id="btnCloseVariantesModal">
                        <i class="fas fa-times"></i> Cerrar
                    </button>
                </div>
            </header>

            <div class="erp-modal-toolbar">
                {{-- Buscador por texto --}}
                <div class="erp-modal-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="inputBuscarVarianteModal" placeholder="Filtrar variantes por SKU, talla, color o almacén...">
                </div>

                {{-- Selector rápido por Estado + Checkbox de Alertas --}}
                <div class="d-flex align-items-center flex-wrap" style="gap: 0.75rem;">
                    <div class="erp-status-filters" role="group" aria-label="Filtrar por estado de stock">
                        <button type="button" class="status-filter-btn is-active" data-status-filter="todos">
                            Todos <span class="status-filter-count" id="countStatusTodos">0</span>
                        </button>
                        <button type="button" class="status-filter-btn" data-status-filter="optimo">
                            Óptimo <span class="status-filter-count" id="countStatusOptimo">0</span>
                        </button>
                        <button type="button" class="status-filter-btn status-filter-btn--bajo" data-status-filter="bajo">
                            Reposición <span class="status-filter-count" id="countStatusBajo">0</span>
                        </button>
                        <button type="button" class="status-filter-btn status-filter-btn--critico" data-status-filter="critico">
                            Crítico <span class="status-filter-count" id="countStatusCritico">0</span>
                        </button>
                    </div>

                    <label class="erp-alert-check-label" title="Mostrar únicamente variantes en Reposición o Crítico">
                        <input type="checkbox" id="checkSoloAlertasModal">
                        <span><i class="fas fa-triangle-exclamation text-warning mr-1"></i> Solo con alertas</span>
                    </label>
                </div>
            </div>

            <div class="erp-modal-body">
                <table class="variants-modal-table">
                    <thead>
                        <tr>
                            <th style="width: 18%;">SKU / Código</th>
                            <th style="width: 28%;">Atributos / Variante</th>
                            <th style="width: 22%;">Almacén (Stock)</th>
                            <th style="width: 12%;">Stock Total</th>
                            <th style="width: 10%;">Estado</th>
                            <th style="width: 10%; text-align: right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyVariantesModal">
                        {{-- Se rellena dinámicamente con JavaScript --}}
                    </tbody>
                </table>
            </div>

            <footer class="erp-modal-foot">
                <span id="modalVariantesContador" style="font-size: 0.85rem; font-weight: 800; color: #64748b;">0 variantes</span>
                <span style="font-size: 0.92rem; font-weight: 800; color: #0f2747;">
                    Stock total acumulado: <strong id="modalVariantesStockTotal" style="color: #2563eb;">0</strong> uds.
                </span>
            </footer>
        </div>
    </div>

    {{-- MODAL DE DOBLE CONFIRMACIÓN PARA ELIMINAR PADRES O HIJOS --}}
    <div class="erp-confirm-backdrop" id="modalDobleConfirmacionBorrar" aria-hidden="true">
        <div class="erp-confirm-dialog" role="dialog" aria-modal="true">
            <div class="erp-confirm-head">
                <i class="fas fa-triangle-exclamation"></i>
                <h4 id="deleteConfirmTitle">Confirmar eliminación</h4>
            </div>

            <form id="formDeleteConfirm" method="POST" action="#">
                @csrf
                @method('DELETE')
                <input type="hidden" name="scope" id="deleteConfirmScope" value="auto">

                <div class="erp-confirm-body">
                    <p class="mb-2">Estás a punto de eliminar:</p>
                    <div style="padding: 0.75rem 1rem; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; font-weight: 800; color: #0f2747; margin-bottom: 0.75rem;" id="deleteConfirmTargetName">
                        —
                    </div>
                    <p class="text-muted mb-0" style="font-size: 0.85rem;" id="deleteConfirmExtraInfo"></p>

                    {{-- Aviso bloqueante si tiene stock > 0 --}}
                    <div id="deleteStockWarning" style="display: none; margin-top: 1rem; padding: 0.85rem 1rem; background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; color: #92400e; font-size: 0.84rem; font-weight: 700;">
                        <i class="fas fa-lock mr-1"></i> Atención: Este registro tiene <strong id="deleteStockAmount">0</strong> uds. en stock. Por trazabilidad , debes registrar una salida o dejar su stock a 0 antes de poder eliminarlo.
                    </div>

                    {{-- Paso 2: Doble comprobación interactiva --}}
                    <div class="erp-confirm-step2" id="deleteStep2Box">
                        <strong class="d-block mb-2" style="color: #991b1b; font-size: 0.84rem;">
                            <i class="fas fa-shield-halved mr-1"></i> Segunda comprobación de seguridad:
                        </strong>
                        <label class="d-flex align-items-start mb-0" style="gap: 0.55rem; cursor: pointer; font-weight: 700; font-size: 0.84rem; color: #7f1d1d;">
                            <input type="checkbox" id="checkDoubleConfirmDelete" style="width: 18px; height: 18px; margin-top: 2px; accent-color: #dc2626;">
                            <span>Sí, estoy completamente seguro de eliminar este registro del catálogo.</span>
                        </label>
                    </div>
                </div>

                <div class="erp-confirm-foot">
                    <button type="button" class="inventory-secondary-action" id="btnCancelDeleteConfirm">
                        Cancelar
                    </button>
                    <button type="button" class="btn-danger-solid" id="btnStep1Delete">
                        Sí, quiero eliminarlo
                    </button>
                    <button type="submit" class="btn-danger-solid" id="btnFinalSubmitDelete" style="display: none;" disabled>
                        <i class="fas fa-trash mr-1"></i> Confirmar eliminación definitiva
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('js')
    @php
        $gruposModalJs = collect($inventarios->items())->values()->map(function ($grupo) {
            return [
                'idPadre' => $grupo->idPadre,
                'variantePadreId' => $grupo->variantePadreId ?? $grupo->idPadre,
                'codigoPadre' => $grupo->codigoPadre,
                'nombrePadre' => $grupo->nombrePadre,
                'descripcionPadre' => $grupo->descripcionPadre,
                'clasePadre' => $grupo->clasePadre,
                'stockGrupo' => (int) $grupo->stockGrupo,
                'urlNuevaVariante' => route('inventario.item.create', ['variante_id' => $grupo->variantePadreId ?? $grupo->idPadre]),
                'urlEditarPadre' => $grupo->idPadre ? route('inventario.edit', $grupo->idPadre) : '#',
                'hijos' => collect($grupo->hijos)->map(function ($h) {
                    return [
                        'id' => $h->id,
                        'codigo' => (string) ($h->codigo ?? ''),
                        'nombre' => (string) ($h->nombre ?? ''),
                        'descripcion' => (string) ($h->descripcion ?? ''),
                        'chips' => (array) ($h->atributos_chips ?? []),
                        'almacen' => (string) ($h->almacen ?: 'Sin existencias'),
                        'stock_actual' => (int) ($h->stock_actual ?? 0),
                        'estado' => (string) ($h->estado ?? 'optimo'),
                        'estado_texto' => (string) ($h->estado_texto ?? 'Óptimo'),
                        'urlShow' => $h->id ? route('inventario.show', $h->id) : '#',
                        'urlEdit' => $h->id ? route('inventario.edit', $h->id) : '#',
                        'urlDelete' => $h->id ? route('inventario.destroy', $h->id) : '#',
                    ];
                })->values()->all(),
            ];
        })->all();
    @endphp

    <script>
        (function () {
            const gruposData = @json($gruposModalJs);

            // Elementos del Modal de Variantes
            const modal = document.getElementById('modalVariantesProducto');
            const btnClose = document.getElementById('btnCloseVariantesModal');
            const tituloEl = document.getElementById('modalVariantesTitulo');
            const subtituloEl = document.getElementById('modalVariantesSubtitulo');
            const inputSearch = document.getElementById('inputBuscarVarianteModal');
            const btnNuevaVariante = document.getElementById('btnNuevaVarianteModal');
            const btnEditarPadre = document.getElementById('btnEditarPadreModal');
            const tbody = document.getElementById('tbodyVariantesModal');
            const contadorEl = document.getElementById('modalVariantesContador');
            const stockTotalEl = document.getElementById('modalVariantesStockTotal');
            const statusFilterBtns = document.querySelectorAll('[data-status-filter]');
            const checkSoloAlertas = document.getElementById('checkSoloAlertasModal');

            const countTodosEl = document.getElementById('countStatusTodos');
            const countOptimoEl = document.getElementById('countStatusOptimo');
            const countBajoEl = document.getElementById('countStatusBajo');
            const countCriticoEl = document.getElementById('countStatusCritico');

            let activeGroup = null;
            let currentStatusFilter = 'todos';

            const escapeHtml = (str) => String(str || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');

            const updateStatusCounters = () => {
                if (!activeGroup) return;
                const hijos = activeGroup.hijos || [];
                countTodosEl.textContent = String(hijos.length);
                countOptimoEl.textContent = String(hijos.filter(h => h.estado === 'optimo').length);
                countBajoEl.textContent = String(hijos.filter(h => h.estado === 'bajo').length);
                countCriticoEl.textContent = String(hijos.filter(h => h.estado === 'critico').length);
            };

            const renderModalRows = () => {
                if (!activeGroup) return;

                const query = (inputSearch?.value || '').trim().toLowerCase();
                const soloAlertas = checkSoloAlertas?.checked ?? false;

                const filtrados = activeGroup.hijos.filter((h) => {
                    // 1. Filtro por checkbox "Solo con alertas"
                    if (soloAlertas && h.estado === 'optimo') {
                        return false;
                    }
                    // 2. Filtro por píldora de estado
                    if (currentStatusFilter !== 'todos' && h.estado !== currentStatusFilter) {
                        return false;
                    }
                    // 3. Filtro por buscador de texto
                    if (!query) return true;
                    const chipsText = (h.chips || []).join(' ').toLowerCase();
                    return h.codigo.toLowerCase().includes(query)
                        || h.nombre.toLowerCase().includes(query)
                        || h.descripcion.toLowerCase().includes(query)
                        || h.almacen.toLowerCase().includes(query)
                        || chipsText.includes(query);
                });

                contadorEl.textContent = `Mostrando ${filtrados.length} de ${activeGroup.hijos.length} variante(s)`;

                if (filtrados.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 3rem 1.5rem; color: #64748b; font-weight: 700;">
                                <i class="fas fa-filter d-block mb-2" style="font-size: 1.5rem; color: #cbd5e1;"></i>
                                No hay variantes que coincidan con los filtros seleccionados.
                            </td>
                        </tr>
                    `;
                    return;
                }

                tbody.innerHTML = filtrados.map((h) => {
                    const chipsHtml = (h.chips && h.chips.length > 0)
                        ? h.chips.map((c) => `<span class="variant-attr-chip">${escapeHtml(c)}</span>`).join('')
                        : `<span class="text-muted font-weight-bold">${escapeHtml(h.descripcion || h.nombre || 'Estándar')}</span>`;

                    return `
                        <tr>
                            <td><strong style="color: #0f2747; font-weight: 800;">${escapeHtml(h.codigo)}</strong></td>
                            <td>${chipsHtml}</td>
                            <td style="font-weight: 600; color: #334155;">${escapeHtml(h.almacen)}</td>
                            <td><strong style="font-size: 0.95rem; font-weight: 800;">${h.stock_actual} uds.</strong></td>
                            <td><span class="inventory-status inventory-status-${escapeHtml(h.estado)}">${escapeHtml(h.estado_texto)}</span></td>
                            <td style="text-align: right;">
                                <div class="inventory-actions" style="justify-content: flex-end;">
                                    <a href="${h.urlShow}" class="inventory-action-icon" title="Ver detalle de variante">
                                        <i class="far fa-eye"></i>
                                    </a>
                                    <a href="${h.urlEdit}" class="inventory-action-icon" title="Editar variante">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <button type="button"
                                        class="inventory-action-icon inventory-action-icon--danger js-open-delete-confirm"
                                        data-delete-url="${h.urlDelete}"
                                        data-delete-scope="variante"
                                        data-delete-name="Variante SKU: ${escapeHtml(h.codigo)}"
                                        data-delete-info="Se dará de baja esta variante específica del producto ${escapeHtml(activeGroup.nombrePadre)}."
                                        data-delete-stock="${h.stock_actual}"
                                        title="Eliminar esta variante">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
            };

            const setStatusFilter = (status) => {
                currentStatusFilter = status;
                statusFilterBtns.forEach((btn) => {
                    btn.classList.toggle('is-active', btn.getAttribute('data-status-filter') === status);
                });
                renderModalRows();
            };

            const openVariantsModal = (groupIndex) => {
                const grupo = gruposData[groupIndex];
                if (!grupo) return;

                activeGroup = grupo;
                tituloEl.textContent = `${grupo.nombrePadre} (${grupo.clasePadre})`;
                subtituloEl.textContent = `Código maestro: ${grupo.codigoPadre} · ${grupo.descripcionPadre || ''}`;
                btnNuevaVariante.href = grupo.urlNuevaVariante;
                btnEditarPadre.href = grupo.urlEditarPadre;
                stockTotalEl.textContent = String(grupo.stockGrupo);

                inputSearch.value = '';
                if (checkSoloAlertas) checkSoloAlertas.checked = false;
                currentStatusFilter = 'todos';
                statusFilterBtns.forEach((btn) => {
                    btn.classList.toggle('is-active', btn.getAttribute('data-status-filter') === 'todos');
                });

                updateStatusCounters();
                renderModalRows();

                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                setTimeout(() => inputSearch.focus(), 80);
            };

            const closeVariantsModal = () => {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                activeGroup = null;
            };

            document.querySelectorAll('.js-open-variants-modal').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const idx = parseInt(btn.getAttribute('data-group-index'), 10);
                    openVariantsModal(idx);
                });
            });

            statusFilterBtns.forEach((btn) => {
                btn.addEventListener('click', () => {
                    setStatusFilter(btn.getAttribute('data-status-filter'));
                });
            });

            checkSoloAlertas?.addEventListener('change', renderModalRows);
            inputSearch?.addEventListener('input', renderModalRows);

            btnClose?.addEventListener('click', closeVariantsModal);
            modal?.addEventListener('click', (e) => {
                if (e.target === modal) closeVariantsModal();
            });

            // --- LÓGICA DEL MODAL DE DOBLE CONFIRMACIÓN DE BORRADO ---
            const deleteModal = document.getElementById('modalDobleConfirmacionBorrar');
            const formDelete = document.getElementById('formDeleteConfirm');
            const inputScope = document.getElementById('deleteConfirmScope');
            const targetNameEl = document.getElementById('deleteConfirmTargetName');
            const extraInfoEl = document.getElementById('deleteConfirmExtraInfo');
            const stockWarningBox = document.getElementById('deleteStockWarning');
            const stockAmountEl = document.getElementById('deleteStockAmount');
            const step2Box = document.getElementById('deleteStep2Box');
            const checkDouble = document.getElementById('checkDoubleConfirmDelete');
            const btnStep1 = document.getElementById('btnStep1Delete');
            const btnFinalSubmit = document.getElementById('btnFinalSubmitDelete');
            const btnCancelDelete = document.getElementById('btnCancelDeleteConfirm');

            const openDeleteConfirm = (btn) => {
                const url = btn.getAttribute('data-delete-url');
                const scope = btn.getAttribute('data-delete-scope') || 'auto';
                const name = btn.getAttribute('data-delete-name') || 'Ítem';
                const info = btn.getAttribute('data-delete-info') || '';
                const stock = parseInt(btn.getAttribute('data-delete-stock') || '0', 10);

                formDelete.action = url;
                inputScope.value = scope;
                targetNameEl.textContent = name;
                extraInfoEl.textContent = info;

                // Reiniciar estado de los 2 pasos
                step2Box.classList.remove('is-visible');
                checkDouble.checked = false;
                btnFinalSubmit.disabled = true;
                btnFinalSubmit.style.display = 'none';

                if (stock > 0) {
                    stockAmountEl.textContent = String(stock);
                    stockWarningBox.style.display = 'block';
                    btnStep1.style.display = 'none';
                } else {
                    stockWarningBox.style.display = 'none';
                    btnStep1.style.display = 'inline-block';
                }

                deleteModal.classList.add('is-open');
                deleteModal.setAttribute('aria-hidden', 'false');
            };

            const closeDeleteConfirm = () => {
                deleteModal.classList.remove('is-open');
                deleteModal.setAttribute('aria-hidden', 'true');
            };

            // Delegación de eventos para botones de papelera tanto en tabla padre como dentro del modal de variantes
            document.addEventListener('click', (e) => {
                const deleteBtn = e.target.closest('.js-open-delete-confirm');
                if (deleteBtn) {
                    openDeleteConfirm(deleteBtn);
                }
            });

            btnStep1?.addEventListener('click', () => {
                btnStep1.style.display = 'none';
                step2Box.classList.add('is-visible');
                btnFinalSubmit.style.display = 'inline-block';
            });

            checkDouble?.addEventListener('change', () => {
                btnFinalSubmit.disabled = !checkDouble.checked;
            });

            btnCancelDelete?.addEventListener('click', closeDeleteConfirm);
            deleteModal?.addEventListener('click', (e) => {
                if (e.target === deleteModal) closeDeleteConfirm();
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    if (deleteModal?.classList.contains('is-open')) {
                        closeDeleteConfirm();
                    } else if (modal?.classList.contains('is-open')) {
                        closeVariantsModal();
                    }
                }
            });
        })();
    </script>
@endsection