<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; margin: 0; padding: 20px; }
        .container { background-color: #ffffff; max-width: 600px; margin: 0 auto; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header { background-color: #173e67; color: #ffffff; padding: 25px; text-align: center; border-bottom: 4px solid #ea580c; }
        .header h2 { margin: 0; font-size: 22px; font-weight: 800; letter-spacing: 0.5px; }
        .content { padding: 30px; color: #334155; line-height: 1.6; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { padding: 14px 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .table th { background-color: #f1f5f9; color: #64748b; font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em; }
        .table td { font-size: 15px; color: #0f172a; }
        .button-container { text-align: center; margin-top: 35px; }
        .button { display: inline-block; padding: 14px 28px; background-color: #ea580c; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 15px; transition: background 0.2s; }
        .footer { background-color: #f8fafc; padding: 20px; text-align: center; color: #94a3b8; font-size: 12px; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Acción Requerida: Validar Formación</h2>
        </div>
        <div class="content">
            <p>Hola,</p>
            <p>El sistema ha detectado <strong>{{ $pendientes->count() }} trabajador(es)</strong> en estado de nueva alta o reactivación que están pendientes de revisión por parte del departamento de Prevención.</p>

            <table class="table">
                <thead>
                    <tr>
                        <th>Trabajador</th>
                        <th>ID RRHH</th>
                        <th>DNI/NIE</th>
                        <th>Departamento</th>
                        <th>Puesto</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pendientes as $personal)
                    @php
                        $deptos = is_string($personal->departamento) ? json_decode($personal->departamento, true) ?? explode(',', $personal->departamento) : (array) $personal->departamento;
                        $deptoStr = !empty($deptos) ? implode(', ', $deptos) : 'Sin departamento';
                    @endphp
                    <tr>
                        <td><strong>{{ $personal->name }} {{ $personal->apellido }}</strong></td>
                        <td style="color: #64748b; font-weight: 600;">{{ $personal->id_rrhh ?: '—' }}</td>
                        <td style="color: #64748b;">{{ $personal->dni_nie ?: '—' }}</td>
                        <td style="color: #64748b;">{{ $personal->puesto ?: '—' }}</td>
                        <td>{{ $deptoStr }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="button-container">
                <!-- Ajusta la URL base a tu sistema -->
                <a href="{{ config('app.url') }}/cursos" class="button">Ir al directorio para revisar</a>
            </div>
        </div>
        <div class="footer">
            Este es un aviso automático generado por el módulo de Gestión de Formación. Por favor, no respondas a este correo.
        </div>
    </div>
</body>
</html>