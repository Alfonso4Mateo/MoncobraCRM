<section id="section-variant-types" class="item-section item-section--stacked">
    <aside class="item-section-label">
        <span>SECCIÓN 1.5</span>
        <h2>Variantes Dinámicas del Producto</h2>
        <p>Selecciona los atributos del catálogo rápido (o gestiona los tuyos propios) y escribe sus valores pulsando <strong>Enter</strong> o coma.</p>
    </aside>

    <div class="item-section-fields variants-grid-fields">
        <div class="section-content" style="display: block !important;">
            {{-- Input oculto que mantiene el contrato con InventarioController --}}
            <input id="tipos_atributos" name="tipos_atributos" type="hidden" value="{{ old('tipos_atributos', implode(', ', $varianteBase?->tipos_atributos ?? array_keys($valoresIniciales ?? []))) }}">

            {{-- Barra limpia: Atributos dinámicos a la izquierda + Botón de Modal a la derecha --}}
            <div class="variant-type-adder">
                <div class="variant-type-adder__Quick">
                    <span class="variant-type-adder__label">Atributos rápidos:</span>
                    <div id="quick-attributes-pills" class="d-inline-flex flex-wrap align-items-center" style="gap: 0.45rem;">
                        {{-- Se renderiza dinámicamente por JS desde el catálogo de atributos --}}
                    </div>
                </div>

                <div>
                    <button type="button" id="btn-open-manage-attributes" class="btn-manage-attrs">
                        <i class="fas fa-sliders-h"></i> Gestionar / Añadir atributos
                    </button>
                </div>
            </div>

            {{-- Grid de tarjetas de atributos activos con sus Chips --}}
            <div id="atributos-container" class="variant-chips-grid">
                <!-- Generado dinámicamente con JS -->
            </div>
        </div>
    </div>
</section>

{{-- MODAL PARA GESTIONAR / AÑADIR / QUITAR ATRIBUTOS RÁPIDOS --}}
<div class="erp-modal-backdrop" id="modalGestionarAtributos" aria-hidden="true">
    <div class="erp-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="modalAtributosTitle">
        <div class="erp-modal-header">
            <h5 class="erp-modal-title" id="modalAtributosTitle">
                <i class="fas fa-tags mr-2" style="color: #173e67;"></i> Catálogo de Atributos de Variantes
            </h5>
            <button type="button" class="erp-modal-close" id="btnCloseModalAtributos">&times;</button>
        </div>

        <div class="erp-modal-body">
            <p style="font-size: 0.85rem; color: #64748b; margin-top: 0; margin-bottom: 1rem;">
                Añade nuevos niveles (ej. <em>Talla, Color, Voltaje, Medida</em>) o elimina de tu barra rápida los que no utilices.
            </p>

            {{-- Formulario para crear un nuevo atributo en el catálogo --}}
            <div class="d-flex" style="gap: 0.5rem; margin-bottom: 1.25rem;">
                <input type="text" id="inputNuevoAtributoCatalogo" class="erp-input" placeholder="Nombre del atributo (ej. Material, Ignífugo, Calibre)...">
                <button type="button" id="btnGuardarAtributoCatalogo" class="btn-erp-primary" style="white-space: nowrap;">
                    <i class="fas fa-plus"></i> Añadir
                </button>
            </div>

            <span class="erp-label">Atributos disponibles en tu barra rápida</span>
            <div id="listaCatalogoAtributosModal" class="attr-catalog-list">
                {{-- Lista generada por JS con opción de Usar en el producto o Eliminar de la barra --}}
            </div>
        </div>

        <div class="erp-modal-footer">
            <button type="button" class="btn-erp-back" id="btnResetAtributosDefault" title="Restaurar sugerencias originales">
                Restaurar por defecto
            </button>
            <button type="button" class="btn-erp-primary" id="btnDoneModalAtributos">
                Listo
            </button>
        </div>
    </div>
</div>