@extends('adminlte::page')

@php
    $editing = isset($item);
    $item = $item ?? null;
    $action = $editing ? route('herramientas.planta.update', $item) : route('herramientas.store');
@endphp

@section('title', $editing ? 'Editar herramienta' : 'Nueva herramienta')

@section('content_header')
<div class="plant-form-head">
    <div>
        <small>Gestión de activos / Herramientas de planta</small>
        <h1>{{ $editing ? 'Editar ficha de herramienta' : 'Nueva herramienta de planta' }}</h1>
        <p>Registra la información operativa y de mantenimiento del equipo.</p>
    </div>
    <a href="{{ route('herramientas.index') }}" class="btn btn-light"><i class="fas fa-arrow-left mr-1"></i>Volver al catálogo</a>
</div>
@stop

@section('content')
@include('herramientas.partials.form-ux')

<style>
    .plant-form-head { display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:18px; }
    .plant-form-head small { font-size:10px; text-transform:uppercase; letter-spacing:.12em; font-weight:800; color:#71808c; }
    .plant-form-head h1 { font-size:29px; font-weight:800; margin:5px 0; color:#191c1e; }
    .plant-form-head p { font-size:13px; color:#687781; margin:0; }
    .plant-form { border:0; box-shadow:0 8px 32px rgba(25,28,30,0.04); }
    .plant-form .card-header { background:#002442; color:#fff; padding:14px 18px; }
    .plant-form .card-title { font-size:13px; font-weight:800; }
    .plant-form .card-body { padding:22px; }
    .plant-form label { font-size:10px; color:#586873; letter-spacing:.08em; text-transform:uppercase; font-weight:800; }
    .plant-form .form-control { height:38px; background:#eef2f5; border:0; border-radius:4px; font-size:12px; }
    .plant-form textarea.form-control { height:auto; }
    .plant-form .form-control:focus { background:#fff; box-shadow:0 0 0 2px rgba(23,97,154,0.3); }
    .plant-section { font-size:12px; font-weight:800; color:#002442; border-bottom:2px solid #e9eef1; padding-bottom:8px; margin:10px 0 16px; }
    .plant-form-footer { background:#f7f9fb; border:0; padding:15px 22px; }
    .plant-submit { background:#002442; color:#fff; border:0; border-radius:5px; padding:10px 18px; font-weight:800; font-size:12px; transition: all 0.2s; }
    .plant-submit:hover { background:#155785; }
    
    @media(max-width:700px) { .plant-form-head { align-items:flex-start; flex-direction:column; gap:12px; } }
</style>

<div class="card plant-form asset-form-ux">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-tools mr-2"></i>Ficha operativa de maquinaria</h3>
    </div>
    
    <form class="asset-smart-form" method="POST" action="{{ $action }}" enctype="multipart/form-data">
        @csrf 
        @if($editing) @method('PUT') @endif
        
        <!-- CAMPOS OCULTOS PARA MANTENER LA INTEGRIDAD DEL BACKEND -->
        <input type="hidden" name="stock" value="1">
        <input type="hidden" name="unidad_medida" value="ud">
        <input type="hidden" name="criticidad" value="Media">
        
        <div class="card-body">
            @if($errors->any())
                <div class="alert alert-danger" style="background:#fee1df; color:#c92328; border:0; font-size: 13px; font-weight: 700;">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                    </ul>
                </div>
            @endif

            <!-- PASO 1: IDENTIFICACIÓN -->
            <div class="plant-section">Identificación y clasificación</div>
            <div class="row">
                <div class="col-md-3 form-group">
                    <label>ID Interno *</label>
                    <input required name="id_interno" class="form-control" placeholder="Ej. HER-001" value="{{ old('id_interno', $item->id_interno ?? '') }}">
                </div>
                <div class="col-md-6 form-group">
                    <label>Nombre de la herramienta *</label>
                    <input required name="nombre" class="form-control" placeholder="Ej. Taladro Percutor Bosch" value="{{ old('nombre', $item->nombre ?? '') }}">
                </div>
                <div class="col-md-3 form-group">
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
                    <label>Número de serie</label>
                    <input name="numero_serie" class="form-control" value="{{ old('numero_serie', $item->numero_serie ?? '') }}">
                </div>
                <div class="col-md-4 form-group">
                    <label>Marca</label>
                    <input name="marca" class="form-control" value="{{ old('marca', $item->marca ?? '') }}">
                </div>
                <div class="col-md-4 form-group">
                    <label>Modelo</label>
                    <input name="modelo" class="form-control" value="{{ old('modelo', $item->modelo ?? '') }}">
                </div>
            </div>

            <!-- PASO 2: ESPECIFICACIONES TÉCNICAS -->
            <div class="plant-section">Especificaciones técnicas (Opcional)</div>
            <div class="row">
                <div class="col-md-3 form-group">
                    <label>Potencia</label>
                    <input name="potencia" class="form-control" placeholder="Ej. 1800 W" value="{{ old('potencia', $item->potencia ?? '') }}">
                </div>
                <div class="col-md-3 form-group">
                    <label>Tensión / Presión</label>
                    <input name="tension" class="form-control" placeholder="Ej. 18 V / 8 bar" value="{{ old('tension', $item->tension ?? '') }}">
                </div>
                <div class="col-md-3 form-group">
                    <label>Combustible / Fuente</label>
                    <input name="combustible" class="form-control" placeholder="Ej. Diésel, Batería..." value="{{ old('combustible', $item->combustible ?? '') }}">
                </div>
                <div class="col-md-3 form-group">
                    <label>Horas de uso registradas</label>
                    <input type="number" min="0" name="horas_uso" class="form-control" value="{{ old('horas_uso', $item->horas_uso ?? '') }}">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Proveedor habitual</label>
                    <input name="proveedor" class="form-control" value="{{ old('proveedor', $item->proveedor ?? '') }}">
                </div>
                <div class="col-md-6 form-group">
                    <label>Fotografía de la herramienta</label>
                    <input type="file" name="imagen" accept="image/*" class="form-control" style="padding: 6px;">
                </div>
            </div>

            <!-- PASO 3: CUSTODIA Y MANTENIMIENTO -->
            <div class="plant-section">Custodia y mantenimiento</div>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Responsable / Operario asignado</label>
                    <input name="responsable" class="form-control" placeholder="Nombre del trabajador o cuadrilla" value="{{ old('responsable', $item->responsable ?? '') }}">
                </div>
                <div class="col-md-6 form-group">
                    <label>Ubicación / Obra</label>
                    <input name="ubicacion" class="form-control" placeholder="Ej. Nave Sur, Obra Madrid" value="{{ old('ubicacion', $item->ubicacion ?? '') }}">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3 form-group">
                    <label>Fecha de registro *</label>
                    <input required type="date" name="fecha_registro" class="form-control" value="{{ old('fecha_registro', optional($item->fecha_registro ?? null)->format('Y-m-d') ?: now()->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3 form-group">
                    <label>Fin de Garantía</label>
                    <input type="date" name="garantia_hasta" class="form-control" value="{{ old('garantia_hasta', optional($item->garantia_hasta ?? null)->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3 form-group">
                    <label>Próx. Mantenimiento (Taller)</label>
                    <input type="date" name="fecha_mantenimiento" class="form-control" value="{{ old('fecha_mantenimiento', optional($item->fecha_mantenimiento ?? null)->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3 form-group">
                    <label>Inspección Legal (OCA)</label>
                    <input type="date" name="fecha_inspeccion" class="form-control" value="{{ old('fecha_inspeccion', optional($item->fecha_inspeccion ?? null)->format('Y-m-d')) }}" style="background: #fff4e4; border: 1px dashed #dca54a;">
                </div>
            </div>
            
            <div class="form-group">
                <label>Observaciones / Condición general</label>
                <!-- Hemos fusionado Condición Física y Descripción en un solo campo amplio -->
                <textarea name="descripcion" rows="4" class="form-control" placeholder="Anota el estado general, accesorios incluidos, daños visibles o particularidades de la herramienta...">{{ old('descripcion', $item->descripcion ?? '') }}{{ old('condicion_fisica', $item->condicion_fisica ?? '') ? "\nCondición: " . old('condicion_fisica', $item->condicion_fisica) : '' }}</textarea>
            </div>

            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="custom-control custom-checkbox mb-2">
                        <input type="hidden" name="bloqueado" value="0">
                        <input type="checkbox" name="bloqueado" value="1" class="custom-control-input" id="bloqueado" @checked(old('bloqueado', $item->bloqueado ?? false))>
                        <label class="custom-control-label" for="bloqueado" style="font-size: 12px; color: #a12328; font-weight: 800;">
                            <i class="fas fa-lock mr-1"></i> Bloquear herramienta en almacén (Prohíbe nuevas asignaciones a obra hasta su revisión)
                        </label>
                    </div>
                    <input name="motivo_bloqueo" class="form-control" placeholder="Si marcas el bloqueo, indica aquí el motivo (Ej. Cable pelado, Fallo de motor)..." value="{{ old('motivo_bloqueo', $item->motivo_bloqueo ?? '') }}" style="background: #fff4e4; border: 1px dashed #dca54a;">
                </div>
            </div>
            
        </div>
        
        <div class="card-footer plant-form-footer text-right">
            <a href="{{ route('herramientas.index') }}" class="btn btn-light mr-2">Cancelar</a>
            <button class="plant-submit"><i class="fas fa-save mr-1"></i>Guardar herramienta</button>
        </div>
    </form>
</div>
@stop