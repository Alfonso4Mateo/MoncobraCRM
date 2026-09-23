<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dotación EPI - {{ $puesto->nombre }}</title>
    <style>
        body { font-family: sans-serif; padding: 40px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f4f4f4; }
    </style>
</head>
<body>
    <h1 style="color: #173e67;">Documento de Entrega de EPIs</h1>
    <h2>Puesto de Trabajo: {{ $puesto->nombre }}</h2>
    
    <p>Este es el borrador del listado que inyectaremos en el PDF final:</p>

    <table>
        <thead>
            <tr>
                <th>Categoría</th>
                <th>Equipo de Protección</th>
                <th>Cantidad Requerida</th>
            </tr>
        </thead>
        <tbody>
            @foreach($puesto->epis as $epi)
                <tr>
                    <td>{{ $epi->categoria ?: 'General' }}</td>
                    <td>{{ $epi->nombre }}</td>
                    <td>{{ $epi->pivot->cantidad }} Uds.</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>