<?php

namespace App\Http\Controllers;

use App\Enums\PipeSegmentStatus;
use App\Models\PipeSegment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PipeSegmentController extends Controller
{
    public function index(): Response
    {
        Gate::authorize("manage", PipeSegment::class);

        $pipeSegments = PipeSegment::orderBy("zone")
            ->orderBy("name")
            ->get()
            ->map(fn (PipeSegment $pipeSegment) => $this->present($pipeSegment));

        return Inertia::render("Admin/PipeSegments/Index", [
            "pipeSegments" => $pipeSegments,
            "statuses" => $this->statusOptions(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize("manage", PipeSegment::class);

        return Inertia::render("Admin/PipeSegments/Create", [
            "zones" => config("utility.zones"),
            "zoneCenters" => config("utility.zone_centers"),
            "serviceAreaBounds" => config("utility.service_area_bounds"),
            "statuses" => $this->statusOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize("manage", PipeSegment::class);

        $bounds = config("utility.service_area_bounds");

        $validated = $request->validate([
            "name" => "required|string|max:255",
            "zone" => ["required", Rule::in(config("utility.zones"))],
            "status" => ["required", Rule::in(array_column(PipeSegmentStatus::cases(), "value"))],
            "points" => "required|array|min:2",
            "points.*.lat" => "required|numeric|between:{$bounds['min_lat']},{$bounds['max_lat']}",
            "points.*.lng" => "required|numeric|between:{$bounds['min_lng']},{$bounds['max_lng']}",
        ]);

        PipeSegment::create([
            "name" => $validated["name"],
            "zone" => $validated["zone"],
            "status" => $validated["status"],
            "points" => $validated["points"],
            "created_by" => $request->user()->id,
        ]);

        return redirect()
            ->route("admin.pipe-segments.index")
            ->with("status", "Pipe segment added.");
    }

    public function updateStatus(Request $request, PipeSegment $pipeSegment): RedirectResponse
    {
        Gate::authorize("manage", PipeSegment::class);

        $validated = $request->validate([
            "status" => ["required", Rule::in(array_column(PipeSegmentStatus::cases(), "value"))],
        ]);

        $pipeSegment->status = $validated["status"];
        $pipeSegment->save();

        return redirect()
            ->route("admin.pipe-segments.index")
            ->with("status", "Pipe segment status updated.");
    }

    public function destroy(PipeSegment $pipeSegment): RedirectResponse
    {
        Gate::authorize("manage", PipeSegment::class);

        $pipeSegment->delete();

        return redirect()
            ->route("admin.pipe-segments.index")
            ->with("status", "Pipe segment removed.");
    }

    protected function statusOptions(): array
    {
        return array_map(
            fn (PipeSegmentStatus $status) => ["value" => $status->value, "label" => $status->label()],
            PipeSegmentStatus::cases(),
        );
    }

    protected function present(PipeSegment $pipeSegment): array
    {
        return [
            "id" => $pipeSegment->id,
            "name" => $pipeSegment->name,
            "zone" => $pipeSegment->zone,
            "status" => $pipeSegment->status->value,
            "status_label" => $pipeSegment->status->label(),
            "points" => $pipeSegment->points,
            "created_by" => $pipeSegment->createdBy?->name,
            "created_at" => $pipeSegment->created_at->toDateString(),
        ];
    }
}
