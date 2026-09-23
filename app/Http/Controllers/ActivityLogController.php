<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use Carbon\Carbon; // 1. IMPORTANTE: Importar Carbon para manejar las horas

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:255'],
            'event' => ['nullable', 'in:created,updated,deleted,restored'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $activities = Activity::query()
            ->with('causer')
            ->when($validated['q'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('description', 'like', "%{$search}%")
                        ->orWhere('subject_type', 'like', "%{$search}%")
                        ->orWhere('log_name', 'like', "%{$search}%")
                        ->orWhere('properties', 'like', "%{$search}%"); // <-- Añade esta línea
                });
            })
            ->when($validated['model'] ?? null, fn ($query, string $model) => $query->where('subject_type', $model))
            ->when($validated['event'] ?? null, fn ($query, string $event) => $query->where('description', $event))
            // 3. MODIFICADO: Cambiamos whereDate por where y formateamos con Carbon para respetar las horas y minutos
            ->when($validated['from'] ?? null, fn ($query, string $from) => $query->where('created_at', '>=', Carbon::parse($from)->format('Y-m-d H:i:s')))
            ->when($validated['to'] ?? null, fn ($query, string $to) => $query->where('created_at', '<=', Carbon::parse($to)->format('Y-m-d H:i:s')))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $models = Activity::query()
            ->whereNotNull('subject_type')
            ->distinct()
            ->orderBy('subject_type')
            ->pluck('subject_type');

        return view('activity-log.index', compact('activities', 'models'));
    }
}