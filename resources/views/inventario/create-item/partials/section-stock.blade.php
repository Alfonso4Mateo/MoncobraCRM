<section class="item-section item-section--stacked">
    <aside class="item-section-label">
        <span>SECCION 2</span>
        <h2>Datos comunes de stock</h2>
        <p>Estos valores se aplicarán a todas las combinaciones generadas.</p>
    </aside>

    <div class="item-section-fields fields-3">
        <div class="section-content">
            @if(!empty($showStockActual))
                <div class="field-group field-tight field-stock-simple">
                    <label for="stock_actual">Stock actual</label>
                    <input
                        id="stock_actual"
                        name="stock_actual"
                        type="number"
                        min="0"
                        max="99999"
                        value="{{ old('stock_actual', $varianteBase->stock_actual ?? 0) }}"
                        oninput="if(this.value.length > 5) this.value = this.value.slice(0, 5);"
                        class="@error('stock_actual') is-invalid @enderror"
                    >
                </div>
            @endif

            <div class="field-group field-tight field-stock-min">
                <label for="stock_minimo">Mínimo stock (alerta)</label>
                <input id="stock_minimo" name="stock_minimo" type="number" min="0" step="1" value="{{ old('stock_minimo', $varianteBase->stock_minimo ?? 10) }}" class="@error('stock_minimo') is-invalid @enderror">
            </div>

            <div class="field-group field-tight field-critical">
                <label for="nivel_critico">Stock crítico</label>
                <input id="nivel_critico" name="nivel_critico" type="number" min="0" step="1" value="{{ old('nivel_critico', $varianteBase->nivel_critico ?? 5) }}" class="@error('nivel_critico') is-invalid @enderror">
            </div>

            <div class="field-group field-warehouse">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="almacen" class="mb-0">Almacén</label>
                    <button type="button" class="btn btn-sm btn-link p-0 font-weight-bold" data-bs-toggle="modal" data-bs-target="#modalCrearAlmacenQuick" style="color: #173e67; font-size: 0.78rem; text-decoration: none;">
                        <i class="fas fa-plus-circle mr-1"></i> Nuevo almacén
                    </button>
                </div>
                <select id="almacen" name="almacen" style="border: 1px solid #d5dfec; border-radius: 10px; padding: 0.5rem 0.75rem; width: 100%; color: #173e67; font-weight: 700; background: #fff; height: 42px;">
                    <option value="" disabled @selected(empty(old('almacen', $varianteBase->almacen ?? '')))>Selecciona un almacén...</option>
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
    </div>
</section>