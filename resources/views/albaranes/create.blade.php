@extends('adminlte::page')

@section('title', 'Crear Albaran Cliente - MoncobraCRM')

@section('header-title')
    <i class="fas fa-file-invoice"></i> ALBARAN CLIENTE
@endsection

@section('content')
    @include('albaranes.partials.form', [
        'mode' => 'create',
        'clientes' => $clientes,
        'pedidoContext' => $pedidoContext ?? null,
        'pedidoBolsa' => $pedidoBolsa ?? false,
        'pedidoModoRestringido' => $pedidoModoRestringido ?? false,
        'pedidoPendienteFacturar' => $pedidoPendienteFacturar ?? null,
        'lineasIniciales' => $lineasIniciales ?? [],
        'pedidoDefaults' => $pedidoDefaults ?? [],
    ])
@endsection

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap4.min.css" rel="stylesheet">
    @vite(['resources/css/albaranes-form.css'])
@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    @vite(['resources/js/albaranes-form.js'])
    
    <script>
        // --- PROTECCIÓN FRONTEND CONTRA DOBLE ENVÍO ---
            const formularios = document.querySelectorAll('form');
            formularios.forEach(form => {
                form.addEventListener('submit', function (e) {
                    
                    // --- NUEVA LÓGICA DE BLOQUEO ---
                    // Buscamos si hay algún input con el borde rojo (que indica que se excedió el límite)
                    const inputsConError = this.querySelectorAll('input[style*="border-color: rgb(192, 57, 43)"], input[style*="border-color: #c0392b"]');
                    if (inputsConError.length > 0) {
                        e.preventDefault(); // Detenemos el envío
                        alert("Por favor, corrige los importes o cantidades marcados en rojo antes de guardar. Hay líneas que superan el saldo disponible.");
                        return false;
                    }
                    // --- FIN NUEVA LÓGICA ---
                    
                    const btnSubmit = this.querySelector('button[type="submit"]');
                    if (btnSubmit) {
                        if (!this.checkValidity()) return;
                        
                        btnSubmit.disabled = true;
                        btnSubmit.style.pointerEvents = 'none';
                        
                        const originalWidth = btnSubmit.offsetWidth;
                        if(originalWidth > 0) btnSubmit.style.width = originalWidth + 'px';
                        
                        btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando';
                    }
                });
            });
    </script>
@endsection