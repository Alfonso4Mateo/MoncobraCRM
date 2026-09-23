@extends('adminlte::page')

@php
    $editing = isset($item);
    $item = $item ?? null;
    $action = $editing ? route('equipos.update', $item) : route('equipos.store');
@endphp

@section('title', $editing ? 'Editar equipo informático' : 'Registrar equipo informático')

@section('content_header')
<div class="it-form-heading">
    <div>
        <small>Gestión de activos / Equipos informáticos</small>
        <h1>{{ $editing ? 'Editar ficha de equipo' : 'Registrar equipo informático' }}</h1>
        <p>Completa la ficha técnica para mantener el inventario IT trazable.</p>
    </div>
    <a href="{{ route('equipos.index') }}" class="btn btn-light"><i class="fas fa-arrow-left mr-1"></i>Volver al parque IT</a>
</div>
@stop

@section('content')
@include('herramientas.partials.form-ux')

<style>
    .it-form-heading { display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:18px; }
    .it-form-heading small { font-size:10px; text-transform:uppercase; letter-spacing:.12em; font-weight:800; color:#71808c; }
    .it-form-heading h1 { font-size:28px; font-weight:800; margin:4px 0; color:#191c1e; }
    .it-form-heading p { margin:0; color:#687781; font-size:13px; }
    .it-form-card { border:0; box-shadow:0 8px 32px #191c1e0a; }
    .it-form-card .card-header { background:#002442; color:#fff; padding:14px 18px; }
    .it-form-card .card-title { font-size:13px; font-weight:800; }
    .it-form-card .card-body { padding:22px; }
    .it-form-card label { font-size:10px; color:#586873; letter-spacing:.08em; text-transform:uppercase; font-weight:800; }
    .it-form-card .form-control { border:0; background:#eef2f5; border-radius:4px; font-size:12px; height:37px; }
    .it-form-card textarea.form-control { height:auto; }
    .it-form-card .form-control:focus { box-shadow:0 0 0 2px #17619a44; background:#fff; }
    .it-section { font-size:12px; font-weight:800; color:#002442; border-bottom:2px solid #e9eef1; padding-bottom:8px; margin:10px 0 16px; }
    .it-upload { background:#eef2f5; text-align:center; padding:20px; color:#71808c; font-size:11px; border:1px dashed #b8c4cc; border-radius: 4px; }
    .it-form-footer { padding:15px 22px; background:#f7f9fb; border-top:0; }
    .it-primary { background:#002442; color:#fff; border:0; border-radius:5px; padding:9px 18px; font-weight:800; font-size:12px; transition: all 0.2s; }
    .it-primary:hover { background: #135381; }
    @media(max-width:700px){ .it-form-heading { align-items:flex-start; flex-direction:column; gap:12px; } }
</style>

<div class="card it-form-card asset-form-ux">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-desktop mr-2"></i>Ficha técnica del equipo</h3>
    </div>
    
    <form class="asset-smart-form" method="POST" action="{{ $action }}" enctype="multipart/form-data">
        @csrf 
        @if($editing) @method('PUT') @endif
        
        <div class="card-body">
            @if($errors->any())
                <div class="alert alert-danger" style="background:#fee1df; color:#c92b2b; border:0; font-size: 13px; font-weight: 700;">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                    </ul>
                </div>
            @endif

            <div class="it-section">Identificación y clasificación</div>
            <div class="row">
                <div class="col-md-5 form-group">
                    <label>Nombre del equipo *</label>
                    <input required name="nombre" class="form-control" placeholder="Ej. Dell Precision 5570 Intel vPro" value="{{ old('nombre', $item->nombre ?? '') }}">
                </div>
                <div class="col-md-2 form-group">
                    <label>Código Interno</label>
                    <input name="codigo" class="form-control" placeholder="Ej. IT-045" value="{{ old('codigo', $item->codigo ?? '') }}">
                </div>
                <div class="col-md-3 form-group">
                    <label>Tipo de equipo</label>
                    <select name="tipo_equipo" class="form-control">
                        <option value="">Seleccionar tipo</option>
                        @foreach(['Ordenador portátil','Ordenador sobremesa','Servidor','Monitor','Tablet','Móvil','Periférico','Otro'] as $tipoEquipo)
                            <option value="{{ $tipoEquipo }}" @selected(old('tipo_equipo', $item->tipo_equipo ?? '') === $tipoEquipo)>{{ $tipoEquipo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 form-group">
                    <label>Estado *</label>
                    <select required name="estado" class="form-control">
                        @foreach(['operativo','asignado','mantenimiento','reparacion'] as $estado)
                            <option value="{{ $estado }}" @selected(old('estado', $item->estado ?? 'operativo') === $estado)>{{ ucfirst($estado) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 form-group">
                    <label>Número de serie (S/N)</label>
                    <input name="numero_serie" class="form-control" value="{{ old('numero_serie', $item->numero_serie ?? '') }}">
                </div>
                <div class="col-md-4 form-group">
                    <label>Marca</label>
                    <input name="marca" class="form-control" placeholder="Ej. Dell, Lenovo..." value="{{ old('marca', $item->marca ?? '') }}">
                </div>
                <div class="col-md-4 form-group">
                    <label>Modelo exacto</label>
                    <input name="modelo" class="form-control" placeholder="Ej. ThinkPad T14 Gen 3" value="{{ old('modelo', $item->modelo ?? '') }}">
                </div>
            </div>

            <div class="it-section">Configuración de hardware y red (Opcional)</div>
            <div class="row">
                <div class="col-md-3 form-group">
                    <label>Procesador</label>
                    <input name="procesador" class="form-control" placeholder="Ej. i7-12800H" value="{{ old('procesador', $item->procesador ?? '') }}">
                </div>
                <div class="col-md-2 form-group">
                    <label>Memoria RAM</label>
                    <input name="memoria_ram" class="form-control" placeholder="Ej. 32 GB" value="{{ old('memoria_ram', $item->memoria_ram ?? '') }}">
                </div>
                <div class="col-md-2 form-group">
                    <label>Disco / Almacenamiento</label>
                    <input name="almacenamiento" class="form-control" placeholder="Ej. 1 TB NVMe" value="{{ old('almacenamiento', $item->almacenamiento ?? '') }}">
                </div>
                <div class="col-md-3 form-group">
                    <label>Sistema operativo</label>
                    <input name="sistema_operativo" class="form-control" placeholder="Ej. Windows 11 Pro" value="{{ old('sistema_operativo', $item->sistema_operativo ?? '') }}">
                </div>
                <div class="col-md-2 form-group">
                    <label>MAC Address</label>
                    <input name="mac_address" class="form-control" placeholder="00:1A:2B..." value="{{ old('mac_address', $item->mac_address ?? '') }}">
                </div>
            </div>

            <div class="it-section">Custodia, ubicación y ciclo de vida</div>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Persona asignada</label>
                    <input name="responsable" class="form-control" placeholder="Nombre del trabajador" value="{{ old('responsable', $item->responsable ?? '') }}">
                </div>
                <div class="col-md-6 form-group">
                    <label>Centro / Oficina</label>
                    <input name="ubicacion" class="form-control" placeholder="Ej. Sede Central, Teletrabajo..." value="{{ old('ubicacion', $item->ubicacion ?? '') }}">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3 form-group">
                    <label>Fecha adquisición</label>
                    <input type="date" name="fecha_adquisicion" class="form-control" value="{{ old('fecha_adquisicion', optional($item->fecha_adquisicion ?? null)->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3 form-group">
                    <label>Garantía de Hardware</label>
                    <input type="date" name="garantia_hasta" class="form-control" value="{{ old('garantia_hasta', optional($item->garantia_hasta ?? null)->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3 form-group">
                    <label>Renovación / Fin de ciclo</label>
                    <input type="date" name="soporte_hasta" class="form-control" value="{{ old('soporte_hasta', optional($item->soporte_hasta ?? null)->format('Y-m-d')) }}" style="background: #fff4e4; border: 1px dashed #dca54a;">
                </div>
                <div class="col-md-3 form-group">
                    <label>Próx. Mantenimiento IT</label>
                    <input type="date" name="fecha_mantenimiento" class="form-control" value="{{ old('fecha_mantenimiento', optional($item->fecha_mantenimiento ?? null)->format('Y-m-d')) }}">
                </div>
            </div>
            
            <div class="row mt-2">
                <div class="col-md-8 form-group">
                    <label>Observaciones e incidencias previas</label>
                    <textarea name="descripcion" rows="4" class="form-control" placeholder="Anota el estado general de la carcasa, daños en pantalla, si incluye cargador original...">{{ old('descripcion', $item->descripcion ?? '') }}</textarea>
                </div>
                <div class="col-md-4 form-group">
                    <label>Fotografía del equipo</label>
                    <div class="it-upload">
                        <i class="fas fa-camera fa-2x mb-2" style="color: #87949b;"></i>
                        <input type="file" name="imagen" accept="image/*" class="w-100">
                        @if($item && $item->imagen)
                            <small style="color: #0c9b77; font-weight: bold; margin-top: 5px; display: block;"><i class="fas fa-check"></i> Imagen guardada</small>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card-footer it-form-footer text-right">
            <a href="{{ route('equipos.index') }}" class="btn btn-light mr-2">Cancelar</a>
            <button class="it-primary"><i class="fas fa-save mr-1"></i>Guardar ficha IT</button>
        </div>
    </form>
</div>
@stop