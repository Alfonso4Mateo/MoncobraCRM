@extends('adminlte::page')

@section('title', 'Cargar Documento - MoncobraCRM')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/documentos-create.css'])
@endsection

@section('content')
    <section class="docs-upload-page">
        <header class="docs-upload-hero">
            <div>
                <div class="docs-upload-crumbs">
                    <span>GESTION DE ARCHIVOS</span>
                    <i class="fas fa-chevron-right"></i>
                    <span>CARGAR DOCUMENTOS</span>
                </div>
                <h1>Cargar Nuevo Documento</h1>
                <p>Centraliza nuevos registros industriales y asociarlos a su proyecto y cliente.</p>
            </div>
            <a href="{{ route('documentos.index') }}" class="docs-upload-back">Cancelar</a>
        </header>

        <div class="docs-upload-layout">
            <div class="docs-upload-main">
                <div class="docs-type-selector" role="tablist">
                    <button type="button" class="type-chip is-active">Albaran</button>
                    <button type="button" class="type-chip">Presupuesto</button>
                    <button type="button" class="type-chip">Pedido</button>
                    <button type="button" class="type-chip">EPI</button>
                    <button type="button" class="type-chip">Factura</button>
                </div>

                <section class="docs-dropzone">
                    <div class="docs-dropzone-icon">
                        <i class="fas fa-cloud-arrow-up"></i>
                    </div>
                    <h2>Arrastra y suelta tus archivos aqui</h2>
                    <p>o haz clic para seleccionar un documento desde tu equipo.</p>
                    <button type="button" class="docs-dropzone-btn">Seleccionar archivos</button>
                    <span class="docs-dropzone-note">Soporta PDF, JPG, PNG, XML (Max 10 MB)</span>
                </section>
            </div>

            <aside class="docs-upload-side">
                <article class="docs-meta-card">
                    <h3>Metadatos del Documento</h3>

                    <label for="fecha_documento">Fecha de documento</label>
                    <input id="fecha_documento" type="date" placeholder="mm/dd/yyyy">

                    <label for="numero_documento">Nº de documento</label>
                    <input id="numero_documento" type="text" placeholder="Ej: ALB-2025-001">

                    <label for="ot_documento">OT asociada</label>
                    <input id="ot_documento" type="text" placeholder="Orden de trabajo">

                    <label for="cliente_documento">Proveedor / Cliente</label>
                    <input id="cliente_documento" type="text" placeholder="Buscar entidad...">

                    <button type="button" class="docs-upload-submit">
                        <i class="fas fa-cloud-arrow-up"></i>
                        Cargar Documento
                    </button>

                    <a href="{{ route('documentos.index') }}" class="docs-upload-cancel">Cancelar</a>
                </article>
            </aside>
        </div>

        <section class="docs-upload-assistant">
            <div class="assistant-icon">
                <i class="fas fa-wand-magic-sparkles"></i>
            </div>
            <div>
                <h4>Asistente de clasificacion</h4>
                <p>Cuando cargues un archivo, el sistema intenta detectar automaticamente numero, fecha y entidad asociada.</p>
            </div>
        </section>
    </section>
@endsection
