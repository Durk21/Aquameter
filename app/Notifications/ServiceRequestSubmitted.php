<?php

namespace App\Notifications;

use App\Models\ServiceRequest;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ServiceRequestSubmitted extends Notification
{
    public function __construct(protected ServiceRequest $serviceRequest)
    {
    }

    /**
     * Always mail + database — this goes to admins for operational
     * awareness, not the customer-facing notification_preferences toggle.
     */
    public function via(object $notifiable): array
    {
        return ["mail", "database"];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New service request: {$this->serviceRequest->zone}")
            ->greeting("Hello {$notifiable->name},")
            ->line($this->summary())
            ->line($this->serviceRequest->description)
            ->action("View Work Orders", route("admin.work-orders.index"))
            ->line("A work order has already been added to the technician dispatch queue.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            "type" => "service_request_submitted",
            "title" => "New service request: {$this->serviceRequest->zone}",
            "message" => $this->summary(),
            "service_request_id" => $this->serviceRequest->id,
            "zone" => $this->serviceRequest->zone,
            "action_route" => "admin.work-orders.index",
        ];
    }

    protected function summary(): string
    {
        $type = str($this->serviceRequest->type)->replace("_", " ")->title();

        return "A {$type} request was submitted in {$this->serviceRequest->zone}.";
    }
}
