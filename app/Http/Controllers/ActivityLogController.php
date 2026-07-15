<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        if (! $request->user()->hasAnyRole([config("roles.admin"), config("roles.management")])) {
            abort(403);
        }

        $logs = ActivityLog::with("causedBy")
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (ActivityLog $log) => [
                "id" => $log->id,
                "subject_type" => class_basename($log->subject_type),
                "subject_id" => $log->subject_id,
                "from_status" => $log->from_status,
                "to_status" => $log->to_status,
                "caused_by" => $log->causedBy?->name ?? "System",
                "notes" => $log->notes,
                "created_at" => $log->created_at->toDateTimeString(),
            ]);

        return Inertia::render("Admin/ActivityLog/Index", [
            "logs" => $logs,
        ]);
    }
}
