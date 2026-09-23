@extends('adminlte::page')

@php
    $editing = isset($item);
    $item = $item ?? null;
    $action = $editing ? route('calibrables.update', $item) : route('calibrables.store');
@endphp

@section('title', $editing ? 'Editar aparato calibrable' : 'Registrar aparato calibrable')

@section('content_header')
<div class="met-form-head">
    <div>
        <small>Gestión de activos / Aparatos calibrables</small>
        <h1>{{ $editing ? 'Editar ficha metrológica' : 'Registrar aparato calibrable' }}</h1>
        <p>Documenta la trazabilidad, exactitud y ciclo de calibración del instrumento.</p>
    </div>
    <a href="{{ route('calibrables.index') }}" class="btn btn-light"><i class="fas fa-arrow-left mr-1"></i>Volver a metrología</a>
</div>
@stop

@section('content')
@include('herramientas.partials.form-ux')

<style>
    .met-form-head { display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:18px; }
    .met-form-head small { font-size:10px; text-transform:uppercase; letter-spacing:.12em; font-weight:800; color:#71808c; }
    .met-form-head h1 { font-size:29px; font-weight:800; margin:5px 0; color:#191c1e; }
    .met-form-head p { font-size:13px; color:#687781; margin:0; }
    .met-form { border:0; box-shadow:0 8px 32px #191c1e0a; }
    .met-form .card-header { background:#002442; color:#fff; padding:14px 18px; }
    .met-form .card-title { font-size:13px; font-weight:800; }
    .met-form .card-body { padding:22px; }
    .met-form label { font-size:10px; color:#586873; letter-spacing:.08em; text-transform:uppercase; font-weight:800; }
    .met-form .form-control { height:38px; background:#eef2f5; border:0; border-radius:4px; font-size:12px; }
    .met-form textarea.form-control { height:auto; }
    .met-form .form-control:focus { background:#fff; box-shadow:0 0 0 2px #17619a44; }
    .met-section { font-size:12px; font-weight:800; color:#002442; border-bottom:2px solid #e9eef1; padding-bottom:8px; margin:10px 0 16px; }
    .met-form-footer { background:#f7f9fb; border:0; padding:15px 22px; }
    .met-submit { background:#002442; color:#fff; border:0; border-radius:5px; padding:10px 18px; font-weight:800; font-size:12px; transition: all 0.2s; }
    .met-submit:hover { background:#155785; }
    @media(max-width:700px){ .met-form-head { align-items:flex-start; flex-direction:column; gap:12px; } }
</style>

<div class="card met-form asset-form-ux">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-ruler-combined mr-2"></i>Ficha de instrumento y calibración</h3>
    </div>
    
    <form class="asset-smart-form" method="POST" action="{{ $action }}" enctype="multipart/form-data">
        @csrf 
        @if($editing) @method('PUT') @endif
        
        <div class="card-body">
            @if($errors->any())
                <div class="alert alert-danger" style="background:#fee1df; color:#c92328; border:0; font-size: 13px; font-weight: 700;">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                    </ul>
                </div>
            @endif

            <div class="met-section">Identificación del instrumento</div>
            <div class="row">
                <div class="col-md-5 form-group">
                    <label>Nombre del aparato *</label>
                    <input required name="nombre" class="form-control" placeholder="Ej. Multímetro Calibrador Fluke 754 HART" value="{{ old('nombre', $item->nombre ?? '') }}">
                </div>
                <div class="col-md-4 form-group">
                    <label>Magnitud / Tipo de instrumento</label>
                    <input name="instrumento" class="form-control" placeholder="Ej. Eléctrica, Presión, Dimensional..." value="{{ old('instrumento', $item->instrumento ?? '') }}">
                </div>
                <div class="col-md-3 form-group">
                    <label>Estado operativo *</label>
                    <select required name="estado" class="form-control">
                        <option value="operativo" @selected(old('estado', $item->estado ?? 'operativo') === 'operativo')>Operativo / Apto</option>
                        <option value="asignado" @selected(old('estado', $item->estado ?? 'operativo') === 'asignado')>Asignado en planta</option>
                        <option value="calibracion" @selected(old('estado', $item->estado ?? 'operativo') === 'calibracion')>En Laboratorio / Calibración</option>
                        <option value="aislado" @selected(old('estado', $item->estado ?? 'operativo') === 'aislado')>Aislado / No Conforme</option>
                    </select>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3 form-group">
                    <label>Código Interno</label>
                    <input name="codigo" class="form-control" value="{{ old('codigo', $item->codigo ?? '') }}">
                </div>
                <div class="col-md-3 form-group">
                    <label>Número de serie (S/N)</label>
                    <input name="numero_serie" class="form-control" value="{{ old('numero_serie', $item->numero_serie ?? '') }}">
                </div>
                <div class="col-md-3 form-group">
                    <label>Marca</label>
                    <input name="marca" class="form-control" value="{{ old('marca', $item->marca ?? '') }}">
                </div>
                <div class="col-md-3 form-group">
                    <label>Modelo</label>
                    <input name="modelo" class="form-control" value="{{ old('modelo', $item->modelo ?? '') }}">
                </div>
            </div>

            <div class="met-section">Características metrológicas (Parámetros)</div>
            <div class="row">
                <div class="col-md-3 form-group">
                    <label>Rango de medida</label>
                    <input name="rango_medida" class="form-control" placeholder="Ej. 4-20 mA / 0-30 bar" value="{{ old('rango_medida', $item->rango_medida ?? '') }}">
                </div>
                <div class="col-md-3 form-group">
                    <label>Clase de exactitud</label>
                    <input name="clase_exactitud" class="form-control" placeholder="Ej. 0,15% FS" value="{{ old('clase_exactitud', $item->clase_exactitud ?? '') }}">
                </div>
                <div class="col-md-3 form-group">
                    <label>Tolerancia permitida</label>
                    <input name="tolerancia" class="form-control" placeholder="Ej. ±0,05 bar" value="{{ old('tolerancia', $item->tolerancia ?? '') }}">
                </div>
                <div class="col-md-3 form-group">
                    <label>Incertidumbre</label>
                    <input name="incertidumbre" class="form-control" placeholder="Ej. k=2, 95%" value="{{ old('incertidumbre', $item->incertidumbre ?? '') }}">
                </div>
            </div>

            <div class="met-section">Ciclo de calibración y trazabilidad</div>
            <div class="row">
                <div class="col-md-3 form-group">
                    <label>Última calibración efectuada</label>
                    <input type="date" name="ultima_calibracion" class="form-control" value="{{ old('ultima_calibracion', optional($item->ultima_calibracion ?? null)->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3 form-group">
                    <label>Próxima Calibración (Caducidad) *</label>
                    <input type="date" name="proxima_calibracion" required class="form-control" value="{{ old('proxima_calibracion', optional($item->proxima_calibracion ?? null)->format('Y-m-d')) }}" style="background: #fff4e4; border: 1px dashed #dca54a;">
                </div>
               <div class="col-md-2 form-group">
                    <label>Nº Certificado</label>
                    <input name="certificado" class="form-control" placeholder="Ej. C-2026-88" value="{{ old('certificado', $item->certificado ?? '') }}">
                </div>
                <div class="col-md-2 form-group">
                    <label>Laboratorio / Entidad</label>
                    <input name="laboratorio" class="form-control" placeholder="Ej. ENAC, Trescal..." value="{{ old('laboratorio', $item->laboratorio ?? '') }}">
                </div>
                <div class="col-md-2 form-group">
                    <label>Adjuntar PDF Oficial</label>
                    <input type="file" name="certificado_pdf" accept=".pdf" class="form-control" style="padding: 6px;">
                    @if($item && $item->certificado_pdf)
                        <small style="color: #0d9d7b; font-weight: bold; display: block; margin-top: 4px;"><i class="fas fa-check"></i> PDF Guardado</small>
                    @endif
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Responsable / Área asignada</label>
                    <input name="responsable" class="form-control" placeholder="Nombre del trabajador o departamento" value="{{ old('responsable', $item->responsable ?? '') }}">
                </div>
                <div class="col-md-6 form-group">
                    <label>Ubicación física (Si no está asignado)</label>
                    <input name="ubicacion" class="form-control" placeholder="Ej. Armario Metrología A" value="{{ old('ubicacion', $item->ubicacion ?? '') }}">
                </div>
            </div>
            
            <div class="row mt-2">
                <div class="col-md-8 form-group">
                    <label>Observaciones de trazabilidad</label>
                    <textarea name="descripcion" rows="4" class="form-control" placeholder="Anotaciones sobre desviaciones en la última revisión, cambio de sondas, estado de la maleta de transporte...">{{ old('descripcion', $item->descripcion ?? '') }}</textarea>
                </div>
                <div class="col-md-4 form-group">
                    <label>Fotografía del instrumento</label>
                    <div style="background:#eef2f5; text-align:center; padding:20px; color:#71808c; border:1px dashed #b8c4cc; border-radius: 4px;">
                        <i class="fas fa-camera fa-2x mb-2"></i>
                        <input type="file" name="imagen" accept="image/*" class="w-100" style="font-size: 10px;">
                        @if($item && $item->imagen)
                            <small style="color: #0c9b77; font-weight: bold; margin-top: 5px; display: block;"><i class="fas fa-check"></i> Imagen guardada</small>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card-footer met-form-footer text-right">
            <a href="{{ route('calibrables.index') }}" class="btn btn-light mr-2">Cancelar</a>
            <button class="met-submit"><i class="fas fa-save mr-1"></i>Guardar ficha metrológica</button>
        </div>
    </form>
</div>
@stop