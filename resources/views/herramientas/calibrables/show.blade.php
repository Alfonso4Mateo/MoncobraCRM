@extends('adminlte::page')

@section('title', $herramienta->nombre)

@section('content_header')
    <div class="cal-detail-head">
        <div>
            <small>Gestión de activos / Aparatos calibrables</small>
            <h1>{{ $herramienta->nombre }}</h1>
            <p>Ficha técnica, trazabilidad metrológica e historial de calibraciones.</p>
        </div>
        <div class="cal-detail-actions">
            <a href="{{ route('calibrables.index') }}" class="cal-btn cal-btn-soft">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <a href="{{ route('calibrables.edit', $herramienta) }}" class="cal-btn cal-btn-soft">
                <i class="fas fa-edit"></i> Editar ficha
            </a>
            <!-- Botón para añadir a la bitácora -->
            <button type="button" class="cal-btn cal-btn-main" data-toggle="modal" data-target="#event-modal">
                <i class="fas fa-plus"></i> Añadir Registro / Certificado
            </button>
        </div>
    </div>
@stop

@section('content')
@include('herramientas.partials.architectural-ledger')

<style>
    :root {
        --cal-navy: #002442;
        --cal-ink: #191c1e;
        --cal-bg: #f7f9fb;
        --cal-low: #eef2f5;
        --cal-muted: #687681;
        --cal-red: #c92328;
        --cal-amber: #dca54a;
        --cal-blue: #17619a;
    }
    
    .content-wrapper { background: var(--cal-bg); }
    
    /* Encabezado */
    .cal-detail-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 18px; margin-bottom: 20px; }
    .cal-detail-head small { font-size: 10px; letter-spacing: .12em; text-transform: uppercase; font-weight: 800; color: #71808c; }
    .cal-detail-head h1 { font-size: 28px; color: var(--cal-ink); font-weight: 800; margin: 5px 0; }
    .cal-detail-head p { font-size: 13px; color: var(--cal-muted); margin: 0; }
    
    /* Botones */
    .cal-detail-actions { display: flex; gap: 8px; flex-wrap: wrap; }
    .cal-btn { border: 0; border-radius: 5px; padding: 10px 14px; font-size: 11px; font-weight: 800; text-decoration: none; cursor: pointer; display: inline-flex; align-items: center; }
    .cal-btn i { margin-right: 6px; }
    .cal-btn-soft { background: #e8edf1; color: #284252; transition: all 0.2s; }
    .cal-btn-soft:hover { background: #dcecf8; color: #0d4675; }
    .cal-btn-main { background: linear-gradient(110deg, #002442, #155785); color: #fff; box-shadow: 0 6px 16px #0024422b; }
    
    /* Layout Principal */
    .cal-detail { display: grid; grid-template-columns: 320px 1fr; gap: 20px; align-items: start; }
    .cal-panel { background: #fff; padding: 22px; box-shadow: 0px 8px 32px rgba(25,28,30,0.04); margin-bottom: 20px; border-radius: 8px; }
    .cal-panel h2 { font-size: 17px; color: var(--cal-ink); font-weight: 800; margin: 0 0 18px; }
    
    /* Foto y Lista Lateral */
    .cal-photo { height: 190px; background: var(--cal-low); display: grid; place-items: center; margin-bottom: 16px; overflow: hidden; border-radius: 6px; border: 1px solid #edf0f2; }
    .cal-photo img { width: 100%; height: 100%; object-fit: contain; }
    .cal-photo i { font-size: 48px; color: #82929d; }
    
    .cal-status { display: flex; justify-content: flex-end; margin-bottom: 12px; }
    .cal-status span { padding: 6px 12px; border-radius: 99px; background: #eef2f5; color: #52636d; font-size: 10px; font-weight: 800; letter-spacing: 0.05em; }
    .cal-status span.status-operativo { background: #e2f7ee; color: #08734e; }
    .cal-status span.status-aislado { background: #fee1df; color: #c92328; }
    .cal-status span.status-calibracion { background: #dcecf8; color: #0d4675; }
    
    .cal-list { display: grid; grid-template-columns: 1fr 1fr; gap: 9px; margin: 0; }
    .cal-list div { background: var(--cal-low); padding: 10px; border-radius: 4px; }
    .cal-list dt { font-size: 9px; text-transform: uppercase; letter-spacing: .1em; color: #687681; margin-bottom: 3px; }
    .cal-list dd { font-size: 11px; color: var(--cal-ink); font-weight: 800; margin: 0; word-break: break-word; }
    
    /* Especificaciones */
    .cal-specs { list-style: none; margin: 0; padding: 0; }
    .cal-specs li { display: flex; justify-content: space-between; padding: 11px 0; border-bottom: 1px solid #edf0f2; font-size: 12px; color: #687681; }
    .cal-specs li:last-child { border-bottom: 0; padding-bottom: 0; }
    .cal-specs strong { color: var(--cal-ink); text-align: right; font-weight: 800; }
    
    /* Línea de tiempo (Bitácora) */
    .event-list { position: relative; padding-left: 28px; margin-top: 15px; }
    .event-list:before { content: ""; position: absolute; left: 8px; top: 5px; bottom: 10px; width: 1px; background: #d8e1e6; }
    .event-item { position: relative; padding: 0 0 24px; }
    .event-item:last-child { padding-bottom: 4px; }
    
    .event-dot { position: absolute; left: -26px; top: 4px; width: 17px; height: 17px; border-radius: 50%; background: #17619a; border: 4px solid #dcecf8; }
    .event-dot.averia, .event-dot.reparacion, .event-dot.aislado { background: #c92328; border-color: #fee1df; }
    .event-dot.calibracion { background: #17619a; border-color: #dcecf8; }
    
    .event-top { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; }
    .event-type { font-size: 10px; color: #7b8991; text-transform: uppercase; letter-spacing: .05em; font-weight: 800; }
    .event-title { font-size: 14px; color: #22343d; font-weight: 800; margin-top: 2px; }
    .event-date { background: var(--cal-low); padding: 4px 8px; color: #52636d; font-size: 10px; white-space: nowrap; font-weight: 700; border-radius: 4px; }
    
    .event-description { font-size: 12px; color: #52636d; line-height: 1.5; margin: 6px 0 0; max-width: 680px; }
    .event-by { font-size: 10px; color: #87949b; margin-top: 8px; }
    
    .event-attachment { display: inline-flex; align-items: center; gap: 6px; background: #e8edf1; color: #17619a; padding: 6px 12px; border-radius: 4px; font-size: 10px; font-weight: 800; text-decoration: none; margin-top: 10px; }
    .event-attachment:hover { background: #dcecf8; color: #0d4675; }
    
    .empty-events { padding: 40px 10px; text-align: center; color: #7b8991; font-size: 12px; background: var(--cal-low); border-radius: 6px; }
    
    /* Modal UX */
    .detail-modal { display: none; position: fixed; inset: 0; background: rgba(0,36,66,0.5); z-index: 1100; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(4px); }
    .detail-modal.show { display: flex; }
    .detail-dialog { background: #fff; width: 100%; max-width: 550px; padding: 26px; box-shadow: 0 16px 45px rgba(0,36,66,0.2); border-radius: 8px; }
    .detail-dialog h2 { font-size: 20px; font-weight: 800; margin: 0 0 5px; }
    .detail-dialog p { font-size: 12px; color: #687681; margin-bottom: 20px; }
    .detail-dialog label { font-size: 10px; text-transform: uppercase; letter-spacing: .08em; font-weight: 800; color: #586873; display: block; margin-bottom: 4px; }
    .detail-dialog .form-control { border: 0; background: var(--cal-low); font-size: 12px; margin-bottom: 16px; width: 100%; padding: 10px; border-radius: 4px; }
    .detail-dialog .form-control:focus { outline: 2px solid #17619a; background: #fff; }
    .detail-modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px; }

    @media(max-width: 900px) {
        .cal-detail { grid-template-columns: 1fr; }
        .cal-detail-head { align-items: flex-start; flex-direction: column; }
    }
</style>

@if(session('success'))
    <div class="alert alert-success mt-3" style="font-size: 13px; font-weight: 700; background: #dff3e9; color: #08734e; border: 0;">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
    </div>
@endif

<div class="cal-detail">
    <!-- Columna Izquierda: Resumen -->
    <aside>
        <section class="cal-panel">
            <div class="cal-status">
                <span class="status-{{ $herramienta->estado }}">{{ $herramienta->estado_label }}</span>
            </div>
            
            <div class="cal-photo">
                @if($herramienta->imagen)
                    <img src="{{ asset('storage/'.$herramienta->imagen) }}" alt="{{ $herramienta->nombre }}">
                @else
                    <i class="fas fa-ruler-combined"></i>
                @endif
            </div>
            <h2>{{ $herramienta->nombre }}</h2>
            <dl class="cal-list">
                <div style="grid-column: span 2;">
                    <dt>Magnitud / Tipo</dt>
                    <dd>{{ $herramienta->instrumento ?: 'No indicado' }}</dd>
                </div>
                <div>
                    <dt>Código (ID)</dt>
                    <dd>{{ $herramienta->codigo ?: 'S/C' }}</dd>
                </div>
                <div>
                    <dt>Número de serie</dt>
                    <dd>{{ $herramienta->numero_serie ?: 'S/N' }}</dd>
                </div>
                <div>
                    <dt>Marca</dt>
                    <dd>{{ $herramienta->marca ?: 'No indicada' }}</dd>
                </div>
                <div>
                    <dt>Modelo</dt>
                    <dd>{{ $herramienta->modelo ?: 'No indicado' }}</dd>
                </div>
                <div>
                    <dt>Responsable / Área</dt>
                    <dd>{{ $herramienta->responsable ?: 'No asignado' }}</dd>
                </div>
                <div>
                    <dt>Ubicación física</dt>
                    <dd>{{ $herramienta->ubicacion ?: 'Almacén' }}</dd>
                </div>
            </dl>
        </section>
    </aside>

    <!-- Columna Derecha: Especificaciones y Bitácora -->
    <main>
        <section class="cal-panel">
            <h2>Metrología y Trazabilidad de Calibración</h2>
            @php 
                $isExpired = $herramienta->proxima_calibracion && $herramienta->proxima_calibracion->isPast();
            @endphp
            
            <ul class="cal-specs">
                <li><span>Rango de medida</span><strong>{{ $herramienta->rango_medida ?: 'No indicado' }}</strong></li>
                <li><span>Clase de exactitud</span><strong>{{ $herramienta->clase_exactitud ?: 'No indicada' }}</strong></li>
                <li><span>Tolerancia permitida</span><strong>{{ $herramienta->tolerancia ?: 'No indicada' }}</strong></li>
                <li><span>Incertidumbre</span><strong>{{ $herramienta->incertidumbre ?: 'No indicada' }}</strong></li>
                <li><span>Norma de calibración</span><strong>{{ $herramienta->norma_calibracion ?: 'No indicada' }}</strong></li>
                
                <li style="border-top: 1px dashed #d8e1e6; margin-top: 8px; padding-top: 15px;">
                    <span>Última calibración efectuada</span>
                    <strong>{{ optional($herramienta->ultima_calibracion)->format('d/m/Y') ?: 'Pendiente' }}</strong>
                </li>
                <li>
                    <span>Próxima calibración (Caducidad)</span>
                    <strong style="{{ $isExpired ? 'color: var(--cal-red); background: #fee1df; padding: 2px 6px; border-radius: 4px;' : 'color: var(--cal-ink);' }}">
                        @if($isExpired) <i class="fas fa-exclamation-triangle mr-1"></i> @endif
                        {{ optional($herramienta->proxima_calibracion)->format('d/m/Y') ?: 'No planificada' }}
                    </strong>
                </li>
                <li><span>Certificado Oficial Válido</span><strong>{{ $herramienta->certificado ?: 'Pendiente' }}</strong></li>
                <li><span>Laboratorio certificador</span><strong>{{ $herramienta->laboratorio ?: 'No indicado' }}</strong></li>
            </ul>
        </section>

        <!-- Historial Cronológico de Sucesos (Bitácora) -->
        <section class="cal-panel">
            <h2>Historial de movimientos y certificados</h2>
            
            <div class="event-list">
                @forelse($herramienta->eventos as $evento)
                    <div class="event-item">
                        <span class="event-dot {{ $evento->tipo }}"></span>
                        <div class="event-top">
                            <div>
                                <div class="event-type">{{ $evento->tipo_label ?? ucfirst($evento->tipo) }}</div>
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
                                        <i class="fas fa-file-pdf"></i> Ver Certificado / Comprobante
                                    </a>
                                @endif
                            </div>
                        @endif
                        
                        <div class="event-by">Registrado por: {{ optional($evento->usuario)->name ?: 'Sistema' }}</div>
                    </div>
                @empty
                    <div class="empty-events">
                        <i class="fas fa-file-certificate fa-2x mb-2" style="opacity: 0.5;"></i><br>
                        El equipo no tiene certificados ni movimientos registrados.<br>
                        Utiliza el botón superior para documentar su primera calibración.
                    </div>
                @endforelse
            </div>
        </section>
    </main>
</div>

<!-- Modal para registrar una nueva calibración/certificado -->
<div class="detail-modal" id="event-modal">
    <div class="detail-dialog">
        <h2>Añadir Registro Metrológico</h2>
        <p>Añade un nuevo certificado de calibración, reporta un fallo de tolerancia o documenta un movimiento interno.</p>
        
        <form method="POST" action="{{ route('calibrables.eventos.store', $herramienta) }}" enctype="multipart/form-data">
            @csrf
            
            <label>Tipo de registro *</label>
            <select required name="tipo" class="form-control">
                <option value="calibracion">Nueva Calibración (Subida de Certificado)</option>
                <option value="reparacion">Reparación / Ajuste interno</option>
                <option value="averia">Fallo o Avería (Desviación metrológica)</option>
                <option value="responsable">Asignación a área/usuario</option>
                <option value="otro">Otro suceso</option>
            </select>
            
            <label>Título breve *</label>
            <input required type="text" name="titulo" class="form-control" placeholder="Ej. Certificado anual ENAC, Calibración interna... ">
            
            <label>Fecha de la actuación *</label>
            <input required type="datetime-local" name="fecha" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}">
            
            <label>Descripción detallada</label>
            <textarea name="descripcion" rows="4" class="form-control" placeholder="Anota si se ha requerido ajuste, si hay derivas importantes en las mediciones..."></textarea>
            
            <label>Certificado Oficial (PDF) o Foto</label>
            <input type="file" name="archivo_adjunto" class="form-control" accept=".pdf, image/*" style="padding: 6px;">
            
            <div class="detail-modal-actions">
                <button type="button" class="cal-btn cal-btn-soft" onclick="document.getElementById('event-modal').classList.remove('show')">Cancelar</button>
                <button type="submit" class="cal-btn cal-btn-main"><i class="fas fa-save"></i> Guardar en trazabilidad</button>
            </div>
        </form>
    </div>
</div>

@push('js')
<script>
    document.querySelector('[data-target="#event-modal"]').addEventListener('click', function() {
        document.getElementById('event-modal').classList.add('show');
    });
</script>
@endpush
@stop