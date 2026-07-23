<?php

namespace App\Notifications;

use App\Models\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ComplaintStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Complaint $complaint)
    {
    }

    public function via(object $notifiable): array
    {
        return $notifiable->wantsEmailFor("complaint_updates") ? ["mail", "database"] : ["database"];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Update on your complaint: {$this->complaint->subject}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your complaint \"{$this->complaint->subject}\" has been updated to: {$this->complaint->status->label()}.")
            ->when($this->complaint->resolution_notes, function (MailMessage $message) {
                $message->line("Resolution notes: {$this->complaint->resolution_notes}");
            })
            ->action("View Complaint", route("customer.complaints.index"))
            ->line("Thank you for using Aquameter.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            "type" => "complaint_status_updated",
            "title" => "Update on your complaint: {$this->complaint->subject}",
            "message" => "Status: {$this->complaint->status->label()}",
            "complaint_id" => $this->complaint->id,
            "subject" => $this->complaint->subject,
            "status" => $this->complaint->status->value,
            "status_label" => $this->complaint->status->label(),
            "resolution_notes" => $this->complaint->resolution_notes,
            "action_route" => "customer.complaints.index",
        ];
    }
}
