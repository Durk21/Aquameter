<?php

namespace App\Notifications;

use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class DisconnectionNoticeIssued extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected WorkOrder $workOrder)
    {
    }

    public function via(object $notifiable): array
    {
        return $notifiable->wantsEmailFor("disconnection_notices") ? ["mail", "database"] : ["database"];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Notice: your water account is scheduled for disconnection")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your account has a defaulted bill and is now scheduled for disconnection.")
            ->line("You have until {$this->workOrder->notice_deadline->toFormattedDateString()} to settle the outstanding balance or dispute this notice.")
            ->line("If you believe this notice was issued in error, you can dispute it from your dashboard before a technician is dispatched.")
            ->action("View Account", route("customer.bills.index"))
            ->line("Thank you for using Aquameter.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            "type" => "disconnection_notice_issued",
            "title" => "Disconnection notice issued",
            "message" => "Settle your balance or dispute by {$this->workOrder->notice_deadline->toDateString()}.",
            "work_order_id" => $this->workOrder->id,
            "notice_deadline" => $this->workOrder->notice_deadline->toDateString(),
            "action_route" => "customer.bills.index",
        ];
    }
}
