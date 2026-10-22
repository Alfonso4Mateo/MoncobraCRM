@extends('adminlte::page')

@section('title', 'Matriz de Formación y EPIs - ' . $puesto->nombre)

@section('css')
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .puesto-edit-shell { font-family: 'Manrope', sans-serif; padding: 14px; }

        /* CABECERA ESTILO STITCH (SCREEN 4 & 6) */
        .header-panel {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 20px 24px;
            margin-bottom: 18px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.04);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }
        .header-kicker {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
            margin-bottom: 6px;
        }
        .header-badge-id {
            background: #e0f2fe;
            color: #0369a1;
            padding: 2px 8px;
            border-radius: 6px;
            font-weight: 800;
            font-size: 0.7rem;
        }
        .header-badge-norma {
            background: #f1f5f9;
            color: #475569;
            padding: 2px 8px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 0.7rem;
        }
        .header-title-input {
            font-size: 1.65rem;
            font-weight: 800;
            color: #0f2942;
            border: 1px solid transparent;
            background: transparent;
            padding: 2px 8px;
            margin-left: -8px;
            border-radius: 8px;
            transition: all 0.2s;
            min-width: 260px;
        }
        .header-title-input:hover,
        .header-title-input:focus {
            border-color: #cbd5e1;
            background: #f8fafc;
            outline: none;
        }

        .header-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .btn-top-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 15px;
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 800;
            text-decoration: none !important;
            border: 1px solid transparent;
            transition: all 0.18s ease;
        }
        .btn-top-action--ghost {
            background: #f8fafc;
            border-color: #dbe3ef;
            color: #334155 !important;
        }
        .btn-top-action--ghost:hover { background: #eef2f7; }
        .btn-top-action--csv {
            background: #eef6ff;
            border-color: #bfdbfe;
            color: #1d4ed8 !important;
        }
        .btn-top-action--csv:hover { background: #dbeafe; }

        /* CONTENEDOR PRINCIPAL Y PESTAÑAS */
        .macro-container {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 22px 24px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.04);
        }
        .section-tabs-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            border-bottom: 2px solid #f1f5f9;
            margin-bottom: 22px;
        }
        .section-tabs { display: flex; gap: 8px; }
        .tab-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 12px 18px;
            font-weight: 800;
            font-size: 0.9rem;
            color: #64748b;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.15s ease;
        }
        .tab-btn:hover { color: #0f2942; }
        .tab-btn.is-active { color: #0f2942; border-bottom-color: #0f2942; }
        .tab-btn .tab-count {
            background: #eef2f7;
            color: #64748b;
            font-size: 0.72rem;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 999px;
        }
        .tab-btn.is-active .tab-count { background: #0f2942; color: #fff; }
        .tab-panel { display: none; }
        .tab-panel.is-active { display: block; }

        /* CABECERA INTERNA DE PESTAÑA + BUSCADOR */
        .tab-subhead {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 14px;
            margin-bottom: 18px;
        }
        .tab-subhead h4 { font-weight: 800; color: #0f2942; margin: 0 0 4px 0; font-size: 1.15rem; }
        .tab-subhead p { color: #64748b; margin: 0; font-size: 0.86rem; }
        .tab-search-input {
            border: 1px solid #dbe3ef;
            background: #f8fafc;
            border-radius: 10px;
            padding: 8px 14px;
            font-size: 0.85rem;
            min-width: 250px;
            color: #1e293b;
        }
        .tab-search-input:focus {
            background: #fff;
            border-color: #0284c7;
            outline: none;
        }

        /* PESTAÑA 1: CURSOS OBLIGATORIOS */
        .category-group { margin-bottom: 20px; }
        .category-title {
            font-size: 0.8rem;
            font-weight: 800;
            color: #475569;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 2px solid #f1f5f9;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .course-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 14px;
            border: 1px solid #edf2f7;
            border-radius: 10px;
            margin-bottom: 8px;
            background: #f8fafc;
            transition: all 0.2s;
            gap: 10px;
        }
        .course-item:hover { border-color: #cbd5e1; background: #fff; }
        .course-item.is-checked { background: #f0f9ff; border-color: #bae6fd; }
        .course-name { font-weight: 700; color: #1e293b; font-size: 0.88rem; line-height: 1.3; }

        .course-toggle { position: relative; width: 46px; height: 24px; display: inline-block; margin: 0; flex-shrink: 0; }
        .course-toggle input { opacity: 0; width: 0; height: 0; }
        .course-toggle__track { position: absolute; inset: 0; background: #cbd5e1; border-radius: 999px; transition: all .18s ease; cursor: pointer; }
        .course-toggle__thumb { position: absolute; top: 3px; left: 3px; width: 18px; height: 18px; background: #fff; border-radius: 50%; box-shadow: 0 2px 6px rgba(15, 23, 42, .16); transition: all .18s ease; }
        .course-toggle input:checked + .course-toggle__track { background: #0f2942; }
        .course-toggle input:checked + .course-toggle__track .course-toggle__thumb { transform: translateX(22px); }

        /* PESTAÑA 2: DOTACIÓN DE EPIS (DISEÑO STITCH SCREEN 6) */
        .epi-banner-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            padding: 12px 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            margin-bottom: 16px;
        }
        .epi-banner-meta {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 0.8rem;
            font-weight: 700;
            color: #475569;
        }
        .btn-pdf-epi {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 16px;
            border-radius: 10px;
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: #fff !important;
            font-size: 0.82rem;
            font-weight: 800;
            text-decoration: none !important;
            box-shadow: 0 6px 14px rgba(220, 38, 38, 0.22);
            transition: transform 0.15s ease, opacity 0.15s ease;
        }
        .btn-pdf-epi:hover { transform: translateY(-1px); opacity: 0.95; }

        .btn-pdf-epi--download {
            background: #0f2942;
            box-shadow: 0 6px 14px rgba(15, 41, 66, 0.18);
        }

        .puesto-assign-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-height: 520px;
            overflow-y: auto;
            padding-right: 4px;
        }
        .puesto-assign-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 18px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 12px;
            transition: all 0.18s ease;
            gap: 14px;
        }
        .puesto-assign-item:hover { border-color: #cbd5e1; background: #fff; }
        .puesto-assign-item.is-selected {
            background: #ffffff;
            border-color: #0284c7;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.07);
        }
        .epi-check-input {
            width: 18px;
            height: 18px;
            accent-color: #0f2942;
            cursor: pointer;
            flex-shrink: 0;
        }
        .epi-item-title-row {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }
        .epi-item-name {
            font-weight: 800;
            color: #0f2942;
            font-size: 0.92rem;
            text-transform: uppercase;
        }
        .epi-norma-pill {
            background: #e2e8f0;
            color: #334155;
            font-size: 0.68rem;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 6px;
            letter-spacing: 0.03em;
        }
        .puesto-assign-item__desc {
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 3px;
            display: block;
        }

        /* CONTROL DE UNIDADES (- 2 +) ESTILO STITCH SCREEN 6 */
        .qty-stepper {
            display: inline-flex;
            align-items: center;
            background: #fff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            overflow: hidden;
            height: 34px;
        }
        .qty-stepper.is-disabled {
            opacity: 0.45;
            pointer-events: none;
            background: #f1f5f9;
        }
        .qty-step-btn {
            width: 30px;
            height: 100%;
            border: none;
            background: #f8fafc;
            color: #334155;
            font-weight: 800;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background 0.15s;
        }
        .qty-step-btn:hover { background: #e2e8f0; }
        .qty-input {
            width: 46px;
            height: 100%;
            border: none;
            border-left: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
            text-align: center;
            font-weight: 800;
            font-size: 0.88rem;
            color: #0f2942;
            -moz-appearance: textfield;
        }
        .qty-input::-webkit-outer-spin-button,
        .qty-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

        /* BOTONES DE GUARDADO */
        .btn-save {
            background: #0f2942;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 11px 24px;
            font-weight: 800;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            box-shadow: 0 8px 16px rgba(15, 41, 66, 0.16);
            transition: all 0.18s;
        }
        .btn-save:hover { background: #173e67; color: white; transform: translateY(-1px); }

        /* TARJETAS KPI INFERIORES (SCREEN 6) */
        .epi-kpi-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            margin-top: 20px;
        }
        .epi-kpi-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 18px;
        }
        .epi-kpi-card__label {
            font-size: 0.68rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #64748b;
            margin-bottom: 6px;
        }
        .epi-kpi-card__value {
            font-size: 1.35rem;
            font-weight: 800;
            color: #0f2942;
        }
        .epi-kpi-card__sub {
            font-size: 0.76rem;
            color: #64748b;
            margin-top: 4px;
        }

        .epis-empty { padding: 34px; text-align: center; border: 2px dashed #cbd5e1; border-radius: 12px; color: #64748b; }

        @media (max-width: 900px) {
            .epi-kpi-grid { grid-template-columns: 1fr; }
            .section-tabs { overflow-x: auto; }
        }
    </style>
@endsection

@section('content')
    <section class="puesto-edit-shell">
        @if (session('success'))
            <div class="alert alert-success" style="border-radius: 12px; font-weight: 700;">
                <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger" style="border-radius: 12px; font-weight: 600;">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- CABECERA DEL PERFIL FORMATIVO -->
        <div class="header-panel">
            <div>
                <div class="header-kicker">
                    <span>CONFIGURANDO MATRIZ PARA:</span>
                    <span class="header-badge-id">ID PUESTO: PRF-{{ str_pad((string) $puesto->id, 3, '0', STR_PAD_LEFT) }}</span>
                    <span class="header-badge-norma">ISO 45001 • RD 773/1997</span>
                </div>

                <form action="{{ route('puestos.update', $puesto->id) }}" method="POST" class="d-flex align-items-center" style="gap: 8px;">
                    @csrf
                    @method('PUT')
                    <input type="text" name="nombre" value="{{ $puesto->nombre }}" class="header-title-input" required title="Haz clic para renombrar el puesto">
                    <button type="submit" class="btn btn-sm btn-outline-secondary" style="border-radius: 8px; font-weight: 700;" title="Guardar nuevo nombre">
                        <i class="fas fa-save"></i>
                    </button>
                </form>
            </div>

            <div class="header-actions">
                <a href="{{ route('puestos.index') }}" class="btn-top-action btn-top-action--ghost">
                    <i class="fas fa-arrow-left"></i> Volver al Catálogo
                </a>

                <a href="{{ route('puestos.auditoria', $puesto->id) }}" class="btn-top-action btn-top-action--ghost">
                    <i class="fas fa-chart-bar"></i> Ver Auditoría
                </a>

                <a href="{{ route('puestos.auditoria.export', $puesto->id) }}" class="btn-top-action btn-top-action--csv">
                    <i class="fas fa-file-excel"></i> Descargar Auditoría CSV
                </a>
            </div>
        </div>

        <!-- CONTENEDOR PRINCIPAL DE PESTAÑAS -->
        <div class="macro-container">

            <div class="section-tabs-bar">
                <div class="section-tabs" id="puesto-tabs">
                    <button type="button" class="tab-btn" data-tab="cursos">
                        <i class="fas fa-graduation-cap"></i> CURSOS OBLIGATORIOS
                        <span class="tab-count" id="badge-count-cursos">{{ count($cursosAsignados) }}</span>
                    </button>
                    <button type="button" class="tab-btn" data-tab="epis">
                        <i class="fas fa-hard-hat"></i> DOTACIÓN DE EPIS
                        <span class="tab-count" id="badge-count-epis">{{ count($episAsignados) }}</span>
                    </button>
                </div>

                <div style="font-size: 0.78rem; color: #64748b; font-weight: 700;">
                    <i class="fas fa-shield-alt text-primary mr-1"></i> Norma Oficial SST-0840-002-1-2 Rev. 4
                </div>
            </div>

            <!-- ===================== PESTAÑA 1: CURSOS OBLIGATORIOS ===================== -->
            <div class="tab-panel" data-panel="cursos">
                <div class="tab-subhead">
                    <div>
                        <h4>Asignación de Cursos Obligatorios</h4>
                        <p>Activa los cursos que el sistema deberá exigir e inyectar automáticamente a cualquier trabajador que ocupe este puesto.</p>
                    </div>
                    <input type="search" id="search-cursos-input" class="tab-search-input" placeholder="Filtrar curso por nombre..." autocomplete="off">
                </div>

                <form action="{{ route('puestos.sync-cursos', $puesto->id) }}" method="POST">
                    @csrf

                    <div class="row" id="cursos-categories-grid">
                        @forelse ($cursosPorCategoria as $categoria => $cursos)
                            <div class="col-md-6 col-lg-4 js-curso-cat-col">
                                <div class="category-group">
                                    <div class="category-title">
                                        <span>{{ $categoria }}</span>
                                        <span class="badge badge-light">{{ $cursos->count() }} módulos</span>
                                    </div>

                                    @foreach ($cursos as $curso)
                                        @php
                                            $cursoActivo = in_array($curso->id, $cursosAsignados);
                                        @endphp
                                        <div class="course-item js-curso-row {{ $cursoActivo ? 'is-checked' : '' }}" data-name="{{ mb_strtolower($curso->nombre) }}">
                                            <span class="course-name">{{ $curso->nombre }}</span>

                                            <label class="course-toggle" title="Marcar como obligatorio">
                                                <input type="checkbox" name="cursos[]" value="{{ $curso->id }}" class="js-curso-checkbox" @checked($cursoActivo)>
                                                <span class="course-toggle__track"><span class="course-toggle__thumb"></span></span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="epis-empty">
                                    <i class="fas fa-graduation-cap fa-2x mb-2 text-muted"></i>
                                    <p class="mb-0">No hay cursos registrados en el catálogo.</p>
                                </div>
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center flex-wrap" style="gap: 12px;">
                        <span class="text-muted small font-weight-bold">
                            <i class="fas fa-info-circle mr-1"></i> Al guardar, los cursos activos se asignarán automáticamente a los trabajadores con este perfil.
                        </span>
                        <button type="submit" class="btn-save">
                            <i class="fas fa-save"></i> Guardar Matriz de Cursos
                        </button>
                    </div>
                </form>
            </div>

            <!-- ===================== PESTAÑA 2: DOTACIÓN DE EPIS ===================== -->
            <div class="tab-panel" data-panel="epis">
                <div class="tab-subhead">
                    <div>
                        <h4>Dotación de EPIs Obligatorios <span class="header-badge-norma ml-1">RD 773/1997</span></h4>
                        <p>Selecciona y parametriza los Equipos de Protección Individual obligatorios que este puesto exige según evaluación de riesgos.</p>
                    </div>
                    <input type="search" id="search-epis-input" class="tab-search-input" placeholder="Buscar EPI o norma EN..." autocomplete="off">
                </div>

                <!-- BARRA DE ACCIONES DOCUMENTALES EPI -->
                <div class="epi-banner-actions">
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        @can('cursos.view')
                            <button type="button" class="btn-pdf-epi" data-toggle="modal" data-target="#modal-config-pdf" style="cursor: pointer;">
                                <i class="fas fa-sliders-h"></i> Parametrizar y Sacar Documento EPIs
                            </button>
                        @endcan
                    </div>

                    <div class="epi-banner-meta">
                        <span><i class="fas fa-check-circle text-success mr-1"></i> Formato Oficial SST-0840-002-1-2</span>
                        <span><i class="fas fa-layer-group text-primary mr-1"></i> <strong id="epi-prescritos-text">{{ count($episAsignados) }}</strong> de {{ $episDisponibles->count() }} ítems prescritos</span>
                    </div>
                </div>

                <form action="{{ route('puestos.sync-epis', $puesto->id) }}" method="POST">
                    @csrf

                    @if ($episDisponibles->isEmpty())
                        <div class="epis-empty">
                            <i class="fas fa-hard-hat fa-2x mb-2 text-muted"></i>
                            <p class="mb-1">Todavía no hay EPIs activos en el catálogo.</p>
                            <a href="{{ route('epis.index') }}" style="font-weight: 800; color: #0369a1;">
                                Ir a Gestionar Catálogo de EPIs <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    @else
                        <div class="puesto-assign-list" id="epis-assign-list">
                            @foreach ($episDisponibles as $epi)
                                @php
                                    $estaAsignado = array_key_exists($epi->id, $episAsignados);
                                    $cantidadActual = $estaAsignado ? (int) $episAsignados[$epi->id]['cantidad'] : 1;
                                @endphp
                                <div class="puesto-assign-item js-epi-row {{ $estaAsignado ? 'is-selected' : '' }}"
                                     data-search="{{ mb_strtolower($epi->nombre . ' ' . ($epi->categoria ?? '') . ' ' . ($epi->descripcion ?? '')) }}">
                                    <label style="margin: 0; display: flex; align-items: flex-start; gap: 12px; cursor: pointer; flex: 1; min-width: 0;">
                                        <input
                                            type="checkbox"
                                            name="epis[]"
                                            value="{{ $epi->id }}"
                                            class="epi-checkbox epi-check-input mt-1"
                                            data-target="epi-qty-{{ $epi->id }}"
                                            @checked($estaAsignado)
                                        >
                                        <div style="min-width: 0;">
                                            <div class="epi-item-title-row">
                                                <span class="epi-item-name">{{ $epi->nombre }}</span>
                                                @if (!empty($epi->categoria))
                                                    <span class="epi-norma-pill">{{ $epi->categoria }}</span>
                                                @endif
                                            </div>
                                            @if ($epi->descripcion)
                                                <span class="puesto-assign-item__desc">{{ $epi->descripcion }}</span>
                                            @else
                                                <span class="puesto-assign-item__desc">Homologación CE / UNE-EN reglamentaria</span>
                                            @endif
                                        </div>
                                    </label>

                                    <div style="display: flex; align-items: center; gap: 10px; flex-shrink: 0;">
                                        <span style="font-size: 0.72rem; color: #64748b; font-weight: 800; letter-spacing: 0.05em;">UDS:</span>
                                        <div class="qty-stepper {{ !$estaAsignado ? 'is-disabled' : '' }}" id="stepper-{{ $epi->id }}">
                                            <button type="button" class="qty-step-btn js-qty-step" data-target="epi-qty-{{ $epi->id }}" data-step="-1">−</button>
                                            <input
                                                type="number"
                                                name="cantidades[{{ $epi->id }}]"
                                                id="epi-qty-{{ $epi->id }}"
                                                class="qty-input js-epi-qty-input"
                                                value="{{ $cantidadActual }}"
                                                min="1"
                                                max="99"
                                                @disabled(!$estaAsignado)
                                            >
                                            <button type="button" class="qty-step-btn js-qty-step" data-target="epi-qty-{{ $epi->id }}" data-step="1">+</button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center flex-wrap" style="gap: 12px;">
                            <span class="text-muted small font-weight-bold">
                                <i class="fas fa-info-circle mr-1"></i> Cualquier cambio se reflejará inmediatamente al generar la Ficha Oficial de EPIs en PDF.
                            </span>
                            <button type="submit" class="btn-save">
                                <i class="fas fa-save"></i> Guardar Dotación de EPIs
                            </button>
                        </div>

                        <!-- TARJETAS INFERIORES DE RESUMEN (STITCH SCREEN 6) -->
                        <div class="epi-kpi-grid">
                            <div class="epi-kpi-card">
                                <div class="epi-kpi-card__label">TOTAL DOTACIÓN ESTIMADA</div>
                                <div class="epi-kpi-card__value"><span id="kpi-total-unidades">0</span> <small style="font-size: 0.85rem; font-weight: 600; color: #64748b;">unidades / operario</small></div>
                                <div class="epi-kpi-card__sub">Equipamiento reglamentario asignado al perfil</div>
                            </div>

                            <div class="epi-kpi-card">
                                <div class="epi-kpi-card__label">CONFORMIDAD CON EVALUACIÓN PRL</div>
                                <div class="epi-kpi-card__value" style="color: #0d9488;">100% <small style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase;">Homologado</small></div>
                                <div class="epi-kpi-card__sub">Alineado con matriz de riesgos de {{ $puesto->nombre }}</div>
                            </div>

                            <div class="epi-kpi-card">
                                <div class="epi-kpi-card__label">DOCUMENTO OFICIAL VINCULADO</div>
                                <div class="epi-kpi-card__value">SST-0840-002</div>
                                <div class="epi-kpi-card__sub">Edición Rev. 4 • Dpto. Prevención Moncobra, S.A.</div>
                            </div>
                        </div>
                    @endif
                </form>
            </div>

        </div>
    </section>

    <!-- MODAL DE CONFIGURACIÓN DE PDF -->
    <div class="modal fade" id="modal-config-pdf" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius: 14px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                <div class="modal-header" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; border-radius: 14px 14px 0 0; padding: 18px 24px;">
                    <h5 class="modal-title" style="font-weight: 800; color: #0f2942;"><i class="fas fa-sliders-h mr-2 text-primary"></i> Parametrizar Documento Oficial</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                
                <!-- Fíjate: es un form GET con target="_blank" -->
                <form action="{{ route('puestos.epis.pdf', $puesto->id) }}" method="GET" target="_blank">
                    <div class="modal-body" style="padding: 24px;">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label style="font-size: 0.8rem; font-weight: 800; color: #475569; text-transform: uppercase;">Código del Documento</label>
                                <input type="text" name="codigo_iso" class="form-control" value="SST-0840-002-1-2" style="border-radius: 8px;">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label style="font-size: 0.8rem; font-weight: 800; color: #475569; text-transform: uppercase;">Revisión / Versión</label>
                                <input type="text" name="revision_iso" class="form-control" value="Rev. 4" style="border-radius: 8px;">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label style="font-size: 0.8rem; font-weight: 800; color: #475569; text-transform: uppercase;">Fecha de Emisión</label>
                                <input type="text" name="fecha_emision" class="form-control" value="{{ now()->format('d/m/Y') }}" style="border-radius: 8px;">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label style="font-size: 0.8rem; font-weight: 800; color: #475569; text-transform: uppercase;">Marco Reglamentario PRL</label>
                                <input type="text" name="marco_prl" class="form-control" value="RD 773/1997" style="border-radius: 8px;">
                            </div>
                        </div>
                        
                        <hr style="border-color: #e2e8f0; margin: 20px 0;">
                        <h6 style="font-weight: 800; color: #0f2942; margin-bottom: 16px;">Textos de Sellos y Departamentos</h6>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label style="font-size: 0.8rem; font-weight: 800; color: #475569; text-transform: uppercase;">Sello Prevención SST</label>
                                    <input type="text" name="sello_prevencion" class="form-control" value="FIRMADO / HOMOLOGADO" style="border-radius: 8px;">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label style="font-size: 0.8rem; font-weight: 800; color: #475569; text-transform: uppercase;">Sello Resp. Delegación</label>
                                    <input type="text" name="sello_delegacion" class="form-control" value="CONFORME / VALIDADO" style="border-radius: 8px;">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label style="font-size: 0.8rem; font-weight: 800; color: #475569; text-transform: uppercase;">Dpto. Prevención (Texto bajo sello)</label>
                                    <input type="text" name="dpto_prevencion" class="form-control" value="Dpto. SST Moncobra, S.A." style="border-radius: 8px;">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label style="font-size: 0.8rem; font-weight: 800; color: #475569; text-transform: uppercase;">Dpto. Delegación (Texto bajo sello)</label>
                                    <input type="text" name="dpto_delegacion" class="form-control" value="Jefatura de Producción / Planta" style="border-radius: 8px;">
                                </div>
                            </div>
                    </div>
                    
                    <div class="modal-footer" style="background: #f8fafc; border-top: 1px solid #e2e8f0; border-radius: 0 0 14px 14px; padding: 16px 24px;">
                        <button type="button" class="btn btn-light" data-dismiss="modal" style="font-weight: 700; border-radius: 8px;">Cancelar</button>
                        <button type="submit" name="download" value="0" class="btn btn-danger" style="font-weight: 700; border-radius: 8px; background: linear-gradient(135deg, #dc2626, #b91c1c); border: none;" onclick="$('#modal-config-pdf').modal('hide');">
                            <i class="fas fa-file-pdf mr-1"></i> Visualizar PDF
                        </button>
                        <button type="submit" name="download" value="1" class="btn btn-primary" style="font-weight: 700; border-radius: 8px; background: #0f2942; border: none;" onclick="$('#modal-config-pdf').modal('hide');">
                            <i class="fas fa-download mr-1"></i> Descargar Archivo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // ---------------------------------------------------------
            // 1. PESTAÑAS (CURSOS / EPIS)
            // ---------------------------------------------------------
            const tabButtons = document.querySelectorAll('#puesto-tabs .tab-btn');
            const tabPanels = document.querySelectorAll('.tab-panel');
            const storageKey = 'puestoEditActiveTab';

            const activateTab = (tabName) => {
                tabButtons.forEach(b => b.classList.toggle('is-active', b.dataset.tab === tabName));
                tabPanels.forEach(p => p.classList.toggle('is-active', p.dataset.panel === tabName));
                localStorage.setItem(storageKey, tabName);
            };

            tabButtons.forEach(btn => {
                btn.addEventListener('click', () => activateTab(btn.dataset.tab));
            });

            const urlParams = new URLSearchParams(window.location.search);
            const tabFromUrl = urlParams.get('tab');

            @php
                $oldTieneEpis = old('epis') !== null || old('cantidades') !== null;
                $oldTieneCursos = old('cursos') !== null;
            @endphp

            let defaultTab = 'cursos';
            if (tabFromUrl === 'epis' || tabFromUrl === 'cursos') {
                defaultTab = tabFromUrl;
            } else {
                @if ($oldTieneEpis)
                    defaultTab = 'epis';
                @elseif ($oldTieneCursos)
                    defaultTab = 'cursos';
                @else
                    defaultTab = localStorage.getItem(storageKey) || 'cursos';
                @endif
            }

            activateTab(defaultTab);

            // ---------------------------------------------------------
            // 2. BUSCADOR EN VIVO DE CURSOS Y CONTADOR
            // ---------------------------------------------------------
            const searchCursos = document.getElementById('search-cursos-input');
            const badgeCountCursos = document.getElementById('badge-count-cursos');

            document.querySelectorAll('.js-curso-checkbox').forEach(chk => {
                chk.addEventListener('change', function () {
                    const row = this.closest('.js-curso-row');
                    if (row) row.classList.toggle('is-checked', this.checked);
                    if (badgeCountCursos) {
                        badgeCountCursos.textContent = document.querySelectorAll('.js-curso-checkbox:checked').length;
                    }
                });
            });

            if (searchCursos) {
                searchCursos.addEventListener('input', function () {
                    const term = this.value.trim().toLowerCase();
                    document.querySelectorAll('.js-curso-cat-col').forEach(col => {
                        let visibleInCat = 0;
                        col.querySelectorAll('.js-curso-row').forEach(row => {
                            const match = term === '' || (row.dataset.name || '').includes(term);
                            row.style.display = match ? '' : 'none';
                            if (match) visibleInCat++;
                        });
                        col.style.display = visibleInCat > 0 ? '' : 'none';
                    });
                });
            }

            // ---------------------------------------------------------
            // 3. LÓGICA DE EPIS: CHECKBOXES, BOTONES (- / +) Y KPIS EN VIVO
            // ---------------------------------------------------------
            const badgeCountEpis = document.getElementById('badge-count-epis');
            const prescritosText = document.getElementById('epi-prescritos-text');
            const kpiTotalUnidades = document.getElementById('kpi-total-unidades');
            const searchEpis = document.getElementById('search-epis-input');

            const recalculateEpiTotals = () => {
                let checkedCount = 0;
                let totalUnits = 0;

                document.querySelectorAll('.epi-checkbox').forEach(chk => {
                    if (chk.checked) {
                        checkedCount++;
                        const inputQty = document.getElementById(chk.dataset.target);
                        const val = parseInt(inputQty ? inputQty.value : '1', 10);
                        totalUnits += Number.isFinite(val) && val > 0 ? val : 1;
                    }
                });

                if (badgeCountEpis) badgeCountEpis.textContent = checkedCount;
                if (prescritosText) prescritosText.textContent = checkedCount;
                if (kpiTotalUnidades) kpiTotalUnidades.textContent = totalUnits;
            };

            document.querySelectorAll('.epi-checkbox').forEach(chk => {
                chk.addEventListener('change', function () {
                    const targetId = this.getAttribute('data-target');
                    const inputQty = document.getElementById(targetId);
                    const row = this.closest('.js-epi-row');
                    const stepper = row ? row.querySelector('.qty-stepper') : null;

                    if (row) row.classList.toggle('is-selected', this.checked);
                    if (stepper) stepper.classList.toggle('is-disabled', !this.checked);

                    if (inputQty) {
                        if (this.checked) {
                            inputQty.disabled = false;
                        } else {
                            inputQty.disabled = true;
                            inputQty.value = 1;
                        }
                    }
                    recalculateEpiTotals();
                });
            });

            document.querySelectorAll('.js-qty-step').forEach(btn => {
                btn.addEventListener('click', function () {
                    const inputQty = document.getElementById(this.dataset.target);
                    if (!inputQty || inputQty.disabled) return;
                    const step = parseInt(this.dataset.step || '0', 10);
                    const current = parseInt(inputQty.value || '1', 10);
                    const nextVal = Math.max(1, Math.min(99, current + step));
                    inputQty.value = nextVal;
                    recalculateEpiTotals();
                });
            });

            document.querySelectorAll('.js-epi-qty-input').forEach(input => {
                input.addEventListener('input', recalculateEpiTotals);
            });

            if (searchEpis) {
                searchEpis.addEventListener('input', function () {
                    const term = this.value.trim().toLowerCase();
                    document.querySelectorAll('.js-epi-row').forEach(row => {
                        const match = term === '' || (row.dataset.search || '').includes(term);
                        row.style.display = match ? '' : 'none';
                    });
                });
            }

            recalculateEpiTotals();
        });
    </script>
@endsection