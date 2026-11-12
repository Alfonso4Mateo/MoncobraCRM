{{-- resources/views/epis/card.blade.php --}}
@php
    $epiPuestosCantidades = $epi->puestos->pluck('pivot.cantidad', 'id');
    $stockTotal = $epi->stock_disponible;
    $codigoInventario = $epi->inventarioVariante?->codigo ?? $epi->inventario?->codigo;
    $nombreInventario = $epi->inventarioVariante?->nombre ?? $epi->inventario?->nombre ?? $epi->inventario?->descripcion;
@endphp
<div class="epi-card {{ !$epi->activo ? 'opacity-50' : '' }}"
     id="epi-card-{{ $epi->id }}"
     data-epi-id="{{ $epi->id }}"
     data-epi-nombre="{{ $epi->nombre }}"
     data-epi-descripcion="{{ $epi->descripcion }}"
     data-epi-categoria="{{ $epi->categoria }}"
     data-epi-activo="{{ $epi->activo ? '1' : '0' }}"
     data-epi-inventario-id="{{ $epi->inventario_id ?? '' }}"
     data-epi-inventario-variante-id="{{ $epi->inventario_variante_id ?? '' }}"
     data-epi-puestos='@json($epiPuestosCantidades)'>

    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px;">
        <h3 class="epi-card__title" style="margin-bottom: 4px;">{{ $epi->nombre }}</h3>
        @if(!$epi->activo)
            <span class="badge badge-secondary" style="flex-shrink: 0;">Inactivo</span>
        @else
            <span class="badge badge-success" style="background: #10b981; flex-shrink: 0;">Activo</span>
        @endif
    </div>

    {{-- VINCULACIÓN CON INVENTARIO --}}
    @if($codigoInventario)
        <div style="margin-bottom: 8px;">
            <span style="display: inline-flex; align-items: center; gap: 5px; font-size: 0.75rem; background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 2px 8px; border-radius: 6px; font-weight: 700;" title="Artículo de Inventario: {{ $nombreInventario }}">
                <i class="fas fa-boxes"></i> SKU: {{ $codigoInventario }} · Stock: {{ $stockTotal }} uds.
            </span>
        </div>
    @else
        <div style="margin-bottom: 8px;">
            <span style="font-size: 0.72rem; color: #94a3b8; font-style: italic;">
                <i class="fas fa-unlink"></i> Sin ítem de inventario vinculado
            </span>
        </div>
    @endif

    <p class="epi-card__desc">{{ $epi->descripcion ?: 'Sin descripción detallada.' }}</p>

    <div class="epi-card__footer">
        <span class="epi-badge" title="Cantidad de puestos que requieren este EPI">
            <i class="fas fa-users mr-1"></i> {{ $epi->puestos_count }} Puestos
        </span>
    </div>

    <div class="epi-card__actions">
        <button type="button" class="epi-action-btn epi-action-btn--edit js-btn-edit-epi" title="Editar EPI">
            <i class="fas fa-pen"></i> Editar
        </button>

        <button type="button"
                class="epi-action-btn js-btn-toggle-epi {{ $epi->activo ? 'epi-action-btn--baja' : 'epi-action-btn--activar' }}"
                style="flex:1;"
                title="{{ $epi->activo ? 'Dar de baja' : 'Reactivar' }}">
            <i class="fas {{ $epi->activo ? 'fa-ban' : 'fa-check-circle' }}"></i>
            {{ $epi->activo ? 'Dar de baja' : 'Reactivar' }}
        </button>

        <button type="button" class="epi-action-btn epi-action-btn--delete js-btn-delete-epi" style="flex:1;" title="Eliminar EPI">
            <i class="fas fa-trash"></i> Eliminar
        </button>
    </div>
</div>