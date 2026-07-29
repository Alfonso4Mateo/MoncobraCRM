@extends('adminlte::page')

@section('title', 'Previsualizar presupuesto - MoncobraCRM')

@include('shared.document-preview', [
    'previewPrefix' => 'presupuesto',
    'heading' => 'Previsualizar presupuesto ' . ($presupuesto->numero ?: ''),
    'subheading' => 'Revisa el documento antes de imprimirlo o descargarlo.',
    'note' => 'Si tu navegador bloquea la impresión directa del visor, usa "Abrir PDF" y luego imprime desde la pestaña del documento.',
    'pdfUrl' => $pdfUrl,
    'downloadUrl' => $downloadUrl,
    'iframeId' => 'presupuesto-preview-frame',
    'iframeTitle' => 'Vista previa del presupuesto',
])
