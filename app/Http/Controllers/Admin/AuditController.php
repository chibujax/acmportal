<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')->latest('created_at');

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('resource')) {
            $query->where('resource_type', $request->resource);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(50)->withQueryString();

        $resourceTypes = ActivityLog::select('resource_type')
            ->distinct()
            ->orderBy('resource_type')
            ->pluck('resource_type');

        return view('admin.audit.index', compact('logs', 'resourceTypes'));
    }
}
