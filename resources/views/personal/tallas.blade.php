@extends('adminlte::page')

@section('title', 'Gestión de Tallas')

@section('css')
    @vite(['resources/css/personal-index.css'])
    <style>
        /* Efectos para hacer las tarjetas interactivas */
        .js-filter-missing, .js-filter-size {
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
        }
        .js-filter-missing:hover, .js-filter-size:hover {
            transform: translateY(-2px);
        }
        .js-filter-missing:hover {
            box-shadow: 0 4px 6px rgba(185, 28, 28, 0.15) !important;
        }
        .js-filter-size:hover {
            box-shadow: 0 4px 6px rgba(23, 62, 103, 0.15) !important;
        }
        .js-filter-missing.is-active {
            box-shadow: 0 0 0 2px #b91c1c, 0 4px 10px rgba(185, 28, 28, 0.2) !important;
            transform: scale(1.05);
        }
        .js-filter-size.is-active {
            box-shadow: 0 0 0 2px #1d4ed8, 0 4px 10px rgba(29, 78, 216, 0.2) !important;
            transform: scale(1.05);
        }
       /* Botón CSV (Verde) */
        .personal-btn-csv {
            background: #10b981 !important;
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.2) !important;
        }
        .personal-btn-csv:hover {
            background: #059669 !important;
        }

        /* Botón PDF (Rojo oscuro) */
        .personal-btn-pdf {
            background: #ef4444 !important;
            box-shadow: 0 10px 20px rgba(239, 68, 68, 0.2) !important;
        }
        .personal-btn-pdf:hover {
            background: #dc2626 !important;
        }
    </style>
@endsection

@section('content')
<section class="personal-page">
    <header class="personal-hero">
        <div class="personal-hero-copy">
            <div class="personal-crumbs">GESTIÓN DE PERSONAL <span>•</span> TALLAS</div>
            <h1>Gestión de Tallas</h1>
            <p>Visión agregada de tallas y equipamiento de todos los trabajadores.</p>
        </div>

        <div class="personal-hero-actions">
            <a href="{{ route('personal.index') }}" class="personal-action-btn personal-action-btn--soft">
                <i class="fas fa-arrow-left"></i>
                Volver al listado
            </a>
        </div>
    </header>

    <div class="personal-shell">
        <article class="personal-card">
            <header class="personal-card__header">
                <div>
                    <h3>Resumen por tallas</h3>
                    <p>Filtra por estado o nombre si lo deseas.</p>
                </div>
                <div class="personal-card__actions">
                    <form method="GET" action="{{ route('personal.tallas') }}" class="personal-search-form" style="display:flex; gap:10px; align-items:center;">
                        
                        <div class="personal-search-field">
                            <label for="estado" style="display:none">Estado</label>
                            <select name="estado" id="estado" style="padding: 8px 12px; border-radius: 6px; border: 1px solid #e6edf3; font-family: inherit; color: #173e67;">
                                <option value="todos" @selected(($estado ?? 'todos') === 'todos')>Todos los estados</option>
                                <option value="falta_epi" @selected(($estado ?? 'todos') === 'falta_epi')>Falta algún EPI</option>
                                <option value="sin_departamento" @selected(($estado ?? 'todos') === 'sin_departamento')>Sin departamento asignado</option>
                                <option value="sin_oficina" @selected(($estado ?? 'todos') === 'sin_oficina')>Sin personal de oficina</option>
                            </select>
                        </div>

                        <div class="personal-search-field">
                            <label for="departamento" style="display:none">Departamento</label>
                            <select name="departamento" id="departamento" style="padding: 8px 12px; border-radius: 6px; border: 1px solid #e6edf3; font-family: inherit; color: #173e67;">
                                <option value="todos" @selected(($departamentoFiltro ?? 'todos') === 'todos')>Todos los departamentos</option>
                                @foreach($departamentosCatalogo as $depto)
                                    <option value="{{ $depto->nombre }}" @selected(($departamentoFiltro ?? '') === $depto->nombre)>
                                        {{ strtoupper($depto->nombre) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="personal-search-field">
                            <label for="q" style="display:none">Buscar</label>
                            <input type="search" id="q" name="q" placeholder="Buscar por nombre o apellidos..." value="{{ $query ?? '' }}" style="padding: 8px 12px; border-radius: 6px; border: 1px solid #e6edf3; width: 250px;">
                        </div>

                        <div class="personal-search-field" style="display: flex; align-items: center; padding-left: 8px;">
                            <input type="checkbox" id="incluir_inactivos" name="incluir_inactivos" value="1" @checked($incluirInactivos) onchange="this.form.submit()" style="cursor: pointer; width: 16px; height: 16px; margin-right: 6px; accent-color: #173e67;">
                            <label for="incluir_inactivos" style="margin: 0; font-size: 0.85rem; font-weight: 700; color: #173e67; cursor: pointer; white-space: nowrap;">
                                Incluir bajas
                            </label>
                        </div>
                        
                        <div class="personal-search-actions" style="display:flex; gap: 8px;">
                            <button type="submit" class="personal-search-submit">
                                Filtrar
                            </button>
                            
                            <!-- CANDADO PARA EXPORTACIONES MASIVAS -->
                            @can('personal.export')
                                <button type="submit" name="export" value="csv" class="personal-search-submit personal-btn-csv" title="Descargar listado actual en Excel">
                                    <i class="fas fa-file-csv"></i> Exportación simple
                                </button>
                                <button type="submit" name="export" value="pdf" class="personal-search-submit personal-btn-pdf" title="Descargar listado actual en PDF">
                                    <i class="fas fa-file-pdf"></i> PDF (Próximamente)
                                </button>
                            @endcan
                        </div>
                    </form>
                </div>
            </header>

            <div class="personal-card__body" style="padding: 18px;">
                <p>Mostrando: <strong>{{ $personals->count() }} trabajadores</strong></p>

                @foreach($columns as $col)
                    @php
                        $personalQueNecesitaTalla = $personals->where('sin_tallas', false);
                        $values = $personalQueNecesitaTalla->pluck($col)->filter(fn($v) => $v !== null && trim((string) $v) !== '');
                        $counts = $values->countBy()->sortKeys();
                        $missing = $personalQueNecesitaTalla->count() - $values->count();
                    @endphp

                    <section style="margin-bottom:18px;">
                        <h4 style="margin:6px 0 8px; text-transform:capitalize; border-bottom: 1px solid #e6edf3; padding-bottom: 8px;">
                            {{ ucfirst($col) }}
                        </h4>

                        @if($counts->isEmpty() && $missing == 0)
                            <div style="padding:10px; background:#f8fafc; border-radius:8px;">No hay datos para mostrar.</div>
                        @else
                            <div style="display:flex; gap:12px; flex-wrap:wrap">
                                @foreach($counts as $size => $c)
                                    <div class="js-filter-size" data-col="{{ $col }}" data-size="{{ $size }}" title="Haz clic para ver quién usa esta talla" style="background:#fff; border:1px solid #e6edf3; padding:8px 12px; border-radius:10px; min-width:120px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); cursor:pointer;">
                                        <div style="font-weight:800; font-size: 1.1rem; color: #173e67;">{{ $size }}</div>
                                        <div style="color:#6b7280; font-size: 0.85rem;">{{ $c }} trabajador{{ $c > 1 ? 'es' : '' }}</div>
                                    </div>
                                @endforeach

                                @if($missing > 0)
                                    <div class="js-filter-missing" data-col="{{ $col }}" title="Haz clic para ver quiénes faltan" style="background:#fff1f2; border:1px solid #f5c2c7; padding:8px 12px; border-radius:10px; min-width:120px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); cursor:pointer;">
                                        <div style="font-weight:800; font-size: 1.1rem; color:#b91c1c;">Faltan</div>
                                        <div style="color:#b91c1c; font-size: 0.85rem;">{{ $missing }} por asignar</div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </section>
                @endforeach

                <hr style="margin:24px 0 18px; border-color: #e6edf3;">

                <div id="js-active-filter-alert" style="display:none; background: #eff6ff; border: 1px solid #bfdbfe; color: #1e3a8a; padding: 12px 16px; border-radius: 8px; margin-bottom: 18px; align-items: center; justify-content: space-between; font-weight: 600;">
                    <span><i class="fas fa-filter" style="margin-right: 8px;"></i> Viendo únicamente personal al que le falta: <strong id="js-filter-name" style="text-transform: uppercase;"></strong></span>
                    <button id="js-clear-filter" type="button" style="background: #1d4ed8; color: white; border: none; border-radius: 6px; padding: 6px 12px; font-weight: 700; cursor: pointer;">Quitar filtro</button>
                </div>

                <h3>Listado detallado</h3>
                <div class="table-responsive">
                    <table class="table" style="font-size: 0.9rem;">
                        <thead style="background: #f8fafc;">
                            <tr>
                                <th>ID RRHH</th>
                                <th>Nombre</th>
                                <th>Departamento</th>
                                @foreach($columns as $c)
                                    <th style="text-transform:capitalize;">{{ $c }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($personals as $p)
                                @php
                                    $deptos = is_string($p->departamento) ? json_decode($p->departamento, true) ?? explode(',', $p->departamento) : (array) $p->departamento;
                                    $deptosStr = !empty($deptos) ? strtoupper(implode(', ', $deptos)) : '—';
                                    
                                    $faltas = [];
                                    if (!$p->sin_tallas) {
                                        foreach ($columns as $c) {
                                            if (empty($p->{$c})) {
                                                $faltas[] = $c;
                                            }
                                        }
                                    }
                                @endphp
                                
                                <tr class="js-worker-row" 
                                    data-faltas="{{ implode(' ', $faltas) }}" 
                                    @foreach($columns as $colName)
                                        data-talla-{{ $colName }}="{{ mb_strtolower(trim($p->{$colName} ?? '')) }}"
                                    @endforeach
                                    style="{{ $p->sin_tallas ? 'opacity: 0.6; background-color: #f9fafb;' : '' }}">
                                    
                                    <td style="font-weight: 600; color: #6b7280;">{{ $p->id_rrhh ?: '—' }}</td>
                                    
                                    <td style="font-weight: 700;">
                                        <!-- CANDADO PARA VER FICHA DEL TRABAJADOR -->
                                        @can('personal.acciones')
                                            <a href="{{ route('personal.show', $p->id) }}" style="color: #173e67; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#1d4ed8'; this.style.textDecoration='underline'" onmouseout="this.style.color='#173e67'; this.style.textDecoration='none'">
                                                {{ $p->name }} {{ $p->apellido }}
                                            </a>
                                        @else
                                            <span style="color: #173e67;">{{ $p->name }} {{ $p->apellido }}</span>
                                        @endcan
                                        
                                        @if($p->sin_tallas)
                                            <span style="font-size: 0.7rem; background: #e5e7eb; padding: 2px 6px; border-radius: 4px; margin-left: 6px;">Oficina</span>
                                        @endif

                                        @if(!$p->activo)
                                            <span style="font-size: 0.7rem; background: #fee2e2; color: #b91c1c; padding: 2px 6px; border-radius: 4px; margin-left: 6px; font-weight: 800; border: 1px solid #f87171;" title="Trabajador dado de baja">De Baja</span>
                                        @endif
                                    </td>
                                    <td>{{ $deptosStr }}</td>
                                    
                                    @foreach($columns as $c)
                                        <td>
                                            @if($p->sin_tallas)
                                                <span style="color: #cbd5e1;">N/A</span>
                                            @else
                                                <strong style="color: {{ $p->{$c} ? '#173e67' : '#ef4444' }};">{{ $p->{$c} ?: 'Falta' }}</strong>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 3 + count($columns) }}" style="text-align: center; padding: 20px; color: #6b7280;">
                                        No hay trabajadores para mostrar en esta búsqueda.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </article>
    </div>
</section>
@endsection

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const missingCards = document.querySelectorAll('.js-filter-missing');
        const sizeCards = document.querySelectorAll('.js-filter-size');
        const rows = document.querySelectorAll('.js-worker-row');
        const alertBox = document.getElementById('js-active-filter-alert');
        const filterNameSpan = document.getElementById('js-filter-name');
        const clearBtn = document.getElementById('js-clear-filter');

        // Función auxiliar para atenuar todas las tarjetas (reseteo visual)
        const dimAllCards = () => {
            missingCards.forEach(c => { c.classList.remove('is-active'); c.style.opacity = '0.4'; });
            sizeCards.forEach(c => { c.classList.remove('is-active'); c.style.opacity = '0.4'; });
        };

        // 1. Lógica para filtrar por EPI Faltante
        missingCards.forEach(card => {
            card.addEventListener('click', function() {
                const colToFind = this.getAttribute('data-col');
                
                rows.forEach(row => {
                    const missingList = row.getAttribute('data-faltas').split(' ');
                    row.style.display = missingList.includes(colToFind) ? '' : 'none';
                });

                filterNameSpan.innerHTML = `FALTA: <strong>${colToFind.toUpperCase()}</strong>`;
                alertBox.style.display = 'flex';
                
                dimAllCards();
                this.style.opacity = '1';
                this.classList.add('is-active');
            });
        });

        // 2. Lógica para filtrar por Talla Específica
        sizeCards.forEach(card => {
            card.addEventListener('click', function() {
                const colToFind = this.getAttribute('data-col');
                // Normalizamos a minúsculas para asegurar la comparación exacta
                const sizeToFind = this.getAttribute('data-size').toLowerCase().trim(); 

                rows.forEach(row => {
                    // Leemos el atributo inyectado desde Laravel (ej: data-talla-sudadera)
                    const workerSize = row.getAttribute('data-talla-' + colToFind);
                    row.style.display = (workerSize === sizeToFind) ? '' : 'none';
                });

                filterNameSpan.innerHTML = `TALLA <strong>${this.getAttribute('data-size').toUpperCase()}</strong> DE <strong>${colToFind.toUpperCase()}</strong>`;
                alertBox.style.display = 'flex';
                
                dimAllCards();
                this.style.opacity = '1';
                this.classList.add('is-active');
            });
        });

        // 3. Botón para resetear todo a su estado natural
        clearBtn.addEventListener('click', function() {
            rows.forEach(row => row.style.display = '');
            alertBox.style.display = 'none';
            
            missingCards.forEach(c => { c.style.opacity = '1'; c.classList.remove('is-active'); });
            sizeCards.forEach(c => { c.style.opacity = '1'; c.classList.remove('is-active'); });
        });
    });
</script>
@endsection