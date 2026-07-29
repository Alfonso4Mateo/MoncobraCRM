<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Configurar Acceso — Moncobra</title>
 
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('node_modules/@fortawesome/fontawesome-free/css/all.min.css') }}">
    <!-- Custom CSS via Vite -->
    @vite(['resources/css/reset-password.css'])
</head>
<body>
 
<div class="login-card">
 
    <!-- Logo -->
    <div class="login-logo">
        <div class="logo-icon">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
            </svg>
        </div>
        <span class="logo-text">Moncobra<span>ERP</span></span>
    </div>
 
    <!-- Cabecera -->
    <div class="form-header">
        <h1 class="form-title">¡Bienvenido al equipo!</h1>
        <p class="form-subtitle">Crea una contraseña segura para activar tu cuenta y acceder al sistema.</p>
    </div>
 
    <!-- Formulario -->
    <form action="{{ route('password.update') }}" method="POST" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
 
        <div class="field-group">
 
            <!-- Email -->
            <div class="field-wrapper">
                <label class="field-label" for="email">Correo electrónico corporativo</label>
                <div class="field-input-wrap">
                    <!-- Añadido request()->email para que se autorellene con el correo del enlace -->
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="custom-input @error('email') is-invalid @enderror"
                        placeholder="tu@empresa.com"
                        value="{{ request()->email ?? old('email') }}"
                        readonly
                        required
                        autocomplete="email"
                        style="background-color: #f3f4f6; cursor: not-allowed;"
                    >
                    <span class="field-icon fas fa-envelope"></span>
                </div>
                @error('email')
                    <span class="invalid-feedback" role="alert">{{ $message }}</span>
                @enderror
                <small style="display: block; margin-top: 5px; color: #6b7280; font-size: 0.8rem;">Vinculado a tu expediente de trabajador.</small>
            </div>
 
            <!-- Contraseña -->
            <div class="field-wrapper" style="margin-top: 15px;">
                <label class="field-label" for="password">Tu nueva contraseña</label>
                <div class="field-input-wrap">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="custom-input @error('password') is-invalid @enderror"
                        placeholder="Mínimo 8 caracteres"
                        required
                        autofocus
                        autocomplete="new-password"
                    >
                    <span class="field-icon fas fa-lock"></span>
                </div>
                @error('password')
                    <span class="invalid-feedback" role="alert">{{ $message }}</span>
                @enderror
            </div>
 
            <!-- Confirmación Contraseña -->
            <div class="field-wrapper">
                <label class="field-label" for="password_confirmation">Repite la contraseña</label>
                <div class="field-input-wrap">
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        class="custom-input @error('password_confirmation') is-invalid @enderror"
                        placeholder="Vuelve a escribirla"
                        required
                        autocomplete="new-password"
                    >
                    <span class="field-icon fas fa-lock"></span>
                </div>
                @error('password_confirmation')
                    <span class="invalid-feedback" role="alert">{{ $message }}</span>
                @enderror
            </div>
 
        </div>
 
        <!-- Botón -->
        <button type="submit" class="submit-btn" style="margin-top: 20px;">
            Activar cuenta y Entrar
            <span class="submit-btn-icon fas fa-check"></span>
        </button>
 
    </form>
 
</div>
 
</body>
</html>