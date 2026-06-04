<section class="item-section item-section--stacked">
    <aside class="item-section-label">
        <span>SECCION 2</span>
        <h2>Datos comunes de stock</h2>
        <p>Estos valores se aplicarán a todas las combinaciones generadas.</p>
    </aside>

    @php
        $stockSectionClass = !empty($showStockActual) ? 'fields-3' : 'fields-2';
    @endphp

    <div class="item-section-fields {{ $stockSectionClass }}">
        <div class="section-content">
        @if(!empty($showStockActual))
        <div class="field-group field-tight">
            <label for="stock_actual">Stock actual</label>
            <input id="stock_actual" name="stock_actual" type="number" min="0" max="99999" oninput="if(this.value.length > 5) this.value = this.value.slice(0, 5);">
        </div>
        @endif

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
            <select id="almacen" name="almacen" style="border: 1px solid #d5dfec; border-radius: 10px; padding: 0.5rem 0.75rem; width: 100%; color: #173e67; font-weight: 700; background: #fff; height: 42px;">
                <option value="" disabled selected>Selecciona un almacén...</option>
                    @isset($almacenes)
                         @foreach($almacenes as $almacenOpt)
                            <option value="{{ $almacenOpt->nombre }}" @selected(old('almacen', $varianteBase->almacen ?? '') === $almacenOpt->nombre)>
                                {{ $almacenOpt->nombre }}
                            </option>
                         @endforeach
                    @endisset
            </select>
        </div>
    </div>
</section>