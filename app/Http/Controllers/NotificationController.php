<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        // Named notificationHistory, not notifications — that key is
        // already used by the globally-shared bell dropdown data in
        // HandleInertiaRequests, and page props silently win over shared
        // props of the same name, which would break the bell on this page.
        $notificationHistory = $request->user()
            ->notifications()
            ->orderByDesc("created_at")
            ->paginate(20)
            ->withQueryString()
            ->through(function ($notification) {
                return [
                    "id" => $notification->id,
                    "data" => $notification->data,
                    "read_at" => $notification->read_at,
                    "created_at" => $notification->created_at->diffForHumans(),
                ];
            });

        return Inertia::render("Notifications/Index", [
            "notificationHistory" => $notificationHistory,
        ]);
    }

    public function markRead(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
