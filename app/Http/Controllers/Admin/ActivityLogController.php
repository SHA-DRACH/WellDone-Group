<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

/**
 * The audit trail — who changed what, and when.
 *
 * Read-only by design: an audit log that can be edited from the panel it
 * audits is not an audit log.
 */
class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('activity.view'), 403);

        $activities = Activity::query()
            ->with('causer:id,name,email')
            ->when($request->query('log'), fn ($q, $log) => $q->where('log_name', $log))
            ->when($request->query('event'), fn ($q, $event) => $q->where('event', $event))
            ->when($request->query('causer'), fn ($q, $id) => $q->where('causer_id', $id))
            ->when($request->query('q'), fn ($q, $term) => $q->where('description', 'like', '%'.$term.'%'))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.activity.index', [
            'records' => $activities,
            'search' => $request->query('q', ''),
            'logs' => Activity::query()->distinct()->orderBy('log_name')->pluck('log_name', 'log_name')->filter(),
            'events' => Activity::query()->distinct()->orderBy('event')->pluck('event', 'event')->filter(),
            'causers' => User::query()->whereIn('id', Activity::query()->distinct()->pluck('causer_id')->filter())
                ->orderBy('name')
                ->pluck('name', 'id'),
        ]);
    }
}
