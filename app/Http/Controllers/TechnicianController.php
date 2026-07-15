<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TechnicianController extends Controller
{
    public function index(Request $request): Response
    {
        if (! $request->user()->hasRole(config("roles.admin"))) {
            abort(403);
        }

        $technicians = User::role(config("roles.technician"))
            ->orderBy("name")
            ->get(["id", "name", "email", "zone"])
            ->map(fn (User $technician) => [
                "id" => $technician->id,
                "name" => $technician->name,
                "email" => $technician->email,
                "zone" => $technician->zone,
            ]);

        return Inertia::render("Admin/Technicians/Index", [
            "technicians" => $technicians,
            "zones" => config("utility.zones"),
        ]);
    }

    public function updateZone(Request $request, User $technician): RedirectResponse
    {
        Gate::authorize("manageZone", $technician);

        $validated = $request->validate([
            "zone" => ["nullable", "string", Rule::in(config("utility.zones"))],
        ]);

        $technician->zone = $validated["zone"] ?? null;
        $technician->save();

        return redirect()
            ->route("admin.technicians.index")
            ->with("status", "Technician zone updated.");
    }
}
