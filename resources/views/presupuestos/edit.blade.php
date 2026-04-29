@extends('adminlte::page')

@section('title', 'Editar Presupuesto - MoncobraCRM')

@section('css')
    @vite(['resources/css/presupuestos-detail.css'])
@endsection

@section('content_header')
    <div class="presupuesto-detail-header">
        <div class="presupuesto-detail-header__copy">
            <h1>Editar Presupuesto</h1>
            <p>Actualiza estado, cliente y datos administrativos del presupuesto.</p>
        </div>
        <a href="{{ route('presupuestos.index') }}" class="presupuesto-detail-btn presupuesto-detail-btn--ghost">
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
            Volver al listado
        </a>
    </div>
@endsection

@section('content')
    <section class="presupuesto-detail-shell">
        <article class="presupuesto-detail-card">
            <header class="presupuesto-detail-card__head">
                <h2>Datos generales</h2>
            </header>

            <div class="presupuesto-detail-card__body">
            @if ($errors->any())
                <div class="alert alert-danger presupuesto-detail-alert" role="alert">
                    <strong>No se pudo actualizar el presupuesto.</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('presupuestos.update', $presupuesto) }}" method="POST" novalidate>
                @csrf
                @method('PUT')

                <div class="presupuesto-detail-grid">
                    <div class="field-group">
                        <label>Documento</label>
                        <div class="documento-display"><strong>Presupuesto</strong></div>
                        <input type="hidden" id="documento" name="documento" value="{{ old('documento', $presupuesto->documento) }}">
                    </div>
                    <div class="field-group">
                        <label for="numero">Número</label>
                        <input type="text" id="numero" name="numero" maxlength="50" required value="{{ old('numero', $presupuesto->numero) }}">
                        <small class="text-muted">Siguiente sugerido: {{ $siguienteNumero }}</small>
                    </div>
                    <div class="field-group">
                        <label for="fecha">Fecha</label>
                        <input type="date" id="fecha" name="fecha" required value="{{ old('fecha', optional($presupuesto->fecha)->toDateString()) }}">
                    </div>
                    <div class="field-group">
                        <label for="cliente_id">Cliente</label>
                        <select id="cliente_id" name="cliente_id" required>
                            <option value="">Seleccione...</option>
                            @foreach($clientes as $cliente)
                                <option value="{{ $cliente->id }}" {{ (string) old('cliente_id', $presupuesto->cliente_id) === (string) $cliente->id ? 'selected' : '' }}>
                                    {{ $cliente->empresa_nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field-group field-group--wide">
                        <label for="titulo">Título</label>
                        <input type="text" id="titulo" name="titulo" maxlength="255" value="{{ old('titulo', $presupuesto->titulo) }}">
                    </div>
                    <div class="field-group">
                        <label for="ot">OT</label>
                        <input type="text" id="ot" name="ot" maxlength="255" value="{{ old('ot', $presupuesto->ot) }}">
                    </div>
                    <div class="field-group">
                        <label for="total">Total</label>
                        <input type="number" id="total" name="total" min="0" step="0.01" value="{{ old('total', number_format((float) ($presupuesto->total ?? 0), 2, '.', '')) }}">
                    </div>
                    <div class="field-group" id="presupuesto-estado">
                        <label for="estado">Estado</label>
                        <select id="estado" name="estado">
                            @php
                                $estadoActual = (string) old('estado', $presupuesto->estado ?? 'pendiente');
                            @endphp
                            @foreach (['pendiente', 'aceptado', 'rechazado', 'pendiente pedido'] as $estado)
                                <option value="{{ $estado }}" {{ $estadoActual === $estado ? 'selected' : '' }}>{{ ucfirst($estado) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="presupuesto-detail-actions">
                    <a href="{{ route('presupuestos.show', $presupuesto) }}" class="presupuesto-detail-btn presupuesto-detail-btn--soft">
                        <i class="fas fa-eye" aria-hidden="true"></i>
                        Ver detalle
                    </a>
                    <button type="submit" class="presupuesto-detail-btn presupuesto-detail-btn--primary">
                        <i class="fas fa-save" aria-hidden="true"></i>
                        Guardar cambios
                    </button>
                </div>
            </form>
            </div>
        </article>
    </section>
@endsection
