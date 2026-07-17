<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Account;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $account = $request->user()->hasRole(config('roles.customer'))
            ? Account::where('user_id', $request->user()->id)->first()
            : null;

        $storedPreferences = $request->user()->notification_preferences ?? [];

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'account' => $account ? [
                'phone' => $account->phone,
                'alternate_email' => $account->alternate_email,
            ] : null,
            'notificationCategories' => $account ? config('notifications.categories') : null,
            'notificationPreferences' => $account
                ? collect(config('notifications.categories'))
                    ->keys()
                    ->mapWithKeys(fn ($category) => [$category => $storedPreferences[$category] ?? true])
                : null,
        ]);
    }

    /**
     * Update the customer's contact info stored on their account.
     */
    public function updateContact(Request $request): RedirectResponse
    {
        if (! $request->user()->hasRole(config('roles.customer'))) {
            abort(403);
        }

        $account = Account::where('user_id', $request->user()->id)->firstOrFail();

        $validated = $request->validate([
            'phone' => 'nullable|string|max:20',
            'alternate_email' => 'nullable|email|max:255',
        ]);

        $account->update($validated);

        return Redirect::route('profile.edit');
    }

    /**
     * Update which notification categories the customer wants emailed
     * to them. In-app notifications are never gated by this.
     */
    public function updateNotificationPreferences(Request $request): RedirectResponse
    {
        if (! $request->user()->hasRole(config('roles.customer'))) {
            abort(403);
        }

        $preferences = collect(config('notifications.categories'))
            ->keys()
            ->mapWithKeys(fn ($category) => [$category => $request->boolean("preferences.{$category}")])
            ->all();

        $request->user()->notification_preferences = $preferences;
        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
