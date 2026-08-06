@extends('adminlte::page')

@section('title', 'Perfil del Trabajador')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/personal-show.css'])
    <style>
        .profile-filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            align-items: flex-end;
        }

        .profile-filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .profile-filter-group label {
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #8a98ab;
        }

        .profile-filter-group input,
        .profile-filter-group select {
            padding: 9px 11px;
            border: 1px solid var(--profile-line);
            border-radius: 8px;
            font-family: var(--profile-font);
            font-size: .9rem;
            color: var(--profile-ink);
            background: #fff;
        }

        .profile-filter-group input:focus,
        .profile-filter-group select:focus {
            outline: none;
            border-color: var(--profile-primary);
            box-shadow: 0 0 0 3px rgba(23, 62, 103, .08);
        }

        .profile-filter-btn {
            padding: 9px 16px;
            background: var(--profile-primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 800;
            font-size: .9rem;
            cursor: pointer;
            transition: all .18s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .profile-filter-btn:hover {
            background: var(--profile-primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(23, 62, 103, .2);
        }

        .profile-filter-reset {
            padding: 9px 12px;
            background: transparent;
            color: var(--profile-muted);
            border: 1px solid var(--profile-line);
            border-radius: 8px;
            font-weight: 600;
            font-size: .85rem;
            cursor: pointer;
            transition: all .18s ease;
        }

        .profile-filter-reset:hover {
            color: var(--profile-ink);
            border-color: var(--profile-ink);
        }

        .profile-course-grid {
            display: grid;
            gap: 12px;
        }

        .profile-course-row {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            align-items: flex-start;
            padding: 14px 16px;
            border: 1px solid var(--profile-line);
            border-radius: 14px;
            background: #fafbfc;
        }

        .profile-course-row__actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .profile-course-row__meta h4 {
            margin: 0 0 4px;
            font-size: .98rem;
            font-weight: 800;
            color: var(--profile-ink);
        }

        .profile-course-row__meta p {
            margin: 0;
            color: var(--profile-muted);
            font-size: .86rem;
        }

        .profile-course-form {
            display: grid;
            grid-template-columns: 1.1fr .9fr .7fr 1.4fr auto;
            gap: 10px;
            align-items: end;
        }

        .profile-course-form .course-field label {
            display: block;
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #8a98ab;
            margin-bottom: 6px;
        }

        .profile-course-form .course-field input,
        .profile-course-form .course-field select,
        .profile-course-form .course-field textarea {
            width: 100%;
            border: 1px solid var(--profile-line);
            border-radius: 10px;
            padding: 10px 12px;
            background: #fff;
            color: var(--profile-ink);
        }

        .profile-course-form .course-submit {
            border: 0;
            border-radius: 10px;
            padding: 11px 14px;
            background: var(--profile-primary);
            color: #fff;
            font-weight: 800;
            cursor: pointer;
        }

        @media (max-width: 980px) {
            .profile-course-form {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 640px) {
            .profile-course-form {
                grid-template-columns: 1fr;
            }
        }

        .profile-action--danger {
            background: #b91c1c;
            color: #fff !important;
            box-shadow: 0 10px 18px rgba(185, 28, 28, .2);
        }

        .profile-action--danger:hover {
            background: #991b1b;
            box-shadow: 0 12px 20px rgba(185, 28, 28, .26);
        }

        .profile-modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .55);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity .18s ease;
            z-index: 2000;
        }

        .profile-modal.is-open {
            opacity: 1;
            pointer-events: auto;
        }

        .profile-modal__panel {
            width: min(520px, 92vw);
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, .25);
            padding: 20px 22px;
        }

        .profile-modal__title {
            margin: 0 0 8px;
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--profile-ink);
        }

        .profile-modal__text {
            margin: 0 0 16px;
            color: var(--profile-muted);
            line-height: 1.5;
        }

        .profile-modal__name {
            font-weight: 800;
            color: var(--profile-ink);
        }

        .profile-modal__actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .profile-modal__btn {
            border-radius: 10px;
            border: 1px solid var(--profile-line);
            padding: 8px 14px;
            font-weight: 800;
            font-size: .85rem;
            cursor: pointer;
            background: #f5f7fb;
            color: var(--profile-ink);
        }

        .profile-modal__btn--danger {
            background: #b91c1c;
            color: #fff;
            border-color: #b91c1c;
        }

        .profile-modal__btn--danger:hover {
            background: #991b1b;
        }

        @media (max-width: 768px) {
            .profile-filters {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('content')
    <section class="profile-page">
        <header class="profile-hero">
            <div>
                <div class="profile-crumbs">GESTIÓN DE PERSONAL <span>•</span> PERFIL TRABAJADOR</div>
                <h1>Perfil del Trabajador</h1>
            </div>

            <div class="profile-hero-actions">
                <a href="{{ route('inventario.salida.create', ['personal_id' => $personal->id]) }}" class="profile-action profile-action--soft">
                    <i class="fas fa-arrow-up-from-box"></i>
                    Registrar Salida
                </a>
                @can('manage-users')
                    <a href="{{ route('personal.edit', $personal->id) }}" class="profile-action profile-action--primary">
                        <i class="fas fa-pen"></i>
                        Editar Perfil
                    </a>
                @endcan
                @if(auth()->user() && auth()->user()->role === 'superadmin')
                    <button type="button" class="profile-action profile-action--danger" id="open-delete-personal">
                        <i class="fas fa-trash"></i>
                        Eliminar Trabajador
                    </button>
                @endif
            </div>
        </header>

        <div class="profile-grid">
            <section class="profile-main">
                <article class="profile-card profile-card--main-sidebar">
                    <div class="profile-main-row">
                        <div class="profile-main-left">
                            <div class="profile-status">{{ $personal->activo ? 'ACTIVO' : 'INACTIVO' }}</div>

                            <div class="profile-avatar-wrap">
                                <div class="profile-avatar">
                                    <i class="fas fa-hard-hat"></i>
                                </div>
                            </div>

                            <div class="profile-name-block">
                                <h2>{{ $personal->name }} {{ $personal->apellido }}</h2>
                                @php
                                    $deptosActuales = is_string($personal->departamento) ? json_decode($personal->departamento, true) ?? explode(',', $personal->departamento) : (array) $personal->departamento;
                                @endphp
                                <p>{{ !empty($deptosActuales) ? strtoupper(implode(', ', $deptosActuales)) : 'SIN DEPARTAMENTO' }}</p>
                            </div>

                            <div class="profile-metadata">
                                <div>
                                    <span>ID EMPLEADO</span>
                                    <strong>AL-{{ str_pad((string) $personal->id, 3, '0', STR_PAD_LEFT) }}</strong>
                                </div>
                                <div>
                                    <span>ID RRHH</span>
                                    <strong>{{ $personal->id_rrhh ?: '—' }}</strong>
                                </div>
                               <div>
                                    <span>DEPARTAMENTO</span>
                                    <strong>{{ !empty($deptosActuales) ? strtoupper(implode(', ', $deptosActuales)) : '—' }}</strong>
                                </div>
                                <div>
                                    <span>ÚLTIMA REVISIÓN MÉDICA</span>
                                    <strong>{{ optional($personal->ultima_revision_medica)->format('d M Y') ?: '—' }}</strong>
                                </div>
                                <div>
                                    <span>PRÓXIMA REVISIÓN MÉDICA</span>
                                    <strong>{{ optional($personal->proxima_revision_medica)->format('d M Y') ?: '—' }}</strong>
                                </div>
                                <div>
                                    <span>ANTIGÜEDAD</span>
                                    <strong>{{ optional($personal->created_at)->format('d M Y') }}</strong>
                                </div>
                            </div>
                        </div>

                        <article class="profile-card profile-card--tallas">
                            <h3><i class="fas fa-ruler-combined"></i> Tallas y EPIs</h3>

                            <div class="profile-size-list">
                                @foreach ($tallas as $talla)
                                    <div class="profile-size-row">
                                        <div class="profile-size-label">
                                            <i class="fas {{ $talla['icon'] }}"></i>
                                            <span>{{ $talla['label'] }}</span>
                                        </div>
                                        <strong>{{ $talla['value'] }}</strong>
                                    </div>
                                @endforeach
                            </div>
                        </article>
                    </div>
                </article>

                <!-- Panel de Observaciones -->
                @if($personal->descripcion)
                    <article class="profile-card">
                        <div class="profile-card__header">
                            <div>
                                <h3><i class="fas fa-clipboard-list"></i> Observaciones</h3>
                                <p>Notas y comentarios adicionales sobre el trabajador</p>
                            </div>
                        </div>

                        <div class="profile-card__body" style="padding: 20px;">
                            <div style="color: var(--profile-ink); line-height: 1.6; white-space: pre-wrap; word-wrap: break-word;">
                                {{ $personal->descripcion }}
                            </div>
                        </div>
                    </article>
                @endif

                <article class="profile-card">
                    <div class="profile-card__header">
                        <div>
                            <h3><i class="fas fa-graduation-cap"></i> Formación y Cursos</h3>
                            <p>Histórico de cursos asignados al trabajador</p>
                        </div>
                    </div>

                    <div class="profile-card__body" style="padding: 20px;">
                        <div class="profile-course-grid">
                            @forelse($personal->cursos as $curso)
                                <div class="profile-course-row">
                                    <div class="profile-course-row__meta">
                                        <h4>{{ $curso->nombre }}</h4>
                                        <p>
                                            Realizado: {{ optional($curso->pivot->fecha_realizacion)->format('d M Y') ?: 'Sin fecha' }}
                                            · {{ $curso->pivot->descripcion_aptitud ?: 'Sin observaciones de aptitud' }}
                                        </p>
                                    </div>

                                    <div class="profile-course-row__actions">
                                        <span class="profile-chip {{ ($curso->pivot->apto ?? false) ? 'profile-chip--ok' : 'profile-chip--pending' }}">
                                            {{ ($curso->pivot->apto ?? false) ? 'Apto' : 'No apto' }}
                                        </span>

                                        <form method="POST" action="{{ route('personal.cursos.destroy', [$personal->id, $curso->id]) }}" onsubmit="return confirm('¿Quitar este curso del trabajador?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="profile-icon-link" title="Quitar curso" style="border:0;background:transparent;cursor:pointer;">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <div class="profile-empty-state">
                                    <i class="fas fa-book-open"></i>
                                    <strong>No hay cursos asignados</strong>
                                    <span>La formación aparecerá aquí cuando se vaya registrando.</span>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </article>

                <article class="profile-card">
                    <div class="profile-card__header">
                        <div>
                            <h3><i class="fas fa-circle-plus"></i> Asignar nuevo curso</h3>
                            <p>Registra una nueva formación con fecha, aptitud y observaciones</p>
                        </div>
                    </div>

                    <div class="profile-card__body" style="padding: 20px;">
                        @if($cursosCatalogo->isEmpty())
                            <div class="profile-empty-state">
                                <i class="fas fa-graduation-cap"></i>
                                <strong>No hay cursos en el catálogo</strong>
                                <span>Crea primero un curso para poder asignarlo al personal.</span>
                            </div>
                        @else
                            <form method="POST" action="{{ route('personal.cursos.update', $personal->id) }}" class="profile-course-form">
                                @csrf
                                @method('PUT')

                                <div class="course-field">
                                    <label for="curso_id">Curso</label>
                                    <select id="curso_id" name="curso_id" required>
                                        <option value="">Selecciona un curso</option>
                                        @foreach($cursosCatalogo as $cursoCatalogo)
                                            <option value="{{ $cursoCatalogo->id }}">{{ $cursoCatalogo->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="course-field">
                                    <label for="fecha_realizacion">Fecha realización</label>
                                    <input type="date" id="fecha_realizacion" name="fecha_realizacion">
                                </div>

                                <div class="course-field">
                                    <label for="apto">Apto</label>
                                    <select id="apto" name="apto">
                                        <option value="0">No</option>
                                        <option value="1">Sí</option>
                                    </select>
                                </div>

                                <div class="course-field">
                                    <label for="descripcion_aptitud">Descripción aptitud</label>
                                    <textarea id="descripcion_aptitud" name="descripcion_aptitud" rows="2" placeholder="Observaciones sobre la aptitud del trabajador"></textarea>
                                </div>

                                <button type="submit" class="course-submit">Asignar</button>
                            </form>
                        @endif
                    </div>
                </article>

                <article class="profile-card profile-card--table">
                    <div class="profile-card__header">
                        <div>
                            <h3>Histórico de Salidas de Inventario</h3>
                            <p>Registro detallado de material y EPIs entregados</p>
                        </div>
                    </div>

                    <!-- Panel de Filtros -->
                    <div style="padding: 20px; border-bottom: 1px solid var(--profile-line); background: #fafbfc;">
                        <form id="filters-form" method="GET" style="display: contents;">
                            <div class="profile-filters">
                                <div class="profile-filter-group">
                                    <label for="fecha_desde">Desde</label>
                                    <input type="date" id="fecha_desde" name="fecha_desde" value="{{ request('fecha_desde') }}">
                                </div>

                                <div class="profile-filter-group">
                                    <label for="fecha_hasta">Hasta</label>
                                    <input type="date" id="fecha_hasta" name="fecha_hasta" value="{{ request('fecha_hasta') }}">
                                </div>

                                <div class="profile-filter-group">
                                    <label for="articulo">Buscar Artículo</label>
                                    <input type="text" id="articulo" name="articulo" placeholder="Nombre del artículo..." value="{{ request('articulo') }}">
                                </div>

                                <div style="display: flex; gap: 8px;">
                                    <button type="submit" class="profile-filter-btn">
                                        <i class="fas fa-magnifying-glass"></i>
                                        Buscar
                                    </button>
                                    @if(request('fecha_desde') || request('fecha_hasta') || request('articulo'))
                                        <a href="{{ route('personal.show', $personal->id) }}" class="profile-filter-reset" style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                                            <i class="fas fa-times"></i>
                                            Limpiar
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive profile-table-wrap">
                        <table class="table profile-table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Artículo</th>
                                    <th>Cantidad</th>
                                    <th>OT relacionada</th>
                                    <th>Estado</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($historicoSalidas as $registro)
                                    <tr>
                                        <td data-label="Fecha"><span class="profile-date">{{ $registro->fecha }}</span></td>
                                        <td data-label="Artículo">
                                            <div class="profile-article">
                                                <span class="profile-article__icon"><i class="fas fa-box"></i></span>
                                                <strong>{{ $registro->articulo }}</strong>
                                            </div>
                                        </td>
                                        <td data-label="Cantidad"><strong class="profile-quantity">{{ $registro->cantidad }}</strong></td>
                                        <td data-label="OT relacionada"><span class="profile-chip profile-chip--muted">{{ $registro->ot }}</span></td>
                                        <td data-label="Estado"><span class="{{ $registro->estado_clase }}">{{ $registro->estado }}</span></td>
                                        <td data-label="Acción">
                                            <a href="{{ isset($registro->salida_id) ? route('inventario.salida.documento', $registro->salida_id) : '#' }}" class="profile-icon-link" title="Ver documento de salida">
                                                <i class="far fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6">
                                            <div class="profile-empty-state">
                                                <i class="fas fa-box-open"></i>
                                                <strong>No hay registros para mostrar</strong>
                                                <span>Cuando existan salidas de inventario aparecerán aquí.</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="profile-table-footer">
                        <span>MOSTRANDO 1 - {{ count($historicoSalidas) }} DE {{ count($historicoSalidas) }} REGISTROS</span>
                        <div class="profile-pagination">
                            <button type="button" disabled>&lsaquo;</button>
                            <button type="button" class="is-active" disabled>1</button>
                            <button type="button" disabled>2</button>
                            <button type="button" disabled>&rsaquo;</button>
                        </div>
                    </div>
                </article>
            </section>
        </div>

        <a href="{{ route('personal.index') }}" class="profile-fab" title="Volver al listado">
            <i class="fas fa-arrow-left"></i>
        </a>
    </section>

    @if(auth()->user() && auth()->user()->role === 'superadmin')
        <div class="profile-modal" id="delete-personal-modal" aria-hidden="true" role="dialog" aria-modal="true">
            <div class="profile-modal__panel">
                <h2 class="profile-modal__title">Seguro que deseas eliminar</h2>
                <p class="profile-modal__text">
                    Nombre del trabajador:
                    <span class="profile-modal__name">{{ $personal->name }} {{ $personal->apellido }}</span>
                </p>
                <form method="POST" action="{{ route('personal.destroy', $personal->id) }}" class="profile-modal__actions">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="profile-modal__btn" data-close-modal>Cancelar</button>
                    <button type="submit" class="profile-modal__btn profile-modal__btn--danger">Eliminar</button>
                </form>
            </div>
        </div>
    @endif
@endsection

@section('js')
    <script>
        (function() {
            const openBtn = document.getElementById('open-delete-personal');
            const modal = document.getElementById('delete-personal-modal');

            if (!openBtn || !modal) return;

            const closeButtons = modal.querySelectorAll('[data-close-modal]');

            const openModal = () => {
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
            };

            const closeModal = () => {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
            };

            openBtn.addEventListener('click', openModal);
            closeButtons.forEach((btn) => btn.addEventListener('click', closeModal));
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeModal();
                }
            });
        })();
    </script>
@endsection
