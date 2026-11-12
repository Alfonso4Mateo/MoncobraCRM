@extends('adminlte::page')

@section('title', 'Editar almacén - MoncobraCRM')

@section('content')
<section class="container-fluid py-4">
    <a href="{{ route('almacenes.index') }}">← Volver a almacenes</a>
    <h1 class="mt-3">Editar almacén</h1>
    <form action="{{ route('almacenes.update', $almacen) }}" method="POST" class="card card-body mt-3">
        @csrf @method('PUT')
        <label for="nombre_almacen">Nombre</label>
        <input id="nombre_almacen" name="nombre_almacen" class="form-control mb-3" required value="{{ old('nombre_almacen', $almacen->nombre) }}">
        <label for="descripcion_almacen">Descripción</label>
        <textarea id="descripcion_almacen" name="descripcion_almacen" class="form-control mb-3" rows="4">{{ old('descripcion_almacen', $almacen->descripcion) }}</textarea>
        <div><a href="{{ route('almacenes.index') }}" class="btn btn-secondary">Cancelar</a> <button class="btn btn-primary">Guardar cambios</button></div>
    </form>
</section>
@endsection
