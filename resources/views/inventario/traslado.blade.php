@extends('adminlte::page')

@section('title', 'Traslado de Inventario - MoncobraCRM')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/inventario-traslado.css'])
@endsection

@section('content')
    <section class="inv-transfer-page">
        <nav class="inv-transfer-breadcrumb" aria-label="breadcrumb">
            <a href="{{ route('dashboard') }}">Inicio</a>
            <span><i class="fas fa-chevron-right"></i></span>
            <a href="{{ route('inventario.index') }}">Inventario</a>
            <span><i class="fas fa-chevron-right"></i></span>
            <strong>Traslado de Inventario por Lotes</strong>
        </nav>

        <header class="inv-transfer-head">
            <h1>Traslado de Inventario</h1>
            <p>Gestione el movimiento múltiple de existencias entre ubicaciones de almacenamiento.</p>
        </header>

        @if ($errors->any())
            <div class="inv-transfer-alert" role="alert">
                <strong>No se pudo completar el traslado.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="inv-transfer-form" action="{{ route('inventario.traslado.store') }}" method="POST" novalidate>
            @csrf

            <article class="transfer-card">
                <header class="transfer-card-head">
                    <h2><i class="fas fa-random"></i> Detalles del Movimiento de Lote</h2>
                    <span>ID TRANSACCION: {{ $transaccionId }}</span>
                </header>

                <div class="transfer-toolbar">
                    <div class="field-group">
                        <label for="destino_global">Almacen de destino (global)</label>
                        <select id="destino_global" name="destino_global" class="@error('destino_global') is-invalid @enderror" required>
                            <option value="">Seleccione destino...</option>
                            @foreach ($destinos as $destino)
                                <option value="{{ $destino }}" @selected(old('destino_global') === $destino)>{{ $destino }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field-group">
                        <label for="product_search">Buscar producto</label>
                        <input id="product_search" type="text" list="inventario-catalogo-traslado" placeholder="Buscar por nombre o SKU...">
                    </div>

                    <div class="field-group">
                        <label for="ot">Orden de trabajo (OT)</label>
                        <input id="ot" name="ot" type="text" value="{{ old('ot') }}" placeholder="OT-XXXX" class="@error('ot') is-invalid @enderror">
                    </div>

                    <div class="field-group">
                        <label for="solicitante">Solicitante</label>
                        <input id="solicitante" name="solicitante" type="text" value="{{ old('solicitante', auth()->user()->name ?? '') }}" placeholder="Nombre del solicitante" class="@error('solicitante') is-invalid @enderror">
                    </div>
                </div>

                <div class="transfer-table-wrap">
                    <table class="transfer-table" aria-label="Detalle de productos para traslado">
                        <thead>
                            <tr>
                                <th>Codigo</th>
                                <th>Descripcion</th>
                                <th>Almacen origen</th>
                                <th>Stock disp.</th>
                                <th>Cantidad a trasladar</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="transfer_rows">
                            <tr class="transfer-empty-row">
                                <td colspan="6">Busca un producto y pulsa Enter para agregarlo al traslado.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <footer class="transfer-card-footer">
                    <a href="{{ route('inventario.index') }}" class="btn-transfer-cancel">Cancelar</a>
                    <button type="submit" class="btn-transfer-confirm">
                        <i class="far fa-check-circle"></i>
                        Confirmar Traslado de Lote
                    </button>
                </footer>
            </article>
        </form>

        <datalist id="inventario-catalogo-traslado">
            @foreach ($catalogo as $producto)
                <option value="{{ $producto->descripcion }}" data-codigo="{{ $producto->codigo }}"></option>
                <option value="{{ $producto->codigo }}" data-codigo="{{ $producto->codigo }}"></option>
            @endforeach
        </datalist>

        <template id="transfer-row-template">
            <tr>
                <td class="td-code"></td>
                <td>
                    <strong class="td-desc"></strong>
                    <small class="td-ref"></small>
                </td>
                <td class="td-origin"></td>
                <td class="td-stock"></td>
                <td>
                    <input type="hidden" name="item_ids[]" class="input-item-id">
                    <input type="number" name="cantidades[]" min="1" step="1" class="input-qty" required>
                </td>
                <td>
                    <button type="button" class="btn-remove-row" aria-label="Eliminar fila">
                        <i class="far fa-trash-alt"></i>
                    </button>
                </td>
            </tr>
        </template>
    </section>
@endsection

@section('js')
    @php
        $catalogoTrasladoJs = $catalogo
            ->map(function ($item) {
                return [
                    'id' => (int) $item->id,
                    'codigo' => (string) $item->codigo,
                    'descripcion' => (string) $item->descripcion,
                    'referencia_proveedor' => (string) ($item->referencia_proveedor ?? ''),
                    'almacen' => (string) ($item->almacen ?? 'Sin almacen'),
                    'stock_actual' => (int) ($item->stock_actual ?? 0),
                ];
            })
            ->values();
    @endphp

    <script>
        (function () {
            const catalogo = @json($catalogoTrasladoJs);
            const rowsBody = document.getElementById('transfer_rows');
            const searchInput = document.getElementById('product_search');
            const rowTemplate = document.getElementById('transfer-row-template');

            const normalize = (value) => String(value || '').trim().toLowerCase();

            const removeEmptyRow = () => {
                const empty = rowsBody.querySelector('.transfer-empty-row');
                if (empty) {
                    empty.remove();
                }
            };

            const renderEmptyRow = () => {
                if (rowsBody.children.length > 0) {
                    return;
                }

                const tr = document.createElement('tr');
                tr.className = 'transfer-empty-row';
                tr.innerHTML = '<td colspan="6">Busca un producto y pulsa Enter para agregarlo al traslado.</td>';
                rowsBody.appendChild(tr);
            };

            const productAlreadyAdded = (itemId) => {
                return Array.from(rowsBody.querySelectorAll('.input-item-id')).some((input) => Number(input.value) === itemId);
            };

            const findCandidate = () => {
                const term = normalize(searchInput.value);
                if (!term) {
                    return null;
                }

                return catalogo.find((item) => {
                    return normalize(item.codigo).includes(term) || normalize(item.descripcion).includes(term);
                }) || null;
            };

            const findExactCandidate = () => {
                const term = normalize(searchInput.value);
                if (!term) {
                    return null;
                }

                return catalogo.find((item) => {
                    return normalize(item.codigo) === term || normalize(item.descripcion) === term;
                }) || null;
            };

            const addRow = (item) => {
                if (!item) {
                    return;
                }

                if (productAlreadyAdded(item.id)) {
                    return;
                }

                removeEmptyRow();

                const fragment = rowTemplate.content.cloneNode(true);
                const row = fragment.querySelector('tr');
                row.querySelector('.td-code').textContent = item.codigo;
                row.querySelector('.td-desc').textContent = item.descripcion;
                row.querySelector('.td-ref').textContent = item.referencia_proveedor ? `Ref: ${item.referencia_proveedor}` : '';
                row.querySelector('.td-origin').textContent = item.almacen;
                row.querySelector('.td-stock').textContent = item.stock_actual;
                row.querySelector('.input-item-id').value = String(item.id);

                const qtyInput = row.querySelector('.input-qty');
                qtyInput.max = String(Math.max(1, item.stock_actual));
                qtyInput.value = String(Math.max(1, Math.round(item.stock_actual * 0.2)));

                row.querySelector('.btn-remove-row').addEventListener('click', function () {
                    row.remove();
                    renderEmptyRow();
                });

                rowsBody.appendChild(row);
                searchInput.value = '';
            };

            const tryAddFromSearch = (preferExact) => {
                const item = preferExact ? findExactCandidate() : findCandidate();
                addRow(item);
            };

            searchInput.addEventListener('keydown', function (event) {
                if (event.key !== 'Enter') {
                    return;
                }

                event.preventDefault();
                tryAddFromSearch(false);
            });

            searchInput.addEventListener('change', function () {
                tryAddFromSearch(true);
            });
        })();
    </script>
@endsection
