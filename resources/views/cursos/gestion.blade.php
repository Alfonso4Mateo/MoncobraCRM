@extends('adminlte::page')

@section('title', 'Gestión de Cursos')

@section('css')
    <style>
        /* Estilos corporativos para los botones */
        .cursos-btn { 
            display: inline-flex; 
            align-items: center; 
            border: 0; 
            border-radius: 10px; 
            padding: 8px 16px; 
            font-weight: 700; 
            font-size: 0.85rem; 
            transition: all 0.2s; 
            text-decoration: none;
        }
        .cursos-btn--primary { background: #173e67; color: #fff; }
        .cursos-btn--primary:hover { background: #0f2a47; color: #fff; text-decoration: none; }
        .cursos-btn--soft { background: #eef3f8; color: #173e67; }
        .cursos-btn--soft:hover { background: #dbe3ef; color: #173e67; text-decoration: none; }
        
        /* Control total de la cabecera mediante Flexbox */
        .gestion-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .gestion-title-wrapper { display: flex; flex-direction: column; }
        .gestion-title-wrapper h3 { margin: 0; font-size: 1.4rem; font-weight: 800; color: #173e67; }
        .gestion-title-wrapper small { color: #667085; font-size: 0.88rem; margin-top: 4px; }
        .gestion-actions { display: flex; gap: 12px; margin-left: auto; }
    </style>
@endsection

@section('content')
    <section class="p-3">
        <div class="card">
            <div class="card-header gestion-header">
                <div class="gestion-title-wrapper">
                    <h3>Gestión de cursos</h3>
                    <small>Editar, revisar o eliminar cursos existentes</small>
                </div>
                <div class="gestion-actions">
                    <a href="{{ route('cursos.index') }}" class="cursos-btn cursos-btn--soft">
                        <i class="fas fa-arrow-left mr-2"></i> Volver al catálogo
                    </a>
                    <a href="{{ route('cursos.create') }}" class="cursos-btn cursos-btn--primary">
                        <i class="fas fa-plus mr-2"></i> Nuevo curso
                    </a>
                </div>
            </div>

            <!-- Buscador en vivo -->
            <div class="card-body border-bottom bg-light p-3">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                    </div>
                    <input type="text" id="buscador-gestion" class="form-control" placeholder="Buscar por categoría, nombre del curso o meses de validez..." autocomplete="off">
                </div>
            </div>

            <div class="card-body table-responsive p-0">
                <table class="table table-sm table-hover mb-0" id="tabla-gestion">
                    <thead>
                        <tr>
                            <th>Curso</th>
                            <th class="sortable" data-sort="validez" style="cursor:pointer;" title="Ordenar por validez dentro de la categoría">
                                Validez <i class="fas fa-sort text-muted ml-1"></i>
                            </th>
                            <th class="sortable" data-sort="asignados" style="cursor:pointer;" title="Ordenar por asignados dentro de la categoría">
                                Asignados <i class="fas fa-sort text-muted ml-1"></i>
                            </th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    
                    <!-- Agrupación en múltiples tbody para aislar el ordenamiento -->
                    @forelse($cursos->groupBy('categoria') as $categoriaNombre => $cursosGrupo)
                        @php 
                            $safeId = \Illuminate\Support\Str::slug($categoriaNombre ?: 'sin-categoria'); 
                        @endphp

                        <tbody class="categoria-tbody">
                            <tr class="fila-categoria bg-light" data-toggle="collapse" data-target=".grupo-{{ $safeId }}" style="cursor: pointer;">
                                <td colspan="4">
                                    <i class="fas fa-chevron-down text-muted mr-2" style="width: 16px; text-align: center;"></i>
                                    <strong class="text-uppercase" style="color: #173e67;">{{ $categoriaNombre ?: 'Sin Categoría' }}</strong>
                                    <span class="badge badge-secondary ml-2">{{ $cursosGrupo->count() }} cursos</span>
                                </td>
                            </tr>

                            @foreach($cursosGrupo as $curso)
                                <tr class="collapse show grupo-{{ $safeId }} fila-curso" 
                                    data-validez="{{ $curso->meses_validez ?: 9999 }}" 
                                    data-asignados="{{ $curso->personal_count }}">
                                    
                                    <td class="pl-4">
                                        <strong>{{ $curso->nombre }}</strong>
                                        <span class="d-none categoria-oculta">{{ $curso->categoria }}</span>
                                        @if($curso->descripcion)
                                            <div class="text-muted small">{{ \Illuminate\Support\Str::limit($curso->descripcion, 90) }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($curso->meses_validez)
                                            {{ $curso->meses_validez }} meses / aviso {{ $curso->dias_aviso_previo }} días
                                        @else
                                            Sin caducidad
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('cursos.show', $curso->id) }}" class="badge badge-info" style="text-decoration: none; padding: 6px 10px; font-size: 0.85rem;" title="Ver listado de trabajadores">
                                            {{ $curso->personal_count }} <i class="fas fa-users ml-1"></i>
                                        </a>
                                    </td>
                                    <td class="text-right">
                                        <div class="btn-group">
                                            <a href="{{ route('cursos.show', $curso->id) }}" class="btn btn-sm btn-info text-white" title="Ver ficha del curso">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('cursos.edit', $curso->id) }}" class="btn btn-sm btn-secondary">
                                                Editar
                                            </a>
                                            <form action="{{ route('cursos.destroy', $curso->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar este curso?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" style="border-top-left-radius: 0; border-bottom-left-radius: 0;">Borrar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    @empty
                        <tbody>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No hay cursos para gestionar.</td>
                            </tr>
                        </tbody>
                    @endforelse
                </table>
            </div>
        </div>
    </section>
@endsection

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const buscador = document.getElementById('buscador-gestion');
            const filasCategoria = document.querySelectorAll('.fila-categoria');
            
            // 1. Lógica del buscador en vivo
            if(buscador) {
                buscador.addEventListener('input', function() {
                    const termino = this.value.toLowerCase().trim();

                    filasCategoria.forEach(filaCat => {
                        const targetClass = filaCat.getAttribute('data-target');
                        const filasHijas = document.querySelectorAll(targetClass);
                        let tieneHijosVisibles = false;

                        filasHijas.forEach(filaHija => {
                            const textoFila = filaHija.textContent.toLowerCase();
                            
                            if (textoFila.includes(termino)) {
                                filaHija.style.display = '';
                                filaHija.classList.add('show');
                                tieneHijosVisibles = true;
                            } else {
                                filaHija.style.display = 'none';
                            }
                        });

                        filaCat.style.display = tieneHijosVisibles ? '' : 'none';
                        
                        if (termino !== '') {
                            const icono = filaCat.querySelector('i');
                            if(icono) icono.classList.replace('fa-chevron-right', 'fa-chevron-down');
                        }
                    });
                });
            }

            // 2. Lógica visual para rotar las flechas
            $('.collapse').on('show.bs.collapse', function () {
                const target = '.' + this.classList[1]; 
                $(`tr[data-target="${target}"] i`).removeClass('fa-chevron-right').addClass('fa-chevron-down');
            });

            $('.collapse').on('hide.bs.collapse', function () {
                const target = '.' + this.classList[1]; 
                $(`tr[data-target="${target}"] i`).removeClass('fa-chevron-down').addClass('fa-chevron-right');
            });

            // 3. Lógica de ordenamiento Intra-grupo
            let ordenActual = { columna: null, direccion: 'asc' };

            document.querySelectorAll('th.sortable').forEach(th => {
                th.addEventListener('click', () => {
                    const columna = th.getAttribute('data-sort');
                    
                    if (ordenActual.columna === columna) {
                        ordenActual.direccion = ordenActual.direccion === 'asc' ? 'desc' : 'asc';
                    } else {
                        ordenActual.columna = columna;
                        ordenActual.direccion = columna === 'validez' ? 'asc' : 'desc';
                    }

                    document.querySelectorAll('th.sortable i').forEach(icon => icon.className = 'fas fa-sort text-muted ml-1');
                    const iconoActivo = th.querySelector('i');
                    iconoActivo.className = ordenActual.direccion === 'asc' ? 'fas fa-sort-up ml-1 text-primary' : 'fas fa-sort-down ml-1 text-primary';

                    document.querySelectorAll('.categoria-tbody').forEach(tbody => {
                        const filasCursos = Array.from(tbody.querySelectorAll('.fila-curso'));
                        
                        filasCursos.sort((a, b) => {
                            let valA = parseFloat(a.getAttribute(`data-${columna}`));
                            let valB = parseFloat(b.getAttribute(`data-${columna}`));

                            if (ordenActual.direccion === 'asc') {
                                return valA - valB;
                            } else {
                                return valB - valA;
                            }
                        });

                        // Reinyecta los nodos DOM ordenados sin sacarlos de su categoría
                        filasCursos.forEach(fila => tbody.appendChild(fila));
                    });
                });
            });
        });
    </script>
@endsection