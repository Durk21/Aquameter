<?php

namespace App\Notifications;

use App\Models\Bill;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class BillGenerated extends Notification
{
    public function __construct(protected Bill $bill)
    {
    }

    public function via(object $notifiable): array
    {
        return $notifiable->wantsEmailFor("bill_generated") ? ["mail", "database"] : ["database"];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("A new water bill is ready")
            ->greeting("Hello {$notifiable->name},")
            ->line("A new bill of KES {$this->bill->amount} has been generated for {$this->bill->units_consumed} units consumed.")
            ->line("Due date: {$this->bill->due_date->toFormattedDateString()}.")
            ->action("View Bill", route("customer.bills.index"))
            ->line("Thank you for using Aquameter.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            "type" => "bill_generated",
            "title" => "New bill generated",
            "message" => "KES {$this->bill->amount} due by {$this->bill->due_date->toDateString()}",
            "bill_id" => $this->bill->id,
            "amount" => (string) $this->bill->amount,
            "due_date" => $this->bill->due_date->toDateString(),
            "action_route" => "customer.bills.index",
        ];
    }
}
