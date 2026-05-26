@extends('adminlte::page')

@section('title', 'Documento de Salida - MoncobraCRM')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/inventario-salida.css'])
@endsection

@section('content')
    @php
        $delegacion = $documento['delegacion'] ?? '';
        $delegacion = $delegacion !== '' ? $delegacion : ($delegacionPrefill ?? '');
        $fecha = $documento['fecha'] ?? ($salida->fecha?->format('d/m/Y') ?? '');
        $trabajador = $documento['trabajador'] ?? ($salida->solicitante ?? '');
        $ficha = $documento['ficha'] ?? '';
        $observaciones = $documento['observaciones'] ?? '';
    @endphp

    <section class="pdf-page">
        <header class="pdf-page-header">
            <div>
                <h1>Documento de salida</h1>
                <p>{{ $salida->numero_salida }} · {{ $salida->fecha?->format('d/m/Y H:i') ?? '' }}</p>
            </div>
            <div class="pdf-page-actions">
                <button type="button" class="pdf-print-btn" data-print-doc>
                    <i class="fas fa-print"></i>
                    Imprimir
                </button>
                <a href="{{ route('documentos.index', ['tipo' => 'salidas', 'doc' => $salida->id]) }}">Ver en documentos</a>
            </div>
        </header>

        <article class="pdf-sheet">
            <div class="pdf-sheet__top">
                <div class="pdf-logo">
                    <img src="{{ asset('images/moncobra-1l.png') }}?v={{ @filemtime(public_path('images/moncobra-1l.png')) }}" alt="Moncobra">
                </div>
                <div class="pdf-title">
                    <span>EQUIPO DE PROTECCION PERSONAL (EPI'S)</span>
                    <span>(CONTROL DE ENTREGA/REPOSICION)</span>
                </div>
                <div class="pdf-service">SERVICIO DE PREVENCION</div>
            </div>

            <div class="pdf-meta-row">
                <div class="pdf-field pdf-field--wide">
                    <label>DELEGACION:</label>
                    <input type="text" value="{{ $delegacion }}" readonly>
                </div>
                <div class="pdf-field">
                    <label>FECHA:</label>
                    <input type="text" value="{{ $fecha }}" readonly>
                </div>
            </div>

            <div class="pdf-meta-row">
                <div class="pdf-field pdf-field--wide">
                    <label>TRABAJADOR (NOMBRE Y APELLIDOS):</label>
                    <input type="text" value="{{ $trabajador }}" readonly>
                </div>
                <div class="pdf-field">
                    <label>FICHA N:</label>
                    <input type="text" value="{{ $ficha }}" readonly>
                </div>
            </div>

            <div class="pdf-table-wrap">
                <table class="pdf-table">
                    <thead>
                        <tr>
                            <th>ARTICULO</th>
                            <th>REPOSICIONES / NUEVAS NECESIDADES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for ($i = 0; $i < 10; $i++)
                            @php
                                $linea = $lineasDocumento[$i] ?? null;
                            @endphp
                            <tr>
                                <td>
                                    <textarea class="pdf-cell-input pdf-auto-grow" readonly>{{ $linea['articulo'] ?? '' }}</textarea>
                                </td>
                                <td>
                                    <textarea class="pdf-cell-input pdf-cell-input--center pdf-auto-grow" readonly>{{ $linea['cantidad'] ?? '' }}</textarea>
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>

            <div class="pdf-observaciones">
                <label>OBSERVACIONES:</label>
                <textarea class="pdf-auto-grow" readonly>{{ $observaciones }}</textarea>
            </div>

            <div class="pdf-legal">
                De conformidad con la normativa vigente, este registro documenta la entrega y reposicion de equipos de proteccion.
            </div>

            <div class="pdf-signatures">
                <div class="pdf-sign">Firma del Responsable</div>
                <div class="pdf-sign">Firma del Trabajador</div>
            </div>
        </article>
    </section>
@endsection

@section('js')
    <script>
        (function () {
            const printButton = document.querySelector('[data-print-doc]');
            const autoGrowAreas = document.querySelectorAll('.pdf-auto-grow');

            if (autoGrowAreas.length) {
                const resizeArea = (el) => {
                    el.style.height = 'auto';
                    el.style.height = `${el.scrollHeight}px`;
                };

                autoGrowAreas.forEach((area) => resizeArea(area));
            }
            if (printButton) {
                printButton.addEventListener('click', () => window.print());
            }
        })();
    </script>
@endsection
