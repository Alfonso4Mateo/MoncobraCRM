<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MoncobraCRM')</title>

    <link rel="stylesheet" href="{{ asset('node_modules/admin-lte/dist/css/adminlte.min.css') }}">
    <link rel="stylesheet" href="{{ asset('node_modules/@fortawesome/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('node_modules/icheck-bootstrap/icheck-bootstrap.min.css') }}">

    @yield('styles')

    <style>
    /* === CONTROL TOTAL DE VISIBILIDAD DE MENÚS === */
    
    /* Mostrar submenús cuando el padre tiene la clase 'menu-open' */
    .nav.nav-pills .nav-item.menu-open > .nav-treeview {
        display: block !important;
        height: auto !important;
        visibility: visible !important;
        opacity: 1 !important;
        max-height: 9999px !important;
    }

    /* Ocultar submenús si NO tienen la clase 'menu-open' */
    .nav.nav-pills .nav-item:not(.menu-open) > .nav-treeview {
        display: none !important;
        height: 0 !important;
        visibility: hidden !important;
        opacity: 0 !important;
        max-height: 0 !important;
    }

    /* Remover transiciones de AdminLTE que causan problemas */
    .nav.nav-pills .nav-treeview {
        transition: none !important;
        overflow: visible !important;
    }

    /* Espaciado para indentación visual */
    .nav.nav-pills .nav-treeview {
        padding-left: 15px;
    }

    /* Forzar que los enlaces del menú no sean draggables ni tengan comportamiento raro */
    .nav.nav-pills .nav-item > a[href="#"] {
        cursor: pointer;
        user-select: none;
    }

    /* Sobreescribir estilos de AdminLTE */
    .nav.nav-pills .nav-item {
        position: relative !important;
    }
</style>

    <!-- Bloquear el Treeview de AdminLTE ANTES de que cargue -->
    <script>
    // Configuración: El accordion está deshabilitado en node_modules/admin-lte/src/ts/treeview.ts
    // Esto permite que múltiples menús estén abiertos simultáneamente
    console.log('[MenuFix] Sistema de menús configurado para modo no-accordion');
    </script>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                </li>
            </ul>

            <ul class="navbar-nav ml-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link" data-toggle="dropdown" href="#">
                        <i class="fas fa-user-circle"></i>
                        <span>{{ Auth::user()->name }}</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a href="{{ route('profile.show') }}" class="dropdown-item">
                            <i class="fas fa-user mr-2"></i> Mi Perfil
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="{{ route('logout') }}" class="dropdown-item"
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="fas fa-sign-out-alt mr-2"></i> Cerrar Sesión
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    </div>
                </li>
            </ul>
        </nav>

        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <a href="{{ route('dashboard') }}" class="brand-link">
                <span class="brand-text font-weight-light">Moncobra<b>CRM</b></span>
            </a>

            <nav class="mt-2" data-lte-toggle="treeview" data-accordion="false">
                <ul class="nav nav-pills mi-sidebar-personal flex-column" role="menu">
                    
                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-th-large"></i>
                            <p>Panel de Control</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('inventario.index') }}" class="nav-link {{ request()->routeIs('inventario.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-boxes-stacked"></i>
                            <p>Inventario</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('albaranes.index') }}" class="nav-link {{ request()->routeIs('albaranes.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-file-lines"></i>
                            <p>Documentos</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*', 'profile.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-users-gear"></i>
                            <p>Personal</p>
                        </a>
                    </li>

                    <li class="nav-item {{ request()->is('clientes*', 'albaranes*', 'presupuestos*', 'bolsa*') ? 'menu-open' : '' }}">
                        <a href="#" data-lte-toggle="treeview" class="nav-link {{ request()->is('clientes*', 'albaranes*', 'presupuestos*', 'bolsa*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-users"></i>
                            <p>
                                Área Clientes
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route('clientes.index') }}" class="nav-link {{ request()->routeIs('clientes.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i> <p>Gestión Clientes</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('albaranes.index') }}" class="nav-link {{ request()->routeIs('albaranes.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i> <p>Albaranes Clientes</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li class="nav-item {{ request()->is('proveedores*', 'albaranes-proveedores*', 'pedidos*') ? 'menu-open' : '' }}">
                        <a href="#" data-lte-toggle="treeview" class="nav-link {{ request()->is('proveedores*', 'albaranes-proveedores*', 'pedidos*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-building"></i>
                            <p>
                                Área Proveedores
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route('proveedores.index') }}" class="nav-link {{ request()->routeIs('proveedores.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i> <p>Gestión de Proveedores</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('productos.index') }}" class="nav-link {{ request()->routeIs('productos.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-boxes"></i> <p>Productos</p>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <div class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <h1 class="m-0">@yield('header-title')</h1>
                </div>
            </div>

            <div class="content">
                <div class="container-fluid">
                    @yield('content')
                </div>
            </div>
        </div>

        <footer class="main-footer">
            <strong>MoncobraCRM &copy; 2026</strong>
        </footer>
    </div>

    <script src="{{ asset('node_modules/jquery/dist/jquery.min.js') }}"></script>
    <script src="{{ asset('node_modules/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    
    <!-- LOG INMEDIATO -->
    <script>
    console.log('[MenuFix] Script iniciado ANTES de AdminLTE');
    </script>
    
    <script src="{{ asset('node_modules/admin-lte/dist/js/adminlte.min.js') }}"></script>

    @yield('scripts')

    <!-- LOG Y FIX DESPUÉS DE ADMINLTE -->
    <script>
    console.log('[MenuFix] Script ejecutado DESPUÉS de AdminLTE');
    
    // Intentar inmediatamente
    (function() {
        console.log('[MenuFix] IIFE iniciado');
        
        // Buscar botones
        var buttons = document.querySelectorAll('[data-lte-toggle="treeview"]');
        console.log('[MenuFix] Botones encontrados:', buttons.length);
        
        if (buttons.length === 0) {
            console.log('[MenuFix] ❌ NO SE ENCONTRARON BOTONES CON data-lte-toggle="treeview"');
            return;
        }
        
        // Procesar cada botón
        buttons.forEach(function(btn, index) {
            console.log('[MenuFix] Procesando botón', index);
            
            // Remover listeners antiguos
            var clone = btn.cloneNode(true);
            btn.parentNode.replaceChild(clone, btn);
            
            // Agregar nuevo listener
            clone.addEventListener('click', function(e) {
                console.log('[MenuFix] ✓ CLICK DETECTADO');
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                var navItem = this.closest('.nav-item');
                if (!navItem) {
                    console.log('[MenuFix] ❌ Nav-item no encontrado');
                    return;
                }
                
                // Toggle
                navItem.classList.toggle('menu-open');
                console.log('[MenuFix] TOGGLE realizado, ahora:', navItem.classList.contains('menu-open'));
                
                return false;
            });
        });
        
        console.log('[MenuFix] ✓ Sistema completamente inicializado');
    })();
    </script>
</body>
</html>