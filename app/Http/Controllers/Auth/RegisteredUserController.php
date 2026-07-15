<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\User;
use App\Services\AccountNumberGenerator;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render("Auth/Register", [
            "zones" => config("utility.zones"),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            "name" => "required|string|max:255",
            "email" => "required|string|lowercase|email|max:255|unique:".User::class,
            "password" => ["required", "confirmed", Rules\Password::defaults()],
            "address" => "required|string|max:255",
            "zone" => ["required", "string", Rule::in(config("utility.zones"))],
        ]);

        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                "name" => $request->name,
                "email" => $request->email,
                "password" => Hash::make($request->password),
            ]);

            $user->assignRole(config("roles.customer"));

            Account::create([
                "user_id" => $user->id,
                "account_number" => AccountNumberGenerator::generate(),
                "address" => $request->address,
                "zone" => $request->zone,
                "status" => AccountStatus::Active,
            ]);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route("dashboard", absolute: false));
    }
}
