@extends('adminlte::page') 

@section('title', 'Acceso Denegado')

@section('content_header')
    <h1>Acceso Restringido</h1>
@stop

@section('content')
    <div class="error-page" style="margin-top: 100px;">
        <!-- El número grande del error -->
        <h2 class="headline text-danger"> 403</h2>

        <div class="error-content">
            <h3><i class="fas fa-exclamation-triangle text-danger"></i> ¡Oops! Acceso denegado.</h3>
            
            <p>
                Actualmente no tienes permisos para esta acción, acude a tu superior para que te los conceda.
            </p>
            
            <!-- Botón o enlace para no dejar al usuario atrapado -->
            <p>
                Mientras tanto, puedes <a href="{{ url('/dashboard') }}">volver al panel principal</a> o utilizar el menú lateral para navegar a otras secciones del sistema.
            </p>
        </div>
    </div>
@stop