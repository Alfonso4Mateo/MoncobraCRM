@extends('adminlte::page')

@section('title', 'Previsualizar pedido - MoncobraCRM')

@include('shared.document-preview', [
    'previewPrefix' => 'pedido',
    'heading' => 'Previsualizar pedido ' . ($pedido->numero_pedido ?: ''),
    'subheading' => 'Revisa el documento antes de imprimirlo o descargarlo.',
    'note' => 'Si tu navegador bloquea la impresión directa del visor, usa "Abrir PDF" y luego imprime desde la pestaña del documento.',
    'pdfUrl' => $pdfUrl,
    'downloadUrl' => $downloadUrl,
    'iframeId' => 'pedido-preview-frame',
    'iframeTitle' => 'Vista previa del pedido',
])
