<section class="item-section item-section--stacked">
    <aside class="item-section-label">
        <span>SECCION 1</span>
        <h2>Datos Básicos</h2>
        <p>Identificación fundamental del producto y vinculación con proveedor oficial.</p>
    </aside>

    <div class="item-section-fields fields-2">
        <div class="section-content">
            <div class="field-group field-code">
                <label for="codigo">Código del producto</label>
                <input id="codigo" name="codigo" type="text" value="{{ old('codigo', $varianteBase->codigo ?? '') }}" placeholder="Ejem: PRD-2024-X1" class="@error('codigo') is-invalid @enderror" required>
            </div>

            <div class="field-group field-name">
                <label for="nombre">Nombre del producto</label>
                <input id="nombre" name="nombre" type="text" value="{{ old('nombre', $varianteBase->nombre ?? $varianteBase->descripcion ?? '') }}" placeholder="Nombre corto del producto" class="@error('nombre') is-invalid @enderror" required>
            </div>

            <div class="field-group field-class">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="clase_id" class="mb-0">Clase del producto</label>
                    <button type="button" class="btn btn-sm btn-link p-0 font-weight-bold" data-bs-toggle="modal" data-bs-target="#modalCrearClaseQuick" style="color: #173e67; font-size: 0.78rem; text-decoration: none;">
                        <i class="fas fa-plus-circle mr-1"></i> Nueva clase
                    </button>
                </div>
                <select id="clase_id" name="clase_id" class="@error('clase_id') is-invalid @enderror">
                    <option value="">-- Seleccionar una clase --</option>
                    @foreach($clases as $id => $nombre)
                        <option value="{{ $id }}" @selected(old('clase_id', $varianteBase?->clase_id ?? null) == $id)>{{ $nombre }}</option>
                    @endforeach
                </select>
                @error('clase_id')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <div class="field-group field-provider">
                <label for="referencia_proveedor">Referencia proveedor</label>
                <input id="referencia_proveedor" name="referencia_proveedor" type="text" value="{{ old('referencia_proveedor', $varianteBase->referencia_proveedor ?? '') }}" placeholder="REF-8829-00" class="@error('referencia_proveedor') is-invalid @enderror">
            </div>

            <div class="field-group field-provider-name">
                <label for="nombre_proveedor">Nombre proveedor</label>
                <input id="nombre_proveedor" name="nombre_proveedor" type="text" value="{{ old('nombre_proveedor', $varianteBase->nombre_proveedor ?? '') }}" placeholder="Ej: Würth / 3M / Iturri" class="@error('nombre_proveedor') is-invalid @enderror">
            </div>

            <div class="field-group field-description">
                <label for="descripcion">Descripción del ítem</label>
                <textarea id="descripcion" name="descripcion" rows="1" maxlength="1000" placeholder="Descripción larga o detalles técnicos (opcional)" class="input-auto-grow @error('descripcion') is-invalid @enderror">{{ old('descripcion', $varianteBase->descripcion ?? '') }}</textarea>
            </div>
        </div>
    </div>
</section>