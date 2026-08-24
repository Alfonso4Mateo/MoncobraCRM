<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Alertas de Formación</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px;">
    
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e0e6e8;">
        
        <!-- Cabecera -->
        <div style="background-color: #173e67; padding: 20px; text-align: center;">
            <h2 style="color: #ffffff; margin: 0; font-size: 20px;">Sistema de Gestión de Formación</h2>
            <p style="color: #a3c2e0; margin: 5px 0 0 0; font-size: 14px;">Resumen Automático de Caducidades</p>
        </div>

        <div style="padding: 30px;">
            <p style="color: #4a5568; font-size: 15px; line-height: 1.5; margin-top: 0;">
                Hola equipo,<br><br>Este es el informe automatizado sobre el estado de las certificaciones y cursos del personal activo.
            </p>

            <!-- BLOQUE ROJO: Caducados -->
            @if(count($caducados) > 0)
                <h3 style="color: #e53e3e; font-size: 16px; margin-top: 30px; border-bottom: 2px solid #fed7d7; padding-bottom: 5px;">
                    🔴 Cursos Caducados (Acción Inmediata)
                </h3>
                <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                    <thead>
                        <tr>
                            <th style="text-align: left; padding: 8px; background-color: #f7fafc; color: #4a5568; font-size: 13px; border-bottom: 1px solid #e2e8f0;">Trabajador</th>
                            <th style="text-align: left; padding: 8px; background-color: #f7fafc; color: #4a5568; font-size: 13px; border-bottom: 1px solid #e2e8f0;">Curso</th>
                            <th style="text-align: left; padding: 8px; background-color: #f7fafc; color: #4a5568; font-size: 13px; border-bottom: 1px solid #e2e8f0;">Fecha Cad.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($caducados as $item)
                            <tr>
                                <td style="padding: 8px; border-bottom: 1px solid #edf2f7; font-size: 14px; color: #2d3748; font-weight: bold;">{{ $item['trabajador'] }}</td>
                                <td style="padding: 8px; border-bottom: 1px solid #edf2f7; font-size: 14px; color: #4a5568;">{{ $item['curso'] }}</td>
                                <td style="padding: 8px; border-bottom: 1px solid #edf2f7; font-size: 14px; color: #e53e3e; font-weight: bold;">{{ $item['fecha'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            <!-- BLOQUE NARANJA: En Aviso -->
            @if(count($enAviso) > 0)
                <h3 style="color: #dd6b20; font-size: 16px; margin-top: 30px; border-bottom: 2px solid #feebc8; padding-bottom: 5px;">
                    🟠 Próximos a Caducar (En aviso)
                </h3>
                <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                    <thead>
                        <tr>
                            <th style="text-align: left; padding: 8px; background-color: #f7fafc; color: #4a5568; font-size: 13px; border-bottom: 1px solid #e2e8f0;">Trabajador</th>
                            <th style="text-align: left; padding: 8px; background-color: #f7fafc; color: #4a5568; font-size: 13px; border-bottom: 1px solid #e2e8f0;">Curso</th>
                            <th style="text-align: left; padding: 8px; background-color: #f7fafc; color: #4a5568; font-size: 13px; border-bottom: 1px solid #e2e8f0;">Fecha Cad.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($enAviso as $item)
                            <tr>
                                <td style="padding: 8px; border-bottom: 1px solid #edf2f7; font-size: 14px; color: #2d3748; font-weight: bold;">{{ $item['trabajador'] }}</td>
                                <td style="padding: 8px; border-bottom: 1px solid #edf2f7; font-size: 14px; color: #4a5568;">{{ $item['curso'] }}</td>
                                <td style="padding: 8px; border-bottom: 1px solid #edf2f7; font-size: 14px; color: #dd6b20; font-weight: bold;">{{ $item['fecha'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            <!-- Botón de acción -->
            <div style="text-align: center; margin-top: 40px;">
                <a href="{{ url('/cursos') }}" style="background-color: #ea580c; color: #ffffff; text-decoration: none; padding: 12px 25px; border-radius: 6px; font-weight: bold; display: inline-block;">
                    Abrir Gestor de Cursos
                </a>
            </div>
        </div>

        <div style="background-color: #f7fafc; padding: 15px; text-align: center; border-top: 1px solid #e2e8f0;">
            <p style="color: #a0aec0; font-size: 12px; margin: 0;">
                Este es un mensaje generado automáticamente por el ERP. Por favor, no respondas a este correo.
            </p>
        </div>
    </div>
</body>
</html>