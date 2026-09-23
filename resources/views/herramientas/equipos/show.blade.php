@extends('adminlte::page')

@section('title', $equipo->nombre)

@section('content_header')
    <div class="asset-detail-head">
        <div>
            <small>Gestión de activos / Equipos informáticos</small>
            <h1>{{ $equipo->nombre }}</h1>
            <p>Ficha técnica, estado del equipo e historial de incidencias.</p>
        </div>
        <div class="asset-detail-actions">
            <a href="{{ route('equipos.index') }}" class="detail-btn detail-btn-soft">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <a href="{{ route('equipos.edit', $equipo) }}" class="detail-btn detail-btn-soft">
                <i class="fas fa-edit"></i> Editar ficha
            </a>
            <button type="button" class="detail-btn detail-btn-main" data-toggle="modal" data-target="#event-modal">
                <i class="fas fa-plus"></i> Añadir registro / Parte
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
    .detail-btn-main { background: linear-gradient(110deg, #002442, #155785); color: #fff; box-shadow: 0 6px 16px #0024422b; }
    
    /* Layout Principal */
    .equip-detail { display: grid; grid-template-columns: 320px 1fr; gap: 20px; align-items: start; }
    .equip-panel { background: #fff; padding: 22px; box-shadow: 0px 8px 32px rgba(25,28,30,0.04); margin-bottom: 20px; }
    .equip-panel h2 { font-size: 17px; color: var(--detail-ink); font-weight: 800; margin: 0 0 18px; }
    
    /* Foto y Lista Lateral */
    .equip-photo { height: 190px; background: var(--detail-low); display: grid; place-items: center; margin-bottom: 16px; overflow: hidden; }
    .equip-photo img { width: 100%; height: 100%; object-fit: contain; }
    .equip-photo i { font-size: 48px; color: #82929d; }
    
    .equip-list { display: grid; grid-template-columns: 1fr 1fr; gap: 9px; margin: 0; }
    .equip-list div { background: var(--detail-low); padding: 10px; }
    .equip-list dt { font-size: 9px; text-transform: uppercase; letter-spacing: .1em; color: #687681; margin-bottom: 3px; }
    .equip-list dd { font-size: 11px; color: var(--detail-ink); font-weight: 800; margin: 0; word-break: break-word; }
    
    /* Especificaciones */
    .equip-specs { list-style: none; margin: 0; padding: 0; }
    .equip-specs li { display: flex; justify-content: space-between; padding: 11px 0; border-bottom: 1px solid #edf0f2; font-size: 11px; color: #687681; }
    .equip-specs li:last-child { border-bottom: 0; padding-bottom: 0; }
    .equip-specs strong { color: var(--detail-ink); text-align: right; font-weight: 800; }
    
    /* Línea de tiempo (Bitácora) */
    .event-list { position: relative; padding-left: 28px; margin-top: 15px; }
    .event-list:before { content: ""; position: absolute; left: 8px; top: 5px; bottom: 10px; width: 1px; background: #d8e1e6; }
    .event-item { position: relative; padding: 0 0 24px; }
    .event-item:last-child { padding-bottom: 4px; }
    
    .event-dot { position: absolute; left: -26px; top: 4px; width: 17px; height: 17px; border-radius: 50%; background: #17619a; border: 4px solid #dcecf8; }
    .event-dot.averia, .event-dot.reparacion { background: #c92328; border-color: #fee1df; }
    .event-dot.mantenimiento { background: #c27c15; border-color: #fff0c9; }
    
    .event-top { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; }
    .event-type { font-size: 10px; color: #7b8991; text-transform: uppercase; letter-spacing: .05em; font-weight: 800; }
    .event-title { font-size: 14px; color: #22343d; font-weight: 800; margin-top: 2px; }
    .event-date { background: var(--detail-low); padding: 4px 8px; color: #52636d; font-size: 10px; white-space: nowrap; font-weight: 700; }
    
    .event-description { font-size: 12px; color: #52636d; line-height: 1.5; margin: 6px 0 0; max-width: 680px; }
    .event-by { font-size: 10px; color: #87949b; margin-top: 8px; }
    
    .event-attachment { display: inline-flex; align-items: center; gap: 6px; background: #e8edf1; color: #17619a; padding: 6px 12px; border-radius: 4px; font-size: 10px; font-weight: 800; text-decoration: none; margin-top: 10px; }
    .event-attachment:hover { background: #dcecf8; color: #0d4675; }
    
    .empty-events { padding: 40px 10px; text-align: center; color: #7b8991; font-size: 12px; background: var(--detail-low); }
    
    /* Modal UX */
    .detail-modal { display: none; position: fixed; inset: 0; background: rgba(0,36,66,0.5); z-index: 1100; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(4px); }
    .detail-modal.show { display: flex; }
    .detail-dialog { background: #fff; width: 100%; max-width: 550px; padding: 26px; box-shadow: 0 16px 45px rgba(0,36,66,0.2); }
    .detail-dialog h2 { font-size: 20px; font-weight: 800; margin: 0 0 5px; }
    .detail-dialog p { font-size: 12px; color: #687681; margin-bottom: 20px; }
    .detail-dialog label { font-size: 10px; text-transform: uppercase; letter-spacing: .08em; font-weight: 800; color: #586873; display: block; margin-bottom: 4px; }
    .detail-dialog .form-control { border: 0; background: var(--detail-low); font-size: 12px; margin-bottom: 16px; width: 100%; padding: 10px; }
    .detail-dialog .form-control:focus { outline: 2px solid #17619a; background: #fff; }
    .detail-modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px; }

    @media(max-width: 900px) {
        .equip-detail { grid-template-columns: 1fr; }
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

<div class="equip-detail">
    <!-- Columna Izquierda: Resumen -->
    <aside>
        <section class="equip-panel">
            <div class="equip-photo">
                @if($equipo->imagen)
                    <img src="{{ asset('storage/'.$equipo->imagen) }}" alt="{{ $equipo->nombre }}">
                @else
                    <i class="fas fa-desktop"></i>
                @endif
            </div>
            <h2>{{ $equipo->nombre }}</h2>
            <dl class="equip-list">
                <div style="grid-column: span 2;">
                    <dt>Estado operativo</dt>
                    <dd>{{ $equipo->estado_label }}</dd>
                </div>
                <div>
                    <dt>ID / Código</dt>
                    <dd>{{ $equipo->codigo ?: 'No indicado' }}</dd>
                </div>
                <div>
                    <dt>Número de serie</dt>
                    <dd>{{ $equipo->numero_serie ?: 'No indicado' }}</dd>
                </div>
                <div>
                    <dt>Responsable actual</dt>
                    <dd>{{ $equipo->responsable ?: 'No asignado' }}</dd>
                </div>
                <div>
                    <dt>Ubicación física</dt>
                    <dd>{{ $equipo->ubicacion ?: 'No indicada' }}</dd>
                </div>
            </dl>
        </section>
    </aside>

    <!-- Columna Derecha: Especificaciones y Bitácora -->
    <main>
        <section class="equip-panel">
            <h2>Especificaciones técnicas</h2>
            <ul class="equip-specs">
                <li><span>Tipo de equipo</span><strong>{{ $equipo->tipo_equipo ?: 'No indicado' }}</strong></li>
                <li><span>Procesador</span><strong>{{ $equipo->procesador ?: 'No indicado' }}</strong></li>
                <li><span>Memoria RAM</span><strong>{{ $equipo->memoria_ram ?: 'No indicada' }}</strong></li>
                <li><span>Almacenamiento</span><strong>{{ $equipo->almacenamiento ?: 'No indicado' }}</strong></li>
                <li><span>Sistema operativo</span><strong>{{ $equipo->sistema_operativo ?: 'No indicado' }}</strong></li>
                <li><span>IP / Dirección MAC</span><strong>{{ $equipo->ip_address ?: '—' }} / {{ $equipo->mac_address ?: '—' }}</strong></li>
                <li><span>Garantía de hardware</span><strong>{{ optional($equipo->garantia_hasta)->format('d/m/Y') ?: 'No indicada' }}</strong></li>
                <li><span>Soporte extendido IT</span><strong>{{ optional($equipo->soporte_hasta)->format('d/m/Y') ?: 'No indicado' }}</strong></li>
            </ul>
        </section>

        <!-- Historial Cronológico de Sucesos -->
        <section class="equip-panel">
            <h2>Historial de incidencias y partes</h2>
            
            <div class="event-list">
                @forelse($equipo->eventos as $evento)
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
                                    <!-- Muestra tu botón original si es PDF o documento -->
                                    <a href="{{ asset('storage/'.$evento->archivo_adjunto) }}" target="_blank" class="event-attachment">
                                        <i class="fas fa-file-download"></i> Ver comprobante / Parte
                                    </a>
                                @endif
                            </div>
                        @endif
                        
                        <div class="event-by">Registrado por: {{ optional($evento->usuario)->name ?: 'Sistema CRM' }}</div>
                    </div>
                @empty
                    <div class="empty-events">
                        <i class="fas fa-clipboard-list fa-2x mb-2"></i><br>
                        El equipo no tiene incidencias ni mantenimientos registrados.<br>
                        Utiliza el botón superior para añadir el primer parte.
                    </div>
                @endforelse
            </div>
        </section>
    </main>
</div>

<!-- Modal para registrar un nuevo evento/incidencia -->
<div class="detail-modal" id="event-modal">
    <div class="detail-dialog">
        <h2>Añadir incidencia o parte IT</h2>
        <p>Documenta mantenimientos, reparaciones o entregas. Puedes adjuntar un PDF o imagen como comprobante.</p>
        
        {{-- IMPORTANTE: enctype="multipart/form-data" es clave para que el controlador reciba el archivo --}}
        <form method="POST" action="{{ route('equipos.eventos.store', $equipo) }}" enctype="multipart/form-data">
            @csrf
            
            <label>Tipo de registro *</label>
            <select required name="tipo" class="form-control">
                <option value="mantenimiento">Mantenimiento preventivo</option>
                <option value="averia">Avería reportada</option>
                <option value="reparacion">Intervención / Reparación</option>
                <option value="responsable">Entrega / Asignación a usuario</option>
                <option value="otro">Otro suceso</option>
            </select>
            
            <label>Título breve *</label>
            <input required type="text" name="titulo" class="form-control" placeholder="Ej. Cambio de disco duro, Limpieza térmica...">
            
            <label>Fecha de la actuación *</label>
            <input required type="datetime-local" name="fecha" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}">
            
            <label>Descripción detallada</label>
            <textarea name="descripcion" rows="4" class="form-control" placeholder="Detalla los trabajos realizados, componentes sustituidos o motivos de la avería..."></textarea>
            
            <label>Documento comprobante (Opcional)</label>
            <input type="file" name="archivo_adjunto" class="form-control" accept=".pdf, image/jpeg, image/png" style="padding: 6px;">
            
            <div class="detail-modal-actions">
                <button type="button" class="detail-btn detail-btn-soft" onclick="document.getElementById('event-modal').classList.remove('show')">Cancelar</button>
                <button type="submit" class="detail-btn detail-btn-main"><i class="fas fa-save"></i> Guardar en bitácora</button>
            </div>
        </form>
    </div>
</div>

@push('js')
<script>
    // Script sencillo para abrir y cerrar el modal
    document.querySelector('[data-target="#event-modal"]').addEventListener('click', function() {
        document.getElementById('event-modal').classList.add('show');
    });
</script>
@endpush
@stop