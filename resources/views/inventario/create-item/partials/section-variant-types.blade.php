<section class="item-section item-section--stacked">
    <aside class="item-section-label">
        <span>SECCION 1.5</span>
        <h2>Variantes Dinámicas del Producto</h2>
        <p>Define los tipos de variantes, añade varios valores por tipo y el sistema generará todas las combinaciones posibles.</p>
    </aside>

    <div class="item-section-fields variants-grid-fields">
        <div class="section-content">
        <div class="field-group field-full">
            <label for="tipos_atributos">Tipos de variantes</label>
            <input id="tipos_atributos" name="tipos_atributos" type="text" value="{{ old('tipos_atributos', implode(', ', $varianteBase?->tipos_atributos ?? array_keys($valoresIniciales ?? []))) }}" placeholder="Ejem: Talla, Color, Material (separados por coma)" class="@error('tipos_atributos') is-invalid @enderror">
            <small style="color: #666; display: block; margin-top: 0.5rem;">Define los niveles en orden jerárquico: Talla → Color → Material. El sistema generará las combinaciones finales siguiendo ese orden.</small>
        </div>

        <div id="variant-order-preview" class="field-group field-full" style="margin-top: 0.25rem; padding: 0.75rem 1rem; border: 1px dashed #d7e1ee; border-radius: 12px; background: #f8fbff; color: #33506f; font-size: 0.9rem; font-weight: 700;">
            Orden de variantes: <span style="font-weight: 800; color: #0f2747;">—</span>
        </div>

        <div id="atributos-container" class="field-group field-full" style="margin-top: 1rem;">
            <!-- Los campos de atributos se generarán dinámicamente con JavaScript -->
        </div>
        </div>
    </div>
</section>