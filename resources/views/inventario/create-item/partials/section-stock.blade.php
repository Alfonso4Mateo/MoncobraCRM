<section class="item-section item-section--stacked">
    <aside class="item-section-label">
        <span>SECCION 2</span>
        <h2>Datos comunes de stock</h2>
        <p>Estos valores se aplicarán a todas las combinaciones generadas.</p>
    </aside>

    <div class="item-section-fields fields-2">
        <div class="section-content">
        <div class="field-group field-tight">
            <label for="stock_minimo">Minimo stock (alerta)</label>
            <input id="stock_minimo" name="stock_minimo" type="number" min="0" step="1" value="{{ old('stock_minimo', $varianteBase->stock_minimo ?? 10) }}" class="@error('stock_minimo') is-invalid @enderror">
        </div>

        <div class="field-group field-tight">
            <label for="nivel_critico">Stock critico</label>
            <input id="nivel_critico" name="nivel_critico" type="number" min="0" step="1" value="{{ old('nivel_critico', $varianteBase->nivel_critico ?? 5) }}" class="@error('nivel_critico') is-invalid @enderror">
        </div>

        <div class="field-group">
            <label for="almacen">Almacen</label>
            <input id="almacen" name="almacen" type="text" value="{{ old('almacen', $varianteBase->almacen ?? '') }}" placeholder="Almacen Central" class="@error('almacen') is-invalid @enderror">
        </div>

        <div class="field-group">
            <label for="ubicacion">Ubicacion</label>
            <input id="ubicacion" name="ubicacion" type="text" value="{{ old('ubicacion', $varianteBase->ubicacion ?? '') }}" placeholder="Pasillo 3 / Estanteria 12" class="@error('ubicacion') is-invalid @enderror">
        </div>
        </div>
    </div>
</section>