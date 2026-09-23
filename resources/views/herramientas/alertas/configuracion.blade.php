@extends('adminlte::page')

@section('title', 'Configuración de Alertas automatizadas')

@section('content_header')
<div style="display: flex; gap: 10px; margin-bottom: 20px;">
    <a href="{{ route('alertas.index') }}" class="config-btn config-btn-light">
        <i class="fas fa-arrow-left"></i> Volver al catálogo
    </a>
    <a href="{{ route('alertas.enviar', ['tipo' => 'caducidades']) }}" class="config-btn config-btn-orange">
        <i class="fas fa-calendar-times"></i> Reporte Caducidades
    </a>
    <a href="{{ route('alertas.enviar', ['tipo' => 'averias']) }}" class="config-btn config-btn-navy">
        <i class="fas fa-user-clock"></i> Partes de Avería (semanal)
    </a>
</div>
@stop

@section('content')
<style>
    :root {
        --cfg-navy: #002442; --cfg-ink: #191c1e; --cfg-bg: #f4f6f9;
        --cfg-low: #eef2f5; --cfg-muted: #687681;
        --cfg-orange: #dca54a; --cfg-orange-dark: #b68532;
    }
    
    .content-wrapper { background: var(--cfg-bg); }
    
    .config-btn { border: 0; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 16px; font-size: 13px; font-weight: 800; cursor: pointer; text-decoration: none; transition: 0.2s; }
    .config-btn-light { background: #fff; color: var(--cfg-navy); box-shadow: 0 4px 12px rgba(25,28,30,0.04); }
    .config-btn-light:hover { background: var(--cfg-low); }
    .config-btn-orange { background: #e06c2b; color: #fff; box-shadow: 0 4px 12px rgba(224,108,43,0.2); }
    .config-btn-navy { background: var(--cfg-navy); color: #fff; box-shadow: 0 4px 12px rgba(0,36,66,0.2); }
    .config-btn-main { background: linear-gradient(110deg, #002442, #155785); color: #fff; width: 100%; box-shadow: 0 6px 16px rgba(0,36,66,0.2); margin-top: 20px; }

    .cfg-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start; }
    @media(max-width: 900px) { .cfg-grid { grid-template-columns: 1fr; } }

    .cfg-card { background: #fff; box-shadow: 0 8px 32px rgba(25,28,30,0.04); border-radius: 12px; padding: 30px; }
    .cfg-card h2 { font-size: 20px; font-weight: 800; color: var(--cfg-ink); margin: 0 0 6px; }
    .cfg-card p { font-size: 13px; color: var(--cfg-muted); margin: 0 0 24px; }
    
    .cfg-label { display: block; font-size: 10px; text-transform: uppercase; letter-spacing: 0.1em; font-weight: 800; color: #7b8991; margin-bottom: 8px; }
    
    .cfg-input-group { display: flex; border: 1px solid #dce4e9; border-radius: 6px; overflow: hidden; margin-bottom: 24px; }
    .cfg-input-group span { display: grid; place-items: center; width: 40px; background: #fff; color: #87949b; font-size: 14px; }
    .cfg-input-group input, .cfg-input-group select { border: 0; flex: 1; padding: 12px; font-size: 13px; color: var(--cfg-ink); outline: none; }
    .cfg-input-group .cfg-add-btn { border: 0; background: #e06c2b; color: #fff; width: 45px; display: grid; place-items: center; cursor: pointer; font-size: 16px; transition: 0.2s; }
    .cfg-input-group .cfg-add-btn:hover { background: #c25b22; }

    .cfg-control { display: block; width: 100%; border: 1px solid #dce4e9; border-radius: 6px; padding: 12px; font-size: 13px; color: var(--cfg-ink); margin-bottom: 20px; outline: none; }
    .cfg-control:focus { border-color: var(--cfg-navy); }

    .cfg-email-list { background: #fdfdfe; border: 1px solid #edf0f2; border-radius: 6px; min-height: 60px; }
    .cfg-email-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; border-bottom: 1px solid #edf0f2; font-size: 13px; color: var(--cfg-ink); font-weight: 600; }
    .cfg-email-item:last-child { border-bottom: 0; }
    .cfg-email-item i.user-icon { color: #87949b; margin-right: 8px; font-size: 14px; }
    .cfg-email-item button { border: 0; background: transparent; color: #c92328; cursor: pointer; opacity: 0.6; transition: 0.2s; }
    .cfg-email-item button:hover { opacity: 1; }
    .cfg-empty { padding: 20px; text-align: center; font-size: 12px; color: #87949b; }

    .cfg-checkbox { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 16px; cursor: pointer; }
    .cfg-checkbox input { width: 18px; height: 18px; margin-top: 2px; cursor: pointer; accent-color: var(--cfg-navy); }
    .cfg-checkbox div strong { display: block; font-size: 14px; color: var(--cfg-ink); font-weight: 800; }
    .cfg-checkbox div span { font-size: 12px; color: var(--cfg-muted); }
</style>

@if(session('success'))
    <div class="alert alert-success" style="background: #dff3e9; color: #08734e; border: 0; font-weight: 700; font-size: 13px;">
        <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
    </div>
@endif

<form method="POST" action="{{ route('alertas.configuracion.store') }}">
    @csrf
    <div class="cfg-grid">
        <!-- Tarjeta 1: Destinatarios -->
        <div class="cfg-card">
            <h2>Destinatarios (Mantenimiento)</h2>
            <p>Añade los correos del personal que debe recibir los informes automatizados.</p>

            <label class="cfg-label">AÑADIR DESTINATARIO</label>
            <div class="cfg-input-group">
                <span><i class="fas fa-envelope"></i></span>
                <input type="email" id="email_input" placeholder="Ej: jefe.taller@empresa.com">
                <button type="button" id="btn_add_email" class="cfg-add-btn"><i class="fas fa-plus"></i></button>
            </div>

            <label class="cfg-label" id="list_counter">LISTA DE ENVÍO ({{ count($config->destinatarios ?? []) }})</label>
            <div class="cfg-email-list" id="email_list">
                @forelse($config->destinatarios ?? [] as $email)
                    <div class="cfg-email-item">
                        <div><i class="fas fa-user-circle user-icon"></i> {{ $email }}</div>
                        <input type="hidden" name="destinatarios[]" value="{{ $email }}">
                        <button type="button" onclick="removeEmail(this)"><i class="fas fa-trash"></i></button>
                    </div>
                @empty
                    <div class="cfg-empty" id="empty_state">No hay destinatarios configurados.</div>
                @endforelse
            </div>
        </div>

        <!-- Tarjeta 2: Configuración -->
        <div class="cfg-card">
            <h2>Frecuencia y Tipos de Aviso</h2>
            <p>Configura qué correos y cuándo se enviarán automáticamente a la lista.</p>

            <label class="cfg-label">DÍA DE LA SEMANA</label>
            <select name="dia_semana" class="cfg-control">
                @foreach(['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo', 'Diario'] as $dia)
                    <option value="{{ $dia }}" @selected($config->dia_semana === $dia)>{{$dia }}</option>
                @endforeach
            </select>

            <label class="cfg-label">HORA DE EJECUCIÓN</label>
            <div style="position: relative;">
                <input type="time" name="hora_ejecucion" class="cfg-control" value="{{ \Carbon\Carbon::parse($config->hora_ejecucion)->format('H:i') }}" required>
            </div>

            <label class="cfg-label" style="margin-top: 10px;">TIPOS DE CORREO A ENVIAR</label>
            
            <label class="cfg-checkbox">
                <input type="checkbox" name="tipos_reporte[]" value="caducidades" @checked(in_array('caducidades', $config->tipos_reporte ?? []))>
                <div>
                    <strong>Reporte de Caducidades Críticas</strong>
                    <span>Inspecciones legales (OCA), Fin de Soporte (EOL) y Calibraciones vencidas.</span>
                </div>
            </label>

            <label class="cfg-checkbox">
                <input type="checkbox" name="tipos_reporte[]" value="averias" @checked(in_array('averias', $config->tipos_reporte ?? []))>
                <div>
                    <strong>Aviso de Averías Semanal</strong>
                    <span>Listado de maquinaria o equipos que han pasado a reparación en los últimos 7 días.</span>
                </div>
            </label>
            
            <label class="cfg-checkbox">
                <input type="checkbox" name="tipos_reporte[]" value="preventivos" @checked(in_array('preventivos', $config->tipos_reporte ?? []))>
                <div>
                    <strong>Previsión de Preventivos</strong>
                    <span>Aviso de mantenimientos programados para los próximos 30 días.</span>
                </div>
            </label>

            <button type="submit" class="config-btn config-btn-main"><i class="fas fa-save"></i> Guardar Configuración</button>
        </div>
    </div>
</form>

@push('js')
<script>
    const emailInput = document.getElementById('email_input');
    const btnAdd = document.getElementById('btn_add_email');
    const emailList = document.getElementById('email_list');
    const counterLabel = document.getElementById('list_counter');

    function updateCounter() {
        const items = emailList.querySelectorAll('.cfg-email-item').length;
        counterLabel.textContent = `LISTA DE ENVÍO (${items})`;
        const emptyState = document.getElementById('empty_state');
        if(items === 0 && !emptyState) {
            emailList.innerHTML = '<div class="cfg-empty" id="empty_state">No hay destinatarios configurados.</div>';
        } else if (items > 0 && emptyState) {
            emptyState.remove();
        }
    }

    btnAdd.addEventListener('click', function() {
        const email = emailInput.value.trim().toLowerCase();
        if(!email) return;
        
        // Validación básica de correo
        if(!/^\S+@\S+\.\S+$/.test(email)) {
            alert('Por favor, introduce un correo electrónico válido.');
            return;
        }

        // Evitar duplicados
        const existing = Array.from(emailList.querySelectorAll('input[type="hidden"]')).map(input => input.value);
        if(existing.includes(email)) {
            alert('Este correo ya está en la lista.');
            return;
        }

        const div = document.createElement('div');
        div.className = 'cfg-email-item';
        div.innerHTML = `
            <div><i class="fas fa-user-circle user-icon"></i> ${email}</div>
            <input type="hidden" name="destinatarios[]" value="${email}">
            <button type="button" onclick="removeEmail(this)"><i class="fas fa-trash"></i></button>
        `;
        
        const emptyState = document.getElementById('empty_state');
        if(emptyState) emptyState.remove();
        
        emailList.appendChild(div);
        emailInput.value = '';
        updateCounter();
    });

    // Permitir añadir con la tecla Enter
    emailInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            btnAdd.click();
        }
    });

    window.removeEmail = function(btn) {
        btn.closest('.cfg-email-item').remove();
        updateCounter();
    };
</script>
@endpush
@stop