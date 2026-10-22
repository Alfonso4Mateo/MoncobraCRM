@extends('adminlte::page')

@section('title', 'Perfiles Formativos (Puestos)')

@section('css')
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .puestos-shell { font-family: 'Manrope', sans-serif; padding: 14px; }

        /* CABECERA PRINCIPAL */
        .puestos-hero {
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
        .puestos-hero__kicker {
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.09em;
            color: #64748b;
            margin-bottom: 4px;
        }
        .puestos-hero h1 {
            margin: 0 0 4px 0;
            font-size: 1.65rem;
            font-weight: 800;
            color: #0f2942;
        }
        .puestos-hero h1 span {
            font-weight: 600;
            color: #64748b;
        }
        .puestos-hero p {
            margin: 0;
            color: #64748b;
            font-size: 0.9rem;
        }

        .puestos-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 1px solid transparent;
            border-radius: 10px;
            padding: 9px 15px;
            font-weight: 800;
            font-size: 0.82rem;
            transition: all 0.18s ease;
            text-decoration: none !important;
            cursor: pointer;
        }
        .puestos-btn--primary {
            background: #0f2942;
            color: #fff !important;
            box-shadow: 0 6px 14px rgba(15, 41, 66, 0.16);
        }
        .puestos-btn--primary:hover { background: #173e67; transform: translateY(-1px); }
        .puestos-btn--epi {
            background: linear-gradient(135deg, #0284c7, #0369a1);
            color: #fff !important;
        }
        .puestos-btn--epi:hover { opacity: 0.94; transform: translateY(-1px); }
        .puestos-btn--ghost {
            background: #f8fafc;
            border-color: #dbe3ef;
            color: #334155 !important;
        }
        .puestos-btn--ghost:hover { background: #eef2f7; }

        /* TARJETAS KPI SUPERIORES (SCREEN 5) */
        .puestos-kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }
        .puestos-kpi-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px 18px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.02);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .puestos-kpi-card__label {
            font-size: 0.68rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
            margin-bottom: 6px;
        }
        .puestos-kpi-card__value {
            font-size: 1.7rem;
            font-weight: 800;
            color: #0f2942;
            line-height: 1.1;
        }
        .puestos-kpi-card__sub {
            font-size: 0.74rem;
            color: #64748b;
            margin-top: 6px;
            font-weight: 600;
        }
        .puestos-kpi-card__icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: #f1f5f9;
            color: #0f2942;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            flex-shrink: 0;
        }

        /* BARRA DE CREACIÓN RÁPIDA Y FILTRO (SCREEN 5) */
        .create-toolbar {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 14px 18px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.02);
        }
        .create-toolbar__form {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
            min-width: 280px;
        }
        .create-toolbar__input {
            flex: 1;
            max-width: 420px;
            border: 1px solid #dbe3ef;
            background: #f8fafc;
            border-radius: 10px;
            padding: 9px 14px;
            font-size: 0.86rem;
            font-weight: 600;
            color: #1e293b;
        }
        .create-toolbar__input:focus {
            background: #fff;
            border-color: #0f2942;
            outline: none;
        }
        .create-toolbar__search {
            border: 1px solid #dbe3ef;
            background: #f8fafc;
            border-radius: 10px;
            padding: 9px 14px;
            font-size: 0.85rem;
            min-width: 230px;
            color: #1e293b;
        }
        .create-toolbar__search:focus {
            background: #fff;
            border-color: #0284c7;
            outline: none;
        }

        /* TARJETAS DE PUESTO (SCREEN 5) */
        .puesto-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 18px 20px;
            margin-bottom: 18px;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.03);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: calc(100% - 18px);
            transition: all 0.2s ease;
        }
        .puesto-card:hover {
            border-color: #0f2942;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.07);
            transform: translateY(-2px);
        }
        .puesto-card__top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }
        .puesto-id-pill {
            background: #f1f5f9;
            color: #475569;
            font-size: 0.68rem;
            font-weight: 800;
            padding: 3px 8px;
            border-radius: 6px;
            letter-spacing: 0.04em;
        }
        .puesto-status-pill {
            font-size: 0.68rem;
            font-weight: 800;
            padding: 3px 8px;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .puesto-status-pill--ready {
            background: #dcfce7;
            color: #166534;
        }
        .puesto-status-pill--pending {
            background: #fef3c7;
            color: #92400e;
        }

        .puesto-title {
            font-size: 1.15rem;
            font-weight: 800;
            color: #0f2942;
            margin: 0 0 12px 0;
            line-height: 1.25;
            word-break: break-word;
        }

        .puesto-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 14px;
            font-size: 0.8rem;
            font-weight: 700;
            color: #475569;
        }
        .puesto-stat-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 4px 9px;
            border-radius: 8px;
            text-decoration: none !important;
            color: #334155;
            transition: background 0.15s;
        }
        .puesto-stat-badge:hover {
            background: #eef2f7;
            color: #0f2942;
        }
        .puesto-stat-badge--epi {
            background: #f0f9ff;
            border-color: #bae6fd;
            color: #0369a1;
        }
        .puesto-stat-badge--epi:hover {
            background: #e0f2fe;
            color: #075985;
        }

        .puesto-card__footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 12px;
            border-top: 1px dashed #e2e8f0;
            gap: 8px;
        }
        .puesto-card__footer-label {
            font-size: 0.72rem;
            font-weight: 700;
            color: #94a3b8;
        }
        .puesto-actions-group {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .puesto-icon-btn {
            width: 34px;
            height: 34px;
            border-radius: 9px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #334155;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            text-decoration: none !important;
            transition: all 0.15s ease;
            cursor: pointer;
        }
        .puesto-icon-btn:hover { background: #e2e8f0; color: #0f2942; }
        .puesto-icon-btn--audit { background: #eef2ff; border-color: #c7d2fe; color: #4338ca; }
        .puesto-icon-btn--audit:hover { background: #e0e7ff; color: #3730a3; }
        .puesto-icon-btn--pdf { background: #fef2f2; border-color: #fecaca; color: #dc2626; }
        .puesto-icon-btn--pdf:hover { background: #fee2e2; color: #b91c1c; }
        .puesto-icon-btn--config { background: #0f2942; border-color: #0f2942; color: #fff !important; }
        .puesto-icon-btn--config:hover { background: #173e67; color: #fff !important; }
        .puesto-icon-btn--delete { background: #fff1f2; border-color: #fecdd3; color: #e11d48; }
        .puesto-icon-btn--delete:hover { background: #ffe4e6; color: #be123c; }

        /* BANNER INFERIOR INFORMATIVO (SCREEN 5) */
        .protocol-banner {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px 20px;
            margin-top: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 14px;
        }
        .protocol-banner__left {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .protocol-banner__icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: #0f2942;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }
        .protocol-banner h4 {
            margin: 0 0 3px 0;
            font-size: 0.85rem;
            font-weight: 800;
            color: #0f2942;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .protocol-banner p {
            margin: 0;
            font-size: 0.8rem;
            color: #64748b;
        }

        @media (max-width: 992px) {
            .puestos-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 576px) {
            .puestos-kpi-grid { grid-template-columns: 1fr; }
        }
    </style>
@endsection

@section('content')
    @php
        $totalPuestos = $puestos->count();$totalPersonalAdscrito = $puestos->sum('personal_count');$puestosConMatrizCompleta = $puestos->filter(fn ($p) => ($p->cursos_count ?? 0) > 0 && ($p->epis_count ?? 0) > 0)->count();
        $totalAsignacionesEpi =$puestos->sum('epis_count');
    @endphp

    <section class="puestos-shell">
        @if (session('success'))
            <div class="alert alert-success" style="border-radius: 12px; font-weight: 700;">
                <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger" style="border-radius: 12px; font-weight: 700;">
                <i class="fas fa-exclamation-triangle mr-1"></i> {{ session('error') }}
            </div>
        @endif

        <!-- CABECERA -->
        <header class="puestos-hero">
            <div>
                <div class="puestos-hero__kicker">GESTIÓN PREVENTIVA • MATRIZ DE CAPACITACIÓN Y EPIS</div>
                <h1>Perfiles Formativos <span>(Puestos)</span></h1>
                <p>Gestiona los puestos de la empresa y define qué cursos y Equipos de Protección Individual (EPIs) son obligatorios según el RD 773/1997 e ISO 45001.</p>
            </div>

            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                @can('epis.gestion')
                    <a href="{{ route('epis.index') }}" class="puestos-btn puestos-btn--epi">
                        <i class="fas fa-hard-hat"></i> Catálogo de EPIs
                    </a>
                @endcan
                <a href="{{ route('cursos.index') }}" class="puestos-btn puestos-btn--ghost">
                    <i class="fas fa-arrow-left"></i> Volver a Cursos
                </a>
            </div>
        </header>

        <!-- TARJETAS KPI SUPERIORES -->
        <div class="puestos-kpi-grid">
            <div class="puestos-kpi-card">
                <div>
                    <div class="puestos-kpi-card__label">PUESTOS ACTIVOS</div>
                    <div class="puestos-kpi-card__value">{{ $totalPuestos }}</div>
                    <div class="puestos-kpi-card__sub">{{ $puestosConMatrizCompleta }} con matriz completa</div>
                </div>
                <span class="puestos-kpi-card__icon"><i class="fas fa-id-badge"></i></span>
            </div>

            <div class="puestos-kpi-card">
                <div>
                    <div class="puestos-kpi-card__label">PERSONAL ADSCRITO</div>
                    <div class="puestos-kpi-card__value">{{ $totalPersonalAdscrito }}</div>
                    <div class="puestos-kpi-card__sub">Asignaciones activas en plantilla</div>
                </div>
                <span class="puestos-kpi-card__icon"><i class="fas fa-users"></i></span>
            </div>

            <div class="puestos-kpi-card">
                <div>
                    <div class="puestos-kpi-card__label">DOTACIONES EPI VINCULADAS</div>
                    <div class="puestos-kpi-card__value">{{ $totalAsignacionesEpi }}</div>
                    <div class="puestos-kpi-card__sub">Prescripciones SST-0840-002</div>
                </div>
                <span class="puestos-kpi-card__icon"><i class="fas fa-hard-hat"></i></span>
            </div>

            <div class="puestos-kpi-card">
                <div>
                    <div class="puestos-kpi-card__label">NORMATIVA APLICABLE</div>
                    <div class="puestos-kpi-card__value" style="font-size: 1.25rem; color: #0369a1;">ISO 45001</div>
                    <div class="puestos-kpi-card__sub">RD 773/1997 • Ley 31/1995 PRL</div>
                </div>
                <span class="puestos-kpi-card__icon"><i class="fas fa-shield-alt"></i></span>
            </div>
        </div>

        <!-- BARRA DE CREACIÓN RÁPIDA Y FILTRO -->
        <div class="create-toolbar">
            @can('cursos.edit')
                <form action="{{ route('puestos.store') }}" method="POST" class="create-toolbar__form">
                    @csrf
                    <input type="text" name="nombre" class="create-toolbar__input" placeholder="Ej: FONTANERO/A, SOLDADOR/A, TÉCNICO DE OBRA A..." required>
                    <button type="submit" class="puestos-btn puestos-btn--primary">
                        <i class="fas fa-plus"></i> Crear Nuevo Puesto
                    </button>
                </form>
            @else
                <div class="font-weight-bold text-muted small">Directorio de Perfiles Formativos Homologados</div>
            @endcan

            <input type="search" id="search-puestos-input" class="create-toolbar__search" placeholder="Filtrar puestos por nombre..." autocomplete="off">
        </div>
        @error('nombre')
            <div class="alert alert-danger py-2 mb-3" style="border-radius: 10px; font-size: 0.85rem;">{{ $message }}</div>
        @enderror

        <!-- GRID DE TARJETAS DE PUESTOS -->
        <div class="row" id="puestos-cards-grid">
            @forelse ($puestos as $puesto)
                @php
                    $cursosCount = (int) ($puesto->cursos_count ?? 0);
                    $episCount = (int) ($puesto->epis_count ?? 0);
                    $personalCount = (int) ($puesto->personal_count ?? 0);
                    $matrizLista = $cursosCount > 0 && $episCount > 0;
                @endphp
                <div class="col-md-6 col-lg-4 js-puesto-col" data-name="{{ mb_strtolower($puesto->nombre) }}">
                    <article class="puesto-card">
                        <div>
                            <div class="puesto-card__top">
                                <span class="puesto-id-pill">ID #PRF-{{ str_pad((string) $puesto->id, 3, '0', STR_PAD_LEFT) }}</span>
                                <span class="puesto-status-pill {{ $matrizLista ? 'puesto-status-pill--ready' : 'puesto-status-pill--pending' }}">
                                    {{ $matrizLista ? 'Matriz Completa' : 'En Configuración' }}
                                </span>
                            </div>

                            <h3 class="puesto-title">{{ $puesto->nombre }}</h3>

                            <div class="puesto-stats">
                                <a href="{{ route('puestos.edit', ['puesto' => $puesto->id, 'tab' => 'cursos']) }}" class="puesto-stat-badge" title="Configurar cursos obligatorios">
                                    <i class="fas fa-graduation-cap"></i> {{ $cursosCount }} cursos req.
                                </a>

                                <a href="{{ route('puestos.edit', ['puesto' => $puesto->id, 'tab' => 'epis']) }}" class="puesto-stat-badge puesto-stat-badge--epi" title="Configurar dotación de EPIs">
                                    <i class="fas fa-hard-hat"></i> {{ $episCount }} EPIs
                                </a>

                                <span class="puesto-stat-badge" title="Trabajadores con este perfil asignado">
                                    <i class="fas fa-users"></i> {{ $personalCount }} empleados
                                </span>
                            </div>
                        </div>

                        <div class="puesto-card__footer">
                            <span class="puesto-card__footer-label">Acciones Puesto</span>

                            <div class="puesto-actions-group">
                                <!-- Botón directo para ver la Ficha Oficial de EPIs en PDF -->
                                <a href="{{ route('puestos.epis.pdf', $puesto->id) }}" target="_blank" class="puesto-icon-btn puesto-icon-btn--pdf" title="Ver Ficha Oficial de EPIs (PDF)">
                                    <i class="fas fa-file-pdf"></i>
                                </a>

                                <!-- Botón para ver el Dashboard de Auditoría -->
                                <a href="{{ route('puestos.auditoria', $puesto->id) }}" class="puesto-icon-btn puesto-icon-btn--audit" title="Ver Matriz de Auditoría">
                                    <i class="fas fa-chart-bar"></i>
                                </a>

                                <!-- Botón para Configurar Cursos y EPIs -->
                                @can('cursos.edit')
                                    <a href="{{ route('puestos.edit', $puesto->id) }}" class="puesto-icon-btn puesto-icon-btn--config" title="Configurar Cursos y EPIs">
                                        <i class="fas fa-cog"></i>
                                    </a>

                                    <form action="{{ route('puestos.destroy', $puesto->id) }}" method="POST" style="margin: 0;" onsubmit="return confirm('¿Seguro que quieres eliminar el perfil {{ addslashes($puesto->nombre) }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="puesto-icon-btn puesto-icon-btn--delete" title="Eliminar puesto">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </div>
                    </article>
                </div>
            @empty
                <div class="col-12 text-center py-5 text-muted">
                    <i class="fas fa-sitemap fa-2x mb-2 text-secondary"></i>
                    <p class="mb-0 font-weight-bold">No hay perfiles formativos registrados en el sistema todavía.</p>
                </div>
            @endforelse
        </div>

        <!-- BANNER INFORMATIVO INFERIOR -->
        <div class="protocol-banner">
            <div class="protocol-banner__left">
                <span class="protocol-banner__icon"><i class="fas fa-tasks"></i></span>
                <div>
                    <h4>Protocolo de Asignación Automática y Homologación SST</h4>
                    <p>Al asignar cualquiera de estos perfiles a un trabajador, el sistema inyectará automáticamente sus cursos obligatorios y habilitará la Ficha Oficial de EPIs (`SST-0840-002-1-2 Rev.4`).</p>
                </div>
            </div>
            <span class="puesto-id-pill" style="font-size: 0.75rem; padding: 6px 10px;">Norma: UNE-EN ISO 45001</span>
        </div>
    </section>
@endsection

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('search-puestos-input');
            const cols = document.querySelectorAll('.js-puesto-col');

            if (searchInput && cols.length > 0) {
                searchInput.addEventListener('input', function () {
                    const term = this.value.trim().toLowerCase();
                    cols.forEach(col => {
                        const name = col.getAttribute('data-name') || '';
                        col.style.display = (term === '' || name.includes(term)) ? '' : 'none';
                    });
                });
            }
        });
    </script>
@endsection