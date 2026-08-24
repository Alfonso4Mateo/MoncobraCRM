@extends('adminlte::page')

@section('title', 'Centro de Ayuda')

@section('content_header')
    <h1 class="m-0 text-dark">Centro de Ayuda y Documentación</h1>
@stop

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-book mr-1"></i> Documentación del ERP
                </h3>
            </div>
            <div class="card-body">
                <div class="callout callout-info">
                    <h5><i class="fas fa-info-circle"></i> Bienvenido al portal de ayuda</h5>
                    <p>
                        Aquí encontrarás guías paso a paso sobre el funcionamiento de cada módulo del sistema, 
                        resolución de incidencias comunes y manuales de usuario.
                    </p>
                </div>

                <div class="row mt-4">
                    <!-- Tarjeta de acceso rápido a la documentación externa/interna -->
                    <div class="col-md-6">
                        <div class="info-box bg-light">
                            <span class="info-box-icon bg-info elevation-1"><i class="fas fa-external-link-alt"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text font-weight-bold">Documentación General</span>
                                <span class="info-box-number text-muted font-weight-normal">
                                    Consulta la guía completa de uso del ERP.
                                </span>
                                <a href="{{ asset('manual_usuario/Manual de uso FactuMon.pdf') }}" target="_blank" class="btn btn-sm btn-outline-info mt-2">
                                    Abrir Documentación <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Espacio preparado para futuros submódulos -->
                    <div class="col-md-6">
                        <div class="info-box bg-light">
                            <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-tools"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text font-weight-bold">Módulos en Construcción</span>
                                <span class="info-box-number text-muted font-weight-normal">
                                    Próximamente: Guías específicas por módulo (Clientes, Inventario, Personal).
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@stop

@section('css')
    {{-- Estilos adicionales específicos para la sección de ayuda si fueran necesarios --}}
@stop

@section('js')
    {{-- Scripts adicionales si fueran necesarios --}}
@stop