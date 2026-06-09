@extends('adminlte::page')

@section('title', 'Ajustar correlativo - Presupuestos')

@section('css')
    @vite(['resources/css/correlativos.css'])
@endsection

@section('content')
    <section class="corr-page">
        <header class="corr-hero">
            <p class="corr-eyebrow">Ajustes avanzados</p>
            <h1 class="corr-title">Correlativo de presupuestos</h1>
            <p class="corr-subtitle">Define el formato y el siguiente número para mantener una numeración consistente en el proyecto activo.</p>
        </header>

        <article class="corr-card">
            <div class="corr-stats">
                <div class="corr-stat">
                    <span class="corr-stat__label">Formato actual</span>
                    <p class="corr-stat__value">{{ $formatoActual }}</p>
                </div>
                <div class="corr-stat">
                    <span class="corr-stat__label">Máximo usado</span>
                    <p class="corr-stat__value">{{ $max ?? '0' }}</p>
                </div>
                <div class="corr-stat">
                    <span class="corr-stat__label">Con formato actual</span>
                    <p class="corr-stat__value">{{ $countFormato ?? 0 }}</p>
                </div>
                <div class="corr-stat">
                    <span class="corr-stat__label">Override admin</span>
                    <p class="corr-stat__value">{{ $override ?? 'Sin override' }}</p>
                </div>
            </div>

            @if (!empty($ultimosConFormato) && $ultimosConFormato->isNotEmpty())
                <div class="corr-recent">
                    <p class="corr-recent__label">Ultimos con formato actual</p>
                    <ul class="corr-recent__list">
                        @foreach ($ultimosConFormato as $presupuesto)
                            <li>
                                <strong>{{ $presupuesto->numero }}</strong>
                                <span>{{ optional($presupuesto->fecha)->format('d/m/Y') ?: '-' }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('presupuestos.correlativo.update') }}" method="POST" class="corr-form" novalidate>
                @csrf
                <div class="corr-field">
                    <label for="formato" class="corr-label">Formato base</label>
                    <input type="text" id="formato" name="formato" class="corr-input" value="{{ old('formato', $formatoActual) }}" placeholder="PRES-2026-000" required>
                    <p class="corr-help">Debe terminar en <strong>-000</strong> (tres ceros). Puedes cambiar las letras y el año libremente. Ejemplo: <strong>AIRBUS-2026-000</strong>.</p>
                    @error('formato')
                        <p class="corr-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="corr-field">
                    <label for="next" class="corr-label">Siguiente número correlativo</label>
                    <input type="number" id="next" name="next" class="corr-input" min="1" value="{{ old('next', $suggested) }}" required>
                    <p class="corr-help">Vista previa: <strong id="preview_ejemplo">{{ $ejemplo }}</strong></p>
                    @error('next')
                        <p class="corr-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="corr-actions">
                    <a href="{{ route('presupuestos.index') }}" class="corr-btn corr-btn--ghost">Cancelar</a>
                    <button type="submit" class="corr-btn corr-btn--primary">Guardar ajuste</button>
                </div>
            </form>
        </article>
    </section>
@endsection

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const formatoInput = document.getElementById('formato');
        const nextInput = document.getElementById('next');
        const previewText = document.getElementById('preview_ejemplo');

        function updatePreview() {
            const formato = formatoInput.value.trim();
            const next = parseInt(nextInput.value) || 0;

            // Verificamos que termine en guion y tres ceros (-000)
            if (formato.endsWith('-000')) {
                // Cortamos los últimos 4 caracteres (el "-000") para quedarnos con el prefijo
                const prefix = formato.slice(0, -3); 
                // Rellenamos el nuevo número para que siempre tenga 3 posiciones (ej. 1 -> 001)
                const paddedNext = String(next).padStart(3, '0');
                
                previewText.textContent = prefix + paddedNext;
                previewText.style.color = "inherit";
            } else {
                previewText.textContent = 'El formato debe terminar obligatoriamente en -000';
                previewText.style.color = "red";
            }
        }

        formatoInput.addEventListener('input', updatePreview);
        nextInput.addEventListener('input', updatePreview);
        
        updatePreview();
    });
</script>
@endsection