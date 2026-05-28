<aside class="main-sidebar {{ config('adminlte.classes_sidebar', 'sidebar-dark-primary elevation-4') }} d-flex flex-column">

    {{-- Sidebar brand logo --}}
    @if(config('adminlte.logo_img_xl'))
        @include('adminlte::partials.common.brand-logo-xl')
    @else
        @include('adminlte::partials.common.brand-logo-xs')
    @endif

    @php
        $currentUser = auth()->user();
        $userProyectos = collect();
        $activeProyectoId = null;
        $activeProyectoNombre = null;

        if ($currentUser) {
            $userProyectos = $currentUser
                ->proyectos()
                ->orderBy('nombre')
                ->get(['proyectos.id', 'proyectos.nombre']);

            if ($userProyectos->isNotEmpty()) {
                $sessionProyectoId = (int) session('active_proyecto_id');

                $activeProyecto = $userProyectos->firstWhere('id', $sessionProyectoId) ?? $userProyectos->first();

                $activeProyectoId = $activeProyecto->id;
                $activeProyectoNombre = $activeProyecto->nombre;

                if ($sessionProyectoId !== (int) $activeProyectoId) {
                    session(['active_proyecto_id' => $activeProyectoId]);
                }
            }
        }

        $sidebarMenu = array_values($adminlte->menu('sidebar'));
        $toolsStartIndex = null;

        foreach ($sidebarMenu as $index => $item) {
            $itemClass = $item['class'] ?? '';
            $isToolsHeader = isset($item['header']) && ($item['header'] ?? '') === 'Herramientas';

            if (str_contains($itemClass, 'sidebar-tools-start') || $isToolsHeader) {
                $toolsStartIndex = $index;
                break;
            }
        }

        $mainMenu = $toolsStartIndex === null ? $sidebarMenu : array_slice($sidebarMenu, 0, $toolsStartIndex);
        $toolsMenu = $toolsStartIndex === null ? [] : array_slice($sidebarMenu, $toolsStartIndex);
    @endphp

    {{-- BLOQUE SUPERIOR: Menú principal (Controlado por AdminLTE y su scroll JS) --}}
    <div class="sidebar flex-grow-1">
        <nav class="pt-2">
            <ul class="nav nav-pills nav-sidebar flex-column sidebar-main-menu {{ config('adminlte.classes_sidebar_nav', '') }}"
                data-widget="treeview" role="menu"
                @if(config('adminlte.sidebar_nav_animation_speed') != 300)
                    data-animation-speed="{{ config('adminlte.sidebar_nav_animation_speed') }}"
                @endif
                @if(!config('adminlte.sidebar_nav_accordion'))
                    data-accordion="false"
                @endif>
                @if($userProyectos->count() === 1)
                    <li class="nav-item">
                        <span class="nav-link active">
                            <i class="nav-icon fas fa-building"></i>
                            <p>{{ $activeProyectoNombre }}</p>
                        </span>
                    </li>
                @elseif($userProyectos->count() > 1)
                    <li class="nav-item has-treeview project-switcher-item">
                        <a href="" class="nav-link project-switcher-link">
                            <i class="nav-icon fas fa-building"></i>
                            <p>
                                {{ $activeProyectoNombre }}
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @foreach($userProyectos as $proyecto)
                                <li class="nav-item">
                                    <a href="{{ route('proyectos.seleccionar', $proyecto) }}"
                                       class="nav-link {{ (int) $activeProyectoId === (int) $proyecto->id ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>{{ $proyecto->nombre }}</p>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @else
                    <li class="nav-item">
                        <span class="nav-link text-warning">
                            <i class="nav-icon fas fa-exclamation-triangle"></i>
                            <p>Sin proyecto asignado</p>
                        </span>
                    </li>
                @endif

                {{-- Main sidebar links --}}
                @each('adminlte::partials.sidebar.menu-item', $mainMenu, 'item')
            </ul>
        </nav>
    </div>

    {{-- BLOQUE INFERIOR: Herramientas (FUERA del scroll de AdminLTE, anclado al fondo) --}}
    @if(count($toolsMenu) > 0)
        <div class="sidebar-custom-bottom mt-auto pb-2">
            <nav class="pt-2">
                <ul class="nav nav-pills nav-sidebar flex-column sidebar-tools-menu {{ config('adminlte.classes_sidebar_nav', '') }}"
                    data-widget="treeview" role="menu"
                    @if(config('adminlte.sidebar_nav_animation_speed') != 300)
                        data-animation-speed="{{ config('adminlte.sidebar_nav_animation_speed') }}"
                    @endif
                    @if(!config('adminlte.sidebar_nav_accordion'))
                        data-accordion="false"
                    @endif>
                    {{-- Tools links pinned to bottom --}}
                    @each('adminlte::partials.sidebar.menu-item', $toolsMenu, 'item')
                </ul>
            </nav>
        </div>
    @endif

</aside>

<style>
    /* Aseguramos que el pie se mantenga abajo sin romper nada */
    .sidebar-custom-bottom {
        flex-shrink: 0;
        background-color: inherit;
        z-index: 10;
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }

    .sidebar-custom-bottom .nav-header {
        margin: 0.35rem 0 0.25rem;
        padding: 0.65rem 1rem 0.25rem;
        background: transparent;
        border-top: 1px solid rgba(255,255,255,0.06);
        color: rgba(255, 255, 255, 0.65);
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .sidebar-custom-bottom .nav-sidebar .nav-link {
        border-radius: 0.35rem;
        color: rgba(255,255,255,0.92) !important;
    }

    /* Selected / hovered tool item: blue background and white text/icons */
    .sidebar-custom-bottom .nav-sidebar .nav-link.active,
    .sidebar-custom-bottom .nav-sidebar .nav-link:focus,
    .sidebar-custom-bottom .nav-sidebar .nav-link:hover {
        background: #2563EB !important;
        color: #FFFFFF !important;
    }

    .sidebar-custom-bottom .nav-sidebar .nav-link .nav-icon,
    .sidebar-custom-bottom .nav-sidebar .nav-link i {
        color: #FFFFFF !important;
    }

    /* If any logo/text appears inside the tools area, keep it white */
    .sidebar-custom-bottom .brand-text,
    .sidebar-custom-bottom .brand-logo-image {
        color: #FFFFFF !important;
        filter: none !important;
    }
</style>