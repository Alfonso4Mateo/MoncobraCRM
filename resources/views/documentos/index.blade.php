@extends('adminlte::page')

@section('title', 'Documentos - MoncobraCRM')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/documentos-index.css'])
@endsection

@section('content')
    @php
        $tipoLabel = $tipos[$tipoActual]['label'] ?? 'Documentos';
    @endphp

    <section class="docs-page">
        <header class="docs-hero">
            <div>
                <span class="docs-kicker">GESTION DE ARCHIVOS</span>
                <h1>Documentos</h1>
                <p>Consulta, filtra y revisa documentos clave del ciclo operativo.</p>
            </div>
        </header>

        @if(session('status'))
            <div class="docs-status-banner" style="margin: 0 0 1rem; padding: 0.9rem 1rem; border-radius: 14px; background: rgba(34, 197, 94, 0.12); color: #166534; border: 1px solid rgba(34, 197, 94, 0.25);">
                {{ session('status') }}
            </div>
        @endif

        <section class="docs-type-grid" aria-label="Tipos de documentos">
            @foreach($tipos as $key => $tipo)
                <a
                    href="{{ route('documentos.index', ['tipo' => $key]) }}"
                    class="docs-type-card {{ $tipoActual === $key ? 'is-active' : '' }}"
                >
                    <span class="docs-type-icon">
                        <i class="fas {{ $tipo['icon'] ?? 'fa-file' }}" aria-hidden="true"></i>
                    </span>
                    <div>
                        <span class="docs-type-label">{{ $tipo['label'] }}</span>
                        <strong class="docs-type-value">{{ number_format($counts[$key] ?? 0, 0, ',', '.') }}</strong>
                    </div>
                </a>
            @endforeach
        </section>

        <div class="docs-layout">
            <section class="docs-log">
                <header class="docs-log-header">
                    <div>
                        <h2>Documentos recientes: {{ $tipoLabel }}</h2>
                        <p>Listado breve con los ultimos registros cargados.</p>
                    </div>
                    <form method="GET" action="{{ route('documentos.index') }}" class="docs-log-search">
                        <input type="hidden" name="tipo" value="{{ $tipoActual }}">
                        <input
                            type="text"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="Buscar por ID, cliente, estado o documento..."
                        >
                        <button type="submit">
                            <i class="fas fa-search"></i>
                            Buscar
                        </button>
                        @if(request('q'))
                            <a href="{{ route('documentos.index', ['tipo' => $tipoActual]) }}" class="docs-log-reset">Limpiar</a>
                        @endif
                    </form>
                </header>

                <div class="docs-log-list">
                    <div class="docs-log-row docs-log-head">
                        <span>ID</span>
                        <span>Fecha</span>
                        <span>Cliente / Personal</span>
                        <span>Estado</span>
                        <span>Importe</span>
                    </div>

                    @forelse($documentos as $doc)
                        @php
                            $isActive = $documentoActivo && $documentoActivo['id'] === $doc['id'];
                        @endphp
                        <a
                            href="{{ route('documentos.index', ['tipo' => $tipoActual, 'doc' => $doc['id'], 'page' => $documentos->currentPage(), 'q' => request('q')]) }}"
                            class="docs-log-row {{ $isActive ? 'is-active' : '' }}"
                        >
                            <span class="docs-log-id">{{ $doc['codigo'] }}</span>
                            <span>{{ $doc['fecha'] }}</span>
                            <span class="docs-log-persona">{{ $doc['persona'] }}</span>
                            <span class="docs-status {{ $doc['estado_clase'] }}">{{ $doc['estado'] }}</span>
                            <span class="docs-log-total">{{ $doc['total'] }}</span>
                        </a>
                    @empty
                        <div class="docs-empty">
                            <i class="fas fa-folder-open"></i>
                            <p>No hay documentos disponibles para este tipo.</p>
                        </div>
                    @endforelse
                </div>

                <div class="docs-log-footer">
                    <span>
                        Mostrando {{ $documentos->firstItem() ?? 0 }} - {{ $documentos->lastItem() ?? 0 }}
                        de {{ number_format($documentos->total(), 0, ',', '.') }} documentos
                    </span>
                    <div class="docs-pagination">
                        @if($documentos->onFirstPage())
                            <span class="docs-page-btn is-disabled">&lsaquo;</span>
                        @else
                            <a class="docs-page-btn" href="{{ $documentos->previousPageUrl() }}">&lsaquo;</a>
                        @endif

                        <span class="docs-page-btn is-active">{{ $documentos->currentPage() }}</span>

                        @if($documentos->hasMorePages())
                            <a class="docs-page-btn" href="{{ $documentos->nextPageUrl() }}">&rsaquo;</a>
                        @else
                            <span class="docs-page-btn is-disabled">&rsaquo;</span>
                        @endif
                    </div>
                </div>
            </section>

            <aside class="docs-detail">
                @if($documentoActivo)
                    @php
                        $totales = $documentoActivo['totales'] ?? ['base' => '—', 'iva' => '—', 'total' => $documentoActivo['total']];
                    @endphp
                    <article class="docs-detail-card">
                        <header class="docs-detail-top">
                            <div>
                                <span class="docs-detail-tag">Detalle {{ $tipoLabel }}</span>
                                <h3>{{ $documentoActivo['codigo'] }}</h3>
                                <p>{{ $documentoActivo['titulo'] }}</p>
                            </div>
                            <div class="docs-detail-actions-top">
                                <span class="docs-status {{ $documentoActivo['estado_clase'] }}">{{ $documentoActivo['estado'] }}</span>
                                <a
                                    href="{{ route('documentos.index', ['tipo' => $tipoActual, 'page' => $documentos->currentPage(), 'q' => request('q')]) }}"
                                    class="docs-detail-close"
                                    aria-label="Cerrar detalle"
                                >
                                    <i class="fas fa-xmark"></i>
                                </a>
                            </div>
                        </header>

                        <div class="docs-detail-summary">
                            @foreach($documentoActivo['meta'] as $meta)
                                <div>
                                    <span>{{ $meta['label'] }}</span>
                                    <strong>{{ $meta['value'] }}</strong>
                                </div>
                            @endforeach
                        </div>

                        <div class="docs-detail-lines">
                            <h4>Líneas del documento</h4>
                            @if(!empty($documentoActivo['lineas']))
                                <ul class="docs-line-list">
                                    @foreach($documentoActivo['lineas'] as $linea)
                                        <li class="docs-line-item">
                                            <div>
                                                <strong>{{ $linea['nombre'] }}</strong>
                                                <span>
                                                    Cant: {{ !is_null($linea['cantidad']) ? $linea['cantidad'] . ' uds.' : '—' }}
                                                </span>
                                            </div>
                                            <span class="docs-line-total">
                                                @if(is_numeric($linea['importe']))
                                                    {{ number_format((float) $linea['importe'], 2, ',', '.') }} €
                                                @else
                                                    —
                                                @endif
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <div class="docs-empty-lineas">No hay líneas para este documento.</div>
                            @endif
                        </div>

                        <div class="docs-detail-totals">
                            <div>
                                <span>Base imponible</span>
                                <strong>{{ $totales['base'] }}</strong>
                            </div>
                            <div>
                                <span>IVA (21%)</span>
                                <strong>{{ $totales['iva'] }}</strong>
                            </div>
                            <div class="is-total">
                                <span>Total final</span>
                                <strong>{{ $totales['total'] }}</strong>
                            </div>
                        </div>

                        @if(!empty($documentoActivo['acciones']))
                            <div class="docs-detail-actions">
                                @foreach($documentoActivo['acciones'] as $accion)
                                            @if(($accion['method'] ?? 'GET') === 'DELETE')
                                        <form action="{{ $accion['url'] }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="docs-action-btn {{ $loop->first ? 'docs-action-btn--primary' : '' }}"
                                                onclick="return confirm('{{ $accion['confirm'] ?? '¿Seguro?' }}')"
                                            >
                                                <i class="fas {{ $accion['icon'] ?? 'fa-file' }}"></i>
                                                {{ $accion['label'] }}
                                            </button>
                                        </form>
                                    @else
                                                @if(!empty($accion['preview']))
                                                    <a
                                                        href="{{ $accion['url'] }}"
                                                        target="_blank"
                                                        rel="noopener"
                                                        class="docs-action-btn {{ $loop->first ? 'docs-action-btn--primary' : '' }}"
                                                    >
                                                        <i class="fas {{ $accion['icon'] ?? 'fa-file' }}"></i>
                                                        {{ $accion['label'] }}
                                                    </a>
                                                @else
                                                    <a
                                                        href="{{ $accion['url'] }}"
                                                        class="docs-action-btn {{ $loop->first ? 'docs-action-btn--primary' : '' }}"
                                                    >
                                                        <i class="fas {{ $accion['icon'] ?? 'fa-file' }}"></i>
                                                        {{ $accion['label'] }}
                                                    </a>
                                                @endif
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </article>
                @else
                    <article class="docs-detail-card docs-detail-empty">
                        <i class="fas fa-file-circle-xmark"></i>
                        <p>Selecciona un documento para ver el detalle.</p>
                    </article>
                @endif
            </aside>
        </div>

        @can('documentos.create')
            <div class="docs-footer">
                <a href="{{ route('documentos.create') }}" class="docs-btn docs-btn-primary">
                    <i class="fas fa-cloud-arrow-up"></i>
                    Cargar documentos
                </a>
            </div>
        @endcan
    </section>
@endsection
