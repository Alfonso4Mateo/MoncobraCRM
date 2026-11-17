@extends('adminlte::page')

@section('title', 'Catálogo de EPIs')

@section('css')
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .erp-hero { background: #fff; border: 1px solid #e7ecf3; border-radius: 18px; box-shadow: 0 16px 30px rgba(15, 23, 42, .06); display: flex; justify-content: space-between; gap: 16px; padding: 20px 22px; margin-bottom: 20px; align-items: center; flex-wrap: wrap; }
        .erp-hero h1 { margin: 0 0 4px; font-size: 1.7rem; font-weight: 800; color: #173e67; }
        .erp-hero p { margin: 0; color: #667085; font-size: .92rem; }
        .erp-primary-btn { display: inline-flex; align-items: center; gap: 10px; padding: 10px 16px; border-radius: 12px; background: linear-gradient(135deg, #0284c7, #0369a1); color: #fff !important; text-decoration: none !important; font-weight: 800; border: none; cursor: pointer; transition: opacity 0.2s; }
        .erp-primary-btn:hover { opacity: 0.9; color: #fff !important; }
        .erp-primary-btn:disabled { opacity: 0.6; cursor: not-allowed; }

        .erp-ghost-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 16px; border-radius: 12px; background: #f8fafc; border: 1px solid #dbe3ef; color: #173e67 !important; text-decoration: none !important; font-weight: 800; transition: background 0.2s; }
        .erp-ghost-btn:hover { background: #eef2f7; }

        .epi-toolbar { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 20px; }
        .epi-toolbar input[type="search"],
        .epi-toolbar select { border: 1px solid #dbe3ef; border-radius: 12px; padding: 9px 12px; font-size: 0.9rem; }
        .epi-toolbar input[type="search"] { flex: 1; min-width: 220px; }
        .filter-toggle-inactive { display: flex; align-items: center; gap: 8px; font-size: 0.85rem; font-weight: 700; color: #64748b; cursor: pointer; user-select: none; margin: 0; }

        .epi-category-group { margin-bottom: 28px; }
        .epi-category-title {
            font-size: 0.85rem; font-weight: 800; color: #173e67; text-transform: uppercase;
            letter-spacing: 0.06em; margin-bottom: 12px; padding-bottom: 8px;
            border-bottom: 2px solid #f1f5f9; display: flex; align-items: center; gap: 8px;
        }
        .epi-category-count { background: #eef2f7; color: #667085; font-size: 0.7rem; padding: 2px 8px; border-radius: 999px; font-weight: 800; text-transform: none; letter-spacing: 0; }

        .epi-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }
        .epi-card { background: #fff; border: 1px solid #e7ecf3; border-radius: 14px; padding: 18px; transition: all 0.2s ease; display: flex; flex-direction: column; }
        .epi-card:hover { border-color: #0284c7; box-shadow: 0 8px 15px rgba(15, 23, 42, 0.05); transform: translateY(-2px); }
        .epi-card__title { font-size: 1.15rem; font-weight: 800; color: #173e67; margin: 0 0 4px 0; }
        .epi-card__desc { font-size: 0.85rem; color: #64748b; margin-bottom: 16px; flex-grow: 1; }
        .epi-card__footer { display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px dashed #eef2f7; }
        .epi-badge { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; font-size: 0.75rem; font-weight: 800; padding: 4px 10px; border-radius: 999px; }

        .epi-card__actions { display: flex; gap: 6px; padding-top: 10px; margin-top: 10px; border-top: 1px dashed #eef2f7; }
        .epi-action-btn {
            flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            padding: 7px 8px; border-radius: 8px; border: 1px solid #e2e8f0; background: #f8fafc;
            font-size: 0.75rem; font-weight: 700; color: #475569; cursor: pointer; transition: all 0.15s ease;
        }
        .epi-action-btn:hover { background: #f1f5f9; }
        .epi-action-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .epi-action-btn--edit:hover { color: #0369a1; border-color: #7dd3fc; background: #f0f9ff; }
        .epi-action-btn--baja:hover { color: #b45309; border-color: #fcd34d; background: #fffbeb; }
        .epi-action-btn--activar:hover { color: #166534; border-color: #bbf7d0; background: #f0fdf4; }
        .epi-action-btn--delete:hover { color: #b91c1c; border-color: #fca5a5; background: #fef2f2; }

        .epi-empty-state { grid-column: 1 / -1; padding: 40px; text-align: center; border: 2px dashed #cbd5e1; border-radius: 16px; color: #64748b; }

        .erp-modal-overlay { position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 1060; display: none; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s; backdrop-filter: blur(2px); }
        .erp-modal-overlay.is-open { display: flex; opacity: 1; }
        .erp-modal-panel { background: #fff; border-radius: 16px; width: 100%; max-width: 580px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); transform: translateY(20px); transition: transform 0.2s; padding: 24px; }
        .erp-modal-overlay.is-open .erp-modal-panel { transform: translateY(0); }

        /* ESTILOS DEL BUSCADOR + SELECTOR COMBINADO DE INVENTARIO */
        /* DROPDOWN UNIFICADO CON BUSCADOR DENTRO (COMBOBOX) */
        .inv-combobox { position: relative; width: 100%; }
        .inv-combobox-trigger {
            width: 100%; background: #fff; border: 1px solid #cbd5e1; border-radius: 10px;
            padding: 9px 14px; font-size: 0.86rem; font-weight: 700; color: #1e293b;
            display: flex; justify-content: space-between; align-items: center; gap: 10px;
            cursor: pointer; text-align: left; transition: all 0.15s;
        }
        .inv-combobox-trigger:hover, .inv-combobox.is-open .inv-combobox-trigger {
            border-color: #0284c7; box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
        }
        .inv-combobox-trigger__text { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1; }
        .inv-combobox-trigger__text.is-placeholder { color: #64748b; font-weight: 600; }

        .inv-combobox-menu {
            position: absolute; top: calc(100% + 6px); left: 0; right: 0; z-index: 1100;
            background: #fff; border: 1px solid #cbd5e1; border-radius: 12px;
            box-shadow: 0 15px 30px rgba(15, 23, 42, 0.15); display: none; flex-direction: column; overflow: hidden;
        }
        .inv-combobox.is-open .inv-combobox-menu { display: flex; }

        .inv-combobox-search-bar {
            padding: 10px; background: #f8fafc; border-bottom: 1px solid #e2e8f0;
            display: flex; align-items: center; gap: 8px;
        }
        .inv-combobox-search-input {
            width: 100%; border: 1px solid #cbd5e1; border-radius: 8px;
            padding: 7px 12px; font-size: 0.84rem; font-weight: 600; color: #1e293b; outline: none;
        }
        .inv-combobox-search-input:focus { border-color: #0284c7; }

        .inv-combobox-list { max-height: 240px; overflow-y: auto; padding: 6px; }
        .inv-combobox-group-label {
            font-size: 0.68rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em;
            color: #64748b; padding: 8px 10px 4px;
        }
        .inv-combobox-option {
            padding: 8px 10px; border-radius: 8px; cursor: pointer; display: flex;
            justify-content: space-between; align-items: center; gap: 10px; font-size: 0.83rem;
            font-weight: 600; color: #1e293b; transition: background 0.12s;
        }
        .inv-combobox-option:hover, .inv-combobox-option.is-active { background: #e0f2fe; color: #0369a1; }
        .inv-combobox-option__sku { font-weight: 800; color: #0f2942; }
        .inv-combobox-option__stock {
            font-size: 0.72rem; font-weight: 800; background: #f0fdf4; border: 1px solid #bbf7d0;
            color: #166534; padding: 2px 7px; border-radius: 6px; flex-shrink: 0;
        }

        #toast-container { position: fixed; bottom: 20px; right: 20px; z-index: 2000; display: flex; flex-direction: column; gap: 10px; max-width: 360px; }
        .erp-toast {
            background: #fff; border-left: 4px solid #10b981; border-radius: 10px;
            padding: 12px 16px; box-shadow: 0 10px 25px rgba(15,23,42,0.12);
            font-size: 0.85rem; font-weight: 700; color: #1e293b;
            display: flex; align-items: center; gap: 8px;
            opacity: 0; transform: translateY(10px); transition: all 0.25s ease;
        }
        .erp-toast.is-visible { opacity: 1; transform: translateY(0); }
        .erp-toast--success { border-left-color: #10b981; }
        .erp-toast--success i { color: #10b981; }
        .erp-toast--error { border-left-color: #dc2626; }
        .erp-toast--error i { color: #dc2626; }
    </style>
@endsection

@section('content')
    <section style="font-family: 'Manrope', sans-serif;">
        @if(session('success'))
            <div class="alert alert-success" style="border-radius: 12px; font-weight: 600;">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger" style="border-radius: 12px; font-weight: 600;">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger" style="border-radius: 12px; font-weight: 600;">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <header class="erp-hero">
            <div>
                <div style="font-size:.75rem;font-weight:800;letter-spacing:.12em;color:#8a98ab;text-transform:uppercase;">Prevención y Seguridad</div>
                <h1>Catálogo de EPIs</h1>
                <p>Registra los Equipos de Protección Individual y vincúlalos con el almacén para asignarlos después en cada Perfil Formativo.</p>
            </div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                @canany(['cursos.normas', 'cursos.edit', 'cursos.view'])
                    <a href="{{ route('puestos.index') }}" class="erp-ghost-btn">
                        <i class="fas fa-sitemap"></i> Ir a Perfiles Formativos
                    </a>
                @endcanany
                <button type="button" class="erp-primary-btn" id="btn-open-modal">
                    <i class="fas fa-plus"></i> Crear Nuevo EPI
                </button>
            </div>
        </header>

        <a href="{{ route('cursos.index') }}" class="erp-primary-btn" style="position: fixed; bottom: 20px; right: 20px; z-index: 1050; box-shadow: 0 8px 25px rgba(3, 105, 161, 0.35);">
            <i class="fas fa-arrow-left"></i> Volver
        </a>

        <div class="epi-toolbar">
            <input type="search" id="epi-search" placeholder="Buscar EPI por nombre..." autocomplete="off">

            <select id="epi-filter-categoria">
                <option value="all">Todas las categorías</option>
                @foreach($categoriasDisponibles as $cat)
                    <option value="{{ $cat }}">{{ $cat }}</option>
                @endforeach
            </select>

            <label class="filter-toggle-inactive">
                <input type="checkbox" id="epi-toggle-inactive">
                Mostrar inactivos
            </label>
        </div>

        <div id="epis-catalog">
            @forelse($episPorCategoria as $categoriaNombre => $episGrupo)
                <section class="epi-category-group" data-category="{{ $categoriaNombre }}">
                    <div class="epi-category-title">
                        {{ $categoriaNombre }}
                        <span class="epi-category-count">{{ $episGrupo->count() }}</span>
                    </div>
                    <div class="epi-grid">
                        @foreach($episGrupo as $epi)
                            @include('epis.card', ['epi' => $epi])
                        @endforeach
                    </div>
                </section>
            @empty
            @endforelse
        </div>

        <div id="epis-empty-global" class="epi-empty-state" style="{{ $episPorCategoria->isEmpty() ? '' : 'display:none;' }}">
            <i class="fas fa-hard-hat fa-3x mb-3 text-muted"></i>
            <h4>No hay EPIs registrados</h4>
            <p>Utiliza el botón superior para crear tu primer Equipo de Protección Individual.</p>
        </div>

        <div id="epis-empty-search" class="epi-empty-state" style="display:none;">
            <i class="fas fa-search fa-2x mb-2 text-muted"></i>
            <h4>Sin resultados</h4>
            <p>No hay EPIs que coincidan con la búsqueda o los filtros aplicados.</p>
        </div>
    </section>

    <datalist id="categorias-datalist">
        @foreach($categoriasDisponibles as $cat)
            <option value="{{ $cat }}"></option>
        @endforeach
    </datalist>

    <!-- Modal para Crear EPI -->
    <div class="erp-modal-overlay" id="modal-create-epi">
        <div class="erp-modal-panel">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px;">
                <h3 style="margin: 0; font-weight: 800; color: #173e67;">Crear Nuevo Ítem EPI</h3>
                <button type="button" id="btn-close-modal" style="background: none; border: none; font-size: 1.2rem; color: #64748b; cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="form-create-epi" action="{{ route('epis.store') }}" method="POST">
                @csrf
                <input type="hidden" name="inventario_id" id="create-inventario-id" value="">
                <input type="hidden" name="inventario_variante_id" id="create-inventario-variante-id" value="">

                <!-- Vinculación con Inventario (Buscador + Selector Combinado) -->
                <!-- En modal-create-epi (Para modal-edit-epi cambia create- por edit-) -->
                <div class="inv-picker-box" style="background: #f8fbff; border: 1px solid #dce6f4; border-radius: 12px; padding: 14px; margin-bottom: 16px;">
                    <label style="font-weight: 800; color: #173e67; font-size: 0.85rem; margin-bottom: 4px; display: block;">
                        <i class="fas fa-boxes mr-1"></i> Vincular a Ítem del Almacén (Opcional)
                    </label>
                    <p style="font-size: 0.78rem; color: #64748b; margin-bottom: 8px;">Haz clic en el selector y escribe para buscar en tiempo real por código SKU o nombre.</p>

                    <div class="inv-combobox" id="create-inv-combobox">
                        <button type="button" class="inv-combobox-trigger" id="create-inv-trigger">
                            <span class="inv-combobox-trigger__text is-placeholder" id="create-inv-trigger-text">-- Sin vinculación a inventario --</span>
                            <i class="fas fa-chevron-down text-muted" style="font-size: 0.8rem;"></i>
                        </button>

                        <div class="inv-combobox-menu">
                            <div class="inv-combobox-search-bar">
                                <i class="fas fa-search text-muted ml-1"></i>
                                <input type="search" id="create-inv-search" class="inv-combobox-search-input" placeholder="Escribe SKU, nombre, talla o color..." autocomplete="off">
                            </div>

                            <div class="inv-combobox-list" id="create-inv-list">
                                <div class="inv-combobox-option" data-value="" data-tipo="" data-id="" data-label="-- Sin vinculación a inventario --">
                                    <span class="text-muted"><i class="fas fa-unlink mr-1"></i> -- Sin vinculación a inventario --</span>
                                </div>

                                @if(isset($variantesInventario) && $variantesInventario->isNotEmpty())
                                    <div class="inv-combobox-group" data-group="variantes">
                                        <div class="inv-combobox-group-label">Productos con Variantes (Familias / Tallas)</div>
                                        @foreach($variantesInventario as $var)
                                            <div class="inv-combobox-option js-inv-opt"
                                                data-value="var-{{ $var->id }}"
                                                data-tipo="variante"
                                                data-id="{{ $var->id }}"
                                                data-nombre="{{ $var->nombre }}"
                                                data-label="[FAMILIA] {{ $var->codigo }} - {{ $var->nombre }} ({{ $var->stock_total }} uds.)"
                                                data-search="{{ mb_strtolower(($var->codigo ?? '') . ' ' . ($var->nombre ?? '')) }}">
                                                <div>
                                                    <span class="inv-combobox-option__sku">{{ $var->codigo }}</span> — {{ $var->nombre }}
                                                </div>
                                                <span class="inv-combobox-option__stock">{{ $var->stock_total }} uds.</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                @if(isset($itemsSueltosInventario) && $itemsSueltosInventario->isNotEmpty())
                                    <div class="inv-combobox-group" data-group="items">
                                        <div class="inv-combobox-group-label">Productos Simples</div>
                                        @foreach($itemsSueltosInventario as $it)
                                            @php $nomIt = $it->nombre ?: $it->descripcion; @endphp
                                            <div class="inv-combobox-option js-inv-opt"
                                                data-value="item-{{ $it->id }}"
                                                data-tipo="item"
                                                data-id="{{ $it->id }}"
                                                data-nombre="{{ $nomIt }}"
                                                data-label="[ITEM] {{ $it->codigo }} - {{ $nomIt }} ({{ $it->stock_actual }} uds.)"
                                                data-search="{{ mb_strtolower(($it->codigo ?? '') . ' ' . ($nomIt ?? '')) }}">
                                                <div>
                                                    <span class="inv-combobox-option__sku">{{ $it->codigo }}</span> — {{ $nomIt }}
                                                </div>
                                                <span class="inv-combobox-option__stock">{{ $it->stock_actual }} uds.</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="text-center py-3 text-muted small js-inv-empty" style="display: none;">
                                    No se encontraron artículos con esa búsqueda.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label style="font-weight: 700; color: #1e293b;">Nombre del EPI <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" id="create-epi-nombre" class="form-control" style="border-radius: 8px;" placeholder="Ej. CASCO DE SEGURIDAD CLASE A" required value="{{ old('nombre') }}">
                </div>

                <div class="form-group">
                    <label style="font-weight: 700; color: #1e293b;">Categoría</label>
                    <input type="text" name="categoria" list="categorias-datalist" class="form-control" style="border-radius: 8px;" placeholder="Ej. Protección Craneal" value="{{ old('categoria') }}">
                </div>

                <div class="form-group">
                    <label style="font-weight: 700; color: #1e293b;">Descripción / Normativa</label>
                    <textarea name="descripcion" class="form-control" style="border-radius: 8px;" rows="3" placeholder="Detalles técnicos o normativa aplicable (ej. UNE-EN 397)...">{{ old('descripcion') }}</textarea>
                </div>

                <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 10px; padding: 10px 14px; font-size: 0.8rem; color: #64748b;">
                    <i class="fas fa-info-circle text-primary mr-1"></i>
                    Una vez registrado el EPI en el catálogo, podrás asignarlo a cada puesto desde <strong>Perfiles Formativos &rarr; Dotación de EPIs</strong>.
                </div>

                <div class="form-group text-right" style="margin-top: 24px; border-top: 1px solid #e2e8f0; padding-top: 20px; margin-bottom: 0;">
                    <button type="button" class="btn btn-light mr-2" id="btn-cancel-modal" style="border-radius: 8px; font-weight: 600;">Cancelar</button>
                    <button type="submit" class="erp-primary-btn">
                        <i class="fas fa-save"></i> Guardar Ítem
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para Editar EPI -->
    <div class="erp-modal-overlay" id="modal-edit-epi">
        <div class="erp-modal-panel">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px;">
                <h3 style="margin: 0; font-weight: 800; color: #173e67;">Editar Ítem EPI</h3>
                <button type="button" id="btn-close-edit-modal" style="background: none; border: none; font-size: 1.2rem; color: #64748b; cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="form-edit-epi" action="" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="inventario_id" id="edit-inventario-id" value="">
                <input type="hidden" name="inventario_variante_id" id="edit-inventario-variante-id" value="">
                <input type="hidden" name="inventario_articulo_id" id="edit-inventario-articulo-id" value="">

                <!-- Vinculación con Inventario (Buscador + Selector Combinado) -->
                <!-- En modal-create-epi (Para modal-edit-epi cambia create- por edit-) -->
                <div class="inv-picker-box" style="background: #f8fbff; border: 1px solid #dce6f4; border-radius: 12px; padding: 14px; margin-bottom: 16px;">
                    <label style="font-weight: 800; color: #173e67; font-size: 0.85rem; margin-bottom: 4px; display: block;">
                        <i class="fas fa-boxes mr-1"></i> Vincular a Ítem del Almacén (Opcional)
                    </label>
                    <p style="font-size: 0.78rem; color: #64748b; margin-bottom: 8px;">Haz clic en el selector y escribe para buscar en tiempo real por código SKU o nombre.</p>

                    <div class="inv-combobox" id="edit-inv-combobox">
                        <button type="button" class="inv-combobox-trigger" id="edit-inv-trigger">
                            <span class="inv-combobox-trigger__text is-placeholder" id="edit-inv-trigger-text">-- Sin vinculación a inventario --</span>
                            <i class="fas fa-chevron-down text-muted" style="font-size: 0.8rem;"></i>
                        </button>

                        <div class="inv-combobox-menu">
                            <div class="inv-combobox-search-bar">
                                <i class="fas fa-search text-muted ml-1"></i>
                                <input type="search" id="edit-inv-search" class="inv-combobox-search-input" placeholder="Escribe SKU, nombre, talla o color..." autocomplete="off">
                            </div>

                            <div class="inv-combobox-list" id="edit-inv-list">
                                <div class="inv-combobox-option" data-value="" data-tipo="" data-id="" data-label="-- Sin vinculación a inventario --">
                                    <span class="text-muted"><i class="fas fa-unlink mr-1"></i> -- Sin vinculación a inventario --</span>
                                </div>

                                @if(isset($variantesInventario) && $variantesInventario->isNotEmpty())
                                    <div class="inv-combobox-group" data-group="variantes">
                                        <div class="inv-combobox-group-label">Productos con Variantes (Familias / Tallas)</div>
                                        @foreach($variantesInventario as $var)
                                            <div class="inv-combobox-option js-inv-opt"
                                                data-value="var-{{ $var->id }}"
                                                data-tipo="variante"
                                                data-id="{{ $var->id }}"
                                                data-nombre="{{ $var->nombre }}"
                                                data-label="[FAMILIA] {{ $var->codigo }} - {{ $var->nombre }} ({{ $var->stock_total }} uds.)"
                                                data-search="{{ mb_strtolower(($var->codigo ?? '') . ' ' . ($var->nombre ?? '')) }}">
                                                <div>
                                                    <span class="inv-combobox-option__sku">{{ $var->codigo }}</span> — {{ $var->nombre }}
                                                </div>
                                                <span class="inv-combobox-option__stock">{{ $var->stock_total }} uds.</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                @if(isset($itemsSueltosInventario) && $itemsSueltosInventario->isNotEmpty())
                                    <div class="inv-combobox-group" data-group="items">
                                        <div class="inv-combobox-group-label">Productos Simples</div>
                                        @foreach($itemsSueltosInventario as $it)
                                            @php $nomIt = $it->nombre ?: $it->descripcion; @endphp
                                            <div class="inv-combobox-option js-inv-opt"
                                                data-value="item-{{ $it->id }}"
                                                data-tipo="item"
                                                data-id="{{ $it->id }}"
                                                data-nombre="{{ $nomIt }}"
                                                data-label="[ITEM] {{ $it->codigo }} - {{ $nomIt }} ({{ $it->stock_actual }} uds.)"
                                                data-search="{{ mb_strtolower(($it->codigo ?? '') . ' ' . ($nomIt ?? '')) }}">
                                                <div>
                                                    <span class="inv-combobox-option__sku">{{ $it->codigo }}</span> — {{ $nomIt }}
                                                </div>
                                                <span class="inv-combobox-option__stock">{{ $it->stock_actual }} uds.</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="text-center py-3 text-muted small js-inv-empty" style="display: none;">
                                    No se encontraron artículos con esa búsqueda.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label style="font-weight: 700; color: #1e293b;">Nombre del EPI <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" id="edit-epi-nombre" class="form-control" style="border-radius: 8px;" required>
                </div>

                <div class="form-group">
                    <label style="font-weight: 700; color: #1e293b;">Categoría</label>
                    <input type="text" name="categoria" id="edit-epi-categoria" list="categorias-datalist" class="form-control" style="border-radius: 8px;" placeholder="Ej. Protección Craneal">
                </div>

                <div class="form-group">
                    <label style="font-weight: 700; color: #1e293b;">Descripción / Normativa</label>
                    <textarea name="descripcion" id="edit-epi-descripcion" class="form-control" style="border-radius: 8px;" rows="3"></textarea>
                </div>

                <div class="form-group text-right" style="margin-top: 24px; border-top: 1px solid #e2e8f0; padding-top: 20px; margin-bottom: 0;">
                    <button type="button" class="btn btn-light mr-2" id="btn-cancel-edit-modal" style="border-radius: 8px; font-weight: 600;">Cancelar</button>
                    <button type="submit" class="erp-primary-btn">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de confirmación genérico -->
    <div class="erp-modal-overlay" id="confirm-modal">
        <div class="erp-modal-panel" style="max-width: 420px;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                <div style="width: 40px; height: 40px; border-radius: 50%; background: #fef2f2; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fas fa-exclamation-triangle" style="color: #dc2626;"></i>
                </div>
                <h3 style="margin: 0; font-weight: 800; color: #173e67; font-size: 1.05rem;">Confirmar acción</h3>
            </div>
            <p id="confirm-modal-message" style="color: #475569; margin-bottom: 24px;"></p>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" id="confirm-modal-no" class="btn btn-light" style="border-radius: 8px; font-weight: 600;">Cancelar</button>
                <button type="button" id="confirm-modal-yes" class="btn" style="background: #dc2626; color: #fff; border-radius: 8px; font-weight: 700;">Sí, eliminar</button>
            </div>
        </div>
    </div>

    <div id="toast-container"></div>
@endsection

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const baseEpisUrl = "{{ url('epis') }}";

            const catalog = document.getElementById('epis-catalog');
            const emptyGlobal = document.getElementById('epis-empty-global');
            const emptySearch = document.getElementById('epis-empty-search');

            function showToast(message, type = 'success') {
                const container = document.getElementById('toast-container');
                const toast = document.createElement('div');
                toast.className = `erp-toast erp-toast--${type}`;
                toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i><span>${message}</span>`;
                container.appendChild(toast);
                requestAnimationFrame(() => toast.classList.add('is-visible'));
                setTimeout(() => {
                    toast.classList.remove('is-visible');
                    setTimeout(() => toast.remove(), 300);
                }, 3500);
            }

            function confirmAction(message) {
                return new Promise((resolve) => {
                    const modal = document.getElementById('confirm-modal');
                    document.getElementById('confirm-modal-message').textContent = message;
                    modal.classList.add('is-open');

                    const btnYes = document.getElementById('confirm-modal-yes');
                    const btnNo = document.getElementById('confirm-modal-no');

                    const cleanup = (result) => {
                        modal.classList.remove('is-open');
                        btnYes.removeEventListener('click', onYes);
                        btnNo.removeEventListener('click', onNo);
                        modal.removeEventListener('click', onOverlay);
                        resolve(result);
                    };
                    const onYes = () => cleanup(true);
                    const onNo = () => cleanup(false);
                    const onOverlay = (e) => { if (e.target === modal) cleanup(false); };

                    btnYes.addEventListener('click', onYes);
                    btnNo.addEventListener('click', onNo);
                    modal.addEventListener('click', onOverlay);
                });
            }

            function getOrCreateCategoryGroup(categoriaNombre) {
                let group = catalog.querySelector(`.epi-category-group[data-category="${CSS.escape(categoriaNombre)}"]`);
                if (group) return group;

                group = document.createElement('section');
                group.className = 'epi-category-group';
                group.dataset.category = categoriaNombre;
                group.innerHTML = `
                    <div class="epi-category-title">${categoriaNombre} <span class="epi-category-count"></span></div>
                    <div class="epi-grid"></div>
                `;
                catalog.appendChild(group);
                return group;
            }

            function updateCategoryCount(group) {
                if (!document.body.contains(group)) return;
                const count = group.querySelectorAll('.epi-card').length;
                if (count === 0) {
                    group.remove();
                    return;
                }
                const counter = group.querySelector('.epi-category-count');
                if (counter) counter.textContent = count;
            }

            function insertOrReplaceCard(html, categoriaNombre) {
                const wrapper = document.createElement('div');
                wrapper.innerHTML = html.trim();
                const newCard = wrapper.firstElementChild;
                const epiId = newCard.dataset.epiId;

                const existing = document.getElementById(`epi-card-${epiId}`);
                const oldGroup = existing ? existing.closest('.epi-category-group') : null;
                if (existing) existing.remove();

                const targetGroup = getOrCreateCategoryGroup(categoriaNombre);
                targetGroup.querySelector('.epi-grid').appendChild(newCard);
                updateCategoryCount(targetGroup);

                if (oldGroup && oldGroup !== targetGroup) updateCategoryCount(oldGroup);

                checkEmptyStates();
                applyFilters();
                return newCard;
            }

            function removeCard(epiId) {
                const card = document.getElementById(`epi-card-${epiId}`);
                if (!card) return;
                const group = card.closest('.epi-category-group');
                card.remove();
                if (group) updateCategoryCount(group);
                checkEmptyStates();
                applyFilters();
            }

            function checkEmptyStates() {
                const totalCards = catalog.querySelectorAll('.epi-card').length;
                emptyGlobal.style.display = totalCards === 0 ? '' : 'none';
            }

            // ---------------------------------------------------------
            // Búsqueda + filtros del catálogo general
            // ---------------------------------------------------------
            const searchInput = document.getElementById('epi-search');
            const categoriaSelect = document.getElementById('epi-filter-categoria');
            const toggleInactive = document.getElementById('epi-toggle-inactive');
            const storageKey = 'epiCatalogoMostrarInactivos';

            const savedState = localStorage.getItem(storageKey);
            toggleInactive.checked = savedState === 'true';

            function applyFilters() {
                const term = searchInput.value.trim().toLowerCase();
                const categoriaFiltro = categoriaSelect.value;
                const showInactive = toggleInactive.checked;
                let anyVisible = false;

                catalog.querySelectorAll('.epi-category-group').forEach(group => {
                    let groupHasVisible = false;

                    group.querySelectorAll('.epi-card').forEach(card => {
                        const nombre = (card.dataset.epiNombre || '').toLowerCase();
                        const categoria = card.dataset.epiCategoria || 'Sin categoría';
                        const activo = card.dataset.epiActivo === '1';

                        const matchesSearch = term === '' || nombre.includes(term);
                        const matchesCategoria = categoriaFiltro === 'all' || categoria === categoriaFiltro;
                        const matchesActivo = showInactive || activo;

                        const visible = matchesSearch && matchesCategoria && matchesActivo;
                        card.style.display = visible ? '' : 'none';
                        if (visible) { groupHasVisible = true; anyVisible = true; }
                    });

                    group.style.display = groupHasVisible ? '' : 'none';
                });

                const totalCards = catalog.querySelectorAll('.epi-card').length;
                emptySearch.style.display = (totalCards > 0 && !anyVisible) ? '' : 'none';
            }

            searchInput.addEventListener('input', applyFilters);
            categoriaSelect.addEventListener('change', applyFilters);
            toggleInactive.addEventListener('change', function () {
                localStorage.setItem(storageKey, this.checked);
                applyFilters();
            });

            checkEmptyStates();
            applyFilters();

            const toggleModal = (modal, show) => {
                if (show === undefined) modal.classList.toggle('is-open');
                else modal.classList.toggle('is-open', show);
            };

            // ---------------------------------------------------------
            // COMBINADOR: BUSCADOR EN VIVO + SELECTOR DE INVENTARIO
            // ---------------------------------------------------------
            function setupInventoryCombobox({
                comboboxId,
                triggerId,
                triggerTextId,
                searchInputId,
                listId,
                hiddenInvId,
                hiddenVarId,
                hiddenArtId = null,
                nameInputId,
                autoFillName = false
            }) {
                const box = document.getElementById(comboboxId);
                const trigger = document.getElementById(triggerId);
                const triggerText = document.getElementById(triggerTextId);
                const searchInput = document.getElementById(searchInputId);
                const list = document.getElementById(listId);
                const inputInvId = document.getElementById(hiddenInvId);
                const inputVarId = document.getElementById(hiddenVarId);
                const inputArtId = hiddenArtId ? document.getElementById(hiddenArtId) : null;
                const inputNombre = nameInputId ? document.getElementById(nameInputId) : null;
                const emptyMsg = list.querySelector('.js-inv-empty');

                const filterList = () => {
                    const term = searchInput.value.trim().toLowerCase();
                    let totalVisible = 0;

                    list.querySelectorAll('.inv-combobox-group').forEach(group => {
                        let groupVisible = 0;
                        group.querySelectorAll('.js-inv-opt').forEach(opt => {
                            const match = term === '' || (opt.dataset.search || '').includes(term);
                            opt.style.display = match ? '' : 'none';
                            if (match) { groupVisible++; totalVisible++; }
                        });
                        group.style.display = groupVisible > 0 ? '' : 'none';
                    });

                    if (emptyMsg) emptyMsg.style.display = totalVisible === 0 ? '' : 'none';
                };

                const selectByValue = (val, legacyArticuloId = '') => {
                    inputInvId.value = '';
                    inputVarId.value = '';
                    if (inputArtId) inputArtId.value = '';
                    list.querySelectorAll('.inv-combobox-option').forEach(o => o.classList.remove('is-active'));

                    if (!val && legacyArticuloId) {
                        if (inputArtId) inputArtId.value = legacyArticuloId;
                        triggerText.textContent = `[ARTÍCULO VINCULADO #${legacyArticuloId}] (Mantener enlace actual)`;
                        triggerText.classList.remove('is-placeholder');
                        return;
                    }

                    const opt = list.querySelector(`.inv-combobox-option[data-value="${CSS.escape(val)}"]`);
                    if (!opt || !val) {
                        triggerText.textContent = '-- Sin vinculación a inventario --';
                        triggerText.classList.add('is-placeholder');
                        return;
                    }

                    opt.classList.add('is-active');
                    triggerText.textContent = opt.dataset.label;
                    triggerText.classList.remove('is-placeholder');

                    if (opt.dataset.tipo === 'variante') inputVarId.value = opt.dataset.id;
                    if (opt.dataset.tipo === 'item') inputInvId.value = opt.dataset.id;

                    if (autoFillName && inputNombre && inputNombre.value.trim() === '' && opt.dataset.nombre) {
                        inputNombre.value = opt.dataset.nombre.toUpperCase();
                    }
                };

                trigger.addEventListener('click', () => {
                    const opening = !box.classList.contains('is-open');
                    box.classList.toggle('is-open', opening);
                    if (opening) {
                        searchInput.value = '';
                        filterList();
                        setTimeout(() => searchInput.focus(), 50);
                    }
                });

                searchInput.addEventListener('input', filterList);

                list.addEventListener('click', (e) => {
                    const opt = e.target.closest('.inv-combobox-option');
                    if (!opt) return;
                    selectByValue(opt.dataset.value || '');
                    box.classList.remove('is-open');
                });

                document.addEventListener('click', (e) => {
                    if (!box.contains(e.target)) box.classList.remove('is-open');
                });

                return {
                    reset: () => selectByValue(''),
                    setValue: (val, legacyArticuloId = '') => selectByValue(val, legacyArticuloId)
                };
            }

            const createInvCombo = setupInventoryCombobox({
                comboboxId: 'create-inv-combobox',
                triggerId: 'create-inv-trigger',
                triggerTextId: 'create-inv-trigger-text',
                searchInputId: 'create-inv-search',
                listId: 'create-inv-list',
                hiddenInvId: 'create-inventario-id',
                hiddenVarId: 'create-inventario-variante-id',
                nameInputId: 'create-epi-nombre',
                autoFillName: true
            });

            const editInvCombo = setupInventoryCombobox({
                comboboxId: 'edit-inv-combobox',
                triggerId: 'edit-inv-trigger',
                triggerTextId: 'edit-inv-trigger-text',
                searchInputId: 'edit-inv-search',
                listId: 'edit-inv-list',
                hiddenInvId: 'edit-inventario-id',
                hiddenVarId: 'edit-inventario-variante-id',
                hiddenArtId: 'edit-inventario-articulo-id',
                nameInputId: 'edit-epi-nombre',
                autoFillName: false
            });

            // ---------------------------------------------------------
            // Modal CREAR
            // ---------------------------------------------------------
            const modalCreate = document.getElementById('modal-create-epi');
            const btnOpen = document.getElementById('btn-open-modal');
            const btnClose = document.getElementById('btn-close-modal');
            const btnCancel = document.getElementById('btn-cancel-modal');
            const formCreate = document.getElementById('form-create-epi');

            btnOpen.addEventListener('click', () => {
                createInvCombo.reset();
                toggleModal(modalCreate, true);
            });
            btnClose.addEventListener('click', () => toggleModal(modalCreate, false));
            btnCancel.addEventListener('click', () => toggleModal(modalCreate, false));
            modalCreate.addEventListener('click', (e) => { if (e.target === modalCreate) toggleModal(modalCreate, false); });

            formCreate.addEventListener('submit', async function (e) {
                e.preventDefault();
                const btnSubmit = formCreate.querySelector('button[type="submit"]');
                btnSubmit.disabled = true;

                try {
                    const response = await fetch(formCreate.action, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                        body: new FormData(formCreate),
                    });
                    const data = await response.json();

                    if (!response.ok) {
                        const firstError = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Error al crear el EPI.');
                        throw new Error(firstError);
                    }

                    insertOrReplaceCard(data.html, data.categoria);
                    showToast(data.message, 'success');
                    toggleModal(modalCreate, false);
                    formCreate.reset();
                    createInvCombo.reset();
                } catch (error) {
                    showToast(error.message, 'error');
                } finally {
                    btnSubmit.disabled = false;
                }
            });

            // ---------------------------------------------------------
            // Modal EDITAR
            // ---------------------------------------------------------
            const modalEdit = document.getElementById('modal-edit-epi');
            const btnCloseEdit = document.getElementById('btn-close-edit-modal');
            const btnCancelEdit = document.getElementById('btn-cancel-edit-modal');
            const formEdit = document.getElementById('form-edit-epi');
            const inputEditNombre = document.getElementById('edit-epi-nombre');
            const inputEditDescripcion = document.getElementById('edit-epi-descripcion');
            const inputEditCategoria = document.getElementById('edit-epi-categoria');

            btnCloseEdit.addEventListener('click', () => toggleModal(modalEdit, false));
            btnCancelEdit.addEventListener('click', () => toggleModal(modalEdit, false));
            modalEdit.addEventListener('click', (e) => { if (e.target === modalEdit) toggleModal(modalEdit, false); });

            function openEditModal(card) {
                const epiId = card.dataset.epiId;
                const invId = card.dataset.epiInventarioId || '';
                const varId = card.dataset.epiInventarioVarianteId || '';
                const artId = card.dataset.epiInventarioArticuloId || '';

                formEdit.action = `${baseEpisUrl}/${epiId}`;
                inputEditNombre.value = card.dataset.epiNombre || '';
                inputEditDescripcion.value = card.dataset.epiDescripcion || '';
                inputEditCategoria.value = card.dataset.epiCategoria || '';

                const comboVal = varId ? 'var-' + varId : (invId ? 'item-' + invId : '');
                editInvCombo.setValue(comboVal, artId);

                toggleModal(modalEdit, true);
            }

            formEdit.addEventListener('submit', async function (e) {
                e.preventDefault();
                const btnSubmit = formEdit.querySelector('button[type="submit"]');
                btnSubmit.disabled = true;

                try {
                    const response = await fetch(formEdit.action, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                        body: new FormData(formEdit),
                    });
                    const data = await response.json();

                    if (!response.ok) {
                        const firstError = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Error al actualizar el EPI.');
                        throw new Error(firstError);
                    }

                    insertOrReplaceCard(data.html, data.categoria);
                    showToast(data.message, 'success');
                    toggleModal(modalEdit, false);
                } catch (error) {
                    showToast(error.message, 'error');
                } finally {
                    btnSubmit.disabled = false;
                }
            });

            // ---------------------------------------------------------
            // Delegación de eventos en tarjetas (Editar / Toggle / Eliminar)
            // ---------------------------------------------------------
            catalog.addEventListener('click', async function (e) {
                const btnEdit = e.target.closest('.js-btn-edit-epi');
                if (btnEdit) {
                    openEditModal(btnEdit.closest('.epi-card'));
                    return;
                }

                const btnToggle = e.target.closest('.js-btn-toggle-epi');
                if (btnToggle) {
                    const card = btnToggle.closest('.epi-card');
                    const epiId = card.dataset.epiId;
                    btnToggle.disabled = true;

                    try {
                        const response = await fetch(`${baseEpisUrl}/${epiId}/toggle-activo`, {
                            method: 'PATCH',
                            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                        });
                        const data = await response.json();
                        if (!response.ok) throw new Error(data.message || 'Error al cambiar el estado del EPI.');

                        insertOrReplaceCard(data.html, data.categoria);
                        showToast(data.message, 'success');
                    } catch (error) {
                        showToast(error.message, 'error');
                    } finally {
                        if (document.body.contains(btnToggle)) {
                            btnToggle.disabled = false;
                        }
                    }
                    return;
                }

                const btnDelete = e.target.closest('.js-btn-delete-epi');
                if (btnDelete) {
                    const card = btnDelete.closest('.epi-card');
                    const epiId = card.dataset.epiId;
                    const nombre = card.dataset.epiNombre;

                    const confirmado = await confirmAction(`¿Seguro que quieres eliminar "${nombre}"? Esta acción no se puede deshacer.`);
                    if (!confirmado) return;

                    btnDelete.disabled = true;

                    try {
                        const response = await fetch(`${baseEpisUrl}/${epiId}`, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                        });
                        const data = await response.json();
                        if (!response.ok) throw new Error(data.message || 'Error al eliminar el EPI.');

                        removeCard(epiId);
                        showToast(data.message, 'success');
                    } catch (error) {
                        showToast(error.message, 'error');
                        btnDelete.disabled = false;
                    }
                }
            });
        });
    </script>
@endsection