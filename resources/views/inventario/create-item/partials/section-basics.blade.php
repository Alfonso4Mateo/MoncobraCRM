<section class="item-section item-section--stacked">
    <aside class="item-section-label">
        <span>SECCION 1</span>
        <h2>Datos Basicos</h2>
        <p>Identificacion fundamental del producto y vinculacion con proveedor oficial.</p>
    </aside>

    <div class="item-section-fields fields-2">
        <div class="section-content">
        <div class="field-group">
            <label for="codigo">Codigo del producto</label>
            <input id="codigo" name="codigo" type="text" value="{{ old('codigo', $varianteBase->codigo ?? '') }}" placeholder="Ejem: PRD-2024-X1" class="@error('codigo') is-invalid @enderror" required>
        </div>

        <div class="field-group">
            <label for="referencia_proveedor">Referencia proveedor</label>
            <input id="referencia_proveedor" name="referencia_proveedor" type="text" value="{{ old('referencia_proveedor', $varianteBase->referencia_proveedor ?? '') }}" placeholder="REF-8829-00" class="@error('referencia_proveedor') is-invalid @enderror">
        </div>

        <div class="field-group">
            <label for="nombre">Nombre del producto</label>
            <input id="nombre" name="nombre" type="text" value="{{ old('nombre', $varianteBase->descripcion ?? '') }}" placeholder="Nombre corto del producto" class="@error('nombre') is-invalid @enderror" required>
        </div>

        <div class="field-group field-full">
            <label for="descripcion">Descripcion del item</label>
            <textarea id="descripcion" name="descripcion" rows="1" maxlength="1000" placeholder="Descripcion larga o detalles técnicos (opcional)" class="input-auto-grow @error('descripcion') is-invalid @enderror">{{ old('descripcion', $varianteBase->descripcion ?? '') }}</textarea>
        </div>

        <div class="field-group">
            <label for="clase_id">Clase del producto</label>
            <select id="clase_id" name="clase_id" class="@error('clase_id') is-invalid @enderror">
                <option value="">-- Seleccionar una clase --</option>
                @foreach($clases as $id => $nombre)
                    <option value="{{ $id }}" @selected(old('clase_id', $varianteBase->clase_id ?? null) == $id)>{{ $nombre }}</option>
                @endforeach
            </select>
            @error('clase_id')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
        </div>
    </div>
</section>