<?php

namespace App\Http\Controllers;

use App\Support\ActivityHumanizer;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $misProyectosIds = ($user && $user->role !== 'superadmin')
            ? $user->proyectos()->pluck('proyectos.id')->all()
            : null;

        $baseQuery = Activity::query()
            ->whereNotNull('subject_type');

        if ($misProyectosIds !== null) {
            if (empty($misProyectosIds)) {
                $baseQuery->whereRaw('1 = 0');
            } else {
                $baseQuery->where(function ($q) use ($misProyectosIds) {
                    // 1. Logs cuya propiedad attributes/old/root tenga proyecto_id del usuario
                    foreach ($misProyectosIds as $pid) {
                        $pidInt = (int) $pid;
                        $q->orWhere('properties->proyecto_id', $pidInt)
                          ->orWhere('properties->attributes->proyecto_id', $pidInt)
                          ->orWhere('properties->old->proyecto_id', $pidInt)
                          ->orWhereJsonContains('properties->proyecto_ids', $pidInt);
                    }

                    // 2. Logs de modelos que tienen columna proyecto_id o relación proyectos
                    $q->orWhereHasMorph('subject', [
                        \App\Models\Puesto::class,
                        \App\Models\Epi::class,
                        \App\Models\PuestoEpiDocumento::class,
                        \App\Models\Documento::class,
                        \App\Models\Presupuesto::class,
                        \App\Models\PedidoCliente::class,
                        \App\Models\AlbaranCliente::class,
                        \App\Models\Cliente::class,
                        \App\Models\Inventario::class,
                        \App\Models\InventarioVariante::class,
                    ], function ($sub) use ($misProyectosIds) {
                        $sub->whereIn('proyecto_id', $misProyectosIds);
                    });

                    $q->orWhereHasMorph('subject', [
                        \App\Models\Personal::class,
                        \App\Models\Curso::class,
                    ], function ($sub) use ($misProyectosIds) {
                        $sub->whereHas('proyectos', fn ($pq) => $pq->whereIn('proyectos.id', $misProyectosIds));
                    });
                });
            }
        }

        $activities = (clone $baseQuery)
            ->with(['causer', 'subject'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%' . $request->string('q')->trim() . '%';
                $query->where(function ($query) use ($term) {
                    $query->where('description', 'like', $term)
                        ->orWhere('subject_id', 'like', $term)
                        ->orWhere('properties', 'like', $term)
                        ->orWhereHas('causer', fn ($causer) => $causer->where('name', 'like', $term));
                });
            })
            ->when($request->filled('model'), fn ($query) => $query->where('subject_type', $request->string('model')))
            ->when($request->filled('event'), fn ($query) => $query->where('event', $request->string('event')))
            ->when($request->filled('from'), fn ($query) => $query->where('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($query) => $query->where('created_at', '<=', $request->date('to')))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $models = (clone $baseQuery)
            ->distinct()
            ->orderBy('subject_type')
            ->pluck('subject_type');

        $relaciones = ActivityHumanizer::resolveRelationsForActivities($activities);

        return view('activity-log.index', compact('activities', 'models', 'relaciones'));
    }
}