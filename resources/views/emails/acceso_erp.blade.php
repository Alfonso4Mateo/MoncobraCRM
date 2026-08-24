<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso ERP Moncobra</title>
</head>
<body style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; margin: 0; padding: 40px 0;">

    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
        <!-- Cabecera -->
        <tr>
            <td style="background-color: #173e67; padding: 30px; text-align: center;">
                <h1 style="color: #ffffff; margin: 0; font-size: 24px; letter-spacing: 1px;">ERP MONCOBRA</h1>
                <img src="{{ $message->embed(public_path('images/moncobra-1l.png')) }}" alt="Logo Moncobra" style="margin-top: 15px; width: 120px; height: auto;">
            </td>
        </tr>
        
        <!-- Cuerpo -->
        <tr>
            <td style="padding: 40px 30px;">
                <h2 style="color: #333333; margin-top: 0; font-size: 20px;">Hola, {{ $user->name }}:</h2>
                
                <p style="color: #555555; font-size: 16px; line-height: 1.6; margin-bottom: 25px;">
                    Se ha habilitado tu cuenta de usuario para acceder al sistema de gestión ERP. Para completar el registro y poder iniciar sesión, necesitas establecer tu contraseña personal.
                </p>

                <div style="text-align: center; margin: 35px 0;">
                    <a href="{{ $url }}" style="background-color: #2563eb; color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 6px; font-weight: bold; font-size: 16px; display: inline-block;">
                        Configurar mi contraseña
                    </a>
                </div>

                <p style="color: #555555; font-size: 16px; line-height: 1.6;">
                    Este enlace de acceso seguro expirará en 60 minutos. Si no has solicitado este correo o no necesitas acceso al ERP, puedes ignorar este mensaje.
                </p>
                
                <p style="color: #555555; font-size: 16px; line-height: 1.6; margin-top: 30px;">
                    Un saludo,<br>
                    <strong>El equipo de Soporte Técnico</strong>
                </p>
            </td>
        </tr>

        <!-- Pie de página -->
        <tr>
            <td style="background-color: #f8fafc; padding: 20px 30px; text-align: center; border-top: 1px solid #e2e8f0;">
                <p style="color: #94a3b8; font-size: 12px; margin: 0;">
                    Si tienes problemas para hacer clic en el botón "Configurar mi contraseña", copia y pega la siguiente URL en tu navegador web:<br>
                    <a href="{{ $url }}" style="color: #2563eb; word-break: break-all;">{{ $url }}</a>
                </p>
            </td>
        </tr>
    </table>

</body>
</html>