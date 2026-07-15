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
        $notifications = $request->user()
            ->notifications()
            ->orderByDesc("created_at")
            ->get()
            ->map(function ($notification) {
                return [
                    "id" => $notification->id,
                    "data" => $notification->data,
                    "read_at" => $notification->read_at,
                    "created_at" => $notification->created_at->diffForHumans(),
                ];
            });

        return Inertia::render("Notifications/Index", [
            "notifications" => $notifications,
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
