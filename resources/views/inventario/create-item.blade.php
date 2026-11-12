@extends('adminlte::page')

@section('title', 'Crear Nuevo Ítem de Inventario - MoncobraCRM')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/inventario-item-create.css'])
    <style>
        .inventory-item-layout { display: block !important; width: 100% !important; max-width: 100% !important; }
        .inventory-item-head { display: flex !important; justify-content: space-between !important; align-items: center !important; flex-wrap: wrap !important; gap: 1rem !important; margin-bottom: 1.5rem !important; }
        .inventory-item-head__info { flex: 1 1 500px; }
        .inventory-item-head__info h1 { margin: 0.25rem 0 !important; }
        .inventory-item-head__info p { margin: 0 !important; }
        .btn-erp-back { display: inline-flex; align-items: center; gap: 0.5rem; background: #fff; border: 1px solid #cbd5e1; color: #173e67; font-weight: 800; font-size: 0.85rem; padding: 0.6rem 1.15rem; border-radius: 10px; text-decoration: none !important; transition: all 0.15s ease; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04); white-space: nowrap; }
        .btn-erp-back:hover { background: #f8fafc; border-color: #94a3b8; color: #0f2747; }

        .item-section--stacked { display: block !important; }
        .item-section--stacked .item-section-label { margin-bottom: 1rem !important; max-width: 100% !important; }
        .item-section--stacked .item-section-fields { width: 100% !important; grid-template-columns: minmax(0, 1fr) !important; }
        .item-section .section-content { width: 100% !important; max-width: none !important; }

        .inventory-item-form .item-section-fields .section-content {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 1rem 1.25rem;
            align-items: start;
        }
        .inventory-item-form .section-content > .field-group.field-full { grid-column: 1 / -1 !important; width: 100% !important; }
        .inventory-item-form .section-content > .field-code { grid-column: span 4; }
        .inventory-item-form .section-content > .field-name { grid-column: span 5; }
        .inventory-item-form .section-content > .field-class { grid-column: span 3; }
        .inventory-item-form .section-content > .field-provider { grid-column: span 3; }
        .inventory-item-form .section-content > .field-provider-name { grid-column: span 3; }
        .inventory-item-form .section-content > .field-description { grid-column: span 6; }

        .inventory-item-form .item-section-fields.fields-3 .section-content {
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) !important;
        }
        .inventory-item-form .item-section-fields.fields-3 .section-content > .field-group {
            grid-column: auto !important;
        }

        /* Switch de variantes */
        .variant-toggle-card { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.25rem; margin: 1rem 0; border: 1px solid #dce6f4; border-radius: 14px; background: #f8fbff; }
        .variant-toggle-card input[type="checkbox"] { width: 2.75rem; height: 1.45rem; cursor: pointer; accent-color: #173e67; }
        .variant-section-hidden { display: none !important; }

        /* Creador de Atributos por Chips (Compacto en Grid) */
        .variant-type-adder {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            padding: 0.85rem 1rem;
            background: #f8fbff;
            border: 1px solid #d7e1ee;
            border-radius: 12px;
            margin-bottom: 1rem;
        }
        .variant-type-adder__Quick { display: flex; align-items: center; flex-wrap: wrap; gap: 0.45rem; }
        .variant-type-adder__label { font-size: 0.78rem; font-weight: 800; color: #475569; text-transform: uppercase; margin-right: 0.25rem; }
        .btn-quick-type {
            background: #fff;
            border: 1px solid #cbd5e1;
            color: #173e67;
            font-weight: 800;
            font-size: 0.78rem;
            padding: 0.38rem 0.75rem;
            border-radius: 999px;
            cursor: pointer;
            transition: all 0.15s;
        }
        .btn-quick-type:hover { background: #eff6ff; border-color: #93c5fd; color: #1d4ed8; }
        .variant-type-adder__custom { display: flex; gap: 0.45rem; align-items: center; flex: 1 1 260px; max-width: 380px; }
        .variant-type-adder__custom input { height: 36px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 0.75rem; font-size: 0.85rem; flex: 1; }
        .variant-type-adder__custom button { height: 36px; background: #173e67; color: #fff; border: none; border-radius: 8px; padding: 0 0.85rem; font-weight: 800; font-size: 0.8rem; cursor: pointer; white-space: nowrap; }

        .variant-chips-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
            width: 100%;
        }
        .variant-type-card {
            background: #fff;
            border: 1px solid #dbe4f0;
            border-radius: 12px;
            padding: 0.9rem 1rem;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.02);
        }
        .variant-type-card__head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.6rem;
        }
        .variant-type-card__title {
            font-size: 0.82rem;
            font-weight: 800;
            text-transform: uppercase;
            color: #0f2747;
            letter-spacing: 0.04em;
        }
        .variant-type-card__remove {
            background: transparent;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 0.85rem;
            padding: 0.2rem 0.4rem;
            border-radius: 6px;
        }
        .variant-type-card__remove:hover { background: #fee2e2; color: #dc2626; }
        .variant-chips-box {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            align-items: center;
            min-height: 42px;
            padding: 0.4rem 0.6rem;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #f8fafc;
            cursor: text;
        }
        .variant-chips-box:focus-within {
            background: #fff;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        .variant-value-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: #173e67;
            color: #fff;
            font-size: 0.78rem;
            font-weight: 700;
            padding: 0.22rem 0.6rem;
            border-radius: 6px;
        }
        .variant-value-chip button {
            background: transparent;
            border: none;
            color: #cbd5e1;
            cursor: pointer;
            padding: 0;
            font-size: 0.85rem;
            line-height: 1;
        }
        .variant-value-chip button:hover { color: #fff; }
        .variant-chip-input {
            border: none !important;
            background: transparent !important;
            outline: none !important;
            box-shadow: none !important;
            padding: 0.2rem 0.25rem !important;
            font-size: 0.85rem !important;
            font-weight: 600 !important;
            color: #0f172a !important;
            flex: 1 1 90px;
            min-width: 80px;
            height: 26px !important;
        }

        /* Tabla de combinaciones compacta con scroll interno si crece */
        .variants-grid-toolbar { display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end; margin-top: 0.5rem; padding: 0.85rem 1.1rem; border: 1px solid #d7e1ee; border-radius: 14px; background: #f8fbff; }
        .variants-grid-toolbar__group { display: flex; flex-direction: column; gap: 0.35rem; }
        .variants-grid-toolbar__label { font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: #73839a; }
        .variants-grid-toolbar__inline { display: flex; flex-wrap: wrap; gap: 0.45rem; align-items: center; }
        .variants-grid-toolbar__inline input { width: 100px; height: 36px; border: 1px solid #d5dfec; border-radius: 8px; padding: 0 0.65rem; font-weight: 700; color: #0f2747; background: #fff; }
        .variants-grid-toolbar__btn { height: 36px; padding: 0 0.85rem; border-radius: 8px; border: 1px solid #d5dfec; background: #ffffff; color: #173e67; font-weight: 800; font-size: 0.8rem; cursor: pointer; }
        .variants-grid-toolbar__btn--primary { background: #173e67; border-color: #173e67; color: #fff; }
        .variants-grid-toolbar__summary { margin-left: auto; padding: 0.55rem 0.9rem; border-radius: 10px; background: #fff; border: 1px solid #dce6f4; color: #173e67; font-weight: 800; font-size: 0.84rem; }

        .variants-grid-shell { margin-top: 0.85rem; border: 1px solid #dce6f4; border-radius: 14px; overflow: auto; max-height: 480px; background: #fff; width: 100%; }
        .variants-grid-table { width: 100%; margin: 0; border-collapse: separate; border-spacing: 0; }
        .variants-grid-table thead th { position: sticky; top: 0; z-index: 2; background: #f7f9fc; border-bottom: 1px solid #dce6f4 !important; color: #64748b; font-size: 0.7rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; padding: 0.65rem 0.85rem !important; white-space: nowrap; }
        .variants-grid-table tbody td { border-top: 1px solid #eef3f8 !important; padding: 0.45rem 0.85rem !important; vertical-align: middle !important; color: #1e293b; font-size: 0.86rem; }

        .variant-row-critico td { background: #fff5f5; }
        .variant-row-bajo td { background: #fffbeb; }
        .variant-row-optimo td { background: #ffffff; }
        .variant-row-inactive td { background: #f8fafc !important; opacity: 0.55; }

        .variant-attr-badge { display: inline-block; font-weight: 800; color: #0f2747; background: #f1f5f9; border: 1px solid #e2e8f0; padding: 0.2rem 0.6rem; border-radius: 6px; font-size: 0.82rem; }
        .variant-stock-input { width: 105px; height: 34px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 0.6rem; font-weight: 800; color: #0f2747; background: #fff; }
        .variant-stock-input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15); }

        .variant-state-chip { display: inline-flex; align-items: center; justify-content: center; min-width: 5.2rem; padding: 0.22rem 0.55rem; border-radius: 999px; font-size: 0.68rem; font-weight: 800; text-transform: uppercase; }
        .variant-state-chip--optimo { background: #dcfce7; color: #15803d; }
        .variant-state-chip--bajo { background: #fef3c7; color: #b45309; }
        .variant-state-chip--critico { background: #fee2e2; color: #b91c1c; }
        .variant-state-chip--neutral { background: #e2e8f0; color: #475569; }

        .variant-switch { position: relative; width: 42px; height: 24px; display: inline-flex; align-items: center; margin: 0; }
        .variant-switch input { position: absolute; inset: 0; opacity: 0; margin: 0; cursor: pointer; }
        .variant-switch__track { width: 100%; height: 100%; border-radius: 999px; background: #cbd5e1; transition: background 0.18s ease; }
        .variant-switch__thumb { position: absolute; top: 3px; left: 3px; width: 18px; height: 18px; border-radius: 50%; background: #fff; box-shadow: 0 2px 4px rgba(15, 23, 42, 0.2); transition: transform 0.18s ease; }
        .variant-switch input:checked + .variant-switch__track { background: #173e67; }
        .variant-switch input:checked + .variant-switch__track + .variant-switch__thumb { transform: translateX(18px); }
        .variants-grid-empty { padding: 1.5rem; text-align: center; color: #64748b; font-weight: 700; }

        .btn-manage-attrs {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            background: #0f2747;
            color: #ffffff;
            border: 1px solid #0f2747;
            border-radius: 9px;
            padding: 0.48rem 0.95rem;
            font-size: 0.8rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .btn-manage-attrs:hover { background: #173e67; }
        .btn-quick-type.is-selected {
            background: #173e67;
            border-color: #173e67;
            color: #ffffff;
        }
        /* Estilos del Modal de Gestión de Atributos */
        .erp-modal-backdrop { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.55); z-index: 1060; display: none; align-items: center; justify-content: center; padding: 1rem; backdrop-filter: blur(2px); }
        .erp-modal-backdrop.is-open { display: flex; }
        .erp-modal-dialog { background: #fff; border-radius: 16px; width: 100%; max-width: 480px; box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.2); border: 1px solid #e2e8f0; overflow: hidden; }
        .erp-modal-header { padding: 1.1rem 1.4rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; }
        .erp-modal-title { font-size: 1.05rem; font-weight: 800; color: #0f2747; margin: 0; }
        .erp-modal-close { background: transparent; border: none; color: #94a3b8; font-size: 1.3rem; cursor: pointer; line-height: 1; }
        .erp-modal-body { padding: 1.4rem; }
        .erp-modal-footer { padding: 0.9rem 1.4rem; background: #f8fafc; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
        .erp-label { display: block; font-size: 0.74rem; font-weight: 800; text-transform: uppercase; color: #475569; margin-bottom: 0.5rem; letter-spacing: 0.05em; }
        .erp-input { width: 100%; border: 1px solid #cbd5e1; border-radius: 9px; padding: 0.52rem 0.8rem; font-weight: 600; color: #0f172a; font-size: 0.88rem; }
        .btn-erp-primary { background: #173e67; color: #fff; border: 1px solid #173e67; border-radius: 9px; padding: 0.52rem 1rem; font-weight: 800; font-size: 0.84rem; cursor: pointer; }
        .attr-catalog-list { display: flex; flex-direction: column; gap: 0.45rem; max-height: 240px; overflow-y: auto; }
        .attr-catalog-item { display: flex; justify-content: space-between; align-items: center; padding: 0.55rem 0.8rem; border: 1px solid #e2e8f0; border-radius: 9px; background: #f8fafc; }
        .attr-catalog-item strong { color: #0f2747; font-size: 0.88rem; font-weight: 800; }

        @media (max-width: 992px) {
            .inventory-item-form .section-content > .field-code,
            .inventory-item-form .section-content > .field-name,
            .inventory-item-form .section-content > .field-class,
            .inventory-item-form .section-content > .field-provider,
            .inventory-item-form .section-content > .field-provider-name,
            .inventory-item-form .section-content > .field-description { grid-column: span 6; }
        }
        @media (max-width: 768px) {
            .inventory-item-form .item-section-fields .section-content { grid-template-columns: 1fr; }
            .inventory-item-form .section-content > .field-group { grid-column: 1 / -1 !important; }
        }
    </style>
@endsection

@section('content')
    <section class="inventory-item-page">
        <header class="inventory-item-head">
            <div class="inventory-item-head__info">
                <span class="module-tag">MÓDULO DE INVENTARIO</span>
                <h1>{{ $varianteBase ? 'Añadir variantes a un ítem existente' : 'Crear Nuevo Ítem de Inventario' }}</h1>
                <p>{{ $varianteBase ? 'Se generarán nuevas combinaciones y cada una tendrá su propio stock.' : 'Registro de nuevas existencias con variantes (tallas, colores), especificaciones técnicas y parámetros de stock.' }}</p>
            </div>
            <div>
                <a href="{{ route('inventario.index') }}" class="btn-erp-back">
                    <i class="fas fa-arrow-left"></i> Volver al Inventario
                </a>
            </div>
        </header>

        @if ($errors->any())
            <div class="item-alert" role="alert">
                <strong>No se pudo crear el ítem.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="inventory-item-layout">
            <form action="{{ route('inventario.store') }}" method="POST" class="inventory-item-form" novalidate>
                @csrf
                @if(!empty($varianteBase))
                    <input type="hidden" name="inventario_variante_id" value="{{ $varianteBase->id }}">
                @endif

                @include('inventario.create-item.partials.section-basics')

                <div class="variant-toggle-card">
                    <div>
                        <strong style="color: #0f2747; font-size: 0.95rem;">¿Este artículo tiene variantes?</strong>
                        <small class="d-block text-muted">Actívalo si necesitas desglosar el producto por tallas, colores o medidas.</small>
                    </div>
                    <label class="mb-0 d-flex align-items-center" style="cursor: pointer;">
                        <input id="has-variants-toggle" type="checkbox" @checked(!empty($valoresIniciales))>
                    </label>
                </div>


                @include('inventario.create-item.partials.section-variant-types')
                @php($showStockActual = true)
                @include('inventario.create-item.partials.section-stock')
                @include('inventario.create-item.partials.section-variant-grid')
                @include('inventario.create-item.partials.form-footer')
            </form>
        </div>
    </section>

    {{-- MODAL RÁPIDO: NUEVA CLASE --}}
<div class="erp-modal-backdrop" id="modalCrearClaseQuick" aria-hidden="true" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.55); z-index: 1080; align-items: center; justify-content: center; backdrop-filter: blur(2px);">
    <div class="erp-modal-dialog" style="background: #fff; border-radius: 16px; width: 100%; max-width: 420px; padding: 1.5rem; box-shadow: 0 20px 25px -5px rgba(15,23,42,0.2);">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="m-0 font-weight-bold" style="color: #0f2747;"><i class="fas fa-tags mr-2 text-primary"></i> Nueva Clase Rápida</h5>
            <button type="button" class="close text-dark js-close-quick-modal" data-target="modalCrearClaseQuick">&times;</button>
        </div>
        <div class="form-group mb-3">
            <label class="font-weight-bold" style="font-size: 0.8rem; text-transform: uppercase; color: #475569;">Nombre de la clase</label>
            <input type="text" id="quickInputNombreClase" class="form-control" placeholder="Ej. Herramientas, Consumibles...">
        </div>
        <div class="text-right">
            <button type="button" class="btn btn-light js-close-quick-modal mr-2" data-target="modalCrearClaseQuick" style="font-weight: 700;">Cancelar</button>
            <button type="button" class="btn btn-primary font-weight-bold" id="btnSaveQuickClase" style="background: #173e67; border: none;">Guardar Clase</button>
        </div>
    </div>
</div>

{{-- MODAL RÁPIDO: NUEVO ALMACÉN --}}
<div class="erp-modal-backdrop" id="modalCrearAlmacenQuick" aria-hidden="true" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.55); z-index: 1080; align-items: center; justify-content: center; backdrop-filter: blur(2px);">
    <div class="erp-modal-dialog" style="background: #fff; border-radius: 16px; width: 100%; max-width: 420px; padding: 1.5rem; box-shadow: 0 20px 25px -5px rgba(15,23,42,0.2);">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="m-0 font-weight-bold" style="color: #0f2747;"><i class="fas fa-warehouse mr-2 text-primary"></i> Nuevo Almacén Rápido</h5>
            <button type="button" class="close text-dark js-close-quick-modal" data-target="modalCrearAlmacenQuick">&times;</button>
        </div>
        <div class="form-group mb-2">
            <label class="font-weight-bold" style="font-size: 0.8rem; text-transform: uppercase; color: #475569;">Nombre del almacén</label>
            <input type="text" id="quickInputNombreAlmacen" class="form-control" placeholder="Ej. Almacén Taller Sur">
        </div>
        <div class="form-group mb-3">
            <label class="font-weight-bold" style="font-size: 0.8rem; text-transform: uppercase; color: #475569;">Código corto</label>
            <input type="text" id="quickInputCodigoAlmacen" class="form-control" placeholder="Ej. ALM-SUR">
        </div>
        <div class="text-right">
            <button type="button" class="btn btn-light js-close-quick-modal mr-2" data-target="modalCrearAlmacenQuick" style="font-weight: 700;">Cancelar</button>
            <button type="button" class="btn btn-primary font-weight-bold" id="btnSaveQuickAlmacen" style="background: #173e67; border: none;">Guardar Almacén</button>
        </div>
    </div>
</div>
@endsection

@section('js')
    <style>
        .stock-migration-banner {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            padding: 0.85rem 1.1rem;
            margin-bottom: 0.85rem;
            display: none;
        }
        .stock-migration-banner.is-visible { display: block; }
        .stock-migration-title {
            font-size: 0.8rem;
            font-weight: 800;
            color: #1e3a8a;
            text-transform: uppercase;
            margin-bottom: 0.45rem;
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }
        .stock-migration-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .stock-migration-pill {
            font-size: 0.78rem;
            font-weight: 700;
            padding: 0.28rem 0.65rem;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            background: #fff;
        }
        .stock-migration-pill--ok {
            background: #dcfce7;
            border-color: #86efac;
            color: #166534;
        }
        .stock-migration-pill--pending {
            background: #fef3c7;
            border-color: #fcd34d;
            color: #92400e;
        }
        .stock-migration-pill--over {
            background: #fee2e2;
            border-color: #fca5a5;
            color: #991b1b;
        }
        .level-inherit-bar {
            margin-top: 0.55rem;
            padding: 0.45rem 0.6rem;
            background: #f8fbff;
            border: 1px solid #dbeafe;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.4rem;
        }
        .level-inherit-bar__label {
            font-size: 0.7rem;
            font-weight: 800;
            color: #1e40af;
            text-transform: uppercase;
        }
        .level-inherit-select {
            height: 28px;
            border-radius: 6px;
            border: 1px solid #93c5fd;
            background: #fff;
            color: #0f2747;
            font-size: 0.75rem;
            font-weight: 800;
            padding: 0 0.45rem;
        }
    </style>

    <script>
        (function () {
            const initialValuesMap = @json(old('atributos_variante', $valoresIniciales ?? []));
            const variantesIniciales = @json(old('variantes', $variantesIniciales ?? []));
            const almacenesDisponibles = @json($almacenes ?? []);
            const defaultAlmacenNombre = @json(old('almacen', $varianteBase?->almacen ?? ($almacenes[0]->nombre ?? '')));
            const defaultStockMin = @json(old('stock_minimo', $varianteBase?->stock_minimo ?? 10));
            const defaultNivelCrit = @json(old('nivel_critico', $varianteBase?->nivel_critico ?? 5));

            const tiposAtributosInput = document.getElementById('tipos_atributos');
            const atributosContainer = document.getElementById('atributos-container');
            const variantesContainer = document.getElementById('variantes-container');
            const variantesHead = document.getElementById('variantes-head');
            const stockTotal = document.getElementById('stock-total');
            const variantsSummary = document.getElementById('variants-summary');
            const filterVariantsInput = document.getElementById('filter-variants-input');
            const toggleExpandGrid = document.getElementById('btn-toggle-expand-grid');
            const variantsGridShell = document.getElementById('variants-grid-shell');
            const toggleExpandLabel = document.getElementById('label-toggle-expand');
            const bulkStockValue = document.getElementById('bulk-stock-value');
            const bulkApplyStock = document.getElementById('bulk-apply-stock');
            const bulkSetZero = document.getElementById('bulk-set-zero');
            const bulkActivateAll = document.getElementById('bulk-activate-all');
            const bulkDeactivateAll = document.getElementById('bulk-deactivate-all');
            const bulkAlmacenSelect = document.getElementById('bulk-almacen-select');
            const bulkApplyAlmacen = document.getElementById('bulk-apply-almacen');
            const variantsToggle = document.getElementById('has-variants-toggle');
            const variantSections = [document.getElementById('section-variant-types'), document.getElementById('section-variant-grid')];
            const wrapperStockSimple = document.getElementById('wrapper-section-stock-simple');
            const simpleStock = document.getElementById('stock_actual');

            const STORAGE_KEY_ATTRS = 'factumon_catalogo_atributos_v1';
            const DEFAULT_ATTRS = ['Talla', 'Color', 'Material', 'Ignífugo'];
            const quickPillsContainer = document.getElementById('quick-attributes-pills');
            const modalAtributos = document.getElementById('modalGestionarAtributos');
            const btnOpenManageAttrs = document.getElementById('btn-open-manage-attributes');
            const btnCloseModalAttrs = document.getElementById('btnCloseModalAtributos');
            const btnDoneModalAttrs = document.getElementById('btnDoneModalAtributos');
            const btnResetAttrs = document.getElementById('btnResetAtributosDefault');
            const inputNuevoAttr = document.getElementById('inputNuevoAtributoCatalogo');
            const btnGuardarAttr = document.getElementById('btnGuardarAtributoCatalogo');
            const listaCatalogoModal = document.getElementById('listaCatalogoAtributosModal');

            // Crear banner dinámico de auditoría de stock previo encima de la tabla
            const migrationBanner = document.createElement('div');
            migrationBanner.className = 'stock-migration-banner';
            migrationBanner.innerHTML = `
                <div class="stock-migration-title">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Clasificación del stock existente al añadir nuevos atributos:</span>
                </div>
                <div class="stock-migration-pills" id="stock-migration-pills"></div>
            `;
            variantsGridShell?.parentNode?.insertBefore(migrationBanner, variantsGridShell);
            const migrationPillsContainer = document.getElementById('stock-migration-pills');

            let variantData = {};
            let tiposOrdenados = [];
            let attributeCatalog = [];

            const preferredHeritageValueByType = {};
            const snapshotsByLevelCount = {};
            const exactRowEdits = {};
            const skuCustomTokens = {};

            const cleanNorm = (str) => String(str ?? '')
                .trim()
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '');

            const parseTipos = (value) => String(value || '')
                .split(',')
                .map((item) => item.trim())
                .filter((item) => item.length > 0);

            const normalizeKey = (atributos) => Object.entries(atributos || {})
                .map(([tipo, valor]) => `${cleanNorm(tipo)}:${cleanNorm(valor)}`)
                .join('|');

            const NEUTRAL_VALUES = ['no', '00', '0', 'false', 'estandar', 'standard', 'std', 'unica', 'unico', 'uni', 'n/a', 'na', 'normal'];

            const getSuggestedDefaultValuesForAttr = (attrName) => {
                const norm = cleanNorm(attrName);
                if (norm.includes('ign')) return ['Sí', 'No'];
                const matchingInitKey = Object.keys(initialValuesMap || {}).find((k) => cleanNorm(k) === norm);
                if (matchingInitKey && Array.isArray(initialValuesMap[matchingInitKey]) && initialValuesMap[matchingInitKey].length > 0) {
                    return [...initialValuesMap[matchingInitKey]];
                }
                return [];
            };

            const getDefaultSkuToken = (valor) => {
                const raw = String(valor || '').trim();
                const clean = cleanNorm(raw);

                if (['si', 'yes', 'true'].includes(clean)) return '01';
                if (['no', 'false'].includes(clean)) return '00';

                const lookup = {
                    estandar: 'STD',
                    standard: 'STD',
                    unica: 'UNI',
                    unico: 'UNI',
                    pequeno: 'PEQ',
                    pequena: 'PEQ',
                    mediano: 'MED',
                    mediana: 'MED',
                    grande: 'GRA',
                    metacrilato: 'MET',
                    metacrilatro: 'MET',
                    'fibra carbono': 'FCA',
                    'fibra de carbono': 'FCA',
                };
                if (lookup[clean]) return lookup[clean];

                if (raw.length <= 3) {
                    return clean.toUpperCase().replace(/[^A-Z0-9]/g, '');
                }

                const words = clean.split(/\s+/).filter(Boolean);
                if (words.length >= 2) {
                    return (words[0].substring(0, 1) + words[1].substring(0, 2)).toUpperCase();
                }

                return clean.substring(0, 3).toUpperCase().replace(/[^A-Z0-9]/g, '');
            };

            const skuTokenKey = (attrName, value) => `${cleanNorm(attrName)}:${cleanNorm(value)}`;

            const getSkuTokenForValue = (attrName, value) => {
                const key = skuTokenKey(attrName, value);
                if (skuCustomTokens[key] === undefined) {
                    skuCustomTokens[key] = getDefaultSkuToken(value);
                }
                return skuCustomTokens[key];
            };

            const computeVariantSku = (baseCode, attributes) => {
                const root = String(baseCode || 'PRD').trim().toUpperCase().replace(/-+$/, '');
                const segments = Object.entries(attributes || {})
                    .map(([attribute, value]) => getSkuTokenForValue(attribute, value))
                    .filter(Boolean);
                return [root, ...segments].join('-');
            };

            const saveEntryInSnapshot = (atributosObj, entryData) => {
                const keys = Object.keys(atributosObj || {});
                const levelCount = keys.length;
                const key = levelCount === 0 ? '__BASE__' : normalizeKey(atributosObj);

                if (!snapshotsByLevelCount[levelCount]) {
                    snapshotsByLevelCount[levelCount] = {};
                }
                snapshotsByLevelCount[levelCount][key] = {
                    atributos: { ...atributosObj },
                    ...entryData,
                };
                if (levelCount > 0) {
                    exactRowEdits[key] = {
                        atributos: { ...atributosObj },
                        ...entryData,
                    };
                }
            };

            const buildInitialCombinationMeta = () => {
                (Array.isArray(variantesIniciales) ? variantesIniciales : []).forEach((variant) => {
                    const atributos = variant && typeof variant === 'object' ? (variant.atributos || {}) : {};
                    const normalizedAttributes = {};

                    Object.entries(atributos).forEach(([tipo, valor]) => {
                        const firstValue = Array.isArray(valor) ? valor[0] : valor;
                        if (firstValue !== undefined && firstValue !== null && String(firstValue).trim() !== '') {
                            normalizedAttributes[String(tipo).trim()] = String(firstValue).trim();
                        }
                    });

                    saveEntryInSnapshot(normalizedAttributes, {
                        id: variant.id ? String(variant.id) : '',
                        sku: variant.sku || variant.codigo || '',
                        almacen: variant.almacen_id || variant.almacen || defaultAlmacenNombre || '',
                        stock_actual: String(variant.stock_actual ?? '0'),
                        stock_minimo: String(variant.stock_minimo ?? defaultStockMin),
                        nivel_critico: String(variant.nivel_critico ?? defaultNivelCrit),
                        activo: variant.activo !== undefined ? Boolean(variant.activo) : true,
                    });
                });

                if (!snapshotsByLevelCount[0] && Object.keys(snapshotsByLevelCount).length === 0) {
                    const baseStockVal = parseInt(simpleStock?.value || '0', 10) || 0;
                    if (baseStockVal > 0) {
                        saveEntryInSnapshot({}, {
                            id: '',
                            sku: '',
                            almacen: defaultAlmacenNombre || '',
                            stock_actual: String(baseStockVal),
                            stock_minimo: String(defaultStockMin),
                            nivel_critico: String(defaultNivelCrit),
                            activo: true,
                        });
                    }
                }
            };

            buildInitialCombinationMeta();

            const collectCurrentRowMeta = () => {
                if (!variantesContainer) return;
                const rows = variantesContainer.querySelectorAll('tr[data-combination-key]');
                if (rows.length === 0) return;

                const currentLevel = tiposOrdenados.filter((t) => Array.isArray(variantData[t]) && variantData[t].length > 0).length;
                if (currentLevel > 0) {
                    snapshotsByLevelCount[currentLevel] = {};
                }

                rows.forEach((row) => {
                    const key = row.dataset.combinationKey || '';
                    if (!key) return;

                    const rowAttrs = {};
                    row.querySelectorAll('input[data-attr-name]').forEach((inp) => {
                        rowAttrs[inp.dataset.attrName] = inp.value;
                    });

                    saveEntryInSnapshot(rowAttrs, {
                        id: row.querySelector('input[data-row-id]')?.value || '',
                        sku: row.querySelector('input[data-sku-input]')?.value || '',
                        almacen: row.querySelector('select[data-almacen-select]')?.value || '',
                        stock_actual: row.querySelector('input[data-stock-input]')?.value ?? '0',
                        stock_minimo: row.querySelector('input[data-min-input]')?.value ?? String(defaultStockMin),
                        nivel_critico: row.querySelector('input[data-crit-input]')?.value ?? String(defaultNivelCrit),
                        activo: (row.querySelector('input[data-row-active]')?.value ?? '1') === '1',
                    });
                });
            };

            const getLatestBaselineParentSnapshot = (currentLevelCount) => {
                for (let lvl = currentLevelCount - 1; lvl >= 0; lvl--) {
                    const mapAtLvl = snapshotsByLevelCount[lvl];
                    if (!mapAtLvl) continue;
                    const entries = Object.values(mapAtLvl);
                    const totalStockAtLvl = entries.reduce((acc, item) => acc + (parseInt(item.stock_actual || '0', 10) || 0), 0);
                    if (totalStockAtLvl > 0) {
                        return { level: lvl, entries };
                    }
                }
                return null;
            };

            const getEffectiveHeritageValueForType = (tipo) => {
                const normTipo = cleanNorm(tipo);
                const values = variantData[tipo] || [];
                if (values.length === 0) return null;

                if (preferredHeritageValueByType[normTipo] !== undefined) {
                    const chosen = preferredHeritageValueByType[normTipo];
                    if (chosen === '__MANUAL__') return '__MANUAL__';
                    if (values.some((v) => cleanNorm(v) === cleanNorm(chosen))) {
                        return chosen;
                    }
                }

                const neutral = values.find((v) => NEUTRAL_VALUES.includes(cleanNorm(v)));
                if (neutral) {
                    preferredHeritageValueByType[normTipo] = neutral;
                    return neutral;
                }

                preferredHeritageValueByType[normTipo] = values[0];
                return values[0];
            };

            const resolveMetaForCombinations = (combinations, forceReinherit = false) => {
                const resolvedMap = {};
                const currLevelCount = combinations[0] ? Object.keys(combinations[0]).length : 0;

                const sameLevelSnapshot = snapshotsByLevelCount[currLevelCount] || {};
                let sameLevelStockSum = 0;
                combinations.forEach((attrs) => {
                    const k = normalizeKey(attrs);
                    const found = sameLevelSnapshot[k] || exactRowEdits[k];
                    if (found) {
                        sameLevelStockSum += parseInt(found.stock_actual || '0', 10) || 0;
                    }
                });

                const baseline = getLatestBaselineParentSnapshot(currLevelCount);

                if ((!forceReinherit || !baseline) && sameLevelStockSum > 0) {
                    combinations.forEach((attrs) => {
                        const k = normalizeKey(attrs);
                        const found = sameLevelSnapshot[k] || exactRowEdits[k];
                        if (found) {
                            resolvedMap[k] = { ...found, sku: '' };
                        }
                    });
                    return resolvedMap;
                }

                if (!baseline) {
                    combinations.forEach((attrs) => {
                        const k = normalizeKey(attrs);
                        const found = sameLevelSnapshot[k] || exactRowEdits[k];
                        if (found) resolvedMap[k] = { ...found, sku: '' };
                    });
                    return resolvedMap;
                }

                baseline.entries.forEach((parentMeta) => {
                    const pAttrs = parentMeta.atributos || {};
                    const pKeys = Object.keys(pAttrs);
                    const pKeysNorm = pKeys.map(cleanNorm);

                    const matchingChildren = combinations.filter((cAttrs) => {
                        if (pKeys.length === 0) return true;
                        return pKeys.every((pk) => {
                            const matchKey = Object.keys(cAttrs).find((ck) => cleanNorm(ck) === cleanNorm(pk));
                            return matchKey && cleanNorm(cAttrs[matchKey]) === cleanNorm(pAttrs[pk]);
                        });
                    });

                    if (matchingChildren.length === 0) return;

                    const newTypesInBranch = Object.keys(matchingChildren[0]).filter((ck) => !pKeysNorm.includes(cleanNorm(ck)));
                    const isManualMode = newTypesInBranch.some((nt) => getEffectiveHeritageValueForType(nt) === '__MANUAL__');

                    const targetChild = isManualMode ? null : matchingChildren.find((cAttrs) => {
                        return newTypesInBranch.every((nt) => {
                            const targetVal = getEffectiveHeritageValueForType(nt);
                            return targetVal && cleanNorm(cAttrs[nt]) === cleanNorm(targetVal);
                        });
                    });

                    const chosenKey = targetChild ? normalizeKey(targetChild) : null;

                    matchingChildren.forEach((cAttrs) => {
                        const cKey = normalizeKey(cAttrs);
                        const isChosen = chosenKey && cKey === chosenKey;

                        resolvedMap[cKey] = {
                            id: isChosen ? (parentMeta.id || '') : '',
                            sku: '',
                            almacen: parentMeta.almacen || defaultAlmacenNombre || '',
                            stock_actual: isChosen ? String(parentMeta.stock_actual ?? '0') : '0',
                            stock_minimo: parentMeta.stock_minimo ?? String(defaultStockMin),
                            nivel_critico: parentMeta.nivel_critico ?? String(defaultNivelCrit),
                            activo: parentMeta.activo !== undefined ? parentMeta.activo : true,
                        };
                    });
                });

                return resolvedMap;
            };

            const updateMigrationAuditBanner = () => {
                if (!migrationBanner || !migrationPillsContainer) return;

                const activeTypes = tiposOrdenados.filter((t) => Array.isArray(variantData[t]) && variantData[t].length > 0);
                const baseline = getLatestBaselineParentSnapshot(activeTypes.length);

                if (!baseline || activeTypes.length <= baseline.level) {
                    migrationBanner.classList.remove('is-visible');
                    return;
                }

                migrationBanner.classList.add('is-visible');
                migrationPillsContainer.innerHTML = '';

                baseline.entries.forEach((parentMeta) => {
                    const pAttrs = parentMeta.atributos || {};
                    const pKeys = Object.keys(pAttrs);
                    const expectedStock = parseInt(parentMeta.stock_actual || '0', 10) || 0;
                    if (expectedStock <= 0) return;

                    const labelBase = pKeys.length === 0
                        ? 'Stock base inicial'
                        : pKeys.map((k) => `${k} ${pAttrs[k]}`).join(' · ');

                    let currentBranchSum = 0;
                    variantesContainer.querySelectorAll('tr[data-combination-key]').forEach((row) => {
                        const activeInp = row.querySelector('input[data-row-active]');
                        if (activeInp && activeInp.value !== '1') return;

                        const rowAttrs = {};
                        row.querySelectorAll('input[data-attr-name]').forEach((inp) => {
                            rowAttrs[inp.dataset.attrName] = inp.value;
                        });

                        const belongsToParent = pKeys.every((pk) => {
                            const mk = Object.keys(rowAttrs).find((rk) => cleanNorm(rk) === cleanNorm(pk));
                            return mk && cleanNorm(rowAttrs[mk]) === cleanNorm(pAttrs[pk]);
                        });

                        if (belongsToParent) {
                            const stInput = row.querySelector('input[data-stock-input]');
                            currentBranchSum += parseInt(stInput?.value || '0', 10) || 0;
                        }
                    });

                    const pill = document.createElement('span');
                    const diff = expectedStock - currentBranchSum;

                    if (diff === 0) {
                        pill.className = 'stock-migration-pill stock-migration-pill--ok';
                        pill.innerHTML = `<i class="fas fa-check-circle mr-1"></i>${labelBase}: <strong>${currentBranchSum} / ${expectedStock} uds.</strong> cuadradas`;
                    } else if (diff > 0) {
                        pill.className = 'stock-migration-pill stock-migration-pill--pending';
                        pill.innerHTML = `<i class="fas fa-exclamation-circle mr-1"></i>${labelBase}: <strong>${currentBranchSum} de ${expectedStock} uds.</strong> (Faltan ${diff} por asignar)`;
                    } else {
                        pill.className = 'stock-migration-pill stock-migration-pill--over';
                        pill.innerHTML = `<i class="fas fa-plus-circle mr-1"></i>${labelBase}: <strong>${currentBranchSum} uds.</strong> (${Math.abs(diff)} uds. nuevas añadidas sobre las ${expectedStock} previas)`;
                    }

                    migrationPillsContainer.appendChild(pill);
                });
            };

            const initVariantState = () => {
                const fromInput = parseTipos(tiposAtributosInput?.value || '');
                const fromKeys = Object.keys(initialValuesMap || {});
                tiposOrdenados = Array.from(new Set([...fromInput, ...fromKeys]));

                tiposOrdenados.forEach((tipo) => {
                    const rawVals = initialValuesMap[tipo] || [];
                    const cleanVals = (Array.isArray(rawVals) ? rawVals : [rawVals])
                        .map((v) => String(v || '').trim())
                        .filter((v) => v !== '');

                    if (cleanVals.length === 0) {
                        variantData[tipo] = getSuggestedDefaultValuesForAttr(tipo);
                    } else {
                        variantData[tipo] = Array.from(new Set(cleanVals));
                    }
                });
            };

            const loadSavedCatalog = () => {
                try {
                    const raw = localStorage.getItem(STORAGE_KEY_ATTRS);
                    const saved = raw ? JSON.parse(raw) : DEFAULT_ATTRS;
                    const merged = Array.from(new Set([...(Array.isArray(saved) ? saved : DEFAULT_ATTRS), ...tiposOrdenados]));
                    return merged.length > 0 ? merged : [...DEFAULT_ATTRS];
                } catch (e) {
                    return [...DEFAULT_ATTRS];
                }
            };

            const saveCatalog = () => {
                try {
                    localStorage.setItem(STORAGE_KEY_ATTRS, JSON.stringify(attributeCatalog));
                } catch (e) {}
            };

            const syncHiddenTypesInput = () => {
                if (tiposAtributosInput) {
                    tiposAtributosInput.value = tiposOrdenados.join(', ');
                }
            };

            const syncVariantVisibility = () => {
                const enabled = variantsToggle?.checked ?? false;
                variantSections.forEach((section) => section?.classList.toggle('variant-section-hidden', !enabled));
                if (wrapperStockSimple) {
                    wrapperStockSimple.classList.toggle('variant-section-hidden', enabled);
                }
                if (simpleStock) {
                    simpleStock.disabled = enabled;
                    simpleStock.closest('.item-section')?.classList.toggle('variant-section-hidden', enabled);
                }
            };
            variantsToggle?.addEventListener('change', syncVariantVisibility);

            const renderQuickPills = () => {
                if (!quickPillsContainer) return;
                quickPillsContainer.innerHTML = '';

                if (attributeCatalog.length === 0) {
                    quickPillsContainer.innerHTML = `<span class="text-muted" style="font-size: 0.8rem; font-weight: 600;">Sin atributos rápidos. Pulsa "Gestionar / Añadir atributos".</span>`;
                    return;
                }

                attributeCatalog.forEach((attrName) => {
                    const isActive = tiposOrdenados.some((t) => cleanNorm(t) === cleanNorm(attrName));
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = `btn-quick-type ${isActive ? 'is-selected' : ''}`;
                    btn.innerHTML = `<i class="fas ${isActive ? 'fa-check' : 'fa-plus'}"></i> ${attrName}`;
                    btn.title = isActive ? 'Quitar atributo del producto' : 'Añadir atributo al producto';
                    btn.addEventListener('click', () => toggleVariantType(attrName));
                    quickPillsContainer.appendChild(btn);
                });
            };

            const toggleVariantType = (rawName) => {
                const clean = String(rawName || '').trim();
                if (!clean) return;

                collectCurrentRowMeta();

                const existingIdx = tiposOrdenados.findIndex((t) => cleanNorm(t) === cleanNorm(clean));
                let wasRemoved = false;
                if (existingIdx !== -1) {
                    const realKey = tiposOrdenados[existingIdx];
                    const prevActiveCount = tiposOrdenados.filter((t) => Array.isArray(variantData[t]) && variantData[t].length > 0).length;
                    delete snapshotsByLevelCount[prevActiveCount];
                    tiposOrdenados.splice(existingIdx, 1);
                    delete variantData[realKey];
                    delete preferredHeritageValueByType[cleanNorm(realKey)];
                    wasRemoved = true;
                } else {
                    tiposOrdenados.push(clean);
                    variantData[clean] = getSuggestedDefaultValuesForAttr(clean);
                }

                renderQuickPills();
                renderVariantTypeCards();
                renderCombinations(!wasRemoved);
            };

            const renderVariantTypeCards = () => {
                syncHiddenTypesInput();
                if (!atributosContainer) return;
                atributosContainer.innerHTML = '';

                const activeTypesCount = tiposOrdenados.filter((t) => Array.isArray(variantData[t]) && variantData[t].length > 0).length;
                const baseline = getLatestBaselineParentSnapshot(activeTypesCount);

                tiposOrdenados.forEach((tipo, levelIdx) => {
                    const values = variantData[tipo] || [];
                    const card = document.createElement('div');
                    card.className = 'variant-type-card';

                    const head = document.createElement('div');
                    head.className = 'variant-type-card__head';
                    head.innerHTML = `
                        <span class="variant-type-card__title">Nivel ${levelIdx + 1} · ${tipo}</span>
                        <button type="button" class="variant-type-card__remove" title="Quitar este atributo">
                            <i class="fas fa-times"></i> Quitar
                        </button>
                    `;

                    head.querySelector('.variant-type-card__remove').addEventListener('click', () => {
                        collectCurrentRowMeta();
                        const prevActiveCount = tiposOrdenados.filter((t) => Array.isArray(variantData[t]) && variantData[t].length > 0).length;
                        delete snapshotsByLevelCount[prevActiveCount];
                        tiposOrdenados = tiposOrdenados.filter((t) => t !== tipo);
                        delete variantData[tipo];
                        delete preferredHeritageValueByType[cleanNorm(tipo)];
                        renderQuickPills();
                        renderVariantTypeCards();
                        renderCombinations(false);
                    });

                    const chipsBox = document.createElement('div');
                    chipsBox.className = 'variant-chips-box';

                    values.forEach((val, valIdx) => {
                        const chip = document.createElement('span');
                        chip.className = 'variant-value-chip';
                        chip.innerHTML = `
                            <span>${val}</span>
                            <input type="hidden" name="atributos_variante[${tipo}][]" value="${val}">
                            <button type="button" title="Borrar valor">&times;</button>
                        `;
                        chip.querySelector('button').addEventListener('click', () => {
                            collectCurrentRowMeta();
                            variantData[tipo].splice(valIdx, 1);
                            const currentValKey = preferredHeritageValueByType[cleanNorm(tipo)];
                            if (currentValKey && cleanNorm(currentValKey) === cleanNorm(val)) {
                                delete preferredHeritageValueByType[cleanNorm(tipo)];
                            }
                            renderVariantTypeCards();
                            renderCombinations(false);
                        });
                        chipsBox.appendChild(chip);
                    });

                    const chipInput = document.createElement('input');
                    chipInput.type = 'text';
                    chipInput.className = 'variant-chip-input';
                    chipInput.placeholder = values.length === 0 ? `Escribe valor y pulsa Enter...` : `+ añadir...`;

                    const commitChipValue = () => {
                        const raw = chipInput.value.split(',');
                        const hadValuesBefore = variantData[tipo].length > 0;
                        let added = false;
                        raw.forEach((piece) => {
                            const clean = piece.trim();
                            if (clean && !variantData[tipo].some((v) => cleanNorm(v) === cleanNorm(clean))) {
                                variantData[tipo].push(clean);
                                added = true;
                            }
                        });
                        if (added) {
                            collectCurrentRowMeta();
                            chipInput.value = '';
                            renderCombinations(!hadValuesBefore);
                            renderVariantTypeCards();
                            const inputs = atributosContainer.querySelectorAll('.variant-chip-input');
                            if (inputs[levelIdx]) inputs[levelIdx].focus();
                        }
                    };

                    chipInput.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter' || e.key === ',') {
                            e.preventDefault();
                            commitChipValue();
                        } else if (e.key === 'Backspace' && chipInput.value === '' && variantData[tipo].length > 0) {
                            collectCurrentRowMeta();
                            variantData[tipo].pop();
                            renderCombinations(false);
                            renderVariantTypeCards();
                            const inputs = atributosContainer.querySelectorAll('.variant-chip-input');
                            if (inputs[levelIdx]) inputs[levelIdx].focus();
                        }
                    });

                    chipInput.addEventListener('blur', commitChipValue);
                    chipsBox.addEventListener('click', () => chipInput.focus());
                    chipsBox.appendChild(chipInput);

                    card.appendChild(head);
                    card.appendChild(chipsBox);

                    const baselineAttrKeys = baseline && baseline.entries[0]
                        ? Object.keys(baseline.entries[0].atributos || {}).map(cleanNorm)
                        : [];
                    const isNewAttributeOverBaseline = baseline && !baselineAttrKeys.includes(cleanNorm(tipo));

                    if (values.length > 0 && isNewAttributeOverBaseline) {
                        const currentPreferred = getEffectiveHeritageValueForType(tipo);
                        const inheritBar = document.createElement('div');
                        inheritBar.className = 'level-inherit-bar';
                        inheritBar.innerHTML = `
                            <span class="level-inherit-bar__label">
                                <i class="fas fa-box-open mr-1"></i>¿El stock previo era de este ${tipo}?
                            </span>
                            <select class="level-inherit-select">
                                ${values.map((v) => `<option value="${v}" ${cleanNorm(currentPreferred) === cleanNorm(v) ? 'selected' : ''}>Todo era "${v}"</option>`).join('')}
                                <option value="__MANUAL__" ${currentPreferred === '__MANUAL__' ? 'selected' : ''}>Mezclado / Repartir a mano (Poner 0)</option>
                            </select>
                        `;

                        inheritBar.querySelector('select').addEventListener('change', function () {
                            preferredHeritageValueByType[cleanNorm(tipo)] = this.value;
                            renderCombinations(true);
                            renderVariantTypeCards();
                        });

                        card.appendChild(inheritBar);
                    }

                    if (values.length > 0) {
                        const skuBar = document.createElement('div');
                        skuBar.className = 'sku-tokens-bar';

                        const title = document.createElement('span');
                        title.className = 'sku-tokens-bar__title';
                        title.innerHTML = '<i class="fas fa-barcode mr-1"></i>Segmentos SKU:';
                        skuBar.appendChild(title);

                        values.forEach((value) => {
                            const tokenItem = document.createElement('label');
                            tokenItem.className = 'sku-token-item';
                            tokenItem.title = `Código en el SKU para "${value}"`;

                            const valueLabel = document.createElement('span');
                            valueLabel.textContent = `${value}:`;

                            const tokenInput = document.createElement('input');
                            tokenInput.type = 'text';
                            tokenInput.className = 'js-sku-token-input';
                            tokenInput.dataset.attr = tipo;
                            tokenInput.dataset.val = value;
                            tokenInput.maxLength = 6;
                            tokenInput.value = getSkuTokenForValue(tipo, value);

                            tokenItem.appendChild(valueLabel);
                            tokenItem.appendChild(tokenInput);
                            skuBar.appendChild(tokenItem);
                        });

                        card.appendChild(skuBar);
                    }

                    atributosContainer.appendChild(card);
                });
            };

            const renderCatalogModalList = () => {
                if (!listaCatalogoModal) return;
                listaCatalogoModal.innerHTML = '';

                if (attributeCatalog.length === 0) {
                    listaCatalogoModal.innerHTML = `<div class="text-center text-muted py-3 font-weight-bold" style="font-size: 0.84rem;">No hay atributos en la barra rápida.</div>`;
                    return;
                }

                attributeCatalog.forEach((attrName, idx) => {
                    const isUsed = tiposOrdenados.some((t) => cleanNorm(t) === cleanNorm(attrName));
                    const item = document.createElement('div');
                    item.className = 'attr-catalog-item';
                    item.innerHTML = `
                        <div>
                            <strong>${attrName}</strong>
                            ${isUsed ? '<span class="badge badge-info ml-2" style="font-size: 0.68rem;">En uso</span>' : ''}
                        </div>
                        <div class="d-flex align-items-center" style="gap: 0.4rem;">
                            <button type="button" class="btn btn-sm ${isUsed ? 'btn-outline-secondary' : 'btn-outline-primary'} js-toggle-use-attr" style="font-weight: 700; font-size: 0.75rem; border-radius: 7px;">
                                ${isUsed ? 'Quitar del ítem' : 'Usar en ítem'}
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger js-delete-catalog-attr" title="Eliminar de la barra rápida" style="border-radius: 7px;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;

                    item.querySelector('.js-toggle-use-attr').addEventListener('click', () => {
                        toggleVariantType(attrName);
                        renderCatalogModalList();
                    });

                    item.querySelector('.js-delete-catalog-attr').addEventListener('click', () => {
                        attributeCatalog.splice(idx, 1);
                        saveCatalog();
                        renderQuickPills();
                        renderCatalogModalList();
                    });

                    listaCatalogoModal.appendChild(item);
                });
            };

            const createNewCatalogAttr = () => {
                const clean = String(inputNuevoAttr?.value || '').trim();
                if (!clean) return;

                collectCurrentRowMeta();

                if (!attributeCatalog.some((a) => cleanNorm(a) === cleanNorm(clean))) {
                    attributeCatalog.push(clean);
                    saveCatalog();
                }

                if (!tiposOrdenados.some((t) => cleanNorm(t) === cleanNorm(clean))) {
                    tiposOrdenados.push(clean);
                    variantData[clean] = getSuggestedDefaultValuesForAttr(clean);
                }

                inputNuevoAttr.value = '';
                renderQuickPills();
                renderVariantTypeCards();
                renderCombinations(true);
                renderCatalogModalList();
                inputNuevoAttr.focus();
            };

            btnOpenManageAttrs?.addEventListener('click', () => {
                renderCatalogModalList();
                modalAtributos?.classList.add('is-open');
                setTimeout(() => inputNuevoAttr?.focus(), 60);
            });

            const closeAttrsModal = () => modalAtributos?.classList.remove('is-open');
            btnCloseModalAttrs?.addEventListener('click', closeAttrsModal);
            btnDoneModalAttrs?.addEventListener('click', closeAttrsModal);
            modalAtributos?.addEventListener('click', (e) => {
                if (e.target === modalAtributos) closeAttrsModal();
            });

            btnGuardarAttr?.addEventListener('click', createNewCatalogAttr);
            inputNuevoAttr?.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    createNewCatalogAttr();
                }
            });

            btnResetAttrs?.addEventListener('click', () => {
                attributeCatalog = Array.from(new Set([...DEFAULT_ATTRS, ...tiposOrdenados]));
                saveCatalog();
                renderQuickPills();
                renderCatalogModalList();
            });

            const renderTableHead = (types) => {
                if (!variantesHead) return;
                variantesHead.innerHTML = '';
                const row = document.createElement('tr');

                types.forEach((tipo) => {
                    const th = document.createElement('th');
                    th.className = 'col-attr';
                    th.textContent = tipo;
                    row.appendChild(th);
                });

                [
                    ['SKU / Código', 'col-sku'],
                    ['Almacén', 'col-almacen'],
                    ['Stock Actual', 'col-num'],
                    ['Mín.', 'col-num'],
                    ['Crítico', 'col-num'],
                    ['Estado', 'col-status'],
                    ['Activa', 'col-status'],
                ].forEach(([label, className]) => {
                    const th = document.createElement('th');
                    th.className = className;
                    th.textContent = label;
                    row.appendChild(th);
                });

                variantesHead.appendChild(row);
            };

            const updateVariantsSummary = () => {
                if (!variantsSummary || !variantesContainer) return;
                const rows = Array.from(variantesContainer.querySelectorAll('tr[data-combination-key]'));
                const activeRows = rows.filter((row) => {
                    const activeInput = row.querySelector('input[data-row-active]');
                    return activeInput ? activeInput.value === '1' : true;
                });
                variantsSummary.textContent = `${activeRows.length} activas de ${rows.length} combinaciones`;
            };

            const updateRowState = (row) => {
                const stockInput = row.querySelector('input[data-stock-input]');
                const minInput = row.querySelector('input[data-min-input]');
                const critInput = row.querySelector('input[data-crit-input]');
                const stateChip = row.querySelector('[data-state-chip]');
                const activeInput = row.querySelector('input[data-row-active]');

                const isActive = activeInput ? activeInput.value === '1' : true;
                const stockValue = parseInt(stockInput?.value || '0', 10) || 0;
                const minVal = parseInt(minInput?.value || '0', 10) || 0;
                const critVal = parseInt(critInput?.value || '0', 10) || 0;

                row.classList.remove('variant-row-optimo', 'variant-row-bajo', 'variant-row-critico', 'variant-row-inactive');

                if (!isActive) {
                    row.classList.add('variant-row-inactive');
                    if (stateChip) {
                        stateChip.className = 'variant-state-chip variant-state-chip--neutral';
                        stateChip.textContent = 'Inactiva';
                    }
                    return;
                }

                let state = 'optimo';
                if (stockValue <= critVal) {
                    state = 'critico';
                } else if (stockValue <= minVal) {
                    state = 'bajo';
                }

                row.classList.add(`variant-row-${state}`);

                if (stateChip) {
                    stateChip.className = `variant-state-chip variant-state-chip--${state}`;
                    stateChip.textContent = state === 'critico' ? 'Crítico' : (state === 'bajo' ? 'Reposición' : 'Óptimo');
                }
            };

            const setRowActiveState = (row, active) => {
                const activeInput = row.querySelector('input[data-row-active]');
                const fieldInputs = row.querySelectorAll('[data-row-field]');
                const toggleInput = row.querySelector('input[data-row-toggle]');

                if (activeInput) activeInput.value = active ? '1' : '0';
                if (toggleInput) toggleInput.checked = active;
                fieldInputs.forEach((input) => { input.disabled = !active; });

                updateRowState(row);
                updateVariantsSummary();
                updateTotalStock();
                updateMigrationAuditBanner();
            };

            const updateTotalStock = () => {
                if (!variantesContainer) return;
                const total = Array.from(variantesContainer.querySelectorAll('input[data-stock-input]'))
                    .filter((input) => !input.disabled)
                    .reduce((sum, input) => sum + (parseInt(input.value || '0', 10) || 0), 0);
                if (stockTotal) stockTotal.textContent = String(total);
            };

            const generateCombinations = (types) => {
                const activeTypes = types.filter((t) => Array.isArray(variantData[t]) && variantData[t].length > 0);
                if (activeTypes.length === 0 || activeTypes.length !== types.length) return [];

                return activeTypes.reduce((acc, type) => {
                    const values = variantData[type] || [];
                    const next = [];
                    acc.forEach((existing) => {
                        values.forEach((val) => {
                            next.push({ ...existing, [type]: val });
                        });
                    });
                    return next;
                }, [{}]);
            };

            const renderCombinations = (forceReinherit = false) => {
                if (!variantesContainer) return;
                if (!forceReinherit) {
                    collectCurrentRowMeta();
                }
                variantesContainer.innerHTML = '';
                renderTableHead(tiposOrdenados);
                const emptyColspan = tiposOrdenados.length + 7;

                if (tiposOrdenados.length === 0) {
                    variantesContainer.innerHTML = `<tr><td colspan="${emptyColspan}" class="variants-grid-empty">Selecciona arriba un atributo rápido (o gestiona tus atributos) para empezar.</td></tr>`;
                    if (stockTotal) stockTotal.textContent = '0';
                    migrationBanner?.classList.remove('is-visible');
                    updateVariantsSummary();
                    return;
                }

                const combinations = generateCombinations(tiposOrdenados);

                if (combinations.length === 0) {
                    variantesContainer.innerHTML = `<tr><td colspan="${emptyColspan}" class="variants-grid-empty">Escribe al menos un valor y pulsa Enter en cada tarjeta de atributo para ver la matriz (el stock previo de tus tallas está guardado y se asignará en cuanto escribas el valor).</td></tr>`;
                    migrationBanner?.classList.remove('is-visible');
                    updateVariantsSummary();
                    return;
                }

                const baseCodigo = document.getElementById('codigo')?.value || 'PRD';
                const resolvedMetaMap = resolveMetaForCombinations(combinations, forceReinherit);

                combinations.forEach((atributos, index) => {
                    const combinationKey = normalizeKey(atributos);
                    const saved = resolvedMetaMap[combinationKey] || {};

                    const suggestedSku = saved.sku || computeVariantSku(baseCodigo, atributos);
                    const stockValue = saved.stock_actual ?? '0';
                    const minVal = saved.stock_minimo ?? String(defaultStockMin);
                    const critVal = saved.nivel_critico ?? String(defaultNivelCrit);
                    const selectedAlm = saved.almacen || defaultAlmacenNombre || '';
                    const rowId = saved.id || '';
                    const isRowActive = saved.activo !== undefined ? saved.activo : true;

                    const row = document.createElement('tr');
                    row.dataset.combinationKey = combinationKey;
                    row.className = 'variant-row variant-row-optimo';

                    tiposOrdenados.forEach((tipo) => {
                        const td = document.createElement('td');
                        td.className = 'col-attr';
                        const badge = document.createElement('span');
                        badge.className = 'variant-attr-badge';
                        badge.textContent = atributos[tipo] ?? '—';

                        const hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = `variantes[${index}][atributos][${tipo}]`;
                        hidden.value = atributos[tipo] ?? '';
                        hidden.dataset.attrName = tipo;
                        hidden.dataset.rowField = '1';

                        td.appendChild(badge);
                        td.appendChild(hidden);
                        row.appendChild(td);
                    });

                    const skuTd = document.createElement('td');
                    skuTd.className = 'col-sku';
                    if (rowId) {
                        const hiddenId = document.createElement('input');
                        hiddenId.type = 'hidden';
                        hiddenId.name = `variantes[${index}][id]`;
                        hiddenId.value = rowId;
                        hiddenId.dataset.rowId = '1';
                        hiddenId.dataset.rowField = '1';
                        skuTd.appendChild(hiddenId);
                    }
                    const skuInput = document.createElement('input');
                    skuInput.type = 'text';
                    skuInput.name = `variantes[${index}][sku]`;
                    skuInput.value = suggestedSku;
                    skuInput.dataset.skuInput = '1';
                    skuInput.dataset.rowField = '1';
                    skuInput.className = 'form-control form-control-sm';
                    skuInput.style.fontWeight = '700';
                    skuTd.appendChild(skuInput);
                    row.appendChild(skuTd);

                    const almTd = document.createElement('td');
                    almTd.className = 'col-almacen';
                    const almSelect = document.createElement('select');
                    almSelect.name = `variantes[${index}][almacen_id]`;
                    almSelect.dataset.almacenSelect = '1';
                    almSelect.dataset.rowField = '1';
                    almSelect.className = 'custom-select custom-select-sm';
                    almSelect.style.width = '145px';
                    almSelect.style.fontWeight = '600';

                    almSelect.innerHTML = '<option value="">-- Almacén --</option>' +
                        almacenesDisponibles.map((a) => {
                            const isSel = (String(a.nombre) === String(selectedAlm) || String(a.id) === String(selectedAlm)) ? 'selected' : '';
                            return `<option value="${a.nombre}" ${isSel}>${a.nombre}</option>`;
                        }).join('');
                    almTd.appendChild(almSelect);
                    row.appendChild(almTd);

                    const stockTd = document.createElement('td');
                    stockTd.className = 'col-num';
                    const stockInput = document.createElement('input');
                    stockInput.type = 'number';
                    stockInput.min = '0';
                    stockInput.max = '999999';
                    stockInput.step = '1';
                    stockInput.name = `variantes[${index}][stock_actual]`;
                    stockInput.dataset.stockInput = '1';
                    stockInput.dataset.rowField = '1';
                    stockInput.value = stockValue;
                    stockInput.className = 'variant-stock-input';

                    stockInput.addEventListener('focus', () => stockInput.select());
                    stockInput.addEventListener('input', function () {
                        if (this.value.length > 6) this.value = this.value.slice(0, 6);
                        updateRowState(row);
                        updateTotalStock();
                        collectCurrentRowMeta();
                        updateMigrationAuditBanner();
                    });
                    stockInput.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            const allInputs = Array.from(variantesContainer.querySelectorAll('input[data-stock-input]:not(:disabled)'));
                            const currentIdx = allInputs.indexOf(stockInput);
                            if (currentIdx !== -1 && allInputs[currentIdx + 1]) {
                                allInputs[currentIdx + 1].focus();
                            }
                        }
                    });
                    stockTd.appendChild(stockInput);
                    row.appendChild(stockTd);

                    const minTd = document.createElement('td');
                    minTd.className = 'col-num';
                    const minInput = document.createElement('input');
                    minInput.type = 'number';
                    minInput.min = '0';
                    minInput.name = `variantes[${index}][stock_minimo]`;
                    minInput.dataset.minInput = '1';
                    minInput.dataset.rowField = '1';
                    minInput.value = minVal;
                    minInput.className = 'form-control form-control-sm text-center';
                    minInput.style.width = '75px';
                    minInput.style.fontWeight = '700';
                    minInput.addEventListener('input', () => updateRowState(row));
                    minTd.appendChild(minInput);
                    row.appendChild(minTd);

                    const critTd = document.createElement('td');
                    critTd.className = 'col-num';
                    const critInput = document.createElement('input');
                    critInput.type = 'number';
                    critInput.min = '0';
                    critInput.name = `variantes[${index}][nivel_critico]`;
                    critInput.dataset.critInput = '1';
                    critInput.dataset.rowField = '1';
                    critInput.value = critVal;
                    critInput.className = 'form-control form-control-sm text-center';
                    critInput.style.width = '75px';
                    critInput.style.fontWeight = '700';
                    critInput.addEventListener('input', () => updateRowState(row));
                    critTd.appendChild(critInput);
                    row.appendChild(critTd);

                    const stateTd = document.createElement('td');
                    stateTd.className = 'col-status';
                    const stateChip = document.createElement('span');
                    stateChip.dataset.stateChip = '1';
                    stateTd.appendChild(stateChip);
                    row.appendChild(stateTd);

                    const activeTd = document.createElement('td');
                    activeTd.className = 'col-status';
                    const activeWrapper = document.createElement('label');
                    activeWrapper.className = 'variant-switch';

                    const activeToggle = document.createElement('input');
                    activeToggle.type = 'checkbox';
                    activeToggle.checked = isRowActive;
                    activeToggle.dataset.rowToggle = '1';

                    const activeTrack = document.createElement('span');
                    activeTrack.className = 'variant-switch__track';
                    const activeThumb = document.createElement('span');
                    activeThumb.className = 'variant-switch__thumb';

                    activeToggle.addEventListener('change', () => {
                        setRowActiveState(row, activeToggle.checked);
                        collectCurrentRowMeta();
                    });

                    activeWrapper.appendChild(activeToggle);
                    activeWrapper.appendChild(activeTrack);
                    activeWrapper.appendChild(activeThumb);
                    activeTd.appendChild(activeWrapper);

                    const activeHidden = document.createElement('input');
                    activeHidden.type = 'hidden';
                    activeHidden.name = `variantes[${index}][activo]`;
                    activeHidden.value = isRowActive ? '1' : '0';
                    activeHidden.dataset.rowActive = '1';
                    activeTd.appendChild(activeHidden);
                    row.appendChild(activeTd);

                    variantesContainer.appendChild(row);
                    setRowActiveState(row, isRowActive);
                });

                collectCurrentRowMeta();
                updateTotalStock();
                updateVariantsSummary();
                updateMigrationAuditBanner();
            };

            const applyBulkStock = (value) => {
                const parsedValue = Math.max(0, parseInt(value || '0', 10) || 0);
                variantesContainer.querySelectorAll('tr[data-combination-key]').forEach((row) => {
                    const activeInput = row.querySelector('input[data-row-active]');
                    const stockInput = row.querySelector('input[data-stock-input]');
                    if (activeInput && activeInput.value !== '1') return;
                    if (stockInput && !stockInput.disabled) {
                        stockInput.value = String(parsedValue);
                        updateRowState(row);
                    }
                });
                collectCurrentRowMeta();
                updateTotalStock();
                updateMigrationAuditBanner();
            };

            bulkApplyStock?.addEventListener('click', () => applyBulkStock(bulkStockValue ? bulkStockValue.value : '0'));
            bulkSetZero?.addEventListener('click', () => {
                if (bulkStockValue) bulkStockValue.value = '0';
                applyBulkStock('0');
            });
            bulkActivateAll?.addEventListener('click', () => {
                variantesContainer.querySelectorAll('tr[data-combination-key]').forEach((r) => setRowActiveState(r, true));
                collectCurrentRowMeta();
            });
            bulkDeactivateAll?.addEventListener('click', () => {
                variantesContainer.querySelectorAll('tr[data-combination-key]').forEach((r) => setRowActiveState(r, false));
                collectCurrentRowMeta();
            });
            bulkApplyAlmacen?.addEventListener('click', () => {
                const chosen = bulkAlmacenSelect?.value || '';
                if (!chosen) return;
                variantesContainer.querySelectorAll('tr[data-combination-key]').forEach((row) => {
                    const sel = row.querySelector('select[data-almacen-select]');
                    if (sel && !sel.disabled) sel.value = chosen;
                });
                collectCurrentRowMeta();
            });

            const recalculateTableSkus = () => {
                const baseCodigo = document.getElementById('codigo')?.value || 'PRD';
                variantesContainer.querySelectorAll('tr[data-combination-key]').forEach((row) => {
                    const attributes = {};
                    row.querySelectorAll('input[data-attr-name]').forEach((input) => {
                        attributes[input.dataset.attrName] = input.value;
                    });

                    const skuInput = row.querySelector('input[data-sku-input]');
                    if (skuInput) skuInput.value = computeVariantSku(baseCodigo, attributes);
                });
                collectCurrentRowMeta();
            };

            document.getElementById('codigo')?.addEventListener('input', recalculateTableSkus);

            atributosContainer?.addEventListener('input', (event) => {
                const input = event.target;
                if (!input.classList.contains('js-sku-token-input')) return;

                const attr = input.dataset.attr || '';
                const value = input.dataset.val || '';
                const cleanCode = input.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 6);
                input.value = cleanCode;
                skuCustomTokens[skuTokenKey(attr, value)] = cleanCode;
                recalculateTableSkus();
            });

            filterVariantsInput?.addEventListener('input', () => {
                const query = filterVariantsInput.value.toLowerCase().trim();
                variantesContainer.querySelectorAll('tr[data-combination-key]').forEach((row) => {
                    const sku = row.querySelector('.col-sku input')?.value.toLowerCase() || '';
                    const text = `${row.innerText.toLowerCase()} ${sku}`;
                    row.style.display = text.includes(query) ? '' : 'none';
                });
            });

            toggleExpandGrid?.addEventListener('click', () => {
                const expanded = variantsGridShell?.classList.toggle('is-expanded') ?? false;
                if (toggleExpandLabel) toggleExpandLabel.textContent = expanded ? 'Compactar' : 'Ver todas';
                const icon = toggleExpandGrid.querySelector('i');
                if (icon) icon.className = expanded
                    ? 'fas fa-compress-arrows-alt mr-1'
                    : 'fas fa-expand-arrows-alt mr-1';
            });

            document.querySelectorAll('.input-auto-grow').forEach((area) => {
                const resizeArea = (el) => {
                    el.style.height = 'auto';
                    el.style.height = `${el.scrollHeight}px`;
                };
                resizeArea(area);
                area.addEventListener('input', () => resizeArea(area));
            });

            // Abrir y cerrar modales rápidos (Clase y Almacén)
            document.addEventListener('click', (e) => {
                const openBtn = e.target.closest('[data-bs-target]');
                if (openBtn) {
                    const targetId = openBtn.getAttribute('data-bs-target').replace('#', '');
                    const modalEl = document.getElementById(targetId);
                    if (modalEl) modalEl.style.display = 'flex';
                }

                const closeBtn = e.target.closest('.js-close-quick-modal');
                if (closeBtn) {
                    const targetId = closeBtn.getAttribute('data-target');
                    const modalEl = document.getElementById(targetId);
                    if (modalEl) modalEl.style.display = 'none';
                }
            });

            // Guardar Clase Rápida vía Fetch
            document.getElementById('btnSaveQuickClase')?.addEventListener('click', async () => {
                const nombre = document.getElementById('quickInputNombreClase')?.value.trim() || '';
                if (!nombre) {
                    alert('Introduce un nombre para la clase.');
                    return;
                }

                try {
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                               || document.querySelector('input[name="_token"]')?.value;

                    const response = await fetch("{{ route('clases.store') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ nombre })
                    });

                    const data = await response.json();
                    if (response.ok) {
                        const selectClase = document.getElementById('clase_id');
                        if (selectClase) {
                            const opt = document.createElement('option');
                            opt.value = data.id || data.clase?.id;
                            opt.textContent = nombre;
                            opt.selected = true;
                            selectClase.appendChild(opt);
                        }
                        document.getElementById('modalCrearClaseQuick').style.display = 'none';
                        document.getElementById('quickInputNombreClase').value = '';
                    } else {
                        alert(data.message || 'No se pudo crear la clase.');
                    }
                } catch (err) {
                    location.reload();
                }
            });

            // Guardar Almacén Rápido vía Fetch
            document.getElementById('btnSaveQuickAlmacen')?.addEventListener('click', async () => {
                const nombre = document.getElementById('quickInputNombreAlmacen')?.value.trim() || '';
                const codigo = document.getElementById('quickInputCodigoAlmacen')?.value.trim() || '';
                if (!nombre) {
                    alert('Introduce un nombre para el almacén.');
                    return;
                }

                try {
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                               || document.querySelector('input[name="_token"]')?.value;

                    const response = await fetch("{{ route('almacenes.store') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ nombre, codigo, activo: true })
                    });

                    const data = await response.json();
                    if (response.ok) {
                        const selectAlmSimple = document.getElementById('almacen');
                        if (selectAlmSimple) {
                            const opt = document.createElement('option');
                            opt.value = nombre;
                            opt.textContent = nombre;
                            opt.selected = true;
                            selectAlmSimple.appendChild(opt);
                        }

                        if (bulkAlmacenSelect) {
                            const optBulk = document.createElement('option');
                            optBulk.value = nombre;
                            optBulk.textContent = nombre;
                            optBulk.selected = true;
                            bulkAlmacenSelect.appendChild(optBulk);
                        }

                        document.querySelectorAll('select[data-almacen-select]').forEach((sel) => {
                            const optRow = document.createElement('option');
                            optRow.value = nombre;
                            optRow.textContent = nombre;
                            sel.appendChild(optRow);
                        });

                        if (Array.isArray(almacenesDisponibles)) {
                            almacenesDisponibles.push({ id: data.id || Date.now(), nombre, codigo });
                        }

                        document.getElementById('modalCrearAlmacenQuick').style.display = 'none';
                        document.getElementById('quickInputNombreAlmacen').value = '';
                        document.getElementById('quickInputCodigoAlmacen').value = '';
                    } else {
                        alert(data.message || 'No se pudo crear el almacén.');
                    }
                } catch (err) {
                    location.reload();
                }
            });

            syncVariantVisibility();
            initVariantState();
            attributeCatalog = loadSavedCatalog();
            renderQuickPills();
            renderVariantTypeCards();
            renderCombinations(false);
        })();
    </script>
@endsection