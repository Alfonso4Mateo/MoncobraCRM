<aside class="inventory-item-side">
    <article class="side-card with-accent">
        <h3>Ultima accion</h3>
        @if ($ultimaAccion)
            <p>{{ $ultimaAccion->codigo }} - {{ $ultimaAccion->descripcion }}</p>
        @else
            <p>No hay registros previos en esta sesion.</p>
        @endif
    </article>

    <article class="side-card">
        <h3>Estado de conexion</h3>
        <p><i class="fas fa-circle"></i> Base de Datos: Sincronizada</p>
    </article>
</aside>