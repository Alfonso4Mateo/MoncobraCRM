<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Solicitar Reset de Contraseña — Factumon</title>
 
    <!-- Font Awesome vía CDN para evitar el error 404 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS via Vite -->
    @vite(['resources/css/reset-password.css'])
</head>
<body>
 
<div class="login-card">
 
    <!-- Logo Estructurado -->
    <div class="login-logo-container">
        <img src="{{ asset('images/moncobra-1l.png') }}" alt="Logotipo Moncobra" class="company-logo">
        <span class="app-name">Factumon</span>
    </div>
 
    <!-- Cabecera -->
    <div class="form-header">
        <h1 class="form-title">Olvidaste tu Contraseña</h1>
        <p class="form-subtitle">Introduce tu correo electrónico para recibir un enlace de reset.</p>
    </div>
 
    <!-- Formulario -->
    <form action="{{ route('password.email') }}" method="POST" novalidate>
        @csrf
 
        <div class="field-group">
 
            <!-- Email -->
            <div class="field-wrapper">
                <label class="field-label" for="email">Correo electrónico</label>
                <div class="field-input-wrap">
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="custom-input @error('email') is-invalid @enderror"
                        placeholder="tu@empresa.com"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="email"
                    >
                    <span class="field-icon fas fa-envelope"></span>
                </div>
                @error('email')
                    <span class="invalid-feedback" role="alert">{{ $message }}</span>
                @enderror
            </div>
 
        </div>
 
        <!-- Botón -->
        <button type="submit" class="submit-btn">
            Enviar Enlace de Reset
            <span class="submit-btn-icon fas fa-paper-plane"></span>
        </button>
 
    </form>
 
    <!-- Enlace de volver al login -->
    <div class="register-wrap">
        <p>
            <a href="{{ route('login') }}" class="forgot-link">Volver al inicio de sesión</a>
        </p>
    </div>
 
</div>
 
</body>
</html>