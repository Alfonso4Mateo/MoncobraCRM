@extends('adminlte::page')

@section('title', 'Alertas de Caducidad de Cursos')

@section('content')
    <section class="p-3">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="card-title mb-0">Alertas de caducidad</h3>
                    <small class="text-muted">Cursos en aviso y caducados</small>
                </div>
                <a href="{{ route('cursos.index') }}" class="btn btn-sm btn-outline-primary">Volver al catálogo</a>
            </div>

            <div class="card-body table-responsive p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Trabajador</th>
                            <th>Curso</th>
                            <th>Fecha realización</th>
                            <th>Fecha caducidad</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($alertas as $alerta)
                            <tr>
                                <td>{{ $alerta->personal_name }} {{ $alerta->personal_apellido }}</td>
                                <td>{{ $alerta->curso_nombre }}</td>
                                <td>{{ \Illuminate\Support\Carbon::parse($alerta->fecha_realizacion)->format('d/m/Y') }}</td>
                                <td>{{ \Illuminate\Support\Carbon::parse($alerta->fecha_caducidad)->format('d/m/Y') }}</td>
                                <td>
                                    @if($alerta->estado === 'Caducado')
                                        <span class="badge badge-danger">Caducado</span>
                                    @else
                                        <span class="badge badge-warning">En Aviso</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No hay alertas activas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection