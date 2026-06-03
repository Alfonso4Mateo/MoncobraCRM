@extends('adminlte::page')

@section('title', 'Panel de Control - MoncobraCRM')

@section('content_header')
    <div class="dashboard-page-title">
        <h1 class="m-0">
            <i class="fas fa-th-large mr-2"></i>Panel de Control
        </h1>
    </div>
@endsection

@section('content')
    <div class="dashboard-overview">
        @if (session('success'))
            <div class="alert alert-success dashboard-alert" role="alert">
                {{ session('success') }}
            </div>
        @endif

        <section class="dashboard-intro">
            <div>
                <h2>Hola, {{ Auth::user()->name }}</h2>
                <p>Bienvenido al hub central de gestion industrial.</p>
            </div>
        </section>

        <section class="dashboard-shortcuts">
            <a href="{{ route('profile.show') }}">
                <i class="fas fa-user-cog"></i>
                Perfil
            </a>
            @can('manage-users')
                <a href="{{ route('users.index') }}">
                    <i class="fas fa-users-cog"></i>
                    Panel de Usuarios
                </a>
            @endcan
            <span class="dashboard-shortcut-tip">
                Arrastra paneles para reorganizar. El orden se guarda por usuario.
            </span>
        </section>

        <section class="dashboard-grid" id="dashboard-grid" aria-label="Paneles del dashboard" data-order-endpoint="{{ route('dashboard.panel-order.update') }}">
            @foreach ($dashboardPanels as $panel)
                <article class="dashboard-card tone-{{ $panel['tone'] }}" data-panel-id="{{ $panel['id'] }}" draggable="true">
                    <div class="dashboard-card-top">
                        <span class="dashboard-card-icon" aria-hidden="true">
                            <i class="{{ $panel['icon'] }}"></i>
                        </span>
                        <span class="dashboard-card-category">{{ $panel['category'] }}</span>
                        <button type="button" class="dashboard-drag-handle" aria-label="Arrastrar panel" title="Arrastrar panel">
                            <i class="fas fa-grip-vertical"></i>
                        </button>
                    </div>

                    <h3>{{ $panel['title'] }}</h3>

                    <div class="dashboard-metrics">
                        @foreach ($panel['metrics'] as $metric)
                            <div class="dashboard-metric-item">
                                <span class="dashboard-metric-label">{{ $metric['label'] }}</span>
                                <strong class="dashboard-metric-value">{{ $metric['value'] }}</strong>
                            </div>
                        @endforeach
                    </div>

                    <p class="dashboard-card-description">{{ $panel['description'] }}</p>

                    <div class="dashboard-card-actions">
                        <a href="{{ $panel['route'] }}" class="btn btn-primary btn-sm">{{ $panel['cta'] }}</a>

                        @if (!empty($panel['secondary_route']) && !empty($panel['secondary_text']))
                            <a href="{{ $panel['secondary_route'] }}" class="dashboard-secondary-link">{{ $panel['secondary_text'] }}</a>
                        @endif
                    </div>

                    <div class="dashboard-order-controls" aria-label="Reordenar panel">
                        <button type="button" class="dashboard-order-btn" data-move="up" aria-label="Subir panel">
                            <i class="fas fa-arrow-up"></i>
                        </button>
                        <button type="button" class="dashboard-order-btn" data-move="down" aria-label="Bajar panel">
                            <i class="fas fa-arrow-down"></i>
                        </button>
                    </div>
                </article>
            @endforeach
        </section>
    </div>
@endsection

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var grid = document.getElementById('dashboard-grid');
            if (!grid) {
                return;
            }

            var orderEndpoint = grid.dataset.orderEndpoint;
            var csrfTokenTag = document.querySelector('meta[name="csrf-token"]');
            var csrfToken = csrfTokenTag ? csrfTokenTag.getAttribute('content') : null;
            var draggedCard = null;
            var saveTimer = null;

            var saveOrder = function () {
                if (!orderEndpoint || !csrfToken) {
                    return;
                }

                var order = Array.from(grid.querySelectorAll('.dashboard-card[data-panel-id]')).map(function (card) {
                    return card.dataset.panelId;
                });

                fetch(orderEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ panel_order: order })
                });
            };

            var queueOrderSave = function () {
                if (saveTimer) {
                    clearTimeout(saveTimer);
                }

                saveTimer = setTimeout(saveOrder, 220);
            };

            var moveCard = function (card, direction) {
                if (!card) {
                    return;
                }

                var sibling = direction === 'up' ? card.previousElementSibling : card.nextElementSibling;
                if (!sibling) {
                    return;
                }

                if (direction === 'up') {
                    grid.insertBefore(card, sibling);
                } else {
                    grid.insertBefore(sibling, card);
                }

                queueOrderSave();
            };

            grid.addEventListener('click', function (event) {
                var orderButton = event.target.closest('[data-move]');
                if (!orderButton) {
                    return;
                }

                var card = orderButton.closest('.dashboard-card');
                moveCard(card, orderButton.dataset.move);
            });

            grid.querySelectorAll('.dashboard-card').forEach(function (card) {
                card.addEventListener('dragstart', function (event) {
                    if (event.target.closest('a, button') && !event.target.closest('.dashboard-drag-handle')) {
                        event.preventDefault();
                        return;
                    }

                    draggedCard = card;
                    card.classList.add('is-dragging');
                });

                card.addEventListener('dragend', function () {
                    card.classList.remove('is-dragging');
                    draggedCard = null;
                    queueOrderSave();
                });
            });

            grid.addEventListener('dragover', function (event) {
                event.preventDefault();
                if (!draggedCard) {
                    return;
                }

                var targetCard = event.target.closest('.dashboard-card');
                if (!targetCard || targetCard === draggedCard) {
                    return;
                }

                var targetRect = targetCard.getBoundingClientRect();
                var placeAfter = event.clientY > targetRect.top + (targetRect.height / 2);

                if (placeAfter) {
                    grid.insertBefore(draggedCard, targetCard.nextElementSibling);
                } else {
                    grid.insertBefore(draggedCard, targetCard);
                }
            });
        });
    </script>
@endsection
