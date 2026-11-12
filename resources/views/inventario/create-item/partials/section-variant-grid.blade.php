<style>
    /* Contenedor del Grid: más alto (14 filas visibles), con opción de expandir al 100% */
    .variants-grid-shell {
        max-height: 540px;
        overflow-y: auto;
        overflow-x: auto;
        border: 1px solid #dce6f4;
        border-radius: 12px;
        position: relative;
        transition: max-height 0.25s ease;
    }
    .variants-grid-shell.is-expanded {
        max-height: none !important;
        overflow-y: visible !important;
    }

    /* Cabecera fija al hacer scroll entre 70 variantes */
    .variants-grid-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        table-layout: auto !important; /* Permite que cada columna ocupe según su contenido real */
    }
    .variants-grid-table thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background: #f8fafc;
        border-bottom: 2px solid #cbd5e1;
        white-space: nowrap;
        padding: 0.65rem 0.6rem !important;
        font-size: 0.7rem;
        font-weight: 800;
        color: #475569;
        text-transform: uppercase;
    }

    /* Celdas compactas al estilo Odoo/SAP */
    .variants-grid-table tbody td {
        padding: 0.45rem 0.6rem !important;
        vertical-align: middle;
        white-space: nowrap;
        border-bottom: 1px solid #f1f5f9;
    }

    /* Las columnas de atributos (Ignífugo, Color, Talla...) ocupan solo lo que mide su badge */
    .variants-grid-table th.col-attr,
    .variants-grid-table td.col-attr {
        width: 1%;
        white-space: nowrap;
    }

    /* Columna SKU: Prioridad máxima de espacio para que nunca se corte */
    .variants-grid-table th.col-sku,
    .variants-grid-table td.col-sku {
        min-width: 230px;
        width: 28%;
    }
    .variants-grid-table td.col-sku input {
        width: 100% !important;
        min-width: 215px;
        font-family: 'Manrope', monospace;
        font-weight: 800;
        font-size: 0.82rem;
        color: #0f2747;
        padding: 0.35rem 0.6rem;
        border-radius: 7px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
    }

    /* Almacén y Controles numéricos ajustados sin desperdiciar espacio */
    .variants-grid-table th.col-almacen,
    .variants-grid-table td.col-almacen {
        min-width: 155px;
        width: 15%;
    }
    .variants-grid-table th.col-num,
    .variants-grid-table td.col-num {
        width: 1%;
        text-align: center;
    }
    .variants-grid-table td.col-num input {
        width: 72px !important;
        text-align: center;
        font-weight: 800;
        padding: 0.3rem 0.4rem;
        border-radius: 7px;
    }
    .variants-grid-table th.col-status,
    .variants-grid-table td.col-status {
        width: 1%;
        text-align: center;
    }

    /* Chips de códigos SKU dentro de las tarjetas de la Sección 1.5 */
    .sku-tokens-bar {
        margin-top: 0.55rem;
        padding-top: 0.5rem;
        border-top: 1px dashed #cbd5e1;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.4rem;
    }
    .sku-tokens-bar__title {
        font-size: 0.66rem;
        font-weight: 800;
        color: #64748b;
        text-transform: uppercase;
        margin-right: 0.25rem;
    }
    .sku-token-item {
        display: inline-flex;
        align-items: center;
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 1px 5px;
        font-size: 0.72rem;
    }
    .sku-token-item span {
        color: #475569;
        font-weight: 700;
        margin-right: 4px;
    }
    .sku-token-item input {
        width: 46px;
        border: none;
        border-bottom: 1.5px solid #173e67;
        background: #fff;
        color: #0f2747;
        font-weight: 800;
        font-size: 0.72rem;
        text-align: center;
        text-transform: uppercase;
        border-radius: 3px;
        padding: 0 2px;
    }
</style>


<section id="section-variant-grid" class="item-section item-section--stacked">
    <aside class="item-section-label">
        <span>SECCIÓN 2.5</span>
        <h2>Combinaciones, Stock y Almacén Individual</h2>
        <p>Personaliza el SKU, el almacén de destino, el stock inicial y los umbrales de alerta (mínimo y crítico) para cada variante generada.</p>
    </aside>

    <div class="item-section-fields fields-1">
        <div class="variants-grid-toolbar">
            <div class="variants-grid-toolbar__group">
                <label class="variants-grid-toolbar__label" for="bulk-stock-value">Aplicar Stock a todas</label>
                <div class="variants-grid-toolbar__inline">
                    <input id="bulk-stock-value" type="number" min="0" step="1" value="0" aria-label="Stock masivo">
                    <button type="button" class="variants-grid-toolbar__btn variants-grid-toolbar__btn--primary" id="bulk-apply-stock">Aplicar</button>
                    <button type="button" class="variants-grid-toolbar__btn" id="bulk-set-zero">Poner 0</button>
                </div>
            </div>

            <div class="variants-grid-toolbar__group">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="variants-grid-toolbar__label mb-0" for="bulk-almacen-select">Asignar Almacén a todas</label>
                    <button type="button"
                            class="btn btn-sm btn-link p-0 font-weight-bold"
                            data-bs-toggle="modal"
                            data-bs-target="#modalCrearAlmacenQuick"
                            style="color: #173e67; font-size: 0.72rem; text-decoration: none;"
                            title="Crear un nuevo almacén rápido">
                        <i class="fas fa-plus-circle"></i> Nuevo
                    </button>
                </div>
                <div class="variants-grid-toolbar__inline">
                    <select id="bulk-almacen-select" style="height: 36px; border: 1px solid #d5dfec; border-radius: 8px; padding: 0 0.65rem; font-weight: 700; color: #0f2747; background: #fff; font-size: 0.82rem;">
                        <option value="">-- Seleccionar almacén --</option>
                        @isset($almacenes)
                            @foreach($almacenes as $almOpt)
                                <option value="{{ $almOpt->nombre }}">{{ $almOpt->nombre }}</option>
                            @endforeach
                        @endisset
                    </select>
                    <button type="button" class="variants-grid-toolbar__btn" id="bulk-apply-almacen">Asignar</button>
                    <button type="button"
                            class="variants-grid-toolbar__btn"
                            data-bs-toggle="modal"
                            data-bs-target="#modalCrearAlmacenQuick"
                            title="Crear nuevo almacén sin salir de la matriz"
                            style="padding: 0 0.6rem;">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>

            <div class="variants-grid-toolbar__group">
                <span class="variants-grid-toolbar__label">Estado de filas</span>
                <div class="variants-grid-toolbar__inline">
                    <button type="button" class="variants-grid-toolbar__btn" id="bulk-activate-all">Activar todas</button>
                    <button type="button" class="variants-grid-toolbar__btn" id="bulk-deactivate-all">Desactivar todas</button>
                </div>
            </div>

            <div class="variants-grid-toolbar__group" style="min-width: 210px; flex: 1;">
                <label class="variants-grid-toolbar__label" for="filter-variants-input">Filtrar combinaciones</label>
                <div class="variants-grid-toolbar__inline">
                    <input id="filter-variants-input" type="text" placeholder="Ej: Azul, Grande, CAS-01..." style="width: 100%; height: 36px; border: 1px solid #d5dfec; border-radius: 8px; padding: 0 0.65rem; font-size: 0.82rem; font-weight: 600;">
                </div>
            </div>

            <div class="d-flex align-items-center" style="gap: 0.5rem; margin-left: auto;">
                <button type="button" class="variants-grid-toolbar__btn" id="btn-toggle-expand-grid" title="Ver todas las filas sin scroll o compactar">
                    <i class="fas fa-expand-arrows-alt mr-1"></i> <span id="label-toggle-expand">Ver todas</span>
                </button>
                <div class="variants-grid-toolbar__summary" id="variants-summary">
                    0 combinaciones activas
                </div>
            </div>
        </div>

        <div class="variants-grid-shell table-responsive" id="variants-grid-shell">
            <table class="table variants-grid-table">
                <thead id="variantes-head"></thead>
                <tbody id="variantes-container"></tbody>
            </table>
        </div>

        <div class="field-group field-full" style="margin-top: 1rem; display: flex; justify-content: space-between; align-items: center;">
            <small class="text-muted font-weight-bold">Cada variante puede almacenarse en una ubicación distinta y definir sus propias alertas.</small>
            <strong>Total stock combinado: <span id="stock-total">0</span> uds.</strong>
        </div>
    </div>
</section>