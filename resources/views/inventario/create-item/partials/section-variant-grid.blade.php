<section class="item-section item-section--stacked">
    <aside class="item-section-label">
        <span>SECCION 2.5</span>
        <h2>Combinaciones y stock individual</h2>
        <p>Cada combinación tendrá su propio stock. Ajusta aquí las unidades por talla, color o cualquier otro tipo de variante.</p>
    </aside>

    <div class="item-section-fields fields-1">
        <div class="variants-grid-toolbar">
            <div class="variants-grid-toolbar__group">
                <label class="variants-grid-toolbar__label" for="bulk-stock-value">Aplicar a todas</label>
                <div class="variants-grid-toolbar__inline">
                    <input id="bulk-stock-value" type="number" min="0" step="1" value="0" aria-label="Valor de stock para aplicar a todas las variantes">
                    <button type="button" class="variants-grid-toolbar__btn variants-grid-toolbar__btn--primary" id="bulk-apply-stock">Aplicar</button>
                    <button type="button" class="variants-grid-toolbar__btn" id="bulk-set-zero">Poner 0</button>
                </div>
            </div>

            <div class="variants-grid-toolbar__group">
                <span class="variants-grid-toolbar__label">Acciones masivas</span>
                <div class="variants-grid-toolbar__inline">
                    <button type="button" class="variants-grid-toolbar__btn" id="bulk-activate-all">Activar todas</button>
                    <button type="button" class="variants-grid-toolbar__btn" id="bulk-deactivate-all">Desactivar todas</button>
                </div>
            </div>

            <div class="variants-grid-toolbar__summary" id="variants-summary">
                0 combinaciones activas
            </div>
        </div>

        <div class="variants-grid-shell table-responsive">
            <table class="table variants-grid-table">
                <thead id="variantes-head"></thead>
                <tbody id="variantes-container"></tbody>
            </table>
        </div>

        <div class="field-group field-full" style="margin-top: 1rem;">
            <strong>Total stock combinado: <span id="stock-total">0</span></strong>
        </div>
    </div>
</section>