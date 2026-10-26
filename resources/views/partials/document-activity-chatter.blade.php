@php
    $activityItems = $activities ?? collect();
    $relaciones = \App\Support\ActivityHumanizer::resolveRelationsForActivities($activityItems);
    $ignoredFields = ['id', 'uid', 'updated_at', 'created_at', 'deleted_at'];
@endphp

<section class="card shadow-sm mt-4" style="border-radius: 10px; border: 1px solid #e5e7eb;">
    <div class="card-header bg-white d-flex align-items-center" style="border-radius: 10px 10px 0 0;">
        <h3 class="card-title font-weight-bold mb-0 text-secondary">
            <i class="fas fa-history text-primary mr-2"></i> Últimos movimientos / Trazabilidad
        </h3>
    </div>
    <div class="card-body p-0">
        @if ($activityItems->isEmpty())
            <p class="text-muted mb-0 p-3">Sin movimientos registrados aún</p>
        @else
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th class="pl-3">Fecha y hora</th>
                            <th>Usuario</th>
                            <th>Acción</th>
                            <th>Cambios clave</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($activityItems as $activity)
                            @php
                                $event = (string) ($activity->event ?: '');
                                $action = match ($event) {
                                    'created' => ['Creado', 'badge-success'],
                                    'updated' => ['Actualizado', 'badge-info'],
                                    'deleted' => ['Eliminado', 'badge-danger'],
                                    'albaran_vinculado' => ['Albarán vinculado', 'badge-primary'],
                                    'albaran_actualizado' => ['Imputación actualizada', 'badge-info'],
                                    'albaran_desvinculado' => ['Albarán desvinculado', 'badge-warning'],
                                    'estado_partida' => ['Estado de partida', 'badge-dark'],
                                    default => [$activity->description ?: 'Movimiento', 'badge-secondary'],
                                };
                                $customMessage = data_get($activity->properties, 'custom_message');
                                $attributes = data_get($activity->properties, 'attributes', []);
                                $old = collect(data_get($activity->properties, 'old', []));
                                $changes = collect($attributes)
                                    ->reject(fn ($value, $field) => in_array($field, $ignoredFields, true) || is_object($value))
                                    ->map(function ($value, $field) use ($old, $relaciones) {
                                        if ($field === 'lista_articulos') {
                                            $resumen = \App\Support\ActivityHumanizer::summarizeLineChanges($old->get($field), $value);

                                            return $resumen
                                                ? '<strong>Líneas modificadas:</strong><br><div class="pl-2">' . $resumen . '</div>'
                                                : null;
                                        }
                                        if (is_array($value)) {
                                            return null;
                                        }

                                        $newValue = \App\Support\ActivityHumanizer::formatValue($field, $value, $relaciones);
                                        $oldValue = $old->has($field)
                                            ? \App\Support\ActivityHumanizer::formatValue($field, $old->get($field), $relaciones)
                                            : null;
                                        return \App\Support\ActivityHumanizer::fieldLabel($field) . ': '
                                            . ($oldValue !== null ? $oldValue . ' → ' : '') . $newValue;
                                    })
                                    ->filter()
                                    ->take(5);

                                if ($customMessage) {
                                    $changes->prepend('<strong>' . e($customMessage) . '</strong>');
                                }
                            @endphp
                            <tr>
                                <td class="pl-3 text-muted text-nowrap">
                                    {{ $activity->created_at ? \Illuminate\Support\Carbon::parse((string)$activity->created_at)->timezone('Europe/Madrid')->format('d/m/Y H:i') : '' }}
                                </td>

                                <td>{{ $activity->causer?->name ?? 'Sistema' }}</td>
                                <td><span class="badge {{ $action[1] }}">{{ $action[0] }}</span></td>
                                <td class="text-muted small">
                                    @if ($changes->isNotEmpty())
                                        {!! $changes->implode('<hr class="my-1 border-light">') !!}
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>
