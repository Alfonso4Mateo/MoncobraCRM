<aside class="main-sidebar {{ config('adminlte.classes_sidebar', 'sidebar-dark-primary elevation-4') }}">

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

    {{-- Sidebar menu --}}
    <div class="sidebar">
        <nav class="pt-2 d-flex flex-column h-100">
            <ul class="nav nav-pills nav-sidebar flex-column sidebar-main-menu sidebar-main-menu-split {{ config('adminlte.classes_sidebar_nav', '') }}"
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

            @if(count($toolsMenu) > 0)
                <ul class="nav nav-pills nav-sidebar flex-column sidebar-tools-menu sidebar-tools-menu-split {{ config('adminlte.classes_sidebar_nav', '') }}"
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
            @endif
        </nav>
    </div>

</aside>
<style>
    .main-sidebar .sidebar {
        height: 100%;
    }

    .main-sidebar .sidebar .os-content {
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .main-sidebar .sidebar .os-viewport,
    .main-sidebar .sidebar .os-content {
        height: 100% !important;
    }

    .main-sidebar .sidebar .os-content > nav {
        flex: 1 1 auto;
    }

    .main-sidebar .sidebar > nav {
        display: flex;
        flex-direction: column;
        height: 100%;
        min-height: 0;
        overflow: hidden;
    }

    .main-sidebar .sidebar-main-menu-split {
        flex: 0 0 auto;
        height: 80% !important;
        min-height: 0;
        overflow-y: auto;
    }

    .main-sidebar .sidebar-tools-menu-split {
        flex: 0 0 auto;
        height: 20% !important;
        min-height: 0;
        overflow-y: auto;
        margin-top: 0.65rem;
        padding-top: 0.85rem;
        border-top: 2px solid rgba(255, 255, 255, 0.18);
    }

    .main-sidebar .sidebar-main-menu-split + .sidebar-tools-menu-split {
        margin-top: auto;
    }
</style>
