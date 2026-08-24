@extends('adminlte::page')

@section('title', 'Matriz de Formación')

@section('css')
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .header-panel { background: #fff; border: 1px solid #e7ecf3; border-radius: 14px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(15,23,42,0.02); display: flex; justify-content: space-between; align-items: center; }
        .header-panel h2 { margin: 0; font-weight: 800; color: #173e67; }
        .macro-container { background: #fff; border: 1px solid #e7ecf3; border-radius: 14px; padding: 20px; }
        
        .category-group { margin-bottom: 20px; }
        .category-title { font-size: 1rem; font-weight: 800; color: #173e67; margin-bottom: 10px; padding-bottom: 8px; border-bottom: 2px solid #f1f5f9; text-transform: uppercase; letter-spacing: 0.05em; }
        
        .course-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; border: 1px solid #edf2f7; border-radius: 10px; margin-bottom: 8px; background: #f8fafc; transition: all 0.2s; }
        .course-item:hover { border-color: #cbd5e1; background: #fff; }
        .course-name { font-weight: 700; color: #334155; }
        
        /* Estilos del Toggle heredados de tu interfaz */
        .course-toggle { position: relative; width: 48px; height: 26px; display: inline-block; margin: 0; }
        .course-toggle input { opacity: 0; width: 0; height: 0; }
        .course-toggle__track { position: absolute; inset: 0; background: #dbe3ef; border-radius: 999px; transition: all .18s ease; cursor: pointer; }
        .course-toggle__thumb { position: absolute; top: 3px; left: 3px; width: 20px; height: 20px; background: #fff; border-radius: 50%; box-shadow: 0 2px 8px rgba(15, 23, 42, .16); transition: all .18s ease; }
        .course-toggle input:checked + .course-toggle__track { background: #173e67; }
        .course-toggle input:checked + .course-toggle__track .course-toggle__thumb { transform: translateX(22px); }

        .btn-save { background: #173e67; color: white; border: none; border-radius: 10px; padding: 10px 24px; font-weight: 700; font-size: 0.95rem; display: inline-flex; align-items: center; gap: 8px; }
        .btn-save:hover { background: #0f2a47; color: white; }
    </style>
@endsection

@section('content')
    <section class="p-3">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <!-- Panel Superior (Edición de nombre) -->
        <div class="header-panel">
            <div>
                <p class="text-muted mb-1" style="font-size: 0.8rem; font-weight: 800; text-transform: uppercase;">Configurando Matriz para:</p>
                
                <form action="{{ route('puestos.update', $puesto->id) }}" method="POST" class="d-flex align-items-center" style="gap: 10px;">
                    @csrf
                    @method('PUT')
                    <input type="text" name="nombre" value="{{ $puesto->nombre }}" class="form-control" style="font-size: 1.5rem; font-weight: 800; color: #173e67; border: 1px solid transparent; background: transparent; padding: 0; width: auto;" required>
                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="Renombrar puesto"><i class="fas fa-save"></i></button>
                </form>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <a href="{{ route('puestos.auditoria.export', $puesto->id) }}" class="btn" style="background: #10b981; color: white; border-radius: 10px; font-weight: 700; display: inline-flex; align-items: center; border: none;">
                    <i class="fas fa-file-excel mr-2"></i> Descargar Auditoría CSV
                </a>
                
                <a href="{{ route('puestos.index') }}" class="btn btn-outline-secondary" style="border-radius: 10px; font-weight: 700; display: inline-flex; align-items: center;">
                    <i class="fas fa-arrow-left mr-2"></i> Volver
                </a>
            </div>
        </div>

        <!-- El Panel de Normas (Macros) -->
        <div class="macro-container">
            <div class="mb-4">
                <h4 style="font-weight: 800; color: #173e67;">Asignación de Cursos Obligatorios</h4>
                <p class="text-muted">Activa los cursos que el sistema deberá exigir automáticamente a cualquier trabajador que ocupe este puesto.</p>
            </div>

            <form action="{{ route('puestos.sync-cursos', $puesto->id) }}" method="POST">
                @csrf
                
                <div class="row">
                    @foreach($cursosPorCategoria as $categoria => $cursos)
                        <div class="col-md-6 col-lg-4">
                            <div class="category-group">
                                <div class="category-title">{{ $categoria }}</div>
                                
                                @foreach($cursos as $curso)
                                    <div class="course-item">
                                        <span class="course-name">{{ $curso->nombre }}</span>
                                        
                                        <label class="course-toggle" title="Marcar como obligatorio">
                                            <!-- Si el curso está en el array de asignados, lo marcamos como checked -->
                                            <input type="checkbox" name="cursos[]" value="{{ $curso->id }}" @checked(in_array($curso->id, $cursosAsignados))>
                                            <span class="course-toggle__track"><span class="course-toggle__thumb"></span></span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 pt-3 border-top text-right">
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> Guardar Matriz de Formación
                    </button>
                </div>
            </form>
        </div>
    </section>
@endsection