@extends('adminlte::page')

@section('title', 'Gestión de Tallas')

@section('css')
    @vite(['resources/css/personal-index.css'])
@endsection

@section('content')
<section class="personal-page">
    <header class="personal-hero">
        <div class="personal-hero-copy">
            <div class="personal-crumbs">GESTIÓN DE PERSONAL <span>•</span> TALLAS</div>
            <h1>Gestión de Tallas</h1>
            <p>Visión agregada de tallas y equipamiento de todos los trabajadores.</p>
        </div>

        <div class="personal-hero-actions">
            <a href="{{ route('personal.index') }}" class="personal-action-btn personal-action-btn--soft">
                <i class="fas fa-arrow-left"></i>
                Volver al listado
            </a>
        </div>
    </header>

    <div class="personal-shell">
        <article class="personal-card">
            <header class="personal-card__header">
                <div>
                    <h3>Resumen por tallas</h3>
                    <p>Filtra por nombre si lo deseas.</p>
                </div>
                <div class="personal-card__actions">
                    <form method="GET" action="{{ route('personal.tallas') }}" class="personal-search-form">
                        <div class="personal-search-field">
                            <label for="q" style="display:none">Buscar</label>
                            <input type="search" id="q" name="q" placeholder="Buscar por nombre o apellidos..." value="{{ $query ?? '' }}">
                        </div>
                        <div class="personal-search-actions">
                            <button type="submit" class="personal-search-submit">Buscar</button>
                        </div>
                    </form>
                </div>
            </header>

            <div class="personal-card__body" style="padding: 18px;">
                <p>Total trabajadores: <strong>{{ $personals->count() }}</strong></p>

                @foreach($columns as $col)
                    @php
                        $values = $personals->pluck($col)->filter(fn($v) => $v !== null && trim((string) $v) !== '');
                        $counts = $values->countBy()->sortKeys();
                        $missing = $personals->count() - $values->count();
                    @endphp

                    <section style="margin-bottom:18px;">
                        <h4 style="margin:6px 0 8px; text-transform:capitalize;">{{ ucfirst($col) }} <small style="color:#6b7280; font-weight:600;">(faltan: {{ $missing }})</small></h4>

                        @if($counts->isEmpty())
                            <div style="padding:10px; background:#f8fafc; border-radius:8px;">No hay valores registrados.</div>
                        @else
                            <div style="display:flex; gap:12px; flex-wrap:wrap">
                                @foreach($counts as $size => $c)
                                    <div style="background:#fff; border:1px solid #e6edf3; padding:8px 12px; border-radius:10px; min-width:120px;">
                                        <div style="font-weight:800">{{ $size }}</div>
                                        <div style="color:#6b7280;">{{ $c }} trabajador{{ $c > 1 ? 'es' : '' }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </section>
                @endforeach

                <hr style="margin:18px 0;">

                <h3>Listado detallado</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Departamento</th>
                                @foreach($columns as $c)
                                    <th style="text-transform:capitalize;">{{ $c }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($personals as $p)
                                <tr>
                                    <td>AL-{{ str_pad((string) $p->id, 4, '0', STR_PAD_LEFT) }}</td>
                                    <td>{{ $p->name }} {{ $p->apellido }}</td>
                                    <td>{{ $p->departamento->nombre ?? '—' }}</td>
                                    @foreach($columns as $c)
                                        <td>{{ $p->{$c} ?: '—' }}</td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 3 + count($columns) }}">No hay trabajadores para mostrar.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </article>
    </div>
</section>
@endsection
