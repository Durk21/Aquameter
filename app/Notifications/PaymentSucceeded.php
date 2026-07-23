<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PaymentSucceeded extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Payment $payment)
    {
    }

    public function via(object $notifiable): array
    {
        return $notifiable->wantsEmailFor("payment_succeeded") ? ["mail", "database"] : ["database"];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Payment received — thank you")
            ->greeting("Hello {$notifiable->name},")
            ->line("We've received your payment of KES {$this->payment->amount}.")
            ->line("Method: ".ucfirst(str_replace("_", " ", $this->payment->method)))
            ->action("View Receipt", route("payments.receipt", $this->payment->id))
            ->line("Thank you for using Aquameter.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            "type" => "payment_succeeded",
            "title" => "Payment received",
            "message" => "KES {$this->payment->amount} received via ".ucfirst(str_replace("_", " ", $this->payment->method)).".",
            "payment_id" => $this->payment->id,
            "action_route" => "payments.receipt",
        ];
    }
}
