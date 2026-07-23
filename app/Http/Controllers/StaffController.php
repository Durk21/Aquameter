<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function index(): Response
    {
        Gate::authorize("manage", User::class);

        $staff = User::role(config("roles.staff"))
            ->with("roles")
            ->orderBy("name")
            ->get()
            ->map(fn (User $user) => [
                "id" => $user->id,
                "name" => $user->name,
                "email" => $user->email,
                "role" => $user->roles->first()?->name,
                "role_label" => ucfirst($user->roles->first()?->name ?? ""),
                "zone" => $user->zone,
            ]);

        return Inertia::render("Admin/Staff/Index", [
            "staff" => $staff,
            "zones" => config("utility.zones"),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize("manage", User::class);

        return Inertia::render("Admin/Staff/Create", [
            "roles" => config("roles.staff"),
            "zones" => config("utility.zones"),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize("manage", User::class);

        $validated = $request->validate([
            "name" => "required|string|max:255",
            "email" => "required|string|lowercase|email|max:255|unique:users,email",
            "password" => ["required", "confirmed", Rules\Password::defaults()],
            "role" => ["required", Rule::in(config("roles.staff"))],
            "zone" => ["nullable", "string", Rule::in(config("utility.zones"))],
        ]);

        $staff = DB::transaction(function () use ($validated) {
            $staff = User::create([
                "name" => $validated["name"],
                "email" => $validated["email"],
                "password" => Hash::make($validated["password"]),
                "zone" => $validated["role"] === config("roles.technician") ? ($validated["zone"] ?? null) : null,
            ]);

            // Not mass-assignable — set directly. An admin-provisioned
            // staff account is vouched for on creation, no verification
            // email to click since the admin typed the address in.
            $staff->email_verified_at = now();
            $staff->save();

            $staff->assignRole($validated["role"]);

            return $staff;
        });

        event(new Registered($staff));

        return redirect()
            ->route("admin.staff.index")
            ->with("status", "Staff account created.");
    }

    public function updateZone(Request $request, User $staff): RedirectResponse
    {
        Gate::authorize("manageZone", $staff);

        $validated = $request->validate([
            "zone" => ["nullable", "string", Rule::in(config("utility.zones"))],
        ]);

        $staff->zone = $validated["zone"] ?? null;
        $staff->save();

        return redirect()
            ->route("admin.staff.index")
            ->with("status", "Technician zone updated.");
    }
}
