<?php

namespace App\Notifications;

use App\Models\Outage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class OutageBroadcast extends Notification
{
    public function __construct(protected Outage $outage)
    {
    }

    public function via(object $notifiable): array
    {
        return $notifiable->wantsEmailFor("outage_broadcasts") ? ["mail", "database"] : ["database"];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Water service notice: {$this->outage->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line($this->summary())
            ->line($this->outage->description)
            ->line("Starts: {$this->outage->starts_at->toFormattedDateString()} {$this->outage->starts_at->toTimeString()}")
            ->when($this->outage->ends_at, fn ($message) => $message->line(
                "Expected to end: {$this->outage->ends_at->toFormattedDateString()} {$this->outage->ends_at->toTimeString()}",
            ))
            ->line("We apologize for the inconvenience.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            "type" => "outage_broadcast",
            "title" => $this->outage->title,
            "message" => $this->summary(),
            "outage_id" => $this->outage->id,
            "zone" => $this->outage->zone,
        ];
    }

    protected function summary(): string
    {
        $scope = $this->outage->zone ? "affecting {$this->outage->zone}" : "affecting all zones";

        return "A water service notice has been issued {$scope}.";
    }
}
