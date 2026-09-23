@extends('adminlte::page')

@section('title', 'Perfil del Trabajador')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/personal-show.css'])

    <style>
        .custom-file-upload {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
        }

        .custom-file-upload input[type="file"] {
            display: none; /* Ocultamos el input feo original */
        }

        .custom-file-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: #f8fafc;
            border: 1px dashed #cbd5e1;
            color: #64748b;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s ease;
            margin: 0;
        }

        .custom-file-btn:hover {
            background-color: #f1f5f9;
            border-color: #94a3b8;
            color: #475569;
        }

        .custom-file-upload.has-file .custom-file-btn {
            background-color: #eff6ff;
            border-color: #bfdbfe;
            border-style: solid;
            color: #3b82f6;
        }

        .custom-file-name {
            font-size: 0.85rem;
            color: #94a3b8;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px; 
        }

        /* Interruptor moderno (Toggle Switch) para PRL */
        .prl-toggle-wrapper { display: inline-flex; align-items: center; cursor: pointer; background: #f8fafc; padding: 6px 14px; border-radius: 99px; border: 1px solid #e2e8f0; user-select: none; transition: all 0.2s; }
        .prl-toggle-wrapper:hover { background: #f1f5f9; border-color: #cbd5e1; }
        .prl-toggle-input { display: none; }
        .prl-toggle-track { position: relative; width: 36px; height: 20px; background-color: #cbd5e1; border-radius: 99px; transition: background-color 0.3s ease; }
        .prl-toggle-dot { position: absolute; top: 2px; left: 2px; width: 16px; height: 16px; background-color: white; border-radius: 50%; transition: transform 0.3s ease; box-shadow: 0 1px 2px rgba(0,0,0,0.2); }
        .prl-toggle-input:checked + .prl-toggle-track { background-color: #10b981; }
        .prl-toggle-input:checked + .prl-toggle-track .prl-toggle-dot { transform: translateX(16px); }
        .prl-toggle-text { margin-left: 10px; font-size: 0.85rem; font-weight: 700; color: #475569; transition: color 0.3s; }
        .prl-toggle-input:checked ~ .prl-toggle-text { color: #166534; }
        
        /* Botón limpio para descargar PDF */
        .prl-btn-pdf { display: inline-flex; align-items: center; gap: 6px; background: #eff6ff; color: #3b82f6; padding: 6px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: 800; text-decoration: none !important; white-space: nowrap; transition: all 0.2s; border: 1px solid #bfdbfe; }
        .prl-btn-pdf:hover { background: #dbeafe; color: #2563eb; border-color: #93c5fd; transform: translateY(-1px); box-shadow: 0 2px 4px rgba(59, 130, 246, 0.1); }
    </style>
@endsection

@section('content')
    <section class="profile-page">
        <header class="profile-hero">
            <div class="profile-hero-actions">
                @can('inventario.manage')
                    <a href="{{ route('inventario.salida.create', ['personal_id' => $personal->id]) }}" class="profile-action profile-action--soft">
                        <i class="fas fa-arrow-up-from-box"></i> Registrar Salida
                    </a>
                @endcan

                @can('personal.edit')
                    <a href="{{ route('personal.edit', $personal->id) }}" class="profile-action profile-action--primary">
                        <i class="fas fa-pen"></i> Editar Perfil
                    </a>
                    
                    @if($personal->activo)
                        <!-- Botón Dar de Baja -->
                        <form method="POST" action="{{ route('personal.toggleStatus', $personal->id) }}" style="display:inline;" onsubmit="return confirm('¿Seguro que deseas dar de baja a este trabajador? (Pasará a estado Inactivo)');">
                            @csrf
                            <button type="submit" class="profile-action profile-action--warn" style="background-color: #f59e0b; color: white; border-color: #f59e0b;">
                                <i class="fas fa-user-minus"></i> Dar de Baja
                            </button>
                        </form>
                    @else
                        <!-- Botón Dar de Alta -->
                        <form method="POST" action="{{ route('personal.toggleStatus', $personal->id) }}" style="display:inline;">
                            @csrf
                            <button type="submit" class="profile-action profile-action--primary" style="background-color: #10b981; border-color: #10b981; color: white;">
                                <i class="fas fa-user-check"></i> Dar de Alta
                            </button>
                        </form>
                    @endif
                @endcan

                @can('personal.delete')
                    <button type="button" class="profile-action profile-action--danger" id="open-delete-personal">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                @endcan
            </div>
        </header>

        <div class="profile-grid">
            <section class="profile-main">
                <div class="profile-main-row">
                    
                    <!-- COLUMNA IZQUIERDA FIJA (Perfil Básico) -->
                    <div class="profile-main-left">
                        <article class="profile-card profile-card--main-sidebar" style="position: sticky; top: 20px;">
                            <div class="profile-status {{ $personal->activo ? '' : 'profile-status--inactive' }}">
                                {{ $personal->activo ? 'ACTIVO' : 'INACTIVO' }}
                            </div>

                            <div class="profile-avatar-wrap">
                                <div class="profile-avatar">
                                    <i class="fas fa-hard-hat"></i>
                                </div>
                            </div>

                            <div class="profile-name-block">
                                <h2>{{ $personal->name }} {{ $personal->apellido }}</h2>
                                @php
                                    $deptosActuales = is_string($personal->departamento) ? json_decode($personal->departamento, true) ?? explode(',', $personal->departamento) : (array) $personal->departamento;
                                @endphp
                                <p>{{ !empty($deptosActuales) ? strtoupper(implode(', ', $deptosActuales)) : 'SIN DEPARTAMENTO' }}</p>
                            </div>

                            <div class="profile-metadata">
                                <div>
                                    <span>ID EMPLEADO</span>
                                    <strong>AL-{{ str_pad((string) $personal->id, 3, '0', STR_PAD_LEFT) }}</strong>
                                </div>
                                <div>
                                    <span>ID RRHH</span>
                                    <strong>{{ $personal->id_rrhh ?: '—' }}</strong>
                                </div>
                                <div>
                                    <span>ANTIGÜEDAD</span>
                                    <strong>{{ optional($personal->created_at)->format('d M Y') }}</strong>
                                </div>
                            </div>
                        </article>
                    </div>

                    <!-- COLUMNA DERECHA DINÁMICA (Sistema de Pestañas) -->
                    <div class="profile-main-right" style="min-width: 0;">
                        
                        <!-- MENÚ DE NAVEGACIÓN (TABS) -->
                        <nav class="profile-tabs-nav">
                            <button class="profile-tab-btn is-active" data-target="tab-corporativo">
                                <i class="fas fa-address-card"></i> Datos Corporativos
                            </button>
                            <button class="profile-tab-btn" data-target="tab-epis">
                                <i class="fas fa-ruler-combined"></i> Equipamiento (EPIs)
                            </button>
                            <button class="profile-tab-btn" data-target="tab-formacion">
                                <i class="fas fa-graduation-cap"></i> Formación
                            </button>
                            <button class="profile-tab-btn" data-target="tab-inventario">
                                <i class="fas fa-box-open"></i> Inventario
                            </button>
                        </nav>

                        <!-- PANEL 1: DATOS CORPORATIVOS -->
                        <div id="tab-corporativo" class="profile-tab-panel is-active">
                            <article class="profile-card">
                                <div class="profile-card__header">
                                    <div>
                                        <h3><i class="fas fa-notes-medical"></i> Información Corporativa y Médica</h3>
                                    </div>
                                </div>
                                
                                <div class="profile-right-metadata" style="align-items: start; padding: 20px;">
                                    
                                    <!-- Columna 1: RRHH / Corporativo -->
                                    <div style="display: flex; flex-direction: column; gap: 18px;">
                                        <div>
                                            <span>DEPARTAMENTO</span>
                                            <strong>{{ !empty($deptosActuales) ? strtoupper(implode(', ', $deptosActuales)) : '—' }}</strong>
                                        </div>

                                        <div>
                                            <span>PUESTO</span>
                                            <strong>{{ optional($personal->puestoTrabajo)->nombre ?: '—' }}</strong>
                                        </div>

                                        <div>
                                            <span>TELÉFONO</span>
                                            <strong>{{ $personal->telefono ?: '—' }}</strong>
                                        </div>
                                        <div>
                                            <span>CORREO ELECTRÓNICO</span>
                                            <strong style="text-transform: lowercase;">{{ $personal->correo ?: '—' }}</strong>
                                        </div>
                                    </div>

                                @can('personal.medico')
                                    @php
                                        $hoy = \Carbon\Carbon::now()->startOfDay();

                                        // Lógica para Revisión Médica
                                        $estadoMedica = null;
                                        $colorMedica = null;
                                        if ($personal->proxima_revision_medica) {
                                            $proxMedica = \Carbon\Carbon::parse($personal->proxima_revision_medica)->startOfDay();
                                            $avisoMedica = $proxMedica->copy()->subDays(30); 

                                            if ($hoy->gt($proxMedica)) {
                                                $estadoMedica = 'Caducada';
                                                $colorMedica = 'profile-chip--danger';
                                            } elseif ($hoy->gte($avisoMedica)) {
                                                $estadoMedica = 'En Aviso';
                                                $colorMedica = 'profile-chip--pending';
                                            } else {
                                                $estadoMedica = 'Vigente';
                                                $colorMedica = 'profile-chip--ok';
                                            }
                                        }

                                        // Lógica para Graduación Óptica
                                        $estadoGrad = null;
                                        $colorGrad = null;
                                        if ($personal->proxima_graduacion) {
                                            $proxGrad = \Carbon\Carbon::parse($personal->proxima_graduacion)->startOfDay();
                                            $avisoGrad = $proxGrad->copy()->subDays(30);

                                            if ($hoy->gt($proxGrad)) {
                                                $estadoGrad = 'Caducada';
                                                $colorGrad = 'profile-chip--danger';
                                            } elseif ($hoy->gte($avisoGrad)) {
                                                $estadoGrad = 'En Aviso';
                                                $colorGrad = 'profile-chip--pending';
                                            } else {
                                                $estadoGrad = 'Vigente';
                                                $colorGrad = 'profile-chip--ok';
                                            }
                                        }
                                    @endphp
                                    <!-- Columna 2: Vigilancia de la Salud (Médico) -->
                                    <div style="display: flex; flex-direction: column; gap: 18px;">
                                        <div>
                                            <span>ÚLTIMA REVISIÓN MÉDICA</span>
                                            <strong>{{ optional($personal->ultima_revision_medica)->format('d M Y') ?: '—' }}</strong>
                                        </div>
                                        <div>
                                            <span>PRÓXIMA REVISIÓN MÉDICA</span>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <strong>{{ optional($personal->proxima_revision_medica)->format('d M Y') ?: '—' }}</strong>
                                                @if($estadoMedica)
                                                    <span class="profile-chip {{ $colorMedica }}" style="font-size: 0.6rem; padding: 2px 8px; margin: 0;">{{ $estadoMedica }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div>
                                            <span>CENTRO MÉDICO</span>
                                            <strong>{{ $personal->reconocido_en ?: '—' }}</strong>
                                        </div>
                                    </div>

                                    <!-- Columna 3: Salud Óptica (Gafas) -->
                                    <div style="display: flex; flex-direction: column; gap: 18px;">
                                        <div>
                                            <span>ÚLTIMA GRADUACIÓN</span>
                                            <strong>{{ optional($personal->ultima_graduacion)->format('d M Y') ?: '—' }}</strong>
                                        </div>
                                        <div>
                                            <span>PRÓXIMA GRADUACIÓN</span>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <strong>{{ optional($personal->proxima_graduacion)->format('d M Y') ?: '—' }}</strong>
                                                @if($estadoGrad)
                                                    <span class="profile-chip {{ $colorGrad }}" style="font-size: 0.6rem; padding: 2px 8px; margin: 0;">{{ $estadoGrad }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div>
                                            <span>ÓPTICA</span>
                                            <strong>{{ $personal->graduado_en ?: '—' }}</strong>
                                        </div>
                                    </div>
                                @else
                                    <div style="display: flex; flex-direction: column; gap: 18px; padding-left: 20px; border-left: 1px dashed #e2e8f0; grid-column: span 2;">
                                        <div style="color: #94a3b8; font-size: 0.85rem; padding-top: 10px;">
                                            <i class="fas fa-lock mb-2 fa-2x"></i><br>
                                            Datos médicos y de vigilancia de la salud protegidos (LOPD).<br>
                                            No dispones de los permisos necesarios.
                                        </div>
                                    </div>
                                @endcan

                                </div>
                            </article>

                            @if($personal->descripcion)
                                <article class="profile-card">
                                    <div class="profile-card__header">
                                        <div>
                                            <h3><i class="fas fa-clipboard-list"></i> Observaciones</h3>
                                            <p>Notas y comentarios adicionales sobre el trabajador</p>
                                        </div>
                                    </div>
                                    <div class="profile-card__body" style="padding: 20px;">
                                        <div style="color: var(--profile-ink); line-height: 1.6; white-space: pre-wrap; word-wrap: break-word;">
                                            {{ $personal->descripcion }}
                                        </div>
                                    </div>
                                </article>
                            @endif
                        </div>

                        <!-- PANEL 2: EQUIPAMIENTO (EPIS) -->
                        <div id="tab-epis" class="profile-tab-panel">
                            
                            <!-- BLOQUE 1: TALLAS -->
                            <article class="profile-card">
                                <div class="profile-card__header">
                                    <div>
                                        <h3><i class="fas fa-ruler-combined"></i> Tallas y EPIs</h3>
                                    </div>
                                </div>
                                
                            @can('personal.tallas')
                                <div class="profile-size-list">
                                    @foreach ($tallas as $talla)
                                        @php
                                            $isEmptySize = in_array(mb_strtolower(trim($talla['value'])), ['—', '-', 'sin necesidad', '']);
                                        @endphp
                                        <div class="profile-size-row">
                                            <div class="profile-size-label">
                                                <i class="fas {{ $talla['icon'] }}"></i>
                                                <span>{{ $talla['label'] }}</span>
                                            </div>
                                            <strong class="{{ $isEmptySize ? 'is-empty' : '' }}">
                                                {{ $talla['value'] }}
                                            </strong>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="profile-card__body" style="padding: 40px 20px; text-align: center; color: #64748b;">
                                    <i class="fas fa-lock fa-3x mb-3" style="color: #cbd5e1;"></i>
                                    <h4>Acceso Restringido</h4>
                                    <p>No tienes permisos para visualizar el equipamiento del trabajador.</p>
                                </div>
                            @endcan
                            </article>

                            @can('personal.edit') 
                            <!-- BLOQUE 2: EVALUACIÓN DE RIESGOS -->
                            <article class="profile-card" style="margin-top: 24px;">
                                <div class="profile-card__header">
                                    <div>
                                        <h3><i class="fas fa-file-contract"></i> Documento de Información de Riesgos</h3>
                                        <p>Sube el documento maestro firmado por el trabajador.</p>
                                    </div>
                                </div>
                                <div class="profile-card__body" style="padding: 20px;">
                                    @if(!empty($personal->documento_riesgos))
                                        <div style="margin-bottom: 20px; padding: 15px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                                            <div>
                                                <strong style="color: #166534;"><i class="fas fa-check-circle mr-2"></i> Riesgos firmados</strong>
                                            </div>
                                            <div style="display: flex; gap: 10px;">
                                                <a href="{{ Storage::url($personal->documento_riesgos) }}" target="_blank" class="profile-action profile-action--soft">
                                                    <i class="fas fa-eye"></i> Ver PDF
                                                </a>
                                                <form action="{{ route('personal.entregas_epi.destroy', [$personal->id, 'riesgos']) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar la Info de Riesgos? ESTO ELIMINARÁ TAMBIÉN LOS EPIs ASOCIADOS.');" style="margin: 0;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="profile-action profile-action--danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    @endif

                                    <form class="prl-upload-form" data-tipo="riesgos" data-personal-id="{{ $personal->id }}">
                                        <div class="custom-file-upload upload-wrapper-riesgos">
                                            <label for="archivo_riesgos" class="custom-file-btn">
                                                <i class="fas fa-upload"></i> <span class="btn-text">Seleccionar archivo</span>
                                            </label>
                                            <input type="file" id="archivo_riesgos" name="archivo" accept=".pdf,.jpg,.jpeg,.png" required class="prl-file-input">
                                            <span class="custom-file-name file-name-display">Ningún archivo seleccionado</span>
                                        </div>
                                        <button type="submit" class="profile-action profile-action--primary mt-3 btn-submit-prl">
                                            <i class="fas fa-save"></i> Guardar Riesgos
                                        </button>
                                    </form>
                                </div>
                            </article>

                            <!-- BLOQUE 3: DOCUMENTO DE EPIs -->
                            <article class="profile-card" style="margin-top: 24px;">
                                <div class="profile-card__header">
                                    <div>
                                        <h3><i class="fas fa-hard-hat"></i> Documento de EPIs del Puesto</h3>
                                        <p>Acuse de recibo de los equipos de protección individual.</p>
                                    </div>
                                </div>
                                <div class="profile-card__body" style="padding: 20px;">
                                    @if(!empty($personal->documento_epi))
                                        <div style="margin-bottom: 20px; padding: 15px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                                            <div>
                                                <strong style="color: #166534;"><i class="fas fa-check-circle mr-2"></i> EPIs firmados</strong>
                                            </div>
                                            <div style="display: flex; gap: 10px;">
                                                <a href="{{ Storage::url($personal->documento_epi) }}" target="_blank" class="profile-action profile-action--soft">
                                                    <i class="fas fa-eye"></i> Ver PDF
                                                </a>
                                                <form action="{{ route('personal.entregas_epi.destroy', [$personal->id, 'epi']) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar el documento de EPIs?');" style="margin: 0;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="profile-action profile-action--danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    @endif

                                    <form class="prl-upload-form" data-tipo="epi" data-personal-id="{{ $personal->id }}">
                                        <div class="custom-file-upload upload-wrapper-epi">
                                            <label for="archivo_epi" class="custom-file-btn">
                                                <i class="fas fa-upload"></i> <span class="btn-text">Seleccionar archivo</span>
                                            </label>
                                            <input type="file" id="archivo_epi" name="archivo" accept=".pdf,.jpg,.jpeg,.png" required class="prl-file-input">
                                            <span class="custom-file-name file-name-display">Ningún archivo seleccionado</span>
                                        </div>
                                        <button type="submit" class="profile-action profile-action--primary mt-3 btn-submit-prl">
                                            <i class="fas fa-save"></i> Guardar EPIs
                                        </button>
                                    </form>
                                </div>
                            </article>

                            <!-- BLOQUE 4: HISTORIAL DE SUBIDAS -->
                            <article class="profile-card" style="margin-top: 24px;">
                                <div class="profile-card__header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                                    <div>
                                        <h3 style="margin-bottom: 4px;"><i class="fas fa-history"></i> Registro Histórico de Documentos</h3>
                                        <p style="margin: 0; color: #64748b; font-size: 0.85rem;">Auditoría de todas las subidas realizadas en el perfil de este trabajador.</p>
                                    </div>
                                    
                                    <!-- Interruptor de Filtro Moderno -->
                                    <label class="prl-toggle-wrapper">
                                        <input type="checkbox" id="toggle-history-filter" class="prl-toggle-input" checked>
                                        <div class="prl-toggle-track">
                                            <div class="prl-toggle-dot"></div>
                                        </div>
                                        <span class="prl-toggle-text">Mostrar solo vigentes</span>
                                    </label>
                                </div>
                                
                                <div class="profile-card__body" style="padding: 0;">
                                    <div class="table-responsive">
                                        <table class="table profile-table" style="margin: 0;">
                                            <thead style="background: #f8fafc;">
                                                <tr>
                                                    <th style="padding: 12px 20px;">FECHA DE SUBIDA</th>
                                                    <th>TIPO DE DOCUMENTO</th>
                                                    <th>DOCUMENTO</th>
                                                    <th style="text-align: right; padding-right: 20px;">ACCIONES</th>
                                                </tr>
                                            </thead>
                                            <tbody id="historial-prl-body">
                                                @php
                                                    $vistoRiesgos = false;
                                                    $vistoEpi = false;
                                                @endphp

                                                @forelse($personal->historialPrl ?? [] as $registro)
                                                    @php
                                                        $esVigente = false;
                                                        if ($registro->tipo === 'riesgos' && !$vistoRiesgos) {
                                                            $esVigente = true;
                                                            $vistoRiesgos = true;
                                                        } elseif ($registro->tipo === 'epi' && !$vistoEpi) {
                                                            $esVigente = true;
                                                            $vistoEpi = true;
                                                        }
                                                    @endphp

                                                    <tr class="historial-row" data-is-latest="{{ $esVigente ? 'true' : 'false' }}" style="{{ !$esVigente ? 'display: none; background-color: #f8fafc; opacity: 0.7;' : '' }}">
                                                        <td style="padding: 12px 20px;"><strong>{{ $registro->created_at->format('d M Y - H:i') }}</strong></td>
                                                        <td>
                                                            @if($registro->tipo === 'riesgos')
                                                                <span class="profile-chip profile-chip--pending"><i class="fas fa-file-contract mr-1"></i> INFO. RIESGOS</span>
                                                            @else
                                                                <span class="profile-chip profile-chip--ok"><i class="fas fa-hard-hat mr-1"></i> EPIS PUESTO</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <!-- Botón PDF Mejorado -->
                                                            <a href="{{ Storage::url($registro->archivo_path) }}" target="_blank" class="prl-btn-pdf">
                                                                <i class="fas fa-file-pdf"></i> Abrir PDF
                                                            </a>
                                                        </td>
                                                        <td style="text-align: right; padding-right: 20px;">
                                                            <form action="{{ route('personal.historial_prl.destroy', [$personal->id, $registro->id]) }}" method="POST" onsubmit="return confirm('¿Eliminar este registro histórico?');" style="margin: 0; display: inline-block;">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" style="border: none; background: #fee2e2; width: 32px; height: 32px; border-radius: 6px; cursor: pointer; color: #ef4444; transition: all 0.2s;" title="Eliminar registro" onmouseover="this.style.background='#fecaca'; this.style.color='#dc2626'" onmouseout="this.style.background='#fee2e2'; this.style.color='#ef4444'">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="4" style="text-align: center; color: #94a3b8; padding: 30px;">
                                                            <i class="fas fa-folder-open mb-2" style="font-size: 2rem; color: #cbd5e1; display: block;"></i>
                                                            Aún no hay documentos registrados en el historial.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </article>
                            @endcan
                        </div>

                        <!-- PANEL 3: FORMACIÓN -->
                        <div id="tab-formacion" class="profile-tab-panel">
                        @can('cursos.edit')
                            <article class="profile-card">
                                <div class="profile-card__header">
                                    <div>
                                        <h3><i class="fas fa-circle-plus"></i> Asignar nuevo curso</h3>
                                        <p>Registra una nueva formación con fecha, aptitud y observaciones</p>
                                    </div>
                                </div>
                                <div class="profile-card__body" style="padding: 20px;">
                                    @if($cursosCatalogo->isEmpty())
                                        <div class="profile-empty-state" style="min-height: 100px;">
                                            <i class="fas fa-graduation-cap"></i>
                                            <strong>No hay cursos en el catálogo</strong>
                                            <span>Crea primero un curso para poder asignarlo al personal.</span>
                                        </div>
                                    @else
                                        <!-- Formulario preparado para ser capturado por JavaScript -->
                                        <form id="assign-course-form" data-personal-id="{{ $personal->id }}" class="profile-course-form" enctype="multipart/form-data">
                                            <div class="course-field">
                                                <label for="curso_id">Curso</label>
                                                <select id="curso_id" name="curso_id" required>
                                                    <option value="">Selecciona un curso</option>
                                                    @foreach($cursosCatalogo as $cursoCatalogo)
                                                        <option value="{{ $cursoCatalogo->id }}">{{ $cursoCatalogo->nombre }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="course-field">
                                                <label for="fecha_realizacion">Fecha realización</label>
                                                <input type="date" id="fecha_realizacion" name="fecha_realizacion">
                                            </div>

                                            <div class="course-field">
                                                <label for="apto">Apto</label>
                                                <select id="apto" name="apto">
                                                    <option value="1">Sí</option>
                                                    <option value="0">No</option>
                                                </select>
                                            </div>

                                            <!-- Campo para el diploma con diseño integrado -->
                                            <div class="course-field">
                                                <label>Diploma (Opcional)</label>
                                                <div class="custom-file-upload" id="upload-wrapper">
                                                    <label for="archivo_diploma" class="custom-file-btn">
                                                        <i class="fas fa-upload"></i> <span id="upload-btn-text">Seleccionar archivo</span>
                                                    </label>
                                                    <input type="file" id="archivo_diploma" name="archivo_diploma" accept=".pdf,.jpg,.jpeg,.png">
                                                    <span class="custom-file-name" id="upload-file-name">Ningún archivo seleccionado</span>
                                                </div>
                                            </div>

                                            <button type="submit" class="course-submit" id="assign-course-btn" style="grid-column: 1 / -1;">Asignar Curso y Subir Diploma</button>
                                        </form>
                                    @endif
                                </div>
                            </article>
                        @endcan

                            <article class="profile-card">
                                <div class="profile-card__header">
                                    <div>
                                        <h3><i class="fas fa-graduation-cap"></i> Formación y Cursos</h3>
                                        <p>Histórico de cursos asignados al trabajador</p>
                                    </div>
                                </div>
                                <div class="profile-card__body" style="padding: 20px;">
                                    <div class="profile-course-grid">
                                        @forelse($personal->cursos as $curso)
                                            <div class="profile-course-row">
                                                <div class="profile-course-row__meta">
                                                    <h4>{{ $curso->nombre }}</h4>
                                                    <p>
                                                        Realizado: {{ optional($curso->pivot->fecha_realizacion)->format('d M Y') ?: 'Sin fecha' }}
                                                        · {{ $curso->pivot->descripcion_aptitud ?: 'Sin observaciones' }}
                                                    </p>
                                                </div>
                                                <div class="profile-course-row__actions" style="display: flex; gap: 10px; align-items: center;">
                                                    
                                                    @if(!empty($curso->pivot->archivo_diploma))
                                                        <a href="{{ Storage::url($curso->pivot->archivo_diploma) }}" target="_blank" class="profile-chip" style="background: #eff6ff; color: #3b82f6; border-color: #bfdbfe; text-decoration: none;" title="Ver Diploma">
                                                            <i class="fas fa-file-pdf mr-1"></i> Diploma
                                                        </a>
                                                    @endif

                                                    <span class="profile-chip {{ ($curso->pivot->apto ?? false) ? 'profile-chip--ok' : 'profile-chip--pending' }}">
                                                        {{ ($curso->pivot->apto ?? false) ? 'Apto' : 'No apto' }}
                                                    </span>
                                                @can('cursos.view')
                                                    <button type="button" class="profile-icon-link js-open-history" data-curso-id="{{ $curso->id }}" data-curso-nombre="{{ $curso->nombre }}" title="Ver historial de renovaciones" style="border:0;background:transparent;cursor:pointer;color:#6366f1;">
                                                        <i class="fas fa-history"></i>
                                                    </button>
                                                @endcan

                                                @can('cursos.edit')
                                                    <form method="POST" action="{{ route('personal.cursos.destroy', [$personal->id, $curso->id]) }}" onsubmit="return confirm('¿Quitar este curso del trabajador?');" style="margin: 0;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="profile-icon-link" title="Quitar curso" style="border:0;background:transparent;cursor:pointer;">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                                </div>
                                            </div>
                                        @empty
                                            <div class="profile-empty-state">
                                                <i class="fas fa-book-open"></i>
                                                <strong>No hay cursos asignados</strong>
                                                <span>La formación aparecerá aquí cuando se vaya registrando.</span>
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </article>
                        </div>

                        <!-- PANEL 4: INVENTARIO -->
                        <div id="tab-inventario" class="profile-tab-panel">
                            <article class="profile-card profile-card--table">
                                <div class="profile-card__header">
                                    <div>
                                        <h3>Histórico de Salidas de Inventario</h3>
                                        <p>Registro detallado de material y EPIs entregados</p>
                                    </div>
                                </div>

                                <div style="padding: 20px; border-bottom: 1px solid var(--profile-line); background: #fafbfc;">
                                    <form id="filters-form" method="GET" style="display: contents;">
                                        <input type="hidden" name="tab" value="tab-inventario">
                                        <div class="profile-filters">
                                            <div class="profile-filter-group">
                                                <label for="fecha_desde">Desde</label>
                                                <input type="date" id="fecha_desde" name="fecha_desde" value="{{ request('fecha_desde') }}">
                                            </div>
                                            <div class="profile-filter-group">
                                                <label for="fecha_hasta">Hasta</label>
                                                <input type="date" id="fecha_hasta" name="fecha_hasta" value="{{ request('fecha_hasta') }}">
                                            </div>
                                            <div class="profile-filter-group">
                                                <label for="articulo">Buscar Artículo</label>
                                                <input type="text" id="articulo" name="articulo" placeholder="Nombre del artículo..." value="{{ request('articulo') }}">
                                            </div>
                                            <div style="display: flex; gap: 8px;">
                                                <button type="submit" class="profile-filter-btn">
                                                    <i class="fas fa-magnifying-glass"></i> Buscar
                                                </button>
                                                @if(request('fecha_desde') || request('fecha_hasta') || request('articulo'))
                                                    <a href="{{ route('personal.show', ['personal' => $personal->id, 'tab' => 'tab-inventario']) }}" class="profile-filter-reset" style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                                                        <i class="fas fa-times"></i> Limpiar
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <div class="table-responsive profile-table-wrap">
                                    <table class="table profile-table">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Artículo</th>
                                                <th>Cantidad</th>
                                                <th>OT relacionada</th>
                                                <th>Estado</th>
                                                <th>Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($historicoSalidas as $registro)
                                                <tr>
                                                    <td data-label="Fecha"><span class="profile-date">{{ $registro->fecha }}</span></td>
                                                    <td data-label="Artículo">
                                                        <div class="profile-article">
                                                            <span class="profile-article__icon"><i class="fas fa-box"></i></span>
                                                            <strong>{{ $registro->articulo }}</strong>
                                                        </div>
                                                    </td>
                                                    <td data-label="Cantidad"><strong class="profile-quantity">{{ $registro->cantidad }}</strong></td>
                                                    <td data-label="OT relacionada"><span class="profile-chip profile-chip--muted">{{ $registro->ot }}</span></td>
                                                    <td data-label="Estado"><span class="{{ $registro->estado_clase }}">{{ $registro->estado }}</span></td>
                                                    <td data-label="Acción">
                                                        <a href="{{ isset($registro->salida_id) ? route('inventario.salida.documento', $registro->salida_id) : '#' }}" class="profile-icon-link" title="Ver documento de salida">
                                                            <i class="far fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6">
                                                        <div class="profile-empty-state">
                                                            <i class="fas fa-box-open"></i>
                                                            <strong>No hay registros para mostrar</strong>
                                                            <span>Cuando existan salidas de inventario aparecerán aquí.</span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </article>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <a href="{{ route('personal.index') }}" class="profile-fab" title="Volver al listado">
            <i class="fas fa-arrow-left"></i>
        </a>
    </section>

    @if(auth()->user() && auth()->user()->role === 'superadmin')
        <div class="profile-modal" id="delete-personal-modal" aria-hidden="true" role="dialog" aria-modal="true">
        @can('personal.delete')
            <div class="profile-modal__panel">
                <h2 class="profile-modal__title">Seguro que deseas eliminar</h2>
                <p class="profile-modal__text">
                    Nombre del trabajador:
                    <span class="profile-modal__name">{{ $personal->name }} {{ $personal->apellido }}</span>
                </p>
                <form method="POST" action="{{ route('personal.destroy', $personal->id) }}" class="profile-modal__actions">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="profile-modal__btn" data-close-modal>Cancelar</button>
                    <button type="submit" class="profile-modal__btn profile-modal__btn--danger">Eliminar</button>
                </form>
            </div>
        </div>
        @endcan
    @endif

    <!-- MODAL DE HISTORIAL DE RENOVACIONES -->
    <div class="profile-modal" id="history-modal" aria-hidden="true" role="dialog" aria-modal="true" style="z-index: 1060;">
        <div class="profile-modal__panel" style="max-width: 500px;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px; margin-bottom: 15px;">
                <h3 style="font-size: 1.1rem; font-weight: 800; color: #173e67; margin: 0;">Historial de Renovaciones</h3>
                <button type="button" data-close-history style="background:none; border:none; font-size:1.2rem; color:#94a3b8; cursor:pointer;"><i class="fas fa-times"></i></button>
            </div>
            
            <p style="font-size: 0.9rem; color: #64748b; margin-bottom: 15px;">
                Curso: <strong id="history-curso-name" style="color: #0f172a;">Cargando...</strong>
            </p>

            <div id="history-timeline" style="display: flex; flex-direction: column; gap: 10px;">
                <div style="text-align: center; padding: 20px; color: #64748b;"><i class="fas fa-spinner fa-spin"></i> Cargando datos...</div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        (function() {
            const openBtn = document.getElementById('open-delete-personal');
            const modal = document.getElementById('delete-personal-modal');

            if (!openBtn || !modal) return;

            const closeButtons = modal.querySelectorAll('[data-close-modal]');

            const openModal = () => {
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
            };

            const closeModal = () => {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
            };

            openBtn.addEventListener('click', openModal);
            closeButtons.forEach((btn) => btn.addEventListener('click', closeModal));
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeModal();
                }
            });
        })();

        // --- LÓGICA DE LAS PESTAÑAS (TABS) ---
        (function() {
            const tabButtons = document.querySelectorAll('.profile-tab-btn');
            const tabPanels = document.querySelectorAll('.profile-tab-panel');

            const activateTab = (targetId) => {
                tabButtons.forEach(btn => btn.classList.remove('is-active'));
                tabPanels.forEach(panel => panel.classList.remove('is-active'));

                const activeBtn = document.querySelector(`.profile-tab-btn[data-target="${targetId}"]`);
                const activePanel = document.getElementById(targetId);

                if (activeBtn && activePanel) {
                    activeBtn.classList.add('is-active');
                    activePanel.classList.add('is-active');
                }

                const url = new URL(window.location);
                url.searchParams.set('tab', targetId);
                window.history.replaceState({}, '', url);
            };

            tabButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    activateTab(btn.getAttribute('data-target'));
                });
            });

            const urlParams = new URLSearchParams(window.location.search);
            const tabFromUrl = urlParams.get('tab');
            if (tabFromUrl) {
                activateTab(tabFromUrl);
            }
        })();

        // --- LÓGICA PARA ASIGNAR UN CURSO VÍA AJAX ---
        (function() {
            const assignForm = document.getElementById('assign-course-form');
            
            if (assignForm) {
                assignForm.addEventListener('submit', async function(e) {
                    e.preventDefault(); 
                    
                    const btn = document.getElementById('assign-course-btn');
                    const originalText = btn.innerHTML;
                    const originalBg = btn.style.backgroundColor; 
                    
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Subiendo archivo y asignando...';
                    btn.disabled = true;

                    const personalId = this.getAttribute('data-personal-id');
                    const formData = new FormData(this);
                    formData.append('_method', 'PUT'); 
                    
                    try {
                        const response = await fetch(`{{ url('personal') }}/${personalId}/cursos`, {
                            method: 'POST', 
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: formData 
                        });

                        if (response.ok) {
                            btn.innerHTML = '<i class="fas fa-check-circle"></i> ¡Curso y archivo guardados con éxito!';
                            btn.style.backgroundColor = '#10b981'; 
                            btn.style.borderColor = '#10b981';
                            btn.style.color = '#fff';
                            
                            setTimeout(() => {
                                const url = new URL(window.location);
                                url.searchParams.set('tab', 'tab-formacion');
                                window.location.href = url.toString();
                            }, 1200);
                        } else {
                            alert('Error al asignar el curso. Es posible que el trabajador ya tenga este curso asignado.');
                            btn.innerHTML = originalText;
                            btn.style.backgroundColor = originalBg;
                            btn.disabled = false;
                        }
                    } catch (error) {
                        alert('Error de conexión al intentar asignar el curso.');
                        btn.innerHTML = originalText;
                        btn.style.backgroundColor = originalBg;
                        btn.disabled = false;
                    }
                });
            }
        })();

        // --- LÓGICA DEL HISTORIAL DE CURSOS (RENOVACIONES) ---
        (function() {
            const btnsOpen = document.querySelectorAll('.js-open-history');
            const modalHistory = document.getElementById('history-modal');
            
            if (!modalHistory || btnsOpen.length === 0) return;

            const btnClose = modalHistory.querySelectorAll('[data-close-history]');
            const timelineContainer = document.getElementById('history-timeline');
            const titleCurso = document.getElementById('history-curso-name');
            const personalId = '{{ $personal->id }}';

            const closeHistoryModal = () => {
                modalHistory.classList.remove('is-open');
                modalHistory.setAttribute('aria-hidden', 'true');
            };

            btnClose.forEach(btn => btn.addEventListener('click', closeHistoryModal));

            btnsOpen.forEach(btn => {
                btn.addEventListener('click', async function() {
                    const cursoId = this.getAttribute('data-curso-id');
                    const cursoNombre = this.getAttribute('data-curso-nombre');
                    
                    titleCurso.textContent = cursoNombre;
                    timelineContainer.innerHTML = '<div style="text-align: center; padding: 20px; color: #64748b;"><i class="fas fa-spinner fa-spin"></i> Consultando registros...</div>';
                    modalHistory.classList.add('is-open');
                    modalHistory.setAttribute('aria-hidden', 'false');

                    try {
                        const response = await fetch(`/personal/${personalId}/cursos/${cursoId}/historial`);
                        if (!response.ok) throw new Error('Error al obtener datos');
                        
                        const data = await response.json();
                        timelineContainer.innerHTML = ''; 

                        if (data.actual) {
                            const badgeClase = data.actual.apto ? 'profile-chip--ok' : 'profile-chip--danger';
                            const badgeTexto = data.actual.apto ? 'Apto' : 'No Apto';
                            
                            const diplomaBtn = data.actual.diploma_url 
                                ? `<a href="${data.actual.diploma_url}" target="_blank" style="margin-right: 12px; font-size: 0.75rem; text-decoration: none; color: #166534; background: #dcfce7; padding: 4px 10px; border-radius: 6px; font-weight: 800; border: 1px solid #bbf7d0; transition: all 0.2s;"><i class="fas fa-file-pdf"></i> Ver certificado</a>` 
                                : '';

                            timelineContainer.innerHTML += `
                                <div style="padding: 12px 16px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <div style="font-size: 0.75rem; font-weight: 800; color: #166534; text-transform: uppercase;">Certificado Actual</div>
                                        <div style="font-weight: 700; color: #14532d; font-size: 1.1rem;">${data.actual.fecha}</div>
                                    </div>
                                    <div style="display: flex; align-items: center;">
                                        ${diplomaBtn}
                                        <span class="profile-chip ${badgeClase}" style="margin: 0;">${badgeTexto}</span>
                                    </div>
                                </div>
                            `;
                        }

                        if (data.historico && data.historico.length > 0) {
                            data.historico.forEach(item => {
                                const chipClase = item.apto ? 'profile-chip--ok' : 'profile-chip--danger';
                                const chipTexto = item.apto ? 'Apto' : 'No Apto';
                                
                                const diplomaBtn = item.diploma_url 
                                    ? `<a href="${item.diploma_url}" target="_blank" style="margin-right: 12px; font-size: 0.75rem; text-decoration: none; color: #475569; background: #f1f5f9; padding: 4px 10px; border-radius: 6px; font-weight: 800; border: 1px solid #e2e8f0; transition: all 0.2s;"><i class="fas fa-file-pdf"></i> Ver certificado</a>` 
                                    : '';
                                
                                timelineContainer.innerHTML += `
                                    <div style="padding: 12px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; opacity: 0.85;">
                                        <div>
                                            <div style="font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Registro Archivado</div>
                                            <div style="font-weight: 700; color: #475569; font-size: 1rem;">${item.fecha}</div>
                                        </div>
                                        <div style="display: flex; align-items: center;">
                                            ${diplomaBtn}
                                            <span class="profile-chip ${chipClase}" style="margin: 0; background: #e2e8f0; color: #64748b; border-color: transparent;">${chipTexto}</span>
                                        </div>
                                    </div>
                                `;
                            });
                        } else {
                            if (!data.actual) {
                                timelineContainer.innerHTML = '<div style="text-align: center; color: #64748b;">No hay registros de fechas para este curso.</div>';
                            } else {
                                timelineContainer.innerHTML += '<div style="text-align: center; color: #94a3b8; font-size: 0.85rem; margin-top: 10px;">No existen renovaciones anteriores.</div>';
                            }
                        }

                    } catch (error) {
                        timelineContainer.innerHTML = '<div style="text-align: center; color: #ef4444; padding: 20px;">Error al cargar el historial. Verifica la conexión.</div>';
                    }
                });
            });
        })();

        // --- LÓGICA PARA EL DISEÑO DEL INPUT FILE (Cursos) ---
        (function() {
            const fileInput = document.getElementById('archivo_diploma');
            const fileNameDisplay = document.getElementById('upload-file-name');
            const wrapper = document.getElementById('upload-wrapper');
            const btnText = document.getElementById('upload-btn-text');

            if (fileInput && fileNameDisplay && wrapper && btnText) {
                fileInput.addEventListener('change', function() {
                    if (this.files && this.files.length > 0) {
                        const file = this.files[0];
                        
                        if (file.size > 10 * 1024 * 1024) {
                            alert('El archivo es demasiado grande. Máximo 10MB.');
                            this.value = ''; 
                            wrapper.classList.remove('has-file');
                            fileNameDisplay.innerHTML = 'Ningún archivo seleccionado';
                            btnText.innerHTML = 'Seleccionar archivo';
                            return;
                        }

                        fileNameDisplay.innerHTML = `<i class="fas fa-check text-success" style="margin-right: 4px;"></i> ${file.name}`;
                        fileNameDisplay.style.color = '#166534'; 
                        wrapper.classList.add('has-file');
                        btnText.innerHTML = 'Cambiar archivo';
                    } else {
                        wrapper.classList.remove('has-file');
                        fileNameDisplay.innerHTML = 'Ningún archivo seleccionado';
                        fileNameDisplay.style.color = '#94a3b8';
                        btnText.innerHTML = 'Seleccionar archivo';
                    }
                });
            }
        })();

        // --- LÓGICA PARA FORMULARIOS Y ARCHIVOS DE PRL (Riesgos y EPIs) ---
        (function() {
            // Manejador visual para los inputs de archivo
            document.querySelectorAll('.prl-file-input').forEach(input => {
                input.addEventListener('change', function() {
                    const wrapper = this.closest('.custom-file-upload');
                    const fileNameDisplay = wrapper.querySelector('.file-name-display');
                    const btnText = wrapper.querySelector('.btn-text');

                    if (this.files && this.files.length > 0) {
                        const file = this.files[0];
                        if (file.size > 10 * 1024 * 1024) {
                            alert('El archivo supera el límite permitido de 10MB.');
                            this.value = ''; 
                            wrapper.classList.remove('has-file');
                            fileNameDisplay.innerHTML = 'Ningún archivo seleccionado';
                            btnText.innerHTML = 'Seleccionar archivo';
                            return;
                        }
                        fileNameDisplay.innerHTML = `<i class="fas fa-check text-success" style="margin-right: 4px;"></i> ${file.name}`;
                        fileNameDisplay.style.color = '#166534';
                        wrapper.classList.add('has-file');
                        btnText.innerHTML = 'Cambiar archivo';
                    } else {
                        wrapper.classList.remove('has-file');
                        fileNameDisplay.innerHTML = 'Ningún archivo seleccionado';
                        fileNameDisplay.style.color = '#94a3b8';
                        btnText.innerHTML = 'Seleccionar archivo';
                    }
                });
            });

            // Manejador de envío AJAX para ambos formularios
            document.querySelectorAll('.prl-upload-form').forEach(form => {
                form.addEventListener('submit', async function(e) {
                    e.preventDefault(); 
                    
                    const btn = this.querySelector('.btn-submit-prl');
                    const originalText = btn.innerHTML;
                    const originalBg = btn.style.backgroundColor; 
                    
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
                    btn.disabled = true;

                    const personalId = this.getAttribute('data-personal-id');
                    const tipo = this.getAttribute('data-tipo');
                    
                    const formData = new FormData(this);
                    formData.append('tipo_documento', tipo);
                    
                    try {
                        const response = await fetch(`/personal/${personalId}/entregas-epi`, {
                            method: 'POST', 
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: formData 
                        });

                        if (response.ok) {
                            btn.innerHTML = '<i class="fas fa-check-circle"></i> ¡Documento guardado!';
                            btn.style.backgroundColor = '#10b981'; 
                            btn.style.color = '#fff';
                            
                            setTimeout(() => {
                                const url = new URL(window.location);
                                url.searchParams.set('tab', 'tab-epis'); 
                                window.location.href = url.toString();
                            }, 1000);
                        } else {
                            const errorData = await response.json();
                            alert(errorData.message || 'Error al guardar el documento. Verifica los datos.');
                            btn.innerHTML = originalText;
                            btn.style.backgroundColor = originalBg;
                            btn.disabled = false;
                        }
                    } catch (error) {
                        alert('Error de conexión al intentar comunicarse con el servidor.');
                        btn.innerHTML = originalText;
                        btn.style.backgroundColor = originalBg;
                        btn.disabled = false;
                    }
                });
            });
        })();

        // --- LÓGICA DEL FILTRO DE HISTORIAL PRL ---
        (function() {
            const toggleFilter = document.getElementById('toggle-history-filter');
            const rows = document.querySelectorAll('.historial-row');

            if (toggleFilter) {
                toggleFilter.addEventListener('change', function() {
                    const soloVigentes = this.checked;

                    rows.forEach(row => {
                        const isLatest = row.getAttribute('data-is-latest') === 'true';
                        
                        if (soloVigentes && !isLatest) {
                            row.style.display = 'none'; // Ocultamos los antiguos
                        } else {
                            row.style.display = ''; // Mostramos todos
                        }
                    });
                });
            }
        })();
    </script>
@endsection