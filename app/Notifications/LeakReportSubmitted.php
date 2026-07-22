<?php

namespace App\Notifications;

use App\Models\LeakReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class LeakReportSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected LeakReport $leakReport)
    {
    }

    /**
     * Always mail + database — this goes to admins for operational
     * awareness, not customers, so it isn't gated by the customer-facing
     * notification_preferences toggle (config('notifications.categories')
     * only covers what a customer's own account can opt in/out of).
     */
    public function via(object $notifiable): array
    {
        return ["mail", "database"];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New leak report: {$this->leakReport->zone} ({$this->leakReport->severity->label()})")
            ->greeting("Hello {$notifiable->name},")
            ->line($this->summary())
            ->line($this->leakReport->description)
            ->when($this->leakReport->location_notes, fn ($message) => $message->line(
                "Location notes: {$this->leakReport->location_notes}",
            ))
            ->action("View on Network Map", route("map.index"))
            ->line("A work order has already been added to the technician dispatch queue.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            "type" => "leak_report_submitted",
            "title" => "New leak report: {$this->leakReport->zone}",
            "message" => $this->summary(),
            "leak_report_id" => $this->leakReport->id,
            "severity" => $this->leakReport->severity->value,
            "zone" => $this->leakReport->zone,
            "action_route" => "map.index",
        ];
    }

    protected function summary(): string
    {
        return "A {$this->leakReport->severity->label()} severity leak was reported in {$this->leakReport->zone}.";
    }
}
