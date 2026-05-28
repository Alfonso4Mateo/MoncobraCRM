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
        'pedidoBolsa' => $pedidoBolsa ?? false,
        'pedidoModoRestringido' => $pedidoModoRestringido ?? false,
        'pedidoPendienteFacturar' => $pedidoPendienteFacturar ?? null,
        'lineasIniciales' => $lineasIniciales ?? [],
        'pedidoDefaults' => $pedidoDefaults ?? [],
    ])
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    @vite(['resources/css/albaranes-form.css'])
@endsection

@section('js')
    <script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script>
    @vite(['resources/js/albaranes-form.js'])
@endsection