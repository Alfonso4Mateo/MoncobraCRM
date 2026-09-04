@extends('adminlte::page')

@section('title', 'Gestor de QRs')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1><i class="fas fa-qrcode mr-2"></i> Explorador de Códigos QR</h1>
        </div>
        <div class="col-sm-6 text-right">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalGenerarQr">
                <i class="fas fa-plus-circle"></i> Generar Nuevo QR
            </button>
            <button type="button" class="btn btn-outline-secondary" data-toggle="modal" data-target="#modalNuevaCarpeta">
                <i class="fas fa-folder-plus"></i> Nueva Carpeta
            </button>
        </div>
    </div>
@endsection

@section('content')
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="row">
        {{-- PANEL IZQUIERDO: Árbol de Directorios Interactivo --}}
        <div class="col-md-3">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-sitemap mr-1"></i> Directorios</h3>
                </div>
                <div class="card-body p-2">
                    <div class="mb-2">
                        <a href="{{ route('qrs.index') }}" class="btn btn-xs btn-block {{ empty($carpetaSeleccionada) ? 'btn-primary' : 'btn-light text-left' }}">
                            <i class="fas fa-home mr-2"></i> Todas las Etiquetas
                        </a>
                    </div>
                    <ul class="list-unstyled folder-tree">
                        @php
                            if (!function_exists('dibujarCarpetas')) {
                                function dibujarCarpetas($carpetas, $nivel = 0, $seleccionada = null) {
                                    $html = '';
                                    $margen = $nivel * 12;
                                    foreach ($carpetas as $carpeta) {
                                        $isActive = ($seleccionada == $carpeta->id) ? 'background-color: #007bff; color: white !important; border-radius: 4px;' : '';
                                        $textClass = ($seleccionada == $carpeta->id) ? 'text-white' : 'text-dark';
                                        
                                        $html .= '<li style="margin-left: '.$margen.'px; padding: 4px 6px; '.$isActive.'" class="d-flex align-items-center justify-content-between">';
                                        
                                        // Enlace de la carpeta
                                        $html .= '<a href="'.route('qrs.index', ['carpeta' => $carpeta->id]).'" class="'.$textClass.' text-decoration-none flex-grow-1">';
                                        $html .= '<i class="fas fa-folder text-warning mr-2"></i>' . $carpeta->nombre;
                                        $html .= '</a>';

                                        // Formulario rápido para eliminar la carpeta
                                        $html .= '<form action="'.route('qrs.carpeta.destroy', $carpeta->id).'" method="POST" class="m-0" onsubmit="return confirm(\'¿Seguro que deseas eliminar esta carpeta?\')">';
                                        $html .= '<input type="hidden" name="_token" value="'.csrf_token().'">';
                                        $html .= '<input type="hidden" name="_method" value="DELETE">';
                                        $html .= '<button type="submit" class="btn btn-xs btn-link text-danger p-0 ml-2" title="Eliminar carpeta"><i class="fas fa-times"></i></button>';
                                        $html .= '</form>';
                                        
                                        $html .= '</li>';
                                        
                                        if ($carpeta->subcarpetasRecursivas->count() > 0) {
                                            $html .= '<ul class="list-unstyled mt-1 w-150">';
                                            $html .= dibujarCarpetas($carpeta->subcarpetasRecursivas, $nivel + 1, $seleccionada);
                                            $html .= '</ul>';
                                        }
                                    }
                                    return $html;
                                }
                            }
                        @endphp
                        
                        {!! dibujarCarpetas($carpetas, 0, $carpetaSeleccionada ?? null) !!}
                    </ul>
                </div>
            </div>
        </div>

        {{-- PANEL DERECHO: Grid de Archivos QR Interactivos --}}
        <div class="col-md-9">
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-image mr-1"></i> Archivos en Directorio</h3>
                </div>
                <div class="card-body bg-light">
                    <div class="row">
                        @forelse($qrs as $qr)
                            <div class="col-md-4 mb-3">
                                <div class="card h-100 shadow-sm border">
                                    <div class="card-body text-center p-2 d-flex flex-column align-items-center justify-content-center">
                                        <h6 class="font-weight-bold text-truncate w-100 mb-2" title="{{ $qr->titulo }}">{{ $qr->titulo }}</h6>
                                        
                                        {{-- Contenedor visual del QR con el Logo Moncobra superpuesto --}}
                                        <div style="position: relative; display: inline-block; width: 130px; height: 130px;" class="border rounded p-1 bg-white mb-2">
                                            <img src="{{ asset('storage/' . $qr->ruta_archivo) }}" alt="QR Code" style="width: 100%; height: 100%;">
                                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 50%; padding: 3px; box-shadow: 0 1px 3px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; width: 32px; height: 32px;">
                                                <img src="{{ asset('images/logo_moncobra.png') }}" alt="Moncobra" style="width: 24px; height: 24px; object-fit: contain;">
                                            </div>
                                        </div>

                                        <a href="{{ $qr->contenido_datos }}" target="_blank" class="small text-muted text-truncate w-100" title="{{ $qr->contenido_datos }}">
                                            <i class="fas fa-link mr-1"></i> Ver enlace Drive
                                        </a>
                                    </div>
                                    <div class="card-footer bg-white p-2 text-center d-flex justify-content-around align-items-center">
                                        {{-- Botón Descargar --}}
                                        <a href="{{ route('qrs.download', $qr->id) }}" class="btn btn-xs btn-outline-primary" title="Descargar SVG">
                                            <i class="fas fa-download"></i>
                                        </a>

                                        {{-- Botón Mover de Carpeta --}}
                                        <button type="button" class="btn btn-xs btn-outline-info" data-toggle="modal" data-target="#modalMoverQr{{ $qr->id }}" title="Mover de Carpeta">
                                            <i class="fas fa-folder-open"></i>
                                        </button>

                                        {{-- Botón Imprimir --}}
                                        <button type="button" class="btn btn-xs btn-outline-secondary" onclick="imprimirQr('{{ asset('storage/' . $qr->ruta_archivo) }}', '{{ $qr->titulo }}')" title="Imprimir Etiqueta">
                                            <i class="fas fa-print"></i>
                                        </button>

                                        {{-- Botón Eliminar --}}
                                        <form action="{{ route('qrs.destroy', $qr->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar este código QR?')" class="m-0">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-outline-danger" title="Eliminar QR">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            {{-- MODAL INDIVIDUAL PARA MOVER CADA QR --}}
                            <div class="modal fade" id="modalMoverQr{{ $qr->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header bg-info">
                                            <h5 class="modal-title"><i class="fas fa-folder-open mr-2"></i> Mover QR: {{ $qr->titulo }}</h5>
                                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        
                                        <form action="{{ route('qrs.mover', $qr->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body text-left">
                                                <div class="form-group mb-3">
                                                    <label>Selecciona la nueva carpeta de destino:</label>
                                                    <select name="carpeta_id" class="form-control" required>
                                                        @foreach($todasLasCarpetas as $c)
                                                            <option value="{{ $c->id }}" {{ $qr->carpeta_id == $c->id ? 'selected' : '' }}>
                                                                {{ $c->nombre }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="modal-footer bg-light">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                                <button type="submit" class="btn btn-info"><i class="fas fa-exchange-alt mr-1"></i> Confirmar Movimiento</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-center py-5 text-muted">
                                <i class="fas fa-box-open fa-3x mb-3"></i>
                                <h5>No hay códigos QR en esta carpeta</h5>
                                <p>Haz clic en "Generar Nuevo QR" para empezar.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: Crear Nuevo QR --}}
    <div class="modal fade" id="modalGenerarQr" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title"><i class="fas fa-qrcode mr-2"></i> Crear Nuevo Código QR</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                
                <form action="{{ route('qrs.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label>Título de la Etiqueta</label>
                            <input type="text" name="titulo" class="form-control" required placeholder="Ej. Camiones 01">
                        </div>
                        
                        <div class="form-group mb-3">
                            <label>Enlace Destino (URL de Google Drive)</label>
                            <input type="url" name="contenido_datos" class="form-control" required placeholder="https://drive.google.com/file/d/...">
                        </div>

                        <div class="form-group mb-3">
                            <label>Guardar en Carpeta</label>
                            <select name="carpeta_id" class="form-control" required>
                                <option value="" disabled selected>-- Selecciona un destino --</option>
                                @foreach($todasLasCarpetas as $carpeta)
                                    <option value="{{ $carpeta->id }}">{{ $carpeta->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label>Color Corporativo</label>
                            <input type="color" name="color_qr" class="form-control p-1" value="#0056b3" style="height: 40px;">
                        </div>
                    </div>
                    
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Generar y Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL: Crear Nueva Carpeta --}}
    <div class="modal fade" id="modalNuevaCarpeta" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-secondary">
                    <h5 class="modal-title"><i class="fas fa-folder-plus mr-2"></i> Crear Nueva Carpeta</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                
                <form action="{{ route('qrs.carpeta.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label>Nombre de la Carpeta</label>
                            <input type="text" name="nombre" class="form-control" required placeholder="Ej. Herramientas Taller">
                        </div>
                        
                        <div class="form-group mb-3">
                            <label>Carpeta Padre (Opcional)</label>
                            <select name="parent_id" class="form-control">
                                <option value="">-- Carpeta Raíz (Principal) --</option>
                                @foreach($todasLasCarpetas as $c)
                                    <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-secondary"><i class="fas fa-save mr-1"></i> Guardar Carpeta</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
<script>
    function imprimirQr(urlImagen, titulo) {
        const ventana = window.open('', '_blank', 'width=600,height=600');
        ventana.document.write(`
            <html>
                <head>
                    <title>Imprimir - ${titulo}</title>
                    <style>
                        body { text-align: center; font-family: Arial, sans-serif; padding-top: 40px; }
                        h2 { margin-bottom: 20px; }
                        img { width: 300px; height: 300px; }
                    </style>
                </head>
                <body>
                    <h2>${titulo}</h2>
                    <div>
                        <img src="${urlImagen}" style="width: 250px; height: 250px;" />
                    </div>
                    <script>
                        window.onload = function() { window.print(); window.close(); }
                    <\/script>
                </body>
            </html>
        `);
        ventana.document.close();
    }
</script>
@endsection

@section('css')
    <style>
        .folder-tree a:hover {
            opacity: 0.8;
        }
        .card-outline {
            border-top: 3px solid;
        }
    </style>
@endsection