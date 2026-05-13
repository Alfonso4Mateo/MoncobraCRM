@extends('adminlte::page')

@section('title', 'Crear Albaran Cliente - MoncobraCRM')

@section('header-title')
    <i class="fas fa-file-invoice"></i> ALBARAN CLIENTE
@endsection

@section('content')
    @include('albaranes.partials.form', [
        'mode' => 'create',
        'clientes' => $clientes,
        'pedidoContext' => $pedidoContext ?? null,
        'lineasIniciales' => $lineasIniciales ?? [],
        'pedidoDefaults' => $pedidoDefaults ?? [],
    ])
@endsection

@section('css')
    @vite(['resources/css/albaranes-form.css'])
@endsection

@section('js')
    @vite(['resources/js/albaranes-form.js'])
@endsection