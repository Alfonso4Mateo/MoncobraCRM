{{-- resources/views/epis/card.blade.php --}}
{{-- Se usa tanto en el render inicial de epis/index.blade.php como en las
     respuestas JSON del controlador (crear/editar/dar de baja), para que el
     frontend pueda insertar/reemplazar la tarjeta sin recargar la página. --}}
@php
    $epiPuestosCantidades = $epi->puestos->pluck('pivot.cantidad', 'id');
@endphp
<div class="epi-card {{ !$epi->activo ? 'opacity-50' : '' }}"
     id="epi-card-{{ $epi->id }}"
     data-epi-id="{{ $epi->id }}"
     data-epi-nombre="{{ $epi->nombre }}"
     data-epi-descripcion="{{ $epi->descripcion }}"
     data-epi-categoria="{{ $epi->categoria }}"
     data-epi-activo="{{ $epi->activo ? '1' : '0' }}"
     data-epi-puestos='@json($epiPuestosCantidades)'>

    <h3 class="epi-card__title">{{ $epi->nombre }}</h3>
    <p class="epi-card__desc">{{ $epi->descripcion ?: 'Sin descripción detallada.' }}</p>

    <div class="epi-card__footer">
        <span class="epi-badge" title="Cantidad de puestos que requieren este EPI">
            <i class="fas fa-users mr-1"></i> {{ $epi->puestos_count }} Puestos
        </span>
        @if(!$epi->activo)
            <span class="badge badge-secondary">Inactivo</span>
        @else
            <span class="badge badge-success" style="background: #10b981;">Activo</span>
        @endif
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