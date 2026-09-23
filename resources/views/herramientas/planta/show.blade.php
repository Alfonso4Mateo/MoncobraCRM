@extends('adminlte::page')

@section('title', $herramienta->nombre)

@section('content_header')
    <div class="asset-detail-head">
        <div>
            <small>Gestión de activos / Herramientas de planta</small>
            <h1>{{ $herramienta->nombre }}</h1>
            <p>Ficha operativa, documentación técnica e historial de mantenimiento.</p>
        </div>
        <div class="asset-detail-actions">
            <a href="{{ route('herramientas.index') }}" class="detail-btn detail-btn-soft">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <a href="{{ route('herramientas.planta.edit', $herramienta) }}" class="detail-btn detail-btn-soft">
                <i class="fas fa-edit"></i> Editar ficha
            </a>
            <button type="button" class="detail-btn detail-btn-main" data-toggle="modal" data-target="#event-modal">
                <i class="fas fa-plus"></i> Registrar Parte / Incidencia
            </button>
        </div>
    </div>
@stop

@section('content')
@include('herramientas.partials.architectural-ledger')

<style>
    :root {
        --detail-navy: #002442;
        --detail-ink: #191c1e;
        --detail-bg: #f7f9fb;
        --detail-low: #eef2f5;
        --detail-muted: #687681;
        --detail-green: #0d9d7b;
        --detail-red: #c92328;
    }
    
    .content-wrapper { background: var(--detail-bg); }
    
    /* Encabezado */
    .asset-detail-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 18px; margin-bottom: 20px; }
    .asset-detail-head small { font-size: 10px; letter-spacing: .12em; text-transform: uppercase; font-weight: 800; color: #71808c; }
    .asset-detail-head h1 { font-size: 28px; color: var(--detail-ink); font-weight: 800; margin: 5px 0; }
    .asset-detail-head p { font-size: 13px; color: var(--detail-muted); margin: 0; }
    
    /* Botones */
    .asset-detail-actions { display: flex; gap: 8px; flex-wrap: wrap; }
    .detail-btn { border: 0; border-radius: 5px; padding: 10px 14px; font-size: 11px; font-weight: 800; text-decoration: none; cursor: pointer; display: inline-flex; align-items: center; }
    .detail-btn i { margin-right: 6px; }
    .detail-btn-soft { background: #e8edf1; color: #284252; }
    .detail-btn-main { background: linear-gradient(110deg, #002442, #155785); color: #fff; box-shadow: 0 6px 16px rgba(0,36,66,0.15); }
    
    /* Layout Principal */
    .plant-detail-grid { display: grid; grid-template-columns: 320px 1fr; gap: 20px; align-items: start; }
    .plant-panel { background: #fff; padding: 22px; box-shadow: 0px 8px 32px rgba(25,28,30,0.04); margin-bottom: 20px; border-radius: 6px; }
    .plant-panel h2 { font-size: 17px; color: var(--detail-ink); font-weight: 800; margin: 0 0 18px; display: flex; align-items: center; gap: 8px; }
    .plant-panel h2 i { color: #87949b; font-size: 14px; }
    
    /* Foto y QR */
    .plant-photo { height: 190px; background: var(--detail-low); display: grid; place-items: center; margin-bottom: 16px; overflow: hidden; border-radius: 4px; }
    .plant-photo img { width: 100%; height: 100%; object-fit: contain; }
    .plant-photo i { font-size: 48px; color: #82929d; }
    
    .qr-box { background: #fdfdfd; border: 1px dashed #dce4e9; padding: 16px; text-align: center; border-radius: 6px; margin-top: 16px; }
    .qr-box i { font-size: 32px; color: var(--detail-navy); margin-bottom: 8px; }
    .qr-box p { font-size: 11px; color: #687681; margin: 0 0 12px; line-height: 1.4; }
    
    /* Listas de datos */
    .plant-list { display: grid; grid-template-columns: 1fr 1fr; gap: 9px; margin: 0; }
    .plant-list div { background: var(--detail-low); padding: 10px; border-radius: 4px; }
    .plant-list dt { font-size: 9px; text-transform: uppercase; letter-spacing: .1em; color: #687681; margin-bottom: 3px; }
    .plant-list dd { font-size: 11px; color: var(--detail-ink); font-weight: 800; margin: 0; word-break: break-word; }
    
    .plant-specs { list-style: none; margin: 0; padding: 0; }
    .plant-specs li { display: flex; justify-content: space-between; padding: 11px 0; border-bottom: 1px solid #edf0f2; font-size: 11px; color: #687681; }
    .plant-specs li:last-child { border-bottom: 0; padding-bottom: 0; }
    .plant-specs strong { color: var(--detail-ink); text-align: right; font-weight: 800; }
    
    /* Panel Documentación */
    .doc-panel { background: #fdfdfd; border: 1px solid #edf0f2; padding: 16px; border-radius: 6px; display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 20px; }
    .doc-info { flex: 1; }
    .doc-info h3 { font-size: 14px; font-weight: 800; color: #191c1e; margin: 0 0 4px; }
    .doc-info p { font-size: 11px; color: #687681; margin: 0; }
    .doc-actions { display: flex; gap: 8px; }

    /* Bitácora */
    .event-list { position: relative; padding-left: 28px; margin-top: 15px; }
    .event-list:before { content: ""; position: absolute; left: 8px; top: 5px; bottom: 10px; width: 1px; background: #d8e1e6; }
    .event-item { position: relative; padding: 0 0 24px; }
    
    .event-dot { position: absolute; left: -26px; top: 4px; width: 17px; height: 17px; border-radius: 50%; background: #17619a; border: 4px solid #dcecf8; }
    .event-dot.averia, .event-dot.reparacion { background: #c92328; border-color: #fee1df; }
    .event-dot.mantenimiento { background: #9a6512; border-color: #fff0c9; }
    
    .event-top { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; }
    .event-type { font-size: 10px; color: #7b8991; text-transform: uppercase; letter-spacing: .05em; font-weight: 800; }
    .event-title { font-size: 14px; color: #22343d; font-weight: 800; margin-top: 2px; }
    .event-date { background: var(--detail-low); padding: 4px 8px; color: #52636d; font-size: 10px; white-space: nowrap; font-weight: 700; border-radius: 3px; }
    
    .event-description { font-size: 12px; color: #52636d; line-height: 1.5; margin: 6px 0 0; max-width: 680px; }
    .event-by { font-size: 10px; color: #87949b; margin-top: 8px; }
    
    .event-attachment { display: inline-flex; align-items: center; gap: 6px; background: #e8edf1; color: #17619a; padding: 6px 12px; border-radius: 4px; font-size: 10px; font-weight: 800; text-decoration: none; margin-top: 10px; }
    .event-attachment:hover { background: #dcecf8; color: #0d4675; }
    
    .empty-events { padding: 40px 10px; text-align: center; color: #7b8991; font-size: 12px; background: var(--detail-low); border-radius: 6px; }

    /* Modales */
    .detail-modal { display: none; position: fixed; inset: 0; background: rgba(0,36,66,0.6); z-index: 1100; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(4px); }
    .detail-modal.show { display: flex; }
    .detail-dialog { background: #fff; width: 100%; max-width: 550px; padding: 26px; box-shadow: 0 16px 45px rgba(0,36,66,0.2); border-radius: 8px; }
    .detail-dialog h2 { font-size: 20px; font-weight: 800; margin: 0 0 5px; }
    .detail-dialog p { font-size: 12px; color: #687681; margin-bottom: 20px; }
    .detail-dialog label { font-size: 10px; text-transform: uppercase; letter-spacing: .08em; font-weight: 800; color: #586873; display: block; margin-bottom: 4px; }
    .detail-dialog .form-control { border: 0; background: var(--detail-low); font-size: 12px; margin-bottom: 16px; width: 100%; padding: 10px; border-radius: 4px; }

    @media(max-width: 900px) {
        .plant-detail-grid { grid-template-columns: 1fr; }
        .asset-detail-head { align-items: flex-start; flex-direction: column; }
    }
</style>

@if(session('success'))
    <div class="alert alert-success mt-3" style="font-size: 13px; font-weight: 700; background: #dff3e9; color: #08734e; border: 0;">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger mt-3" style="font-size: 13px; font-weight: 700; background: #fee1df; color: #c92328; border: 0;">
        <ul class="mb-0">
            @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
        </ul>
    </div>
@endif

<div class="plant-detail-grid">
    <!-- Columna Izquierda -->
    <aside>
        <section class="plant-panel">
            <div class="plant-photo">
                @if($herramienta->imagen)
                    <img src="{{ asset('storage/'.$herramienta->imagen) }}" alt="{{ $herramienta->nombre }}">
                @else
                    <i class="fas fa-tools"></i>
                @endif
            </div>
            <h2><i class="fas fa-info-circle"></i> Identificación</h2>
            <dl class="plant-list">
                <div style="grid-column: span 2;">
                    <dt>Estado Operativo</dt>
                    <dd style="color: {{ $herramienta->bloqueado || in_array($herramienta->estado, ['reparacion', 'mantenimiento']) ? 'var(--detail-red)' : 'var(--detail-green)' }}">
                        {{ $herramienta->estado_label }}
                    </dd>
                </div>
                <div>
                    <dt>ID Interno</dt>
                    <dd>{{ $herramienta->id_interno }}</dd>
                </div>
                <div>
                    <dt>Número de serie</dt>
                    <dd>{{ $herramienta->numero_serie ?: 'No indicado' }}</dd>
                </div>
                <div>
                    <dt>Responsable / Obra</dt>
                    <dd>{{ $herramienta->responsable ?: 'Almacén' }}</dd>
                </div>
                <div>
                    <dt>Ubicación física</dt>
                    <dd>{{ $herramienta->ubicacion ?: 'No indicada' }}</dd>
                </div>
            </dl>

            <!-- Zona de Integración QR -->
            <div class="qr-box">
                <i class="fas fa-qrcode"></i>
                <p>Genera una etiqueta inteligente para pegar en la máquina. Los operarios podrán escanearla para ver el manual técnico in situ.</p>
                <form method="POST" action="{{ route('qrs.store') }}">
                    @csrf
                    {{-- Parámetros ocultos que enviamos a tu QrController --}}
                    <input type="hidden" name="contenido_datos" value="{{ $herramienta->manual_pdf ? asset('storage/'.$herramienta->manual_pdf) : 'Sin manual asignado' }}">
                    <input type="hidden" name="titulo" value="{{ $herramienta->id_interno }} - {{ $herramienta->nombre }}">
                    <button type="submit" class="detail-btn detail-btn-main w-100 justify-content-center">
                        Generar Etiqueta QR
                    </button>
                </form>
            </div>
        </section>
    </aside>

    <!-- Columna Derecha -->
    <main>
        <!-- Panel de Documentación Técnica (Manuales) -->
        <section class="plant-panel">
            <h2><i class="fas fa-book"></i> Documentación y Normativa</h2>
            
            <div class="doc-panel">
                <div class="doc-info">
                    @if($herramienta->manual_pdf)
                        <h3>Manual Técnico / Ficha de Seguridad</h3>
                        <p>Documento maestro disponible para operarios vía QR.</p>
                    @else
                        <h3>Sin manual adjunto</h3>
                        <p>Sube el PDF del fabricante para cumplir con la normativa de seguridad.</p>
                    @endif
                </div>
                <div class="doc-actions">
                    @if($herramienta->manual_pdf)
                        <a href="{{ asset('storage/'.$herramienta->manual_pdf) }}" target="_blank" class="detail-btn" style="background: #eef2f5; color: #002442;">
                            <i class="fas fa-eye"></i> Leer Manual
                        </a>
                    @endif
                    <button type="button" class="detail-btn detail-btn-soft" onclick="document.getElementById('manual-modal').classList.add('show')">
                        <i class="fas fa-upload"></i> {{ $herramienta->manual_pdf ? 'Reemplazar PDF' : 'Subir PDF' }}
                    </button>
                </div>
            </div>

            <ul class="plant-specs mt-4">
                <li><span>Marca / Modelo</span><strong>{{ trim(($herramienta->marca ?: '—').' '.($herramienta->modelo ?: '')) }}</strong></li>
                <li><span>Familia</span><strong>{{ optional($herramienta->familia)->nombre ?: 'Sin familia' }}</strong></li>
                <li><span>Potencia / Tensión</span><strong>{{ $herramienta->potencia ?: '—' }} / {{ $herramienta->tension ?: '—' }}</strong></li>
                <li><span>Horas de uso registradas</span><strong>{{ $herramienta->horas_uso ?? '0' }} h</strong></li>
                <li><span>Criticidad operativa</span><strong>{{ $herramienta->criticidad ?: 'Media' }}</strong></li>
                <li><span>Próximo Preventivo</span><strong>{{ optional($herramienta->fecha_mantenimiento)->format('d/m/Y') ?: 'No programado' }}</strong></li>
            </ul>
        </section>

        <!-- Historial Cronológico de Mantenimiento -->
        <section class="plant-panel">
            <h2><i class="fas fa-history"></i> Historial de Intervenciones</h2>
            
            <div class="event-list">
                @forelse($herramienta->eventos as $evento)
                    <div class="event-item">
                        <span class="event-dot {{ $evento->tipo }}"></span>
                        <div class="event-top">
                            <div>
                                <div class="event-type">{{ $evento->tipo_label }}</div>
                                <div class="event-title">{{ $evento->titulo }}</div>
                            </div>
                            <time class="event-date">{{ $evento->fecha->format('d/m/Y H:i') }}</time>
                        </div>
                        
                        @if($evento->descripcion)
                            <p class="event-description">{{ $evento->descripcion }}</p>
                        @endif
                        
                        <!-- Discriminador Automático de Archivos (Imagen vs Documento) -->
                        @if($evento->archivo_adjunto)
                            @php
                                $extension = strtolower(pathinfo($evento->archivo_adjunto, PATHINFO_EXTENSION));
                                $esImagen = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                            @endphp

                            <div style="margin-top: 12px;">
                                @if($esImagen)
                                    <!-- Muestra miniatura si es foto -->
                                    <a href="{{ asset('storage/'.$evento->archivo_adjunto) }}" target="_blank" style="display: inline-block; border-radius: 6px; overflow: hidden; border: 1px solid #edf0f2; transition: 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.05);" onmouseover="this.style.borderColor='#17619a'" onmouseout="this.style.borderColor='#edf0f2'">
                                        <img src="{{ asset('storage/'.$evento->archivo_adjunto) }}" alt="Evidencia de la incidencia" style="height: 60px; width: auto; display: block; object-fit: cover;">
                                    </a>
                                    <div style="font-size: 10px; color: #87949b; margin-top: 4px; font-weight: 600;">
                                        <i class="fas fa-search-plus"></i> Clic para ampliar
                                    </div>
                                @else
                                    <!-- Muestra el botón original si es PDF o documento -->
                                    <a href="{{ asset('storage/'.$evento->archivo_adjunto) }}" target="_blank" class="event-attachment">
                                        <i class="fas fa-file-invoice"></i> Ver Parte de Trabajo / Factura
                                    </a>
                                @endif
                            </div>
                        @endif
                        
                        <div class="event-by">Registrado por: {{ optional($evento->usuario)->name ?: 'Sistema CRM' }}</div>
                    </div>
                @empty
                    <div class="empty-events">
                        <i class="fas fa-clipboard-check fa-2x mb-2"></i><br>
                        La maquinaria no tiene mantenimientos ni averías registradas.<br>
                        El historial se construirá automáticamente al usar la máquina de estados.
                    </div>
                @endforelse
            </div>
        </section>
    </main>
</div>

<!-- Modal para subir el Manual PDF Maestro -->
<div class="detail-modal" id="manual-modal">
    <div class="detail-dialog">
        <h2>Subir Manual Técnico</h2>
        <p>El archivo PDF sustituirá al existente. Este será el documento que los operarios verán al escanear el QR.</p>
        
        <form method="POST" action="{{ route('herramientas.planta.manual', $herramienta) }}" enctype="multipart/form-data">
            @csrf
            <label>Archivo PDF (Máx 10MB) *</label>
            <input required type="file" name="manual_pdf" class="form-control" accept=".pdf" style="padding: 6px;">
            
            <div class="detail-modal-actions" style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="detail-btn detail-btn-soft" onclick="document.getElementById('manual-modal').classList.remove('show')">Cancelar</button>
                <button type="submit" class="detail-btn detail-btn-main"><i class="fas fa-upload"></i> Subir Documento</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal para registrar Partes e Incidencias en la Bitácora -->
<div class="detail-modal" id="event-modal">
    <div class="detail-dialog">
        <h2>Registrar Parte de Trabajo</h2>
        <p>Documenta mantenimientos, averías o calibraciones externas.</p>
        
        <form method="POST" action="{{ route('herramientas.planta.eventos.store', $herramienta) }}" enctype="multipart/form-data">
            @csrf
            <label>Tipo de intervención *</label>
            <select required name="tipo" class="form-control">
                <option value="mantenimiento">Mantenimiento preventivo</option>
                <option value="averia">Avería en obra</option>
                <option value="reparacion">Reparación / Sustitución de piezas</option>
                <option value="otro">Otro registro documental</option>
            </select>
            
            <label>Título del parte *</label>
            <input required type="text" name="titulo" class="form-control" placeholder="Ej. Cambio de escobillas del motor...">
            
            <label>Fecha de la actuación *</label>
            <input required type="datetime-local" name="fecha" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}">
            
            <label>Descripción detallada</label>
            <textarea name="descripcion" rows="4" class="form-control" placeholder="Detalla los trabajos realizados o el diagnóstico..."></textarea>
            
            <label>Parte de trabajo / Factura adjunta (Opcional)</label>
            <input type="file" name="archivo_adjunto" class="form-control" accept=".pdf, image/jpeg, image/png" style="padding: 6px;">
            
            <div class="detail-modal-actions" style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="detail-btn detail-btn-soft" onclick="document.getElementById('event-modal').classList.remove('show')">Cancelar</button>
                <button type="submit" class="detail-btn detail-btn-main"><i class="fas fa-save"></i> Guardar Parte</button>
            </div>
        </form>
    </div>
</div>

@stop