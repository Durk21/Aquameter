<?php

namespace App\Notifications;

use App\Models\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ComplaintSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Complaint $complaint)
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
            ->subject("New complaint: {$this->complaint->subject}")
            ->greeting("Hello {$notifiable->name},")
            ->line($this->summary())
            ->line($this->complaint->description)
            ->action("Review Complaint", route("admin.complaints.show", $this->complaint->id))
            ->line("A customer is waiting on a response.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            "type" => "complaint_submitted",
            "title" => "New complaint: {$this->complaint->subject}",
            "message" => $this->summary(),
            "complaint_id" => $this->complaint->id,
            "action_route" => "admin.complaints.show",
        ];
    }

    protected function summary(): string
    {
        return "A new complaint was submitted: \"{$this->complaint->subject}\".";
    }
}
