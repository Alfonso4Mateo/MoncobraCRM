<?php

namespace App\Http\Controllers;

use App\Support\ActivityHumanizer;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $activities = Activity::query()
            ->with(['causer', 'subject'])
            ->whereNotNull('subject_type')
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%' . $request->string('q')->trim() . '%';
                $query->where(function ($query) use ($term) {
                    $query->where('description', 'like', $term)
                        ->orWhere('subject_id', 'like', $term)
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

        $models = Activity::query()
            ->whereNotNull('subject_type')
            ->distinct()
            ->orderBy('subject_type')
            ->pluck('subject_type');

        $relaciones = ActivityHumanizer::resolveRelationsForActivities($activities);

        return view('activity-log.index', compact('activities', 'models', 'relaciones'));
    }
}
