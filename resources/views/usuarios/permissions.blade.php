@extends('adminlte::page')

@section('title', 'Gestión de Permisos')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0">Gestión de Permisos: {{ $user->name }}</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Panel de Usuarios</a></li>
                <li class="breadcrumb-item active">Permisos</li>
            </ol>
        </div>
    </div>
@endsection

@section('content')
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-key"></i> Asignación de Accesos y Funciones</h3>
        </div>

        <form action="{{ route('users.permissions.update', $user->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card-body">
                @if($user->role === 'superadmin')
                    <div class="alert alert-danger">
                        <i class="fas fa-crown"></i> Este usuario es <strong>Super Admin</strong> y posee acceso total automático a todas las funciones del sistema.
                    </div>
                @endif

                <p class="text-muted">
                    Utiliza el interruptor principal de cada módulo para dar acceso general. Una vez habilitado, podrás afinar los permisos específicos.
                </p>

                <div class="row mt-4">
                    
                    {{-- 1. Submódulo: Gestión de Clientes --}}
                    <div class="col-md-6 mb-4">
                        <div class="p-3 bg-light rounded border h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                                <h5 class="font-weight-bold m-0" style="color: #6f42c1;"><i class="fas fa-building"></i> Clientes</h5>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" name="permissions[]" value="clientes.view" class="custom-control-input master-switch" id="master_clientes" data-module="clientes" @checked($user->hasPermissionTo('clientes.view'))>
                                    <label class="custom-control-label font-weight-bold" style="cursor:pointer;" for="master_clientes">Habilitar Módulo</label>
                                </div>
                            </div>
                            
                            <div class="sub-permissions" id="sub_clientes">
                                <p class="text-muted text-sm mb-3" style="font-size: 0.85rem;">Permite ver el directorio y listado de clientes.</p>

                                <h6 class="text-muted text-uppercase text-sm font-weight-bold mt-3">Operativa</h6>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" name="permissions[]" value="clientes.manage" class="custom-control-input child-clientes" id="cli_manage" @checked($user->hasPermissionTo('clientes.manage'))>
                                    <label class="custom-control-label font-weight-normal" for="cli_manage">Añadir y editar clientes</label>
                                </div>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" name="permissions[]" value="clientes.historial" class="custom-control-input child-clientes" id="cli_historial" @checked($user->hasPermissionTo('clientes.historial'))>
                                    <label class="custom-control-label font-weight-normal" for="cli_historial">Ver historial completo del cliente</label>
                                </div>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" name="permissions[]" value="clientes.export" class="custom-control-input child-clientes" id="cli_export" @checked($user->hasPermissionTo('clientes.export'))>
                                    <label class="custom-control-label font-weight-normal" for="cli_export">Exportar datos y descargar PDFs del historial</label>
                                </div>

                                <h6 class="text-danger text-uppercase text-sm font-weight-bold mt-3">Peligro</h6>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" name="permissions[]" value="clientes.delete" class="custom-control-input child-clientes" id="cli_delete" @checked($user->hasPermissionTo('clientes.delete'))>
                                    <label class="custom-control-label font-weight-normal text-danger" for="cli_delete">Eliminar clientes permanentemente</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 2. Submódulo: Presupuestos --}}
                    <div class="col-md-6 mb-4">
                        <div class="p-3 bg-light rounded border h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                                <h5 class="font-weight-bold m-0" style="color: #0284c7;"><i class="fas fa-file-invoice-dollar"></i> Presupuestos</h5>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" name="permissions[]" value="presupuestos.view" class="custom-control-input master-switch" id="master_presupuestos" data-module="presupuestos" @checked($user->hasPermissionTo('presupuestos.view'))>
                                    <label class="custom-control-label font-weight-bold" style="cursor:pointer;" for="master_presupuestos">Habilitar Módulo</label>
                                </div>
                            </div>
                            
                            <div class="sub-permissions" id="sub_presupuestos">
                                <p class="text-muted text-sm mb-3" style="font-size: 0.85rem;">Permite ver el listado de presupuestos emitidos.</p>

                                <h6 class="text-muted text-uppercase text-sm font-weight-bold mt-3">Operativa</h6>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" name="permissions[]" value="presupuestos.manage" class="custom-control-input child-presupuestos" id="pres_manage" @checked($user->hasPermissionTo('presupuestos.manage'))>
                                    <label class="custom-control-label font-weight-normal" for="pres_manage">Crear y editar presupuestos</label>
                                </div>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" name="permissions[]" value="presupuestos.download" class="custom-control-input child-presupuestos" id="pres_download" @checked($user->hasPermissionTo('presupuestos.download'))>
                                    <label class="custom-control-label font-weight-normal" for="pres_download">Generar y descargar presupuestos en PDF</label>
                                </div>

                                <h6 class="text-danger text-uppercase text-sm font-weight-bold mt-3">Peligro</h6>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" name="permissions[]" value="presupuestos.delete" class="custom-control-input child-presupuestos" id="pres_delete" @checked($user->hasPermissionTo('presupuestos.delete'))>
                                    <label class="custom-control-label font-weight-normal text-danger" for="pres_delete">Eliminar presupuestos permanentemente</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 3. Submódulo: Pedidos Clientes --}}
                    <div class="col-md-6 mb-4">
                        <div class="p-3 bg-light rounded border h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                                <h5 class="font-weight-bold m-0" style="color: #059669;"><i class="fas fa-briefcase"></i> Pedidos Clientes</h5>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" name="permissions[]" value="pedidos.view" class="custom-control-input master-switch" id="master_pedidos" data-module="pedidos" @checked($user->hasPermissionTo('pedidos.view'))>
                                    <label class="custom-control-label font-weight-bold" style="cursor:pointer;" for="master_pedidos">Habilitar Módulo</label>
                                </div>
                            </div>
                            
                            <div class="sub-permissions" id="sub_pedidos">
                                <p class="text-muted text-sm mb-3" style="font-size: 0.85rem;">Permite ver el listado y estado de los pedidos.</p>

                                <h6 class="text-muted text-uppercase text-sm font-weight-bold mt-3">Operativa</h6>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" name="permissions[]" value="pedidos.manage" class="custom-control-input child-pedidos" id="ped_manage" @checked($user->hasPermissionTo('pedidos.manage'))>
                                    <label class="custom-control-label font-weight-normal" for="ped_manage">Crear y gestionar pedidos</label>
                                </div>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" name="permissions[]" value="pedidos.download" class="custom-control-input child-pedidos" id="ped_download" @checked($user->hasPermissionTo('pedidos.download'))>
                                    <label class="custom-control-label font-weight-normal" for="ped_download">Generar y descargar pedidos en PDF</label>
                                </div>

                                <h6 class="text-danger text-uppercase text-sm font-weight-bold mt-3">Peligro</h6>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" name="permissions[]" value="pedidos.delete" class="custom-control-input child-pedidos" id="ped_delete" @checked($user->hasPermissionTo('pedidos.delete'))>
                                    <label class="custom-control-label font-weight-normal text-danger" for="ped_delete">Eliminar pedidos permanentemente</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 4. Submódulo: Albaranes Clientes --}}
                    <div class="col-md-6 mb-4">
                        <div class="p-3 bg-light rounded border h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                                <h5 class="font-weight-bold m-0" style="color: #d97706;"><i class="fas fa-file-alt"></i> Albaranes Clientes</h5>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" name="permissions[]" value="albaranes.view" class="custom-control-input master-switch" id="master_albaranes" data-module="albaranes" @checked($user->hasPermissionTo('albaranes.view'))>
                                    <label class="custom-control-label font-weight-bold" style="cursor:pointer;" for="master_albaranes">Habilitar Módulo</label>
                                </div>
                            </div>
                            
                            <div class="sub-permissions" id="sub_albaranes">
                                <p class="text-muted text-sm mb-3" style="font-size: 0.85rem;">Permite ver el listado de albaranes emitidos.</p>

                                <h6 class="text-muted text-uppercase text-sm font-weight-bold mt-3">Operativa</h6>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" name="permissions[]" value="albaranes.manage" class="custom-control-input child-albaranes" id="alb_manage" @checked($user->hasPermissionTo('albaranes.manage'))>
                                    <label class="custom-control-label font-weight-normal" for="alb_manage">Crear y editar albaranes</label>
                                </div>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" name="permissions[]" value="albaranes.download" class="custom-control-input child-albaranes" id="alb_download" @checked($user->hasPermissionTo('albaranes.download'))>
                                    <label class="custom-control-label font-weight-normal" for="alb_download">Generar y descargar albaranes en PDF</label>
                                </div>

                                <h6 class="text-danger text-uppercase text-sm font-weight-bold mt-3">Peligro</h6>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" name="permissions[]" value="albaranes.delete" class="custom-control-input child-albaranes" id="alb_delete" @checked($user->hasPermissionTo('albaranes.delete'))>
                                    <label class="custom-control-label font-weight-normal text-danger" for="alb_delete">Eliminar albaranes permanentemente</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Módulo Documentos --}}
                    <div class="col-md-6 mb-4">
                        <div class="p-3 bg-light rounded border h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                                <h5 class="font-weight-bold m-0" style="color: #475569;"><i class="fas fa-file-alt"></i> Documentos</h5>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" name="permissions[]" value="documentos.view" class="custom-control-input master-switch" id="master_documentos" data-module="documentos" @checked($user->hasPermissionTo('documentos.view'))>
                                    <label class="custom-control-label font-weight-bold" style="cursor:pointer;" for="master_documentos">Habilitar Módulo</label>
                                </div>
                            </div>

                            <div class="sub-permissions" id="sub_documentos">
                                
                                <h6 class="text-muted text-uppercase text-sm font-weight-bold mt-3">Operativa</h6>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" name="permissions[]" value="documentos.create" class="custom-control-input child-documentos" id="doc_create" @checked($user->hasPermissionTo('documentos.create'))>
                                    <label class="custom-control-label font-weight-normal" for="doc_create">Cargar archivos y generar nuevos documentos</label>
                                </div>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" name="permissions[]" value="documentos.edit" class="custom-control-input child-documentos" id="doc_edit" @checked($user->hasPermissionTo('documentos.edit'))>
                                    <label class="custom-control-label font-weight-normal" for="doc_edit">Editar metadata y líneas de facturación</label>
                                </div>

                                <h6 class="text-info text-uppercase text-sm font-weight-bold mt-3">Accesos Especiales</h6>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" name="permissions[]" value="documentos.download" class="custom-control-input child-documentos" id="doc_download" @checked($user->hasPermissionTo('documentos.download'))>
                                    <label class="custom-control-label font-weight-normal text-info" for="doc_download">Visualizar/Descargar los PDF y archivos físicos</label>
                                </div>

                                <h6 class="text-danger text-uppercase text-sm font-weight-bold mt-3">Peligro</h6>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" name="permissions[]" value="documentos.delete" class="custom-control-input child-documentos" id="doc_delete" @checked($user->hasPermissionTo('documentos.delete'))>
                                    <label class="custom-control-label font-weight-normal text-danger" for="doc_delete">Eliminar documentos permanentemente</label>
                                </div>
                                
                            </div>
                        </div>
                    </div>

                    {{-- Módulo Inventario --}}
                    <div class="col-md-6 mb-4">
                        <div class="p-3 bg-light rounded border h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                                <h5 class="font-weight-bold text-warning m-0"><i class="fas fa-boxes"></i> Inventario</h5>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" name="permissions[]" value="inventario.view" class="custom-control-input master-switch" id="master_inventario" data-module="inventario" @checked($user->hasPermissionTo('inventario.view'))>
                                    <label class="custom-control-label font-weight-bold" style="cursor:pointer;" for="master_inventario">Habilitar Módulo</label>
                                </div>
                            </div>

                            <div class="sub-permissions" id="sub_inventario">
                                <h6 class="text-muted text-uppercase text-sm font-weight-bold mt-3">Operativa</h6>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" name="permissions[]" value="inventario.movimientos" class="custom-control-input child-inventario" id="i_movimientos" @checked($user->hasPermissionTo('inventario.movimientos'))>
                                    <label class="custom-control-label font-weight-normal" for="i_movimientos">Registrar Entradas, Salidas (Vales) y Traslados</label>
                                </div>

                                <h6 class="text-danger text-uppercase text-sm font-weight-bold mt-3">Configuración</h6>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" name="permissions[]" value="inventario.admin" class="custom-control-input child-inventario" id="i_admin" @checked($user->hasPermissionTo('inventario.admin'))>
                                    <label class="custom-control-label font-weight-normal" for="i_admin">Crear/Editar Almacenes, Categorías y Cancelar históricos</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Módulo Personal (RRHH) --}}
                    <div class="col-md-6 mb-4">
                        <div class="p-3 bg-light rounded border h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                                <h5 class="font-weight-bold text-primary m-0"><i class="fas fa-users"></i> Personal (RRHH)</h5>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" name="permissions[]" value="personal.view" class="custom-control-input master-switch" id="master_personal" data-module="personal" @checked($user->hasPermissionTo('personal.view'))>
                                    <label class="custom-control-label font-weight-bold" style="cursor:pointer;" for="master_personal">Habilitar Módulo</label>
                                </div>
                            </div>
                            
                            <div class="sub-permissions" id="sub_personal">
                                <p class="text-muted text-sm mb-3" style="font-size: 0.85rem;">La habilitación del módulo permite <strong>ver los trabajadores registrados</strong>.</p>
                                
                                <h6 class="text-muted text-uppercase text-sm font-weight-bold mt-3">Operativa</h6>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" name="permissions[]" value="personal.create" class="custom-control-input child-personal" id="p_create" @checked($user->hasPermissionTo('personal.create'))>
                                    <label class="custom-control-label font-weight-normal" for="p_create">Permitir añadir trabajadores</label>
                                </div>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" name="permissions[]" value="personal.edit" class="custom-control-input child-personal" id="p_edit" @checked($user->hasPermissionTo('personal.edit'))>
                                    <label class="custom-control-label font-weight-normal" for="p_edit">Editar perfil y datos generales</label>
                                </div>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" name="permissions[]" value="personal.acciones" class="custom-control-input child-personal" id="p_acciones" @checked($user->hasPermissionTo('personal.acciones'))>
                                    <label class="custom-control-label font-weight-normal" for="p_acciones">Ver y gestionar acciones del trabajador</label>
                                </div>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" name="permissions[]" value="personal.bulk" class="custom-control-input child-personal" id="p_bulk" @checked($user->hasPermissionTo('personal.bulk'))>
                                    <label class="custom-control-label font-weight-normal" for="p_bulk">Realizar acciones de edición masiva (Bulk)</label>
                                </div>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" name="permissions[]" value="personal.export" class="custom-control-input child-personal" id="p_export" @checked($user->hasPermissionTo('personal.export'))>
                                    <label class="custom-control-label font-weight-normal" for="p_export">Exportar listados de datos (CSV / PDF)</label>
                                </div>

                                <h6 class="text-info text-uppercase text-sm font-weight-bold mt-3">Datos Sensibles (LOPD)</h6>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" name="permissions[]" value="personal.tallas" class="custom-control-input child-personal" id="p_tallas" @checked($user->hasPermissionTo('personal.tallas'))>
                                    <label class="custom-control-label font-weight-normal" for="p_tallas">Acceder a gestión de tallas y EPIs</label>
                                </div>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" name="permissions[]" value="personal.medico" class="custom-control-input child-personal" id="p_medico" @checked($user->hasPermissionTo('personal.medico'))>
                                    <label class="custom-control-label font-weight-normal text-info" for="p_medico" style="font-weight: 700 !important;">Gestionar reconocimientos médicos</label>
                                </div>

                                <h6 class="text-danger text-uppercase text-sm font-weight-bold mt-3">Peligro</h6>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" name="permissions[]" value="personal.delete" class="custom-control-input child-personal" id="p_delete" @checked($user->hasPermissionTo('personal.delete'))>
                                    <label class="custom-control-label font-weight-normal text-danger" for="p_delete">Eliminar datos del módulo permanentemente</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Módulo Cursos (PRL) --}}
                    <div class="col-md-6 mb-4">
                        <div class="p-3 bg-light rounded border h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                                <h5 class="font-weight-bold text-success m-0"><i class="fas fa-graduation-cap"></i> Cursos (PRL)</h5>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" name="permissions[]" value="cursos.view" class="custom-control-input master-switch" id="master_cursos" data-module="cursos" @checked($user->hasPermissionTo('cursos.view'))>
                                    <label class="custom-control-label font-weight-bold" style="cursor:pointer;" for="master_cursos">Habilitar Módulo</label>
                                </div>
                            </div>
                            
                            <div class="sub-permissions" id="sub_cursos">
                                <h6 class="text-muted text-uppercase text-sm font-weight-bold mt-3">Visualización</h6>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" name="permissions[]" value="cursos.plantilla" class="custom-control-input child-cursos" id="c_plantilla" @checked($user->hasPermissionTo('cursos.plantilla'))>
                                    <label class="custom-control-label font-weight-normal" for="c_plantilla">Directorio de plantilla (Ver tarjetas de trabajadores)</label>
                                </div>

                                <h6 class="text-muted text-uppercase text-sm font-weight-bold mt-3">Operativa</h6>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" name="permissions[]" value="cursos.create" class="custom-control-input child-cursos" id="c_create" @checked($user->hasPermissionTo('cursos.create'))>
                                    <label class="custom-control-label font-weight-normal" for="c_create">Crear nuevos cursos</label>
                                </div>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" name="permissions[]" value="cursos.edit" class="custom-control-input child-cursos" id="c_edit" @checked($user->hasPermissionTo('cursos.edit'))>
                                    <label class="custom-control-label font-weight-normal" for="c_edit">Gestionar/Editar cursos existentes</label>
                                </div>
                                 <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" name="permissions[]" value="cursos.normas" class="custom-control-input child-cursos" id="c_normas" @checked($user->hasPermissionTo('cursos.normas'))>
                                    <label class="custom-control-label font-weight-normal" for="c_normas">Ver Panel de Normas</label>
                                </div>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" name="permissions[]" value="cursos.alertas" class="custom-control-input child-cursos" id="c_alertas" @checked($user->hasPermissionTo('cursos.alertas'))>
                                    <label class="custom-control-label font-weight-normal" for="c_alertas">Configurar alertas de caducidad</label>
                                </div>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" name="permissions[]" value="cursos.export" class="custom-control-input child-cursos" id="c_export" @checked($user->hasPermissionTo('cursos.export'))>
                                    <label class="custom-control-label font-weight-normal" for="c_export">Permitir exportar datos (Excel/CSV)</label>
                                </div>

                                <h6 class="text-danger text-uppercase text-sm font-weight-bold mt-3">Peligro</h6>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" name="permissions[]" value="cursos.delete" class="custom-control-input child-cursos" id="c_delete" @checked($user->hasPermissionTo('cursos.delete'))>
                                    <label class="custom-control-label font-weight-normal text-danger" for="c_delete">Eliminar datos del módulo permanentemente</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Panel de usuario --}}
                    <div class="col-md-6 mb-4">
                        <div class="p-3 bg-light rounded border h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                                <h5 class="font-weight-bold text-info m-0"><i class="fas fa-user-shield"></i> Seguridad</h5>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" name="permissions[]" value="users.view" class="custom-control-input master-switch" id="master_users" data-module="users" @checked($user->hasPermissionTo('users.view'))>
                                    <label class="custom-control-label font-weight-bold" style="cursor:pointer;" for="master_users">Habilitar Módulo</label>
                                </div>
                            </div>
                            
                            <div class="sub-permissions" id="sub_users">
                                <h6 class="text-muted text-uppercase text-sm font-weight-bold mt-3">Operativa</h6>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" name="permissions[]" value="users.manage" class="custom-control-input child-users" id="u_manage" @checked($user->hasPermissionTo('users.manage'))>
                                    <label class="custom-control-label font-weight-normal" for="u_manage">Dar de alta, bloquear o resetear accesos</label>
                                </div>

                                <h6 class="text-danger text-uppercase text-sm font-weight-bold mt-3">Peligro</h6>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" name="permissions[]" value="users.permissions" class="custom-control-input child-users" id="u_permissions" @checked($user->hasPermissionTo('users.permissions'))>
                                    <label class="custom-control-label font-weight-normal text-danger" for="u_permissions">Modificar Matriz de Permisos (Checkboxes) de otros usuarios</label>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="card-footer bg-white">
                <a href="{{ route('users.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver al listado
                </a>
                <button type="submit" class="btn btn-primary float-right">
                    <i class="fas fa-save"></i> Guardar Permisos
                </button>
            </div>
        </form>
    </div>
@endsection

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const masterSwitches = document.querySelectorAll('.master-switch');

        function toggleModulePermissions(masterElement) {
            const moduleName = masterElement.getAttribute('data-module');
            const childCheckboxes = document.querySelectorAll(`.child-${moduleName}`);
            const container = document.getElementById(`sub_${moduleName}`);

            if (masterElement.checked) {
                // Si el maestro está activo, habilitamos los hijos
                childCheckboxes.forEach(checkbox => checkbox.disabled = false);
                container.style.opacity = '1';
                container.style.pointerEvents = 'auto';
            } else {
                // Si el maestro se apaga, desmarcamos y bloqueamos los hijos
                childCheckboxes.forEach(checkbox => {
                    checkbox.checked = false;
                    checkbox.disabled = true;
                });
                container.style.opacity = '0.5'; // Efecto visual de apagado
                container.style.pointerEvents = 'none'; // Evita clics accidentales
            }
        }

        // 1. Inicializar el estado al cargar la página (por si vienen datos guardados de BD)
        masterSwitches.forEach(sw => {
            toggleModulePermissions(sw);
            
            // 2. Escuchar los clicks del usuario
            sw.addEventListener('change', function() {
                toggleModulePermissions(this);
            });
        });
    });
</script>
@endsection