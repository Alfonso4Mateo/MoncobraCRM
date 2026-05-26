@extends('adminlte::page')

@section('title', 'Ajustar correlativo - Albaranes')

@section('css')
    @vite(['resources/css/correlativos.css'])
@endsection

@section('content')
    <section class="corr-page">
        <header class="corr-hero">
            <p class="corr-eyebrow">Ajustes avanzados</p>
            <h1 class="corr-title">Correlativo de albaranes</h1>
            <p class="corr-subtitle">Configura el prefijo y el siguiente número para mantener un flujo ordenado de documentos de entrega.</p>
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
                    <span class="corr-stat__label">Generados con este formato</span>
                    <p class="corr-stat__value">{{ $generadosConFormatoActual ?? '0' }}</p>
                </div>
                <div class="corr-stat">
                    <span class="corr-stat__label">Override admin</span>
                    <p class="corr-stat__value">{{ $override ?? 'Sin override' }}</p>
                </div>
            </div>

            <form action="{{ route('albaranes.correlativo.update') }}" method="POST" class="corr-form" novalidate>
                @csrf
                <div class="corr-field">
                    <label for="formato" class="corr-label">Formato base</label>
                    <input type="text" id="formato" name="formato" class="corr-input" value="{{ old('formato', $formatoActual) }}" placeholder="ALB-2026-000" required>
                    <p class="corr-help">Debe terminar en ceros, por ejemplo <strong>ALB-2026-000</strong> o <strong>ALB-2026-0000</strong>.</p>
                    @error('formato')
                        <p class="corr-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="corr-field">
                    <label for="next" class="corr-label">Siguiente número correlativo</label>
                    <input type="number" id="next" name="next" class="corr-input" min="1" value="{{ old('next', $suggested) }}" required>
                    <p class="corr-help">Vista previa: <strong>{{ $ejemplo }}</strong>.</p>
                    @error('next')
                        <p class="corr-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="corr-actions">
                    <a href="{{ route('albaranes.index') }}" class="corr-btn corr-btn--ghost">Cancelar</a>
                    <button type="submit" class="corr-btn corr-btn--primary">Guardar ajuste</button>
                </div>
            </form>
        </article>
    </section>
@endsection
