@extends('adminlte::page')

@section('title', 'Configurar Alertas')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .config-page { padding: 18px; font-family: 'Manrope', sans-serif; }
        
        .config-layout { display: grid; grid-template-columns: 1.2fr 1fr; gap: 24px; max-width: 1200px; margin: 0 auto; align-items: start; }
        @media (max-width: 900px) { .config-layout { grid-template-columns: 1fr; } }

        .config-card { background: #fff; border: 1px solid #e7ecf3; border-radius: 18px; box-shadow: 0 16px 30px rgba(15, 23, 42, .06); padding: 24px; }
        .config-header { margin-bottom: 24px; }
        .config-header h2 { color: #173e67; font-weight: 800; font-size: 1.5rem; margin-bottom: 5px; }
        .config-header p { color: #667085; font-size: 0.9rem; margin: 0; line-height: 1.4; }
        .config-field label { display: block; font-size: .76rem; font-weight: 800; text-transform: uppercase; letter-spacing: .12em; color: #8a98ab; margin-bottom: 8px; }
        .config-field input:not([type="checkbox"]), .config-field select { width: 100%; border: 1px solid #dbe3ef; border-radius: 12px; padding: 12px 14px; font-size: 0.95rem; font-weight: 600; color: #173e67; transition: all 0.2s; height: 46px; }
        .config-field input:focus, .config-field select:focus { outline: none; border-color: #ea580c; box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.1); }
        
        .config-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px; }
        .config-btn { border: 0; border-radius: 12px; padding: 12px 18px; font-weight: 800; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s; }
        .config-btn--soft { background: #eef3f8; color: #173e67; }
        .config-btn--soft:hover { background: #dbe3ef; color: #173e67; text-decoration: none; }
        .config-btn--primary { background: linear-gradient(135deg, #ea580c, #c2410c); color: #fff; }
        .config-btn--primary:hover { background: linear-gradient(135deg, #c2410c, #9a3412); color: #fff; text-decoration: none; }
        .config-btn--blue { background: linear-gradient(135deg, #173e67, #2f6b9c); color: #fff; }
        .config-btn--blue:hover { background: linear-gradient(135deg, #0f2a47, #1f4f7a); color: #fff; }
        
        .email-list-container { margin-top: 24px; border-top: 1px solid #eef2f7; padding-top: 20px; }
        .email-list-title { display: block; font-size: .76rem; font-weight: 800; text-transform: uppercase; letter-spacing: .12em; color: #8a98ab; margin-bottom: 12px; }
        .email-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; margin-bottom: 8px; transition: all 0.2s; }
        .email-item:hover { border-color: #cbd5e1; background: #fff; box-shadow: 0 4px 6px rgba(15, 23, 42, 0.02); }
        .email-item-text { font-weight: 700; color: #173e67; font-size: 0.9rem; }
        .email-item-btn { color: #94a3b8; background: none; border: none; cursor: pointer; padding: 4px; border-radius: 6px; transition: all 0.2s; }
        .email-item-btn:hover { color: #ef4444; background: #fee2e2; }
        
        .email-scroll-area { max-height: 250px; overflow-y: auto; padding-right: 6px; }
        .email-scroll-area::-webkit-scrollbar { width: 6px; }
        .email-scroll-area::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 10px; }
        .email-scroll-area::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
@endsection

@section('content')
    <section class="config-page">
        
        <!-- Bloque superior para alertas y botón de regreso -->
        <div style="max-width: 1200px; margin: 0 auto 20px; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 16px;">
            
            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <a href="{{ route('cursos.index') }}" class="config-btn config-btn--soft" style="padding: 10px 16px;">
                    <i class="fas fa-arrow-left mr-2"></i> Volver al catálogo
                </a>
                
            @can('cursos.alertas')
                <!-- Botón Manual 1: Caducidades -->
                <form action="{{ route('cursos.config.alertas.manual') }}" method="POST" style="margin: 0;" onsubmit="return confirm('¿Forzar el escaneo y envío del reporte de CADUCIDADES ahora mismo?');">
                    @csrf
                    <button type="submit" class="config-btn config-btn--primary" style="padding: 10px 16px;" title="Enviar listado de cursos caducados y próximos a caducar">
                        <i class="fas fa-calendar-times mr-2"></i> Reporte Caducidades
                    </button>
                </form>

                <!-- Botón Manual 2: Pendientes de Revisión -->
                <form action="{{ route('cursos.config.alertas.manual_pendientes') }}" method="POST" style="margin: 0;" onsubmit="return confirm('¿Forzar el envío del aviso de trabajadores PENDIENTES DE REVISIÓN ahora mismo?');">
                    @csrf
                    <button type="submit" class="config-btn config-btn--blue" style="padding: 10px 16px;" title="Enviar listado de nuevas altas y reactivaciones sin revisar">
                        <i class="fas fa-user-clock mr-2"></i> Aviso Pendientes
                    </button>
                </form>
            @endcan
            </div>

            <div style="flex: 1; max-width: 400px; margin-left: auto;">
                @if(session('success'))
                    <div class="alert alert-success mb-0" style="border-radius: 10px; font-weight: 600; padding: 10px 16px;">
                        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger mb-0" style="border-radius: 10px; font-weight: 600; padding: 10px 16px;">
                        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
                    </div>
                @endif
            </div>
        </div>

        <div class="config-layout">
            
            <!-- COLUMNA IZQUIERDA: DESTINATARIOS -->
            <div class="config-card">
                <div class="config-header">
                    <h2>Destinatarios (PRL)</h2>
                    <p>Añade los correos del personal que debe recibir los informes automatizados.</p>
                </div>

            @can('cursos.alertas')
                <form action="{{ route('cursos.config.alertas.store') }}" method="POST">
                    @csrf
                    <div class="config-field">
                        <label for="email">Añadir destinatario</label>
                        <div class="input-group">
                            <div style="display: flex; align-items: stretch; width: 100%; height: 46px;">
                                <div style="display: flex; align-items: center; justify-content: center; background: #f8fafc; border: 1px solid #dbe3ef; border-right: none; border-radius: 12px 0 0 12px; padding: 0 16px;">
                                    <i class="fas fa-envelope text-muted"></i>
                                </div>
                                <input type="email" id="email" name="email" style="flex: 1; border: 1px solid #dbe3ef; border-left: none; border-right: none; border-radius: 0; margin: 0; padding: 0 14px; outline: none; font-weight: 600; color: #173e67; font-family: inherit;" placeholder="Ej: tecnico@moncobra.com" required autocomplete="off">
                                <button type="submit" class="config-btn config-btn--primary" style="border-radius: 0 12px 12px 0; margin: 0; padding: 0 20px; box-shadow: none;" title="Añadir correo">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        @error('email') <small class="text-danger mt-2 d-block" style="font-weight: 700;">{{ $message }}</small> @enderror
                    </div>
                </form>
            @endcan

                <div class="email-list-container">
                    <span class="email-list-title">Lista de envío ({{ count($emails) }})</span>
                    
                    <div class="email-scroll-area">
                        @forelse($emails as $emailAddress)
                            <div class="email-item">
                                <span class="email-item-text">
                                    <i class="fas fa-user-circle text-muted mr-2"></i> {{ $emailAddress }}
                                </span>
                            
                            @can('cursos.alertas')
                                <form action="{{ route('cursos.config.alertas.destroy') }}" method="POST" style="margin: 0;" onsubmit="return confirm('¿Seguro que deseas eliminar este correo de la lista?');">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="email_to_remove" value="{{ $emailAddress }}">
                                    <button type="submit" class="email-item-btn" title="Eliminar correo">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            @endcan
                            </div>
                        @empty
                            <div style="padding: 24px; text-align: center; border: 1px dashed #cbd5e1; border-radius: 12px; background: #f8fafc;">
                                <i class="fas fa-inbox mb-2 text-muted" style="font-size: 1.5rem;"></i>
                                <p style="color: #64748b; font-size: 0.95rem; margin: 0; font-weight: 600;">
                                    No hay destinatarios configurados.
                                </p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- COLUMNA DERECHA: HORARIO E INTERRUPTORES -->
            <div class="config-card">
                <div class="config-header">
                    <h2>Frecuencia y Tipos de Aviso</h2>
                    <p>Configura qué correos y cuándo se enviarán automáticamente a la lista de destinatarios.</p>
                </div>

            @can('cursos.alertas')
                <form action="{{ route('cursos.config.alertas.horario') }}" method="POST">
                    @csrf
                    
                    <div class="config-field">
                        <label for="dia_envio">Día de la semana</label>
                        <select id="dia_envio" name="dia_envio">
                            <option value="1" @selected($dia == '1')>Lunes</option>
                            <option value="2" @selected($dia == '2')>Martes</option>
                            <option value="3" @selected($dia == '3')>Miércoles</option>
                            <option value="4" @selected($dia == '4')>Jueves</option>
                            <option value="5" @selected($dia == '5')>Viernes</option>
                            <option value="6" @selected($dia == '6')>Sábado</option>
                            <option value="7" @selected($dia == '7')>Domingo</option>
                        </select>
                    </div>
                    
                    <div class="config-field" style="margin-top: 20px;">
                        <label for="hora_envio">Hora de ejecución</label>
                        <input type="time" id="hora_envio" name="hora_envio" value="{{ old('hora_envio', $hora) }}" required>
                    </div>

                    <!-- NUEVO: Interruptores de tipos de correo -->
                    <div class="config-field" style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #eef2f7;">
                        <label>Tipos de correo a enviar</label>
                        
                        <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; margin-bottom: 12px; text-transform: none; letter-spacing: normal;">
                            <input type="hidden" name="enviar_caducidades" value="0">
                            <!-- El backend pasará la variable $enviarCaducidades. Si no existe, por defecto es true -->
                            <input type="checkbox" name="enviar_caducidades" value="1" @checked($enviarCaducidades ?? true) style="width: 20px; height: 20px; margin-top: 2px;">
                            <div>
                                <strong style="color: #173e67; font-size: 0.95rem; display: block;">Reporte de Caducidades</strong>
                                <span style="color: #64748b; font-size: 0.85rem; font-weight: 500;">Listado de cursos caducados y próximos a caducar.</span>
                            </div>
                        </label>

                        <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; margin-bottom: 0; text-transform: none; letter-spacing: normal;">
                            <input type="hidden" name="enviar_pendientes" value="0">
                            <!-- El backend pasará la variable $enviarPendientes. Si no existe, por defecto es true -->
                            <input type="checkbox" name="enviar_pendientes" value="1" @checked($enviarPendientes ?? true) style="width: 20px; height: 20px; margin-top: 2px;">
                            <div>
                                <strong style="color: #173e67; font-size: 0.95rem; display: block;">Aviso de Pendientes de Revisión</strong>
                                <span style="color: #64748b; font-size: 0.85rem; font-weight: 500;">Aviso de trabajadores nuevos/reactivados sin gestionar.</span>
                            </div>
                        </label>
                    </div>
                    
                    <div class="config-actions">
                        <button type="submit" class="config-btn config-btn--blue w-100" style="padding: 14px;">
                            <i class="fas fa-save mr-2"></i> Guardar Configuración
                        </button>
                    </div>
                </form>
            @endcan
            </div>

        </div>
    </section>
@endsection