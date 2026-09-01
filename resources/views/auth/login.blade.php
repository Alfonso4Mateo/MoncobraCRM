<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Iniciar Sesión — Factumon</title>
 
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS via Vite -->
    @vite(['resources/css/login.css'])
</head>
<body>
 
<div class="login-card">
 
    <!-- Logo -->
    <div class="login-logo-container">
        <img src="{{ asset('images/moncobra-1l.png') }}" alt="Logotipo Moncobra" class="company-logo">
        <span class="app-name">Factumon</span>
    </div>
 
    <!-- Cabecera -->
    <div class="form-header">
        <h1 class="form-title">Bienvenido de vuelta</h1>
        <p class="form-subtitle">Introduce tus credenciales para continuar.</p>
    </div>
 
    <!-- Formulario -->
    <form action="{{ route('login') }}" method="POST" novalidate>
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
 
            <!-- Contraseña -->
            <div class="field-wrapper">
                <label class="field-label" for="password">Contraseña</label>
                <div class="field-input-wrap">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="custom-input @error('password') is-invalid @enderror"
                        placeholder="••••••••"
                        required
                        autocomplete="current-password"
                    >
                    <!-- Cambiamos fa-lock por fa-eye y añadimos un ID y estilo de cursor -->
                    <span class="field-icon fas fa-eye" id="togglePassword" style="cursor: pointer;" title="Mostrar/Ocultar contraseña"></span>
                </div>
                @error('password')
                    <span class="invalid-feedback" role="alert">{{ $message }}</span>
                @enderror
            </div>
 
        </div>
 
        <!-- Recuérdame + ¿Olvidaste? -->
        <div class="form-options">
            <label class="remember-wrap">
                <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                <span class="remember-label">Recuérdame</span>
            </label>
            <a href="{{ route('password.request') }}" class="forgot-link">¿Olvidaste tu contraseña?</a>
        </div>
 
        <!-- Botón -->
        <button type="submit" class="submit-btn">
            Iniciar sesión
            <span class="submit-btn-icon fas fa-arrow-right"></span>
        </button>
 
    </form>
</div>
 
<!-- Script para visibilidad de contraseña -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.querySelector('#togglePassword');
            const passwordInput = document.querySelector('#password');

            if(togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function (e) {
                    // Alternar el atributo type
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    
                    // Alternar el icono (ojo abierto / ojo cerrado)
                    this.classList.toggle('fa-eye');
                    this.classList.toggle('fa-eye-slash');
                });
            }
        });
    </script>
</body>
</html>
